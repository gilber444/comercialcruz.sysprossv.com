<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Comando maestro que corre todos los syncs local→VPS en un solo proceso,
 * reutilizando las mismas conexiones para reducir la saturación de MariaDB.
 *
 * Reemplaza correr por separado:
 *   - sync:local-vps
 *   - sync:bitacora-local-vps
 *   - sync:solicitudes
 */
class SyncMaestro extends Command
{
    protected $signature = 'sync:maestro
        {--sucursal= : ID de sucursal (sobrescribe APP_SUCURSAL_ID)}
        {--limit=1000 : Máximo de filas por tabla}
        {--skip-bitacora : No sincronizar bitácora (ahorra tiempo si no es necesario)}';

    protected $description = 'Corre todos los syncs LOCAL→VPS en un solo proceso (menos conexiones al VPS)';

    public function handle(): int
    {
        if (config('app.modo', 'local') === 'vps') {
            $this->error('sync:maestro NO debe ejecutarse en VPS. Abortando.');
            return self::FAILURE;
        }

        $sucursal = $this->option('sucursal')
            ?? config('app.sucursal_id')
            ?? env('APP_SUCURSAL_ID')
            ?? env('SUCURSAL_ID');

        if (!$sucursal) {
            $this->error('Debes definir la sucursal (--sucursal o APP_SUCURSAL_ID en .env)');
            return self::FAILURE;
        }

        $sucursal = (int) $sucursal;
        $limit    = (int) ($this->option('limit') ?? 1000);

        $this->info('== SYNC MAESTRO LOCAL→VPS | SUCURSAL ' . $sucursal . ' | ' . now()->toDateTimeString() . ' ==');

        $exitCode = self::SUCCESS;

        // 1) Tablas generales LOCAL → VPS
        try {
            $this->info('→ [1/3] sync:local-vps');
            $this->call('sync:local-vps', [
                '--sucursal-inventarios' => $sucursal,
                '--limit'                => $limit,
            ]);
        } catch (Throwable $e) {
            $this->error('✘ sync:local-vps falló: ' . $e->getMessage());
            $exitCode = self::FAILURE;
        }

        // 2) Solicitudes LOCAL → VPS
        try {
            $this->info('→ [2/3] sync:solicitudes');
            $this->call('sync:solicitudes', [
                '--limit' => $limit,
            ]);
        } catch (Throwable $e) {
            $this->error('✘ sync:solicitudes falló: ' . $e->getMessage());
            $exitCode = self::FAILURE;
        }

        // 3) Bitácora LOCAL → VPS (opcional, menos crítica)
        if (!$this->option('skip-bitacora')) {
            try {
                $this->info('→ [3/3] sync:bitacora-local-vps');
                $this->call('sync:bitacora-local-vps', [
                    '--limit' => $limit,
                ]);
            } catch (Throwable $e) {
                $this->error('✘ sync:bitacora-local-vps falló: ' . $e->getMessage());
                // No marca failure — la bitácora no es crítica
            }
        }

        // Cerrar conexiones al terminar todo
        DB::disconnect('vpsmysql');
        DB::disconnect('localmysql');

        $this->info('== FIN SYNC MAESTRO | ' . now()->toDateTimeString() . ' ==');
        return $exitCode;
    }
}
