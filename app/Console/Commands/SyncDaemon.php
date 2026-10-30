<?php

namespace App\Console\Commands;

use App\Support\SyncDaemon as SyncDaemonEstado;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Daemon de sincronización LOCAL ↔ VPS.
 *
 * Reemplaza los comandos de sync separados del scheduler por un solo proceso
 * que corre las tareas en orden, una a la vez, cada pocos segundos:
 *   - nunca hay más de una conexión de esta PC al VPS;
 *   - cada tarea corre en un proceso hijo con timeout (si se cuelga, se mata
 *     y su conexión se cierra);
 *   - el ciclo dura unos minutos y el scheduler lo vuelve a lanzar.
 *
 * Intervalos y tareas en config/sync.php.
 */
class SyncDaemon extends Command
{
    protected $signature = 'sync:daemon
        {--minutos= : Minutos que dura el ciclo (por defecto config sync.minutos_por_ciclo)}
        {--una-vez : Corre cada tarea una sola vez y termina (diagnóstico)}';

    protected $description = 'Sincroniza LOCAL ↔ VPS en un solo proceso continuo (reemplaza los syncs separados del scheduler)';

    /** Momento (timestamp) de la próxima corrida de cada tarea */
    private array $proxima = [];

    /** Fallos seguidos por tarea, para espaciar reintentos */
    private array $fallos = [];

    public function handle(): int
    {
        if (config('app.modo', 'local') !== 'local') {
            $this->error('sync:daemon solo corre en PCs locales (APP_MODO=local). Abortando.');
            return self::FAILURE;
        }

        $sucursal = (int) config('app.sucursal_id', 0);
        if ($sucursal <= 0) {
            $this->error('Debes definir APP_SUCURSAL_ID en el .env');
            return self::FAILURE;
        }

        $unaVez  = (bool) $this->option('una-vez');
        $minutos = max(1, (int) ($this->option('minutos') ?? config('sync.minutos_por_ciclo', 10)));
        $pausa   = max(1, (int) config('sync.pausa', 5));
        $timeout = max(30, (int) config('sync.timeout_tarea', 600));
        $tareas  = $this->tareas($sucursal);

        // Tiempo máximo del propio daemon: el ciclo + una tarea completa de margen
        set_time_limit($minutos * 60 + $timeout + 60);

        $fin = time() + $minutos * 60;
        $this->info("== SYNC DAEMON | SUCURSAL {$sucursal} | {$minutos} min | " . now()->toDateTimeString() . ' ==');

        try {
            while (time() < $fin) {
                if (!$unaVez && !SyncDaemonEstado::activo()) {
                    $this->warn('Daemon desactivado (feature flag o SYNC_DAEMON). Saliendo.');
                    break;
                }

                foreach ($tareas as $clave => $tarea) {
                    SyncDaemonEstado::latido();

                    if (time() < ($this->proxima[$clave] ?? 0)) {
                        continue;
                    }
                    if ($this->comandoSeparadoCorriendo($tarea['comando'])) {
                        $this->line(now()->format('H:i:s') . " · {$clave}: el comando separado todavía corre, se omite");
                        $this->proxima[$clave] = time() + $pausa;
                        continue;
                    }

                    $ok = $this->correrTarea($clave, $tarea, $timeout);
                    $this->programarSiguiente($clave, $tarea['cada'], $ok);
                }

                if ($unaVez) {
                    break;
                }

                SyncDaemonEstado::latido();
                sleep($pausa);
            }
        } finally {
            SyncDaemonEstado::terminarLatido();
        }

        $this->info('== FIN SYNC DAEMON | ' . now()->toDateTimeString() . ' ==');
        return self::SUCCESS;
    }

    private function tareas(int $sucursal): array
    {
        $tareas = [];
        foreach ((array) config('sync.tareas', []) as $clave => $tarea) {
            $tareas[$clave] = [
                'comando' => $tarea['comando'],
                'args'    => array_map(fn ($a) => str_replace('{sucursal}', (string) $sucursal, $a), $tarea['args'] ?? []),
                'cada'    => max(5, (int) ($tarea['cada'] ?? 60)),
            ];
        }
        return $tareas;
    }

    /**
     * Cada tarea corre en un proceso hijo: aísla memoria y estado, y si pasa
     * del timeout se mata junto con su conexión al VPS.
     */
    private function correrTarea(string $clave, array $tarea, int $timeout): bool
    {
        $inicio  = microtime(true);
        $process = new Process(
            array_merge([PHP_BINARY, base_path('artisan'), $tarea['comando']], $tarea['args'], ['--no-ansi']),
            base_path(),
            null,
            null,
            $timeout
        );

        $this->line(now()->format('H:i:s') . " ▶ {$clave}: {$tarea['comando']} " . implode(' ', $tarea['args']));

        try {
            $process->run(function ($tipo, $buffer) {
                $this->output->write($buffer);
            });
            $ok = $process->isSuccessful();
        } catch (ProcessTimedOutException $e) {
            $this->error("✘ {$clave}: superó {$timeout}s, proceso terminado");
            $ok = false;
        } catch (Throwable $e) {
            $this->error("✘ {$clave}: " . $e->getMessage());
            $ok = false;
        }

        $segundos = round(microtime(true) - $inicio, 1);
        $estado   = $ok ? '✔' : '✘ código ' . ($process->getExitCode() ?? '-');
        $this->line(now()->format('H:i:s') . " {$estado} {$clave} ({$segundos}s)");

        return $ok;
    }

    /**
     * Si una tarea falla seguido (por ejemplo, sin internet) se espacian los
     * reintentos: cada, x2, x4... hasta 5 minutos.
     */
    private function programarSiguiente(string $clave, int $cada, bool $ok): void
    {
        $this->fallos[$clave] = $ok ? 0 : ($this->fallos[$clave] ?? 0) + 1;

        $espera = $ok ? $cada : min(300, $cada * (2 ** min($this->fallos[$clave], 4)));
        $this->proxima[$clave] = time() + $espera;
    }

    /**
     * Mientras se pasa de los comandos separados al daemon, puede quedar uno
     * corriendo en segundo plano: no correr el mismo comando encima.
     */
    private function comandoSeparadoCorriendo(string $comando): bool
    {
        try {
            foreach (app(Schedule::class)->events() as $evento) {
                if (Str::contains((string) $evento->command, ' ' . $comando . ' ')
                    || Str::endsWith((string) $evento->command, ' ' . $comando)) {
                    if ($evento->withoutOverlapping && $evento->mutex->exists($evento)) {
                        return true;
                    }
                }
            }
        } catch (Throwable $e) {
            // Si no se puede revisar el scheduler, se corre igual
        }

        return false;
    }
}
