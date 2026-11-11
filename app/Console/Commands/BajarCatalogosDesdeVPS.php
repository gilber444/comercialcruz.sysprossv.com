<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copia los catalogos de Hacienda del VPS a esta PC, por id.
 *
 * Los catalogos se actualizan en el VPS (importador del Excel oficial). Las
 * cajas emiten DTE con su copia local: sin esto seguirian mandando los codigos
 * viejos. Se copia por id porque clientes, sucursales y productos llegan del
 * VPS con esos mismos ids. Solo actualiza e inserta; nunca borra filas locales.
 */
class BajarCatalogosDesdeVPS extends Command
{
    protected $signature = 'catalogos:bajar-desde-vps {--dry-run : Solo muestra lo que cambiaria}';

    protected $description = 'Copia los catalogos de Hacienda (y departamentos, municipios y distritos) del VPS a esta PC';

    private const CONN_LOCAL = 'localmysql';
    private const CONN_VPS = 'vpsmysql';

    /** Modelos de catalogo; el orden respeta las llaves (departamento → municipio → distrito). */
    private const MODELOS = [
        'AmbienteDestino', 'TipoDocumento', 'ModeloFacturacion', 'TipoTransmision', 'TipoContigencia',
        'Retencion', 'TipoGeneracionDocumento', 'TipoEstablecimiento', 'ServicioMedico', 'TipoItem',
        'UnidadMedida', 'Tributo', 'CondicionOperacion', 'FormaPagos', 'Plazo', 'ActividadEconomica',
        'Pais', 'DocumentosAsociados', 'IdentificacionReceptor', 'TipoInvalidacion', 'TituloRemiteBienes',
        'Donacion', 'RecintoFiscal', 'Regimen', 'TipoPersona', 'Transporte', 'Incoterms', 'DomicilioFiscal',
        'Departamentos', 'Municipios', 'Distritos',
    ];

    public function handle(): int
    {
        if (config('app.modo', 'local') === 'vps') {
            $this->error('Este comando es para las PC locales (APP_MODO=local).');
            return self::FAILURE;
        }

        try {
            DB::connection(self::CONN_VPS)->getPdo();
            DB::connection(self::CONN_LOCAL)->getPdo();
        } catch (\Throwable $e) {
            $this->error('Sin conexion: ' . $e->getMessage());
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $totalNuevos = $totalCambios = 0;

        foreach (self::MODELOS as $nombre) {
            $clase = 'App\\Models\\' . $nombre;
            if (!class_exists($clase)) {
                continue;
            }
            $tabla = (new $clase())->getTable();
            if (!Schema::connection(self::CONN_VPS)->hasTable($tabla) || !Schema::connection(self::CONN_LOCAL)->hasTable($tabla)) {
                continue;
            }

            // Solo las columnas que existen en los dos lados
            $columnas = array_values(array_intersect(
                Schema::connection(self::CONN_VPS)->getColumnListing($tabla),
                Schema::connection(self::CONN_LOCAL)->getColumnListing($tabla)
            ));
            if (!in_array('id', $columnas, true)) {
                continue;
            }
            $comparar = array_values(array_diff($columnas, ['id', 'created_at', 'updated_at']));

            $remotas = DB::connection(self::CONN_VPS)->table($tabla)->get($columnas);
            $locales = DB::connection(self::CONN_LOCAL)->table($tabla)->get($columnas)->keyBy('id');

            $nuevos = $cambios = 0;
            DB::connection(self::CONN_LOCAL)->transaction(function () use ($tabla, $remotas, $locales, $comparar, $dry, &$nuevos, &$cambios) {
                foreach ($remotas as $r) {
                    $fila = (array) $r;
                    $l = $locales[$r->id] ?? null;

                    if ($l === null) {
                        $nuevos++;
                        if (!$dry) {
                            DB::connection(self::CONN_LOCAL)->table($tabla)->insert($fila);
                        }
                        continue;
                    }

                    $distinto = false;
                    foreach ($comparar as $c) {
                        if ((string) ($l->{$c} ?? '') !== (string) ($r->{$c} ?? '')) {
                            $distinto = true;
                            break;
                        }
                    }
                    if ($distinto) {
                        $cambios++;
                        if (!$dry) {
                            DB::connection(self::CONN_LOCAL)->table($tabla)->where('id', $r->id)->update(array_diff_key($fila, ['id' => 1]));
                        }
                    }
                }
            });

            if ($nuevos || $cambios) {
                $this->line(sprintf('  %-28s %d nuevos, %d actualizados', $tabla, $nuevos, $cambios));
            }
            $totalNuevos += $nuevos;
            $totalCambios += $cambios;
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Catalogos: {$totalNuevos} nuevos, {$totalCambios} actualizados.");

        return self::SUCCESS;
    }
}
