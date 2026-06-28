<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class PurgeRetention extends Command
{ 
    protected $signature = 'purge:retention
        {--days=30 : Días de retención}
        {--conn= : Conexión de BD (por defecto, primary)}
        {--dry-run : Solo mostrar qué se borraría}
        {--optimize : Ejecuta OPTIMIZE TABLE al final (MySQL/MariaDB)}';

    protected $description = 'Purgar bitácoras y logs más antiguos que N días';

    /** Tablas a purgar por created_at (ajusta si quieres) */
    protected array $tables = [
        'bitacora_sincronizacions' => 'created_at', // o 'fecha' si prefieres
    ];

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dry    = (bool) $this->option('dry-run');
        $conn   = $this->option('conn') ?: config('database.default');
        $cutoff = Carbon::now('America/El_Salvador')->subDays($days);

        $this->info("Purgando > {$days} días (corte: {$cutoff})");
        $totalDb = 0;

        // 1) Purga DB (tablas)
        foreach ($this->tables as $table => $tsCol) {
            try {
                $q = DB::connection($conn)->table($table)->where($tsCol, '<', $cutoff);
                $count = (clone $q)->count();

                if ($count === 0) {
                    $this->line("• {$table}: 0 filas antiguas.");
                    continue;
                }

                if ($dry) {
                    $this->line("• {$table}: {$count} filas (DRY-RUN, no se borran)");
                } else {
                    // borrar en lotes para evitar locks largos
                    $deletedTotal = 0;
                    do {
                        $deleted = DB::connection($conn)->table($table)
                            ->where($tsCol, '<', $cutoff)
                            ->limit(5000)
                            ->delete();
                        $deletedTotal += $deleted;
                    } while ($deleted > 0);

                    $this->line("• {$table}: {$deletedTotal} filas borradas.");
                    $totalDb += $deletedTotal;

                    if ($this->option('optimize')) {
                        DB::connection($conn)->statement("OPTIMIZE TABLE {$table}");
                        $this->line("  (OPTIMIZE TABLE ejecutado en {$table})");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("• {$table}: error -> ".$e->getMessage());
            }
        }

        // 2) Purga archivos de log (storage/logs/*.log)
        $logsPath = storage_path('logs');
        $totalFiles = 0; $totalSize = 0;

        if (is_dir($logsPath)) {
            $files = File::files($logsPath);
            foreach ($files as $file) {
                $name = $file->getFilename();
                if ($name === '.gitignore') continue;
                if (pathinfo($name, PATHINFO_EXTENSION) !== 'log') continue;

                $mtime = Carbon::createFromTimestamp($file->getMTime());
                if ($mtime->lt($cutoff)) {
                    if ($dry) {
                        $this->line("• (DRY) log: {$name}");
                    } else {
                        $size = $file->getSize();
                        @File::delete($file->getPathname());
                        $totalFiles++;
                        $totalSize += $size;
                    }
                }
            }
        }

        if (!$dry) {
            if ($totalFiles > 0) {
                $mb = number_format($totalSize / 1048576, 2);
                $this->line("• logs: {$totalFiles} archivos borrados (~{$mb} MB).");
            } else {
                $this->line("• logs: 0 archivos para purgar.");
            }
        }

        $this->info("✔ Listo. Registros borrados: {$totalDb}");
        return self::SUCCESS;
    }
}
