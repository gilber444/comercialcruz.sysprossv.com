<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Limpia storage/logs para que no crezca sin control:
 *  - Borra los .log SIN modificar hace más de N días (default 5).
 *  - Trunca (vacía) los .log activos más grandes que N MB (default 20), para que los logs de
 *    sync (que se escriben cada minuto) no se vuelvan gigantes dentro de la ventana.
 */
class LimpiarLogs extends Command
{
    protected $signature = 'logs:limpiar
        {--dias=5 : Borra los .log sin modificar hace más de N días}
        {--max-mb=20 : Trunca los .log activos más grandes que N MB}';

    protected $description = 'Limpia storage/logs: borra los viejos (>N días) y trunca los activos muy grandes.';

    public function handle(): int
    {
        $dias     = (int) $this->option('dias') ?: 5;
        $maxBytes = ((int) $this->option('max-mb') ?: 20) * 1024 * 1024;
        $dir      = storage_path('logs');

        if (!is_dir($dir)) {
            $this->warn("No existe el directorio: {$dir}");
            return self::SUCCESS;
        }

        $limite    = now()->subDays($dias)->getTimestamp();
        $borrados  = 0;
        $truncados = 0;

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.log') ?: [] as $file) {
            $mtime = @filemtime($file);

            // Viejo (inactivo) → borrar
            if ($mtime !== false && $mtime < $limite) {
                if (@unlink($file)) {
                    $borrados++;
                }
                continue;
            }

            // Activo pero muy grande → vaciar (no perdemos el archivo, se sigue escribiendo)
            if ((@filesize($file) ?: 0) > $maxBytes) {
                if (@file_put_contents($file, '') !== false) {
                    $truncados++;
                }
            }
        }

        $this->info("logs:limpiar → borrados: {$borrados} (>{$dias}d), truncados: {$truncados} (>" . (int) ($maxBytes / 1048576) . "MB)");
        return self::SUCCESS;
    }
}
