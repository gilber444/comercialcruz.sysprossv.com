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
                // LOCAL → VPS: tablas generales
                $schedule->command("sync:local-vps --sucursal-inventarios={$sucursalId} --limit=1000")
                    ->name('push local->vps')
                    ->everyMinute()
                    ->runInBackground()
                    ->withoutOverlapping(59)
                    ->appendOutputTo(storage_path('logs/sync_local_vps_tables.log'));

                // LOCAL → VPS: bitácora
                $schedule->command('sync:bitacora-local-vps --limit=1000')
                    ->name('sync bitacora local to vps')
                    ->everyMinute()
                    ->runInBackground()
                    ->withoutOverlapping(59)
                    ->appendOutputTo(storage_path('logs/sync_bitacora_local_vps.log'));

                // LOCAL → VPS: solicitudes
                $schedule->command('sync:solicitudes --limit=500')
                    ->name('sync solicitudes local to vps')
                    ->everyMinute()
                    ->runInBackground()
                    ->withoutOverlapping(59)
                    ->appendOutputTo(storage_path('logs/sync_solicitudes.log'));

                // VPS → LOCAL: descarga cambios del servidor
                $schedule->command("sync:vps-local --sucursales={$sucursalId}")
                    ->name('pull vps->local')
                    ->everyFiveMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(59)
                    ->appendOutputTo(storage_path('logs/sync_vps_local.log'));

                // VPS → LOCAL: inventarios de productos nuevos
                $schedule->command("producto:nuevo {$sucursalId}")
                    ->name('producto nuevo sync')
                    ->everyFiveMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(4)
                    ->appendOutputTo(storage_path('logs/producto_nuevo.log'));

                // LOCAL → VPS: órdenes de pedido
                $schedule->command('sync:orden-pedido')
                    ->name('sync ordenes pedido local->vps')
                    ->everyFiveMinutes()
                    ->runInBackground()
                    ->withoutOverlapping(4)
                    ->appendOutputTo(storage_path('logs/sync_orden_pedido.log'));

                // LOCAL → VPS: sincroniza existencias cada 10 minutos (sin kardex)
                $schedule->command('sync:existencias-vps')
                    ->name('sync existencias local->vps')
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

            $schedule->command('dte:correo-wm --limit=30')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/dte_correo_scheduler.log'));

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
