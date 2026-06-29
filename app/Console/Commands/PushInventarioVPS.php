<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PushInventarioVPS extends Command
{
    protected $signature = 'push:inventario-vps
                            {producto_local_id : ID del producto en la BD local}
                            {sucursal_id       : ID de la sucursal local}
                            {--conn-local=localmysql : Conexión local}
                            {--conn-vps=vpsmysql     : Conexión VPS}
                            {--sin-kardex            : Solo actualiza existencia, omite replicar kardex}';

    protected $description = 'Fuerza la existencia + kardex de un producto desde LOCAL hacia VPS (solo ese producto/sucursal).';

    public function handle(): int
    {
        $productoLocalId = (int) $this->argument('producto_local_id');
        $sucursalId      = (int) $this->argument('sucursal_id');
        $connLocal       = $this->option('conn-local');
        $connVps         = $this->option('conn-vps');

        try {
            DB::connection($connVps)->getPdo();
            DB::connection($connLocal)->getPdo();
        } catch (Throwable $e) {
            $this->error('Sin conexión: ' . $e->getMessage());
            return self::FAILURE;
        }

        // 1. Inventario local
        $invLocal = DB::connection($connLocal)
            ->table('inventarios')
            ->where('producto', $productoLocalId)
            ->where('sucursal', $sucursalId)
            ->first();

        if (!$invLocal) {
            $this->error("No existe inventario local para producto={$productoLocalId} sucursal={$sucursalId}.");
            return self::FAILURE;
        }

        // 2. Resolver producto en VPS
        $productoVpsId = $this->resolverProductoVpsId($productoLocalId, $connLocal, $connVps);
        if (!$productoVpsId) {
            $this->error("Producto local id={$productoLocalId} no encontrado en VPS.");
            return self::FAILURE;
        }

        // Detectar tabla kardex en local y en VPS
        $kdxTable    = DB::connection($connLocal)->getSchemaBuilder()->hasTable('kardexes2') ? 'kardexes2' : 'kardexes';
        $kdxTableVps = DB::connection($connVps)->getSchemaBuilder()->hasTable('kardexes2')  ? 'kardexes2' : 'kardexes';

        // Enable query logging for debugging
        DB::connection($connLocal)->enableQueryLog();
        DB::connection($connVps)->enableQueryLog();

        DB::connection($connVps)->beginTransaction();
        try {
            // 3. Actualizar o insertar inventario en VPS
            $invVps = DB::connection($connVps)
                ->table('inventarios')
                ->where('producto', $productoVpsId)
                ->where('sucursal', $sucursalId)
                ->first();

            $existencia = $invLocal->existencia ?? 0;

            if ($invVps) {
                DB::connection($connVps)
                    ->table('inventarios')
                    ->where('id', $invVps->id)
                    ->update([
                        'existencia'     => $existencia,
                        'sincro_id'      => $invLocal->sincro_id ?? $invVps->sincro_id,
                        'sincronizacion' => now(),
                        'updated_at'     => now(),
                    ]);
                $invVpsId = $invVps->id;
            } else {
                $invVpsId = DB::connection($connVps)
                    ->table('inventarios')
                    ->insertGetId([
                        'producto'       => $productoVpsId,
                        'empresa'        => $invLocal->empresa ?? 1,
                        'sucursal'       => $sucursalId,
                        'existencia'     => $existencia,
                        'sincro_id'      => $invLocal->sincro_id ?? Str::uuid(),
                        'sincronizacion' => now(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
            }

            $insertados = 0;
            if (!$this->option('sin-kardex')) {
                // 4. Limpiar kardex VPS para este inventario y re-insertar
                DB::connection($connVps)->table($kdxTableVps)->where('inventario', $invVpsId)->delete();

                // 5. Traer kardex local y replicar
                $kardexLocal = DB::connection($connLocal)
                    ->table($kdxTable)
                    ->where('inventario', $invLocal->id)
                    ->orderBy('id')
                    ->get();

                foreach ($kardexLocal as $k) {
                    DB::connection($connVps)->table($kdxTableVps)->insert([
                        'producto'        => $productoVpsId,
                        'inventario'      => $invVpsId,
                        'descripcion'     => $k->descripcion,
                        'fecha'           => $k->fecha,
                        'hora'            => $k->hora,
                        'ingresoCantidad' => $k->ingresoCantidad,
                        'ingresoValor'    => $k->ingresoValor,
                        'egresoCantidad'  => $k->egresoCantidad,
                        'egresoValor'     => $k->egresoValor,
                        'saldoCantidad'   => $k->saldoCantidad,
                        'saldoValor'      => $k->saldoValor,
                        'sincro_id'       => $k->sincro_id ?? Str::uuid(),
                        'created_at'      => $k->created_at ?? now(),
                        'updated_at'      => now(),
                    ]);
                    $insertados++;
                }
            }

            DB::connection($connVps)->commit();

            $kardexInfo = $this->option('sin-kardex') ? 'kardex omitido' : "kardex={$insertados} filas";
            $this->info("OK — existencia={$existencia} | {$kardexInfo} replicadas al VPS.");
            return self::SUCCESS;

        } catch (Throwable $e) {
            DB::connection($connVps)->rollBack();
            $this->error('Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('PushInventarioVPS FAILED', [
                'message' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    protected function resolverProductoVpsId(int $localId, string $connLocal, string $connVps): ?int
    {
        $idVps = DB::connection($connLocal)->table('productos')->where('id', $localId)->value('id_vps');
        if ($idVps) return (int) $idVps;

        $sincroId = DB::connection($connLocal)->table('productos')->where('id', $localId)->value('sincro_id');
        if (!$sincroId) return null;

        $vpsId = DB::connection($connVps)->table('productos')->where('sincro_id', $sincroId)->value('id');
        if ($vpsId) {
            DB::connection($connLocal)->table('productos')->where('id', $localId)->update(['id_vps' => $vpsId, 'updated_at' => now()]);
            return (int) $vpsId;
        }

        return null;
    }
}
