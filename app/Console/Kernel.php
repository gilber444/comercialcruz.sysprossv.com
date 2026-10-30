<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Lee configuración del .env de cada máquina
        // LOCAL: APP_MODO=local  APP_SUCURSAL_ID=X
        // VPS:   APP_MODO=vps
        // ⚠️ IMPORTANTE: NUNCA usar env() directamente aquí. Usar config() para
        // que funcione correctamente cuando config:cache está activo.
        $modo       = config('app.modo', 'local');
        $sucursalId = (int) config('app.sucursal_id', 0);

        // =====================================================================
        // COMANDOS SOLO EN PC LOCAL (APP_MODO=local)
        // =====================================================================
        if ($modo === 'local') {

            // Descarga cambios desde GitHub cada 5 minutos
            $schedule->command('app:update-from-git')
                ->everyFiveMinutes()
                ->withoutOverlapping(4)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/update-from-git.log'));

            // Procesar DTEs pendientes
            $schedule->command('app:procesar-dtes-pendientes --limit=50')
                ->everyMinute()
                ->runInBackground()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/procesar_dtes_pendientes.log'));

            if ($sucursalId > 0) {
                // Daemon de sync: un solo proceso que corre todas las sincronizaciones
                // cada pocos segundos. Se activa con el feature flag 'sync_daemon'
                // (o 'sync_daemon_suc_{ID}') en el VPS, o con SYNC_DAEMON=on.
                // Cuando está activo, los comandos separados de abajo no corren.
                $usarSeparados = fn () => \App\Support\SyncDaemon::usarComandosSeparados();

                $schedule->command('sync:daemon')
                    ->name('sync daemon local<->vps')
                    ->everyMinute()
                    ->runInBackground()
                    ->withoutOverlapping(30)
                    ->when(fn () => \App\Support\SyncDaemon::activo())
                    ->appendOutputTo(storage_path('logs/sync_daemon.log'));

                // LOCAL → VPS: un solo proceso maestro reemplaza los 3 comandos separados
                // (sync:local-vps + sync:solicitudes + sync:bitacora-local-vps)
                // → menos conexiones simultáneas al VPS
                $schedule->command("sync:maestro --sucursal={$sucursalId} --limit=1000")
                    ->name('push maestro local->vps')
                    ->when($usarSeparados)
                    ->everyTwoMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(4)
                    ->appendOutputTo(storage_path('logs/sync_maestro.log'));

                // VPS → LOCAL: descarga cambios del servidor
                $schedule->command("sync:vps-local --sucursales={$sucursalId}")
                    ->name('pull vps->local')
                    ->when($usarSeparados)
                    ->everyFiveMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(25)
                    ->appendOutputTo(storage_path('logs/sync_vps_local.log'));

                // VPS → LOCAL (aparte): reconciliar estado de DTEs RECHAZADO contra el VPS.
                // Si en el VPS terminaron PROCESADO/INVALIDADO, corrige estado+sello en la local.
                $schedule->command('sync:dtes-estado --limit=500')
                    ->name('reconciliar estado dtes vps->local')
                    ->when($usarSeparados)
                    ->everyThirtyMinutes()
                    ->runInBackground()
                    ->withoutOverlapping()
                    ->appendOutputTo(storage_path('logs/sync_dtes_estado.log'));

                // VPS → LOCAL: inventarios de productos nuevos
                $schedule->command("producto:nuevo {$sucursalId}")
                    ->name('producto nuevo sync')
                    ->when($usarSeparados)
                    ->everyFiveMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(4)
                    ->appendOutputTo(storage_path('logs/producto_nuevo.log'));

                // LOCAL → VPS: órdenes de pedido
                $schedule->command('sync:orden-pedido')
                    ->name('sync ordenes pedido local->vps')
                    ->when($usarSeparados)
                    ->everyFiveMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(4)
                    ->appendOutputTo(storage_path('logs/sync_orden_pedido.log'));

                // LOCAL → VPS: sincroniza existencias cada 10 minutos (sin kardex)
                $schedule->command('sync:existencias-vps')
                    ->name('sync existencias local->vps')
                    ->when($usarSeparados)
                    ->everyTenMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(9)
                    ->appendOutputTo(storage_path('logs/sync_existencias_vps.log'));
            }
        }

        // =====================================================================
        // COMANDOS SOLO EN VPS (APP_MODO=vps)
        // =====================================================================
        if ($modo === 'vps') {

            // Maestro VPS: consolida correos de DTEs + procesado en un solo proceso
            $schedule->command('sync:maestro-vps --limit-correos=30 --limit-dtes=50')
                ->everyTwoMinutes()
                ->withoutOverlapping(3)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/maestro_vps.log'));

            // Procesar DTEs en ventana nocturna (fuera del maestro para no saturar en horario pico)
            $schedule->command('app:procesar-dtes-pendientes --limit=50')
                ->everyFiveMinutes()
                ->runInBackground()
                ->between('19:30', '23:59')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/procesar_dtes_pendientes.log'));

            $schedule->command('app:procesar-dtes-pendientes --limit=50')
                ->everyFiveMinutes()
                ->runInBackground()
                ->between('00:00', '06:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/procesar_dtes_pendientes.log'));

            $schedule->command('credito:actualizar')
                ->dailyAt('02:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/credito_actualizar.log'));

            $schedule->command('notificaciones:limpiar')
                ->cron('5 0 */3 * *')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/notificaciones_limpiar.log'));

            $schedule->command('capital:generar-diario')
                ->dailyAt('23:59')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/capital_diario.log'));
        }

        // =====================================================================
        // COMANDOS COMPARTIDOS (corren tanto en VPS como en locales)
        // =====================================================================
        $schedule->command('firmador:limpiar')
            ->weekly()
            ->sundays()
            ->at('13:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/firmador_limpiar.log'));

        // Limpieza de logs: borra los >5 días y trunca los activos >20MB (para que no crezcan).
        // Diario a las 03:00 (sin appendOutputTo a propósito: no generar otro log que crezca).
        $schedule->command('logs:limpiar --dias=5 --max-mb=20')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
