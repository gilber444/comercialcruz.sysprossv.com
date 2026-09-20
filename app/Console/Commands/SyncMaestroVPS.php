<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

/**
 * Comando maestro para el VPS: consolida las tareas periódicas del servidor
 * en un solo proceso para evitar solapamiento y reducir overhead.
 *
 * Reemplaza correr por separado:
 *   - dte:correo-wm
 *   - app:procesar-dtes-pendientes
 */
class SyncMaestroVPS extends Command
{
    protected $signature = 'sync:maestro-vps
        {--limit-dtes=50 : Máximo de DTEs a procesar por ciclo}
        {--limit-correos=30 : Máximo de correos a enviar por ciclo}
        {--skip-correos : No enviar correos en este ciclo}
        {--skip-dtes : No procesar DTEs en este ciclo}';

    protected $description = 'Corre las tareas periódicas del VPS en un solo proceso (menos overhead de scheduler)';

    public function handle(): int
    {
        if (config('app.modo', 'local') !== 'vps') {
            $this->error('sync:maestro-vps SOLO debe ejecutarse en VPS (APP_MODO=vps). Abortando.');
            return self::FAILURE;
        }

        $this->info('== MAESTRO VPS | ' . now()->toDateTimeString() . ' ==');

        $exitCode = self::SUCCESS;

        // 1) Envío de correos de DTEs
        if (!$this->option('skip-correos')) {
            try {
                $limit = (int) ($this->option('limit-correos') ?? 30);
                $this->info("→ [1/2] dte:correo-wm --limit={$limit}");
                $this->call('dte:correo-wm', ["--limit={$limit}"]);
            } catch (Throwable $e) {
                $this->error('✘ dte:correo-wm falló: ' . $e->getMessage());
                $exitCode = self::FAILURE;
            }
        }

        // 2) Procesar DTEs pendientes
        if (!$this->option('skip-dtes')) {
            try {
                $limit = (int) ($this->option('limit-dtes') ?? 50);
                $this->info("→ [2/2] app:procesar-dtes-pendientes --limit={$limit}");
                $this->call('app:procesar-dtes-pendientes', ["--limit={$limit}"]);
            } catch (Throwable $e) {
                $this->error('✘ app:procesar-dtes-pendientes falló: ' . $e->getMessage());
                $exitCode = self::FAILURE;
            }
        }

        $this->info('== FIN MAESTRO VPS | ' . now()->toDateTimeString() . ' ==');
        return $exitCode;
    }
}
