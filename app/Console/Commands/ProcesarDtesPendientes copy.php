<?php

namespace App\Console\Commands;

use App\Models\dte;
use App\Traits\RecepcionDTEF;
use App\Traits\RecepcionDTEC;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesarDtesPendientes extends Command
{ 
    use RecepcionDTEF, RecepcionDTEC;

    /**
     * El nombre y firma del comando de consola.
     *
     * php artisan app:procesar-dtes-pendientes --limit=50
     */
    protected $signature = 'app:procesar-dtes-pendientes {--limit=0 : Limitar cantidad de DTE a procesar (0 = todos)}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Procesa todos los DTE en estado "Creado" sin usar la cola, llamando RecepcionDTEF/RecepcionDTEC directamente.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        // Base: todos los DTE "Creado"
        $baseQuery = dte::whereIn('estado', ['Creado', 'Firmado', 'RECHAZADO'])
            ->orderBy('id', 'asc');

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info('✅ No hay DTE en estado "Creado" para procesar.');
            return Command::SUCCESS;
        }

        if ($limit > 0 && $limit < $total) {
            $this->info("🔎 Hay {$total} DTE en 'Creado', pero solo se procesarán {$limit} (por --limit).");
            $baseQuery->limit($limit);
            $total = $limit;
        } else {
            $this->info("🔎 Se procesarán {$total} DTE en estado 'Creado'.");
        }

        $procesados = 0;
        $errores    = 0;

        // Usamos chunk para no reventar memoria si son muchos
        $baseQuery->chunkById(100, function ($dtes) use (&$procesados, &$errores) {
            /** @var \App\Models\dte $dte */
            foreach ($dtes as $dte) {
                try {
                    $this->line("➡️ Procesando DTE ID {$dte->id} | tipoDte={$dte->tipoDte} | estado={$dte->estado}");

                    if ((int) $dte->tipoDte === 1) {
                        // FACTURA
                        $this->RecepcionDTEF($dte->id);
                    } else {
                        // CRÉDITO FISCAL u otro tipo (ajusta según tu lógica)
                        $this->RecepcionDTEC($dte->id);
                    }

                    $procesados++;
                } catch (\Throwable $e) {
                    $errores++;
                    $this->error("❌ Error procesando DTE ID {$dte->id}: " . $e->getMessage());

                    Log::error('Error en app:procesar-dtes-pendientes', [
                        'dte_id'  => $dte->id,
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ]);
                }
            }
        });

        $this->info("✅ Proceso terminado. OK: {$procesados}, Errores: {$errores}");

        return $errores > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
