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
     * Ruta del binario de PHP que quieres usar (8.2 en VPS, el actual en Windows).
     * Se puede forzar con UPDATE_PHP_BINARY en el .env.
     */
    protected string $phpBinary;

    /**
     * Archivo de log específico para este comando.
     */
    protected string $logFile = 'update-from-git.log';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $this->phpBinary = $this->resolverPhpBinary();

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

        // 4) Ejecutar Composer con el PHP resuelto
        $composerCmd = $this->resolverComposerCmd()
            . ' install --no-dev --prefer-dist --optimize-autoloader';

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
            $fullCmd = $this->quote($this->phpBinary) . ' ' . $cmd;
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
            // En Windows hace falta /d por si el proyecto está en otra unidad.
            $cd = $this->esWindows() ? 'cd /d ' : 'cd ';
            $command = $cd . $this->quote($cwd) . ' && ' . $command;
        }

        exec($command . ' 2>&1', $output, $exitCode);

        return [$exitCode, $output];
    }

    /**
     * ¿Estamos en Windows (local) o en Linux (VPS)?
     */
    protected function esWindows(): bool
    {
        return strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN';
    }

    /**
     * Entrecomilla una ruta según el sistema operativo.
     */
    protected function quote(string $path): string
    {
        return $this->esWindows() ? '"' . $path . '"' : escapeshellarg($path);
    }

    /**
     * Binario de PHP a usar: UPDATE_PHP_BINARY del .env, /usr/bin/php82 en Linux
     * o el PHP con el que se está ejecutando artisan en Windows.
     */
    protected function resolverPhpBinary(): string
    {
        $configurado = trim((string) env('UPDATE_PHP_BINARY', ''));
        if ($configurado !== '') {
            return $configurado;
        }

        if ($this->esWindows()) {
            return PHP_BINARY;
        }

        return is_executable('/usr/bin/php82') ? '/usr/bin/php82' : (PHP_BINARY ?: 'php');
    }

    /**
     * Comando base de Composer (sin argumentos). Prefiere composer.phar ejecutado
     * con el PHP resuelto; si solo hay un ejecutable (composer.bat/composer), lo usa directo.
     */
    protected function resolverComposerCmd(): string
    {
        $configurado = trim((string) env('UPDATE_COMPOSER_PATH', ''));
        if ($configurado !== '') {
            return $this->comandoParaComposer($configurado);
        }

        $candidatos = array_filter([
            base_path('composer.phar'),
            $this->esWindows() ? 'C:\\laragon\\bin\\composer\\composer.phar' : '/usr/local/bin/composer',
            $this->esWindows() ? 'C:\\ProgramData\\ComposerSetup\\bin\\composer.phar' : '/usr/bin/composer',
        ]);

        foreach ($candidatos as $ruta) {
            if (is_file($ruta)) {
                return $this->comandoParaComposer($ruta);
            }
        }

        // Último recurso: buscarlo en el PATH.
        $buscar = $this->esWindows() ? 'where composer' : 'which composer';
        $encontrado = trim((string) @shell_exec($buscar));
        if ($encontrado !== '') {
            $primero = trim(explode("\n", str_replace("\r", '', $encontrado))[0]);
            if ($primero !== '' && is_file($primero)) {
                return $this->comandoParaComposer($primero);
            }
        }

        return 'composer';
    }

    /**
     * Un .phar se ejecuta con PHP; un .bat/.exe/binario se ejecuta directo.
     */
    protected function comandoParaComposer(string $ruta): string
    {
        $esPhar = str_ends_with(strtolower($ruta), '.phar') || !$this->esWindows();

        return $esPhar
            ? $this->quote($this->phpBinary) . ' ' . $this->quote($ruta)
            : $this->quote($ruta);
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
