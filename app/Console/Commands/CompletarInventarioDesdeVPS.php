<?php

namespace App\Console\Commands;

use App\Services\InventarioSucursalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Corre UNA sola vez por PC local: detecta los productos que no tienen inventario
 * en la sucursal de la PC (APP_SUCURSAL_ID) y los descarga del VPS.
 * Al terminar sin pendientes deja una marca en storage/app y ya no vuelve a correr
 * (usar --force para repetirlo).
 */
class CompletarInventarioDesdeVPS extends Command
{
    protected $signature = 'inventario:completar-desde-vps
        {--sucursal= : Sucursal a revisar (por defecto APP_SUCURSAL_ID)}
        {--dry-run : Solo muestra lo que falta, no inserta}
        {--force : Ejecutar aunque ya se haya corrido antes}';

    protected $description = 'Una sola vez: descarga del VPS los inventarios que faltan en la sucursal de esta PC local';

    public function handle(): int
    {
        if (config('app.modo', 'local') !== 'local') {
            $this->error('❌ Este comando solo corre en PC local (APP_MODO=local).');
            return self::FAILURE;
        }

        $sucursalId = (int) ($this->option('sucursal') ?: config('app.sucursal_id', 0));
        if ($sucursalId <= 0) {
            $this->error('❌ No hay APP_SUCURSAL_ID configurado ni se indicó --sucursal.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $marca  = storage_path("app/inventario_completar_sucursal_{$sucursalId}.done");

        if (is_file($marca) && !$this->option('force')) {
            $this->info("✔ Ya se ejecutó para la sucursal {$sucursalId} (" . trim(file_get_contents($marca)) . '). Use --force para repetir.');
            return self::SUCCESS;
        }

        $local = DB::connection(InventarioSucursalService::CONN_LOCAL);
        try {
            $vps = DB::connection(InventarioSucursalService::CONN_VPS);
            $vps->getPdo();
        } catch (\Throwable $e) {
            $this->error('❌ Sin conexión con el VPS: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("🔎 Revisando inventarios de la sucursal {$sucursalId}...");

        $productosLocales = array_flip($local->table('productos')->pluck('id')->all());
        $conInventario    = array_flip($local->table('inventarios')->where('sucursal', $sucursalId)->pluck('producto')->all());

        $insertados = 0;
        $sinProducto = [];
        $fallidos = [];
        $enVps = [];

        $vps->table('inventarios')
            ->where('sucursal', $sucursalId)
            ->orderBy('id')
            ->chunk(1000, function ($filas) use (&$insertados, &$sinProducto, &$fallidos, &$enVps, &$conInventario, $productosLocales, $sucursalId, $dryRun) {
                foreach ($filas as $fila) {
                    $productoId = (int) $fila->producto;
                    $enVps[$productoId] = true;

                    if (isset($conInventario[$productoId])) {
                        continue;
                    }
                    if (!isset($productosLocales[$productoId])) {
                        // El producto aún no baja a esta PC; la sync de productos lo traerá
                        $sinProducto[] = $productoId;
                        continue;
                    }
                    if ($dryRun) {
                        $this->line("   • Falta producto {$productoId} (existencia VPS: {$fila->existencia})");
                        $insertados++;
                        continue;
                    }

                    try {
                        $resultado = InventarioSucursalService::jalarDelVps($productoId, $sucursalId, $fila);
                        if ($resultado === 'insertado') {
                            $insertados++;
                            $conInventario[$productoId] = true;
                            $this->line("   ✔ Producto {$productoId} descargado (existencia: {$fila->existencia})");
                        } elseif ($resultado !== 'existe') {
                            $fallidos[] = $productoId;
                        }
                    } catch (\Throwable $e) {
                        $fallidos[] = $productoId;
                        $this->warn("   ⚠ Producto {$productoId}: " . $e->getMessage());
                    }
                }
            });

        // Productos locales sin inventario en la sucursal que tampoco existen en el VPS
        $huerfanos = array_values(array_filter(
            array_keys($productosLocales),
            fn ($id) => !isset($conInventario[$id]) && !isset($enVps[$id])
        ));

        $this->newLine();
        $this->info(($dryRun ? '🧪 Faltantes encontrados: ' : '✅ Inventarios descargados del VPS: ') . $insertados);
        if ($sinProducto) {
            $this->warn('⚠ Productos que aún no existen en esta PC (se omiten): ' . count($sinProducto) . ' → ' . implode(', ', array_slice($sinProducto, 0, 50)));
        }
        if ($fallidos) {
            $this->warn('⚠ No se pudieron descargar: ' . count($fallidos) . ' → ' . implode(', ', array_slice($fallidos, 0, 50)));
        }
        if ($huerfanos) {
            $this->warn('⚠ Productos sin inventario en esta sucursal ni en el VPS (deben crearse en el VPS): ' . count($huerfanos) . ' → ' . implode(', ', array_slice($huerfanos, 0, 50)));
        }

        Log::info('inventario:completar-desde-vps', [
            'sucursal' => $sucursalId, 'dry_run' => $dryRun, 'insertados' => $insertados,
            'sin_producto' => count($sinProducto), 'fallidos' => count($fallidos), 'huerfanos' => count($huerfanos),
        ]);

        // Solo se marca como hecho si no quedó nada pendiente que se pueda resolver en otra corrida
        if (!$dryRun && !$sinProducto && !$fallidos) {
            file_put_contents($marca, now()->toDateTimeString() . " insertados={$insertados}");
            $this->info('🏁 Marcado como ejecutado; no volverá a correr.');
        } elseif (!$dryRun) {
            $this->warn('↻ Quedaron pendientes; se volverá a intentar en la próxima corrida.');
        }

        return self::SUCCESS;
    }
}
