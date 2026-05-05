<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepararAjustesSyncIncorrectos extends Command
{
    protected $signature = 'ajustes:reparar-sync-egresos {--confirm : Ejecutar la reparacion (sin esto solo muestra lo que encontraria)}';
    protected $description = 'Repara ajustes de tipo Ingreso que el sync aplico como Egreso';

    public function handle()
    {
        $confirm = $this->option('confirm');

        if (!Schema::hasTable('ajustes') || !Schema::hasTable('kardexes')) {
            $this->error('Faltan tablas necesarias (ajustes / kardexes)');
            return self::FAILURE;
        }

        $this->info('Buscando kardex del sync aplicados incorrectamente como egreso...');
        $this->newLine();

        // 1. Traer todos los kardex del sync que son egreso
        $kardexesSync = DB::table('kardexes')
            ->where('descripcion', 'like', '%(sync)%')
            ->where('egresoCantidad', '>', 0)
            ->get();

        if ($kardexesSync->isEmpty()) {
            $this->info('No se encontraron kardex con (sync). Todo limpio.');
            return self::SUCCESS;
        }

        // 2. Para cada kardex, extraer el ajuste_id de la descripcion
        $afectados = [];
        foreach ($kardexesSync as $k) {
            if (preg_match('/#(\d+)/', $k->descripcion, $matches)) {
                $ajusteId = (int) $matches[1];
                $ajuste = DB::table('ajustes')->where('id', $ajusteId)->first(['id', 'tipo', 'status', 'aplicado_local']);

                if ($ajuste && in_array($ajuste->tipo, ['Ingreso por Traslado', 'Ingreso Fac. Comercial'], true)) {
                    $inventario = DB::table('inventarios')->where('id', $k->inventario)->first(['id', 'existencia']);
                    $afectados[] = [
                        'kardex_id'      => $k->id,
                        'ajuste_id'      => $ajuste->id,
                        'tipo'           => $ajuste->tipo,
                        'descripcion'    => $k->descripcion,
                        'egresoCantidad' => $k->egresoCantidad,
                        'inventario_id'  => $k->inventario,
                        'existencia'     => $inventario?->existencia ?? 'N/A',
                    ];
                }
            }
        }

        if (empty($afectados)) {
            $this->info('No se encontraron kardex incorrectos. Todo limpio.');
            return self::SUCCESS;
        }

        $this->warn('Se encontraron ' . count($afectados) . ' kardex del sync aplicados como EGRESO cuando el ajuste es de INGRESO:');
        $this->newLine();

        $headers = ['Ajuste ID', 'Tipo', 'Kardex ID', 'Descripcion', 'Egreso (mal)', 'Existencia actual'];
        $rows = [];
        foreach ($afectados as $a) {
            $rows[] = [
                $a['ajuste_id'],
                $a['tipo'],
                $a['kardex_id'],
                substr($a['descripcion'], 0, 50) . '...',
                $a['egresoCantidad'],
                $a['existencia'],
            ];
        }
        $this->table($headers, $rows);

        if (!$confirm) {
            $this->newLine();
            $this->warn('Esto fue solo una vista previa. Para ejecutar la reparacion usa:');
            $this->line('  php artisan ajustes:reparar-sync-egresos --confirm');
            $this->newLine();
            $this->warn('IMPORTANTE: Haz un respaldo de la base de datos antes de ejecutar.');
            return self::SUCCESS;
        }

        if (!$this->confirm('¿Estas seguro de revertir estos movimientos? Se eliminaran los kardex del sync y se restaurara el inventario.', false)) {
            $this->line('Operacion cancelada.');
            return self::SUCCESS;
        }

        $reparados = 0;
        $ajustesIds = [];

        DB::beginTransaction();
        try {
            foreach ($afectados as $a) {
                // Revertir inventario: como se resto (egreso), sumamos de vuelta
                DB::table('inventarios')
                    ->where('id', $a['inventario_id'])
                    ->increment('existencia', $a['egresoCantidad']);

                // Eliminar el kardex incorrecto del sync
                DB::table('kardexes')->where('id', $a['kardex_id'])->delete();

                $ajustesIds[] = $a['ajuste_id'];
                $reparados++;

                $this->line("  ✔ Ajuste #{$a['ajuste_id']}: revertido +{$a['egresoCantidad']} en inventario #{$a['inventario_id']}, kardex #{$a['kardex_id']} eliminado");
            }

            // Normalizar los ajustes afectados
            $ajustesIds = array_unique($ajustesIds);
            if (!empty($ajustesIds)) {
                DB::table('ajustes')
                    ->whereIn('id', $ajustesIds)
                    ->update([
                        'status'         => 'Finalizado',
                        'aplicado_local' => now(),
                        'updated_at'     => now(),
                    ]);
            }

            DB::commit();

            $this->newLine();
            $this->info("✔ Reparacion completa: {$reparados} kardex eliminados, " . count($ajustesIds) . ' ajustes normalizados.');
            $this->warn('Nota: Si hubo movimientos de inventario POSTERIORES a estos kardex, los saldos de kardex historicos pueden no cuadrar perfectamente, pero la existencia actual del inventario sera correcta.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error durante la reparacion: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
