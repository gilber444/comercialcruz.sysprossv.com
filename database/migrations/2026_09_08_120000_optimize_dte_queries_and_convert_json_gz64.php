<?php

use App\Console\Commands\DiagnosticoJsonDte;
use App\Support\JsonComprimido;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Indices de consulta del modulo DTE y conversion del Archivo DTE al formato
 * canonico gz64.
 *
 * IMPORTANTE. Esta NO es la migracion canonica del prompt aplicada tal cual.
 * Este proyecto guardaba el JSON con gzcompress binario crudo, y comprimir ese
 * binario otra vez lo deja ilegible. Aqui cada valor se detecta, se descomprime
 * a JSON, se valida con json_decode y recien entonces se comprime UNA vez al
 * formato nuevo, verificando el resultado antes de escribirlo.
 *
 * Antes de ejecutarla conviene correr el dry-run, que no modifica nada:
 *
 *     php artisan dte:diagnostico-json
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->indices(true);
        $this->convertir();
    }

    public function down(): void
    {
        // El rollback deja el JSON legible (plano) antes de retirar los
        // indices: nunca se revierte a gzcompress binario.
        $this->descomprimir();
        $this->indices(false);
    }

    /**
     * Los indices se crean solo si faltan: una base que ya los tenga -creados
     * a mano en el servidor- haria fallar la migracion completa.
     */
    private function indices(bool $crear): void
    {
        $definiciones = [
            'dtes' => [
                'idx_dtes_empresa_fecha_id' => ['empresa', 'fecEmi', 'id'],
                'idx_dtes_empresa_estado_fecha' => ['empresa', 'estado', 'fecEmi'],
                'idx_dtes_empresa_sucursal_fecha' => ['empresa', 'sucursal', 'fecEmi'],
                'idx_dtes_empresa_tipo_fecha' => ['empresa', 'tipoDte', 'fecEmi'],
                'idx_dtes_empresa_codigo' => ['empresa', 'codigoGeneracion'],
            ],
            'invalidacion_dtes' => [
                'idx_inva_empresa_numero' => ['empresa', 'numero'],
                'idx_inva_empresa_estado_numero' => ['empresa', 'estado', 'numero'],
                'idx_inva_codigo_estado' => ['codigoGeneracion', 'estado'],
            ],
            'recepcion_dtes' => [
                'idx_recepcion_dte_proceso' => ['dte', 'fhProcesamiento'],
            ],
        ];

        foreach ($definiciones as $tabla => $indices) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($indices as $nombre => $columnas) {
                $faltaColumna = false;

                foreach ($columnas as $columna) {
                    if (!Schema::hasColumn($tabla, $columna)) {
                        $faltaColumna = true;
                    }
                }

                if ($faltaColumna) {
                    continue;
                }

                $existe = $this->existeIndice($tabla, $nombre);

                if ($crear && !$existe) {
                    Schema::table($tabla, fn (Blueprint $t) => $t->index($columnas, $nombre));
                } elseif (!$crear && $existe) {
                    Schema::table($tabla, fn (Blueprint $t) => $t->dropIndex($nombre));
                }
            }
        }
    }

    private function existeIndice(string $tabla, string $nombre): bool
    {
        $filas = DB::select(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
              LIMIT 1',
            [$tabla, $nombre]
        );

        return $filas !== [];
    }

    private function convertir(): void
    {
        $resumen = ['convertidos' => 0, 'omitidos' => 0, 'ilegibles' => 0];
        $ilegibles = [];

        $this->recorrer(function (string $tabla, string $columna, $fila) use (&$resumen, &$ilegibles) {
            $valor = $fila->{$columna};
            $formato = DiagnosticoJsonDte::detectar($valor);

            // Ya convertido o sin contenido: no se toca.
            if (in_array($formato, ['gz64', 'vacio'], true)) {
                $resumen['omitidos']++;

                return null;
            }

            // Dañado: se registra el id y se conserva tal cual. Regenerarlo en
            // silencio destruiria la unica copia del documento firmado.
            if ($formato === 'ilegible') {
                $resumen['ilegibles']++;
                $ilegibles[] = "{$tabla}.{$columna}#{$fila->id}";

                return null;
            }

            $plano = JsonComprimido::descomprimir($valor);

            if ($plano === null || json_decode($plano) === null) {
                $resumen['ilegibles']++;
                $ilegibles[] = "{$tabla}.{$columna}#{$fila->id}";

                return null;
            }

            $nuevo = JsonComprimido::comprimir($plano);

            // Verificacion antes de escribir: si no vuelve a dar el mismo JSON,
            // se deja el valor original.
            if (JsonComprimido::descomprimir($nuevo) !== $plano) {
                $resumen['ilegibles']++;
                $ilegibles[] = "{$tabla}.{$columna}#{$fila->id}";

                return null;
            }

            $resumen['convertidos']++;

            return $nuevo;
        });

        Log::info('Conversion de Archivo DTE a gz64', $resumen + ['ilegibles_ids' => array_slice($ilegibles, 0, 50)]);
    }

    private function descomprimir(): void
    {
        $this->recorrer(function (string $tabla, string $columna, $fila) {
            $valor = $fila->{$columna};

            if ($valor === null || $valor === '') {
                return null;
            }

            $plano = JsonComprimido::descomprimir($valor);

            return ($plano !== null && $plano !== $valor && json_decode($plano) !== null) ? $plano : null;
        });
    }

    /**
     * Recorre por lotes las columnas con JSON. El callback devuelve el valor
     * nuevo, o null para no tocar la fila.
     */
    private function recorrer(callable $transformar): void
    {
        foreach (DiagnosticoJsonDte::CAMPOS as $tabla => $columnas) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($columnas as $columna) {
                if (!Schema::hasColumn($tabla, $columna)) {
                    continue;
                }

                DB::table($tabla)
                    ->select(['id', $columna])
                    ->orderBy('id')
                    ->chunkById(200, function ($filas) use ($tabla, $columna, $transformar) {
                        foreach ($filas as $fila) {
                            $nuevo = $transformar($tabla, $columna, $fila);

                            if ($nuevo !== null) {
                                DB::table($tabla)->where('id', $fila->id)->update([$columna => $nuevo]);
                            }
                        }
                    }, 'id');
            }
        }
    }
};
