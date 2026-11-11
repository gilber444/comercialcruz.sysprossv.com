<?php

namespace App\Console\Commands;

use App\Services\CatalogoDteImportador;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

/**
 * Reporta el dano que dejo el importador de catalogos, sin modificar nada.
 *
 * Busca las dos secuelas conocidas de la importacion desde Excel:
 *  1. Filas internas sin codigo apagadas por el barrido de retiro. La
 *     facturacion las busca con whereNull('codigo') y sin ellas el POS muere
 *     con "Attempt to read property id on null".
 *  2. Duplicados por ceros a la izquierda: la base tenia "1" y Hacienda publica
 *     "01", no hubo match y se inserto una fila nueva.
 *
 * No borra ni actualiza. La limpieza se decide despues de leer esto.
 */
class DiagnosticoCatalogosDte extends Command
{
    protected $signature = 'catalogos:diagnostico {--tabla= : Revisar solo una tabla}';

    protected $description = 'Reporta comodines desactivados y duplicados en los catalogos DTE, sin modificar nada';

    public function handle(): int
    {
        $problemas = 0;

        foreach (CatalogoDteImportador::MAPA as $catalogo => $modelo) {
            $tabla = (new $modelo)->getTable();

            if ($this->option('tabla') && $this->option('tabla') !== $tabla) {
                continue;
            }

            if (!Schema::hasTable($tabla)) {
                $this->warn("  {$tabla}: la tabla no existe, se omite.");
                continue;
            }

            $problemas += $this->revisar($catalogo, $modelo, $tabla);
        }

        $this->newLine();

        if ($problemas === 0) {
            $this->info('Sin hallazgos: no hay comodines apagados ni duplicados por ceros a la izquierda.');

            return self::SUCCESS;
        }

        $this->warn("Total de hallazgos: {$problemas}.");
        $this->line('Nada fue modificado. Revise el detalle antes de decidir la limpieza.');

        return self::SUCCESS;
    }

    private function revisar(string $catalogo, string $modelo, string $tabla): int
    {
        $query = $modelo::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelo), true)) {
            $query->withTrashed();
        }

        $filas = $query->get();
        $hallazgos = 0;

        // --- 1. Comodines sin codigo -------------------------------------
        $comodines = $filas->filter(fn ($f) => CatalogoDteImportador::claveCodigo($f->codigo) === '');

        foreach ($comodines as $fila) {
            if ($fila->status !== 'Activo') {
                $this->newLine();
                $this->error("{$catalogo} / {$tabla}: comodin sin codigo DESACTIVADO");
                $this->line("  id={$fila->id}  valor=" . var_export($fila->valor, true) . "  status={$fila->status}");
                $this->line("  Reactivar con: UPDATE {$tabla} SET status='Activo' WHERE id={$fila->id};");
                $hallazgos++;
            }
        }

        // --- 2. Duplicados por codigo normalizado ------------------------
        $porClave = [];

        foreach ($filas as $fila) {
            $clave = CatalogoDteImportador::claveCodigo($fila->codigo);

            if ($clave === '') {
                continue;
            }

            $porClave[$clave][] = $fila;
        }

        foreach ($porClave as $clave => $grupo) {
            if (count($grupo) < 2) {
                continue;
            }

            $this->newLine();
            $this->error("{$catalogo} / {$tabla}: codigo '{$clave}' duplicado (" . count($grupo) . ' filas)');

            $ordenado = collect($grupo)->sortBy('id');
            $conservar = $ordenado->first();

            foreach ($ordenado as $fila) {
                $marca = $fila->id === $conservar->id ? 'CONSERVAR' : 'sobrante ';
                $this->line(sprintf(
                    '  %s id=%-6s codigo=%-10s status=%-12s valor=%s',
                    $marca,
                    $fila->id,
                    var_export($fila->codigo, true),
                    $fila->status ?? '(sin status)',
                    $fila->valor
                ));
            }

            $sobrantes = $ordenado->skip(1)->pluck('id')->implode(', ');
            $this->line("  Antes de borrar, verifique quien referencia los ids: {$sobrantes}");

            $hallazgos++;
        }

        return $hallazgos;
    }
}
