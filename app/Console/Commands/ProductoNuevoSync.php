<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductoNuevoSync extends Command
{
    protected $signature = 'producto:nuevo {sucursal?}';

    protected $description = 'Inserta inventarios nuevos desde el VPS sin actualizar registros existentes.';

    protected string $vpsConnection   = 'vpsmysql';
    protected string $localConnection = 'localmysql'; // o "mysql" si es tu conexión por defecto

    public function handle()
    {
        if (env('APP_MODO', 'local') === 'vps') {
            $this->error('❌ producto:nuevo NO debe ejecutarse en VPS (APP_MODO=vps). Abortando.');
            return self::FAILURE;
        }

        $sucursal = $this->argument('sucursal')
            ?? env('APP_SUCURSAL_ID')
            ?? env('SUCURSAL_ID');

        if (!$sucursal) {
            $this->error('❌ Debes definir la sucursal (--sucursal o APP_SUCURSAL_ID)');
            return Command::FAILURE;
        }

        $sucursal = (int) $sucursal;

        $this->info("== INSERTAR PRODUCTOS NUEVOS | SUCURSAL {$sucursal} ==");

        try {
            DB::connection($this->vpsConnection)->getPdo();
            DB::connection($this->localConnection)->getPdo();
        } catch (Throwable $e) {
            $this->error("❌ Error de conexión: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Obtener inventarios del VPS
        $vps = DB::connection($this->vpsConnection)
            ->table('inventarios')
            ->where('sucursal', $sucursal)
            //->whereNull('deleted_at')
            ->get();

        if ($vps->isEmpty()) {
            $this->info("⚠ No hay inventario en VPS para esta sucursal.");
            return Command::SUCCESS;
        }

        $insertados = 0;
        $existen    = 0;

        foreach ($vps as $v) {

            // Resolver el ID local del producto: primero por id_vps, luego por sincro_id
            $localProductoId = DB::connection($this->localConnection)
                ->table('productos')
                ->where('id_vps', $v->producto)
                ->value('id');

            if (!$localProductoId) {
                // Fallback: buscar por sincro_id del producto en VPS
                $vpsProductoSincroId = DB::connection($this->vpsConnection)
                    ->table('productos')
                    ->where('id', $v->producto)
                    ->value('sincro_id');

                if ($vpsProductoSincroId) {
                    $localProductoId = DB::connection($this->localConnection)
                        ->table('productos')
                        ->where('sincro_id', $vpsProductoSincroId)
                        ->value('id');

                    // Si lo encontró, actualizar id_vps en local para futuras veces
                    if ($localProductoId) {
                        DB::connection($this->localConnection)
                            ->table('productos')
                            ->where('id', $localProductoId)
                            ->update(['id_vps' => $v->producto]);
                    }
                }
            }

            if (!$localProductoId) {
                $this->warn("⚠ Producto VPS id={$v->producto} no encontrado en local. Saltando.");
                continue;
            }

            // Verificar si ya existe en local por sincro_id (más confiable que producto+sucursal)
            $existeLocal = DB::connection($this->localConnection)
                ->table('inventarios')
                ->where('sincro_id', $v->sincro_id)
                ->exists();

            if (!$existeLocal) {
                // También verificar por producto+sucursal por si no tiene sincro_id
                $existeLocal = DB::connection($this->localConnection)
                    ->table('inventarios')
                    ->where('producto', $localProductoId)
                    ->where('sucursal', $v->sucursal)
                    ->exists();
            }

            if ($existeLocal) {
                $existen++;
                continue;
            }

            // Insertar solo si NO existe — usa ID local del producto, no el del VPS
            DB::connection($this->localConnection)
                ->table('inventarios')
                ->insert([
                    'producto'       => $localProductoId, // ID local, no el del VPS
                    'empresa'        => 1,               // empresa local siempre 1
                    'sucursal'       => $v->sucursal,
                    'existencia'     => 0,               // siempre 0 en local
                    'sincro_id'      => $v->sincro_id,   // copiar sincro del VPS
                    'id_vps'         => $v->id,
                    'sincronizacion' => now(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

            $insertados++;
            $this->line("➕ Insertado inventario producto local_id={$localProductoId} (vps_id={$v->producto})");
        }

        $this->info("✔ EXISTENTES (NO tocados): {$existen}");
        $this->info("✔ INSERTADOS NUEVOS:      {$insertados}");
        $this->info("== FIN INSERTAR PRODUCTOS NUEVOS | SUCURSAL {$sucursal} ==");

        return Command::SUCCESS;
    }
}
