<?php

namespace App\Console\Commands;

use App\Support\JsonComprimido;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dry-run del cambio de formato del Archivo DTE. NO modifica nada.
 *
 * Este proyecto guardaba el JSON con gzcompress binario crudo. El formato
 * canonico nuevo es gz64: (gzencode + Base64 envuelto como string JSON valido).
 * Antes de convertir hay que saber que hay en la base: la migracion canonica
 * aplicada a ciegas sobre binario crudo lo comprimiria por segunda vez y el
 * contenido dejaria de poder leerse.
 */
class DiagnosticoJsonDte extends Command
{
    protected $signature = 'dte:diagnostico-json {--tabla= : Revisar una sola tabla}';

    protected $description = 'Cuenta en que formato esta guardado cada Archivo DTE, sin modificar nada';

    /** Columnas con JSON comprimible, por tabla. */
    public const CAMPOS = [
        'dtes' => ['jsonDte'],
        'firmadors' => ['json'],
        'invalidacion_dtes' => ['json', 'jsonRespuesta'],
        'recepcion_dtes' => ['josn'],
    ];

    public function handle(): int
    {
        $filtro = $this->option('tabla');
        $total = ['gz64' => 0, 'gzcompress' => 0, 'mysql_compress' => 0, 'plano' => 0, 'vacio' => 0, 'ilegible' => 0];

        foreach (self::CAMPOS as $tabla => $columnas) {
            if ($filtro && $filtro !== $tabla) {
                continue;
            }

            if (!Schema::hasTable($tabla)) {
                $this->warn("No existe la tabla {$tabla}, se omite.");
                continue;
            }

            foreach ($columnas as $columna) {
                if (!Schema::hasColumn($tabla, $columna)) {
                    $this->warn("No existe {$tabla}.{$columna}, se omite.");
                    continue;
                }

                $conteo = ['gz64' => 0, 'gzcompress' => 0, 'mysql_compress' => 0, 'plano' => 0, 'vacio' => 0, 'ilegible' => 0];
                $danados = [];

                DB::table($tabla)
                    ->select(['id', $columna])
                    ->orderBy('id')
                    ->chunkById(200, function ($filas) use ($columna, &$conteo, &$danados) {
                        foreach ($filas as $fila) {
                            $formato = self::detectar($fila->{$columna});
                            $conteo[$formato]++;

                            if ($formato === 'ilegible' && count($danados) < 20) {
                                $danados[] = $fila->id;
                            }
                        }
                    }, 'id');

                $this->line(sprintf(
                    '%-22s %-14s  gz64:%-6d gzcompress:%-6d mysql:%-5d plano:%-6d vacio:%-6d ilegible:%d',
                    $tabla,
                    $columna,
                    $conteo['gz64'],
                    $conteo['gzcompress'],
                    $conteo['mysql_compress'],
                    $conteo['plano'],
                    $conteo['vacio'],
                    $conteo['ilegible']
                ));

                if ($danados !== []) {
                    $this->warn('   ids ilegibles (no se tocan al convertir): ' . implode(', ', $danados));
                }

                foreach ($conteo as $k => $v) {
                    $total[$k] += $v;
                }
            }
        }

        $this->newLine();
        $this->info('Total: ' . json_encode($total));

        if ($total['gzcompress'] || $total['mysql_compress']) {
            $this->newLine();
            $this->warn('Hay valores en formato antiguo. Convertir con:');
            $this->line('  php artisan migrate --path=database/migrations/2026_09_08_120000_optimize_dte_queries_and_convert_json_gz64.php --force');
        }

        return self::SUCCESS;
    }

    /**
     * Formato en que esta guardado un valor, sin modificarlo.
     */
    public static function detectar($valor): string
    {
        if ($valor === null || $valor === '') {
            return 'vacio';
        }

        $valor = (string) $valor;

        if (JsonComprimido::estaComprimido($valor)) {
            return 'gz64';
        }

        if (self::esJsonValido($valor)) {
            return 'plano';
        }

        if (self::esJsonValido(@gzuncompress($valor) ?: '')) {
            return 'gzcompress';
        }

        if (strlen($valor) > 4 && self::esJsonValido(@gzuncompress(substr($valor, 4)) ?: '')) {
            return 'mysql_compress';
        }

        return 'ilegible';
    }

    private static function esJsonValido(string $texto): bool
    {
        if ($texto === '') {
            return false;
        }

        json_decode($texto);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
