<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateFromGit extends Command
{
    /**
     * Nombre del comando para Artisan.
     *
     * php artisan app:update-from-git
     */
    protected $signature = 'app:update-from-git'; 

    /**
     * Descripción del comando.
     */
    protected $description = 'Actualiza el proyecto desde GitHub y ejecuta mantenimiento (Composer + Artisan).';

    /**
     * Ruta del binario de PHP que quieres usar (8.2).
     */
    protected string $phpBinary = '/usr/bin/php82';

    /**
     * Archivo de log específico para este comando.
     */
    protected string $logFile = 'update-from-git.log';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $this->logInfo('===== Inicio de app:update-from-git =====');

        $projectPath = base_path();

        if (!is_dir($projectPath)) {
            $msg = "El directorio del proyecto no existe: {$projectPath}";
            $this->logError($msg);
            return Command::FAILURE;
        }

        $this->logInfo('Actualizando desde GitHub...');

        // 1) Traer cambios desde origin
        [$exitFetch, $outFetch] = $this->runShellCommand('git fetch origin', $projectPath);

        if ($exitFetch !== 0) {
            $this->logError("Error ejecutando 'git fetch origin':");
            $this->logError(implode("\n", $outFetch));
            return Command::FAILURE;
        }

        // 2) Comparar HEAD local vs origin/main para ver si hay cambios nuevos
        [$exitLocal, $outLocal]   = $this->runShellCommand('git rev-parse HEAD', $projectPath);
        [$exitRemote, $outRemote] = $this->runShellCommand('git rev-parse origin/main', $projectPath);

        if ($exitLocal === 0 && $exitRemote === 0) {
            $localHead  = trim(implode("\n", $outLocal));
            $remoteHead = trim(implode("\n", $outRemote));

            $this->logInfo("HEAD local:  {$localHead}");
            $this->logInfo("HEAD remoto: {$remoteHead}");

            if ($localHead === $remoteHead) {
                $this->logInfo('No hay cambios nuevos en origin/main. Nada que actualizar.');
                $this->logInfo('===== Fin de app:update-from-git (sin cambios) =====');
                return Command::SUCCESS;
            }

            $this->logInfo('Se detectaron cambios nuevos en origin/main.');
        } else {
            // Si falla la comparación, igual intentamos actualizar
            $this->logWarn('No se pudo comparar HEAD local vs origin/main. Se forzará la actualización.');
        }

        // 3) Forzar que el código local sea igual al remoto (evita ramas divergentes)
        [$exitReset, $outReset] = $this->runShellCommand('git reset --hard origin/main', $projectPath);

        if ($exitReset !== 0) {
            $this->logError("Error ejecutando 'git reset --hard origin/main':");
            $this->logError(implode("\n", $outReset));
            return Command::FAILURE;
        }

        $this->logInfo('Código actualizado desde origin/main correctamente.');
        $this->logInfo('Ejecutando mantenimiento (Composer + Artisan)...');

        // 4) Ejecutar Composer con PHP 8.2
        $composerBin = trim(shell_exec('which composer')) ?: '/usr/bin/composer';

        $composerCmd = sprintf(
            '%s %s install --no-dev --prefer-dist --optimize-autoloader',
            $this->phpBinary,
            $composerBin
        );

        $this->logInfo("Ejecutando Composer: {$composerCmd}");

        [$exitComposer, $outComposer] = $this->runShellCommand($composerCmd, $projectPath);

        if ($exitComposer !== 0) {
            $this->logError('Error ejecutando Composer:');
            $this->logError(implode("\n", $outComposer));
            return Command::FAILURE;
        }

        $this->logInfo("Composer install ejecutado correctamente.");
        $this->logInfo(implode("\n", $outComposer));

        // 5) Comandos Artisan (todos con PHP 8.2)
        $artisanCommands = [
            'artisan migrate --force',
            'artisan optimize:clear',
            'artisan config:cache',
            'artisan route:cache',
        ];

        foreach ($artisanCommands as $cmd) {
            $fullCmd = $this->phpBinary . ' ' . $cmd;
            $this->logInfo("Ejecutando: {$fullCmd}");

            [$exitArtisan, $outArtisan] = $this->runShellCommand($fullCmd, $projectPath);

            if ($exitArtisan !== 0) {
                $this->logError("Error ejecutando {$cmd}:");
                $this->logError(implode("\n", $outArtisan));
                return Command::FAILURE;
            }

            $this->logInfo("Salida de {$cmd}:");
            $this->logInfo(implode("\n", $outArtisan));
        }

        $this->logInfo('Actualización y mantenimiento finalizados correctamente ✅');
        $this->logInfo('===== Fin de app:update-from-git (OK) =====');

        return Command::SUCCESS;
    }

    /**
     * Ejecuta un comando de shell y devuelve [exitCode, outputArray].
     *
     * ⚠️ IMPORTANTE: el nombre es runShellCommand, NO runCommand.
     */
    protected function runShellCommand(string $command, ?string $cwd = null): array
    {
        $output   = [];
        $exitCode = 0;

        if ($cwd) {
            $command = 'cd ' . escapeshellarg($cwd) . ' && ' . $command;
        }

        exec($command . ' 2>&1', $output, $exitCode);

        return [$exitCode, $output];
    }

    /**
     * Helpers para loguear en consola y en archivo.
     */
    protected function logInfo(string $message): void
    {
        $this->info($message);
        $this->writeToLog('[INFO] ' . $message);
    }

    protected function logWarn(string $message): void
    {
        $this->warn($message);
        $this->writeToLog('[WARN] ' . $message);
    }

    protected function logError(string $message): void
    {
        $this->error($message);
        $this->writeToLog('[ERROR] ' . $message);
    }

    protected function writeToLog(string $message): void
    {
        $timestamp = Carbon::now('America/El_Salvador')->format('Y-m-d H:i:s');
        $line = '[' . $timestamp . '] ' . $message . PHP_EOL;

        @file_put_contents(
            storage_path('logs/' . $this->logFile),
            $line,
            FILE_APPEND
        );
    }
}
