<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncExistenciasVPS extends Command
{
    protected $signature = 'sync:existencias-vps
                            {--conn-local=localmysql : Conexión local}
                            {--conn-vps=vpsmysql     : Conexión VPS}';

    protected $description = 'Compara existencias local vs VPS y sube las diferencias de esta PC (sin kardex).';

    public function handle(): int
    {
        $connLocal  = $this->option('conn-local');
        $connVps    = $this->option('conn-vps');
        $sucursalId = (int) config('app.sucursal_id', 0);

        if (!$sucursalId) {
            $msg = 'APP_SUCURSAL_ID no está configurado en .env';
            $this->error($msg);
            Log::channel('daily')->error("[sync:existencias-vps] {$msg}");
            return self::FAILURE;
        }

        try {
            DB::connection($connVps)->getPdo();
            DB::connection($connLocal)->getPdo();
        } catch (Throwable $e) {
            $msg = 'Sin conexión: ' . $e->getMessage();
            $this->error($msg);
            Log::channel('daily')->error("[sync:existencias-vps] {$msg}");
            return self::FAILURE;
        }

        $inicio     = now();
        $totalDiferencias = 0;
        $totalSubidos     = 0;
        $totalErrores     = 0;
        $errores          = [];

        $this->line("── Sucursal {$sucursalId}");
        Log::channel('daily')->info("[sync:existencias-vps] Iniciando sucursal={$sucursalId}");

        $diferencias = $this->obtenerDiferencias($sucursalId, $connLocal, $connVps);

        if ($diferencias->isEmpty()) {
            $this->line("   Sin diferencias.");
            Log::channel('daily')->info("[sync:existencias-vps] Sin diferencias en sucursal={$sucursalId}");
        } else {
            $this->line("   {$diferencias->count()} diferencia(s) encontrada(s).");
            $totalDiferencias = $diferencias->count();

            foreach ($diferencias as $productoLocalId) {
                try {
                    $exit = Artisan::call('push:inventario-vps', [
                        'producto_local_id' => $productoLocalId,
                        'sucursal_id'       => $sucursalId,
                        '--sin-kardex'      => true,
                        '--conn-local'      => $connLocal,
                        '--conn-vps'        => $connVps,
                    ]);

                    if ($exit === 0) {
                        $totalSubidos++;
                    } else {
                        $totalErrores++;
                        $errores[] = "producto_id={$productoLocalId} (exit={$exit})";
                        $this->warn("   Error en producto_id={$productoLocalId}");
                    }
                } catch (Throwable $e) {
                    $totalErrores++;
                    $errores[] = "producto_id={$productoLocalId}: " . $e->getMessage();
                    $this->warn("   Excepción producto_id={$productoLocalId}: " . $e->getMessage());
                    Log::channel('daily')->error("[sync:existencias-vps] Excepción producto_id={$productoLocalId}", [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $elapsed = now()->diffInSeconds($inicio);
        $resumen = "Sync completado en {$elapsed}s: {$totalDiferencias} diferencias | {$totalSubidos} subidos | {$totalErrores} errores.";
        $this->info($resumen);
        Log::channel('daily')->info("[sync:existencias-vps] {$resumen}", $errores ? ['errores' => $errores] : []);

        return self::SUCCESS;
    }

    protected function obtenerDiferencias(int $sucursalId, string $connLocal, string $connVps): \Illuminate\Support\Collection
    {
        $locales = DB::connection($connLocal)
            ->table('inventarios as i')
            ->join('productos as p', 'p.id', '=', 'i.producto')
            ->where('i.sucursal', $sucursalId)
            ->select('i.producto as producto_local_id', 'i.existencia as existencia_local', 'i.sincro_id')
            ->get();

        if ($locales->isEmpty()) return collect();

        $sincroIds = $locales->pluck('sincro_id')->filter()->unique()->values()->toArray();
        $vpsMap    = collect();

        if (!empty($sincroIds)) {
            $vpsRows = DB::connection($connVps)
                ->table('inventarios')
                ->where('sucursal', $sucursalId)
                ->whereIn('sincro_id', $sincroIds)
                ->select('sincro_id', 'existencia')
                ->get();
            foreach ($vpsRows as $v) {
                $vpsMap[$v->sincro_id] = (float) $v->existencia;
            }
        }

        return $locales->filter(function ($local) use ($vpsMap) {
            $exLocal = (float) $local->existencia_local;
            $key     = $local->sincro_id;

            if (!$key || !isset($vpsMap[$key])) return true; // sin registro en VPS = diferente

            return abs($exLocal - $vpsMap[$key]) > 0.0001;
        })->pluck('producto_local_id');
    }
}
