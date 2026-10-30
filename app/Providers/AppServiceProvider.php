<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->protegerComandosSync();
        }
    }

    /**
     * Evita conexiones abiertas y procesos colgados en los comandos de consola:
     * - Al terminar cualquier comando (bien, con error o por fatal) se cierran
     *   todas las conexiones a BD, incluida la del VPS.
     * - En la PC local, los comandos de sync tienen un tiempo máximo. En Windows
     *   max_execution_time mide tiempo real, así que corta un proceso que quedó
     *   esperando una red caída y libera el withoutOverlapping del scheduler.
     */
    private function protegerComandosSync(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (config('app.modo', 'local') !== 'local' || !$this->esComandoSync($event->command)) {
                return;
            }

            set_time_limit((int) config('app.sync_max_segundos', 1200));
        });

        Event::listen(CommandFinished::class, fn () => $this->cerrarConexiones());

        register_shutdown_function(fn () => $this->cerrarConexiones());
    }

    private function esComandoSync(?string $comando): bool
    {
        return $comando !== null
            && Str::startsWith($comando, ['sync:', 'producto:nuevo', 'push:inventario-vps']);
    }

    private function cerrarConexiones(): void
    {
        try {
            foreach (array_keys(DB::getConnections()) as $nombre) {
                DB::disconnect($nombre);
            }
        } catch (Throwable $e) {
            // Al apagar el proceso no hay nada más que hacer; PHP cierra el socket igual.
        }
    }
}
