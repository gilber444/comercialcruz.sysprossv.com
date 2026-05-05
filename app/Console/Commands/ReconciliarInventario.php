<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ReconciliarInventario extends Command
{
    protected $signature = 'reconciliar:inventario
                            {sucursal : ID de sucursal local a reconciliar}';

    protected $description = 'Reconcilia el inventario de una sucursal local contra el VPS (solo inserta o corrige sincro_id/id_vps, nunca borra ni sobreescribe existencias).';

    protected string $connVps   = 'vpsmysql';
    protected string $connLocal = 'localmysql';

    public function handle()
    {
        if (env('APP_MODO', 'local') === 'vps') {
            $this->error('❌ reconciliar:inventario NO debe ejecutarse en VPS (APP_MODO=vps). Abortando.');
            return self::FAILURE;
        }

        $sucursal = (int) $this->argument('sucursal');

        if ($sucursal <= 0) {
            $this->error('❌ Debes indicar una sucursal válida.');
            return self::FAILURE;
        }

        $this->info("== INICIO RECONCILIACIÓN INVENTARIO SUCURSAL {$sucursal} ==");

        try {
            DB::connection($this->connVps)->getPdo();
            DB::connection($this->connLocal)->getPdo();
        } catch (Throwable $e) {
            $this->error('❌ Error de conexión: ' . $e->getMessage());
            return self::FAILURE;
        }

        $vpsInventarios = DB::connection($this->connVps)
            ->table('inventarios')
            ->where('sucursal', $sucursal)
            ->whereNull('deleted_at')
            ->whereNotNull('sincro_id')
            ->where('sincro_id', '!=', '')
            ->orderBy('id')
            ->get();

        if ($vpsInventarios->isEmpty()) {
            $this->info("⚠ No hay inventario en VPS para sucursal {$sucursal}.");
            return self::SUCCESS;
        }

        $insertados        = 0;
        $actualizados      = 0;
        $sinCambios        = 0;
        $productosSaltados = 0;
        $errores           = 0;

        foreach ($vpsInventarios as $vpsInv) {
            try {
                $localProductoId = $this->resolverProductoLocalId((int) $vpsInv->producto);

                if (!$localProductoId) {
                    $this->warn("⚠ Producto VPS id={$vpsInv->producto} no encontrado en local. Inventario VPS id={$vpsInv->id} saltado.");
                    $productosSaltados++;
                    continue;
                }

                // 1) Buscar por sincro_id
                $localPorSincro = DB::connection($this->connLocal)
                    ->table('inventarios')
                    ->where('sincro_id', $vpsInv->sincro_id)
                    ->first();

                if ($localPorSincro) {
                    // Si ya existe por sincro_id, solo completar id_vps si hace falta
                    $dataUpdate = [];

                    if ((int) ($localPorSincro->id_vps ?? 0) !== (int) $vpsInv->id) {
                        $dataUpdate['id_vps'] = (int) $vpsInv->id;
                    }

                    if (!empty($dataUpdate)) {
                        $dataUpdate['updated_at'] = now();

                        DB::connection($this->connLocal)
                            ->table('inventarios')
                            ->where('id', $localPorSincro->id)
                            ->update($dataUpdate);

                        $actualizados++;
                    } else {
                        $sinCambios++;
                    }

                    continue;
                }

                // 2) Buscar por producto local + sucursal
                $localPorProdSuc = DB::connection($this->connLocal)
                    ->table('inventarios')
                    ->where('producto', $localProductoId)
                    ->where('sucursal', $sucursal)
                    ->first();

                if ($localPorProdSuc) {
                    DB::connection($this->connLocal)
                        ->table('inventarios')
                        ->where('id', $localPorProdSuc->id)
                        ->update([
                            'sincro_id'      => $vpsInv->sincro_id,
                            'id_vps'         => (int) $vpsInv->id,
                            'sincronizacion' => now(),
                            'updated_at'     => now(),
                        ]);

                    $actualizados++;
                    continue;
                }

                // 3) Insertar nuevo inventario local
                DB::connection($this->connLocal)
                    ->table('inventarios')
                    ->insert([
                        'producto'       => $localProductoId,
                        'empresa'        => 1,
                        'sucursal'       => $sucursal,
                        'existencia'     => 0.00,           // nunca traer existencia del VPS a local
                        'sincro_id'      => $vpsInv->sincro_id,
                        'id_vps'         => (int) $vpsInv->id,
                        'sincronizacion' => now(),
                        'created_at'     => $vpsInv->created_at ?? now(),
                        'updated_at'     => $vpsInv->updated_at ?? now(),
                    ]);

                $insertados++;
                $this->line("➕ Insertado inventario local para producto_local_id={$localProductoId} (producto_vps_id={$vpsInv->producto}, inventario_vps_id={$vpsInv->id})");
            } catch (Throwable $e) {
                $errores++;
                $this->error("✘ Error procesando inventario VPS id={$vpsInv->id}: " . $e->getMessage());
            }
        }

        $this->info("✔ SIN CAMBIOS:                 {$sinCambios}");
        $this->info("✔ ACTUALIZADOS:                {$actualizados}");
        $this->info("✔ INSERTADOS NUEVOS:           {$insertados}");
        $this->info("⚠ PRODUCTOS NO ENCONTRADOS:    {$productosSaltados}");
        $this->info("✘ ERRORES:                     {$errores}");
        $this->info("== FIN RECONCILIACIÓN INVENTARIO SUCURSAL {$sucursal} ==");

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resuelve el ID local del producto a partir del ID del producto en VPS.
     */
    protected function resolverProductoLocalId(int $productoVpsId): ?int
    {
        if ($productoVpsId <= 0) {
            return null;
        }

        // 1) Buscar por id_vps
        $localProductoId = DB::connection($this->connLocal)
            ->table('productos')
            ->where('id_vps', $productoVpsId)
            ->value('id');

        if ($localProductoId) {
            return (int) $localProductoId;
        }

        // 2) Buscar sincro_id del producto en VPS
        $productoVps = DB::connection($this->connVps)
            ->table('productos')
            ->select('id', 'sincro_id')
            ->where('id', $productoVpsId)
            ->first();

        if (!$productoVps || empty($productoVps->sincro_id)) {
            return null;
        }

        // 3) Buscar en local por sincro_id
        $localProductoId = DB::connection($this->connLocal)
            ->table('productos')
            ->where('sincro_id', $productoVps->sincro_id)
            ->value('id');

        if (!$localProductoId) {
            return null;
        }

        // 4) Guardar id_vps para futuras búsquedas
        DB::connection($this->connLocal)
            ->table('productos')
            ->where('id', $localProductoId)
            ->update([
                'id_vps'     => $productoVpsId,
                'updated_at' => now(),
            ]);

        return (int) $localProductoId;
    }
}
