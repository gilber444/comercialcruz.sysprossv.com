<?php

namespace App\Console\Commands;

use App\Services\CatalogoDteImportador;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fusiona los duplicados que dejo la importacion de catalogos.
 *
 * Criterio: se conserva SIEMPRE el id mas antiguo del grupo, porque es el que
 * referencian los DTE ya sellados, los productos y las cotizaciones. El
 * sobrante se elimina, pero antes se repuntan a la fila conservada todas las
 * referencias que apunten a el.
 *
 * El codigo de la fila conservada NO se toca: los generadores de JSON emiten
 * uniMedida con (int) $codigo, asi que "01" y "1" salen igual hacia Hacienda.
 * Los textos se dejan a la reimportacion del Excel, que ya sabe normalizarlos.
 *
 * Por defecto solo simula. Requiere --ejecutar para escribir.
 */
class FusionarCatalogosDte extends Command
{
    protected $signature = 'catalogos:fusionar
                            {--ejecutar : Aplica los cambios. Sin esta bandera solo simula}
                            {--repuntar : Autoriza mover referencias al id conservado. Obligatoria si hay alguna}
                            {--tabla= : Limitar a una tabla}';

    protected $description = 'Fusiona duplicados de catalogos conservando el id mas antiguo y repuntando referencias';

    /**
     * Fragmentos de nombre de columna que pueden apuntar a cada catalogo. Se
     * usan para barrer TODO el esquema, no una lista fija de tablas: una
     * columna nueva que nadie recuerde tiene que aparecer igual.
     */
    private const PISTAS = [
        'tipo_documentos' => ['tipodte', 'tipodocumento', 'tipodoc'],
        'unidad_medidas' => ['medidamh', 'unimedida', 'unidadmedida', 'medida', 'unidad'],
    ];

    /** Fragmentos genericos, por si el catalogo no esta en PISTAS. */
    private function pistas(string $tabla): array
    {
        if (isset(self::PISTAS[$tabla])) {
            return self::PISTAS[$tabla];
        }

        // "tipo_contigencias" -> "contigencia", "tributos" -> "tributo"
        $base = rtrim(str_replace('_', '', $tabla), 's');

        return [$base];
    }

    public function handle(): int
    {
        $ejecutar = (bool) $this->option('ejecutar');

        if (!$ejecutar) {
            $this->warn('MODO SIMULACION. Nada se modifica. Agregue --ejecutar para aplicar.');
            $this->newLine();
        }

        $columnas = $this->columnasDelEsquema();
        $this->line('Columnas del esquema analizadas: ' . count($columnas));
        $this->newLine();

        $grupos = [];

        foreach (CatalogoDteImportador::MAPA as $catalogo => $modelo) {
            $tabla = (new $modelo)->getTable();

            if ($this->option('tabla') && $this->option('tabla') !== $tabla) {
                continue;
            }

            if (!Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($this->duplicados($modelo) as $grupo) {
                $grupos[] = ['catalogo' => $catalogo, 'tabla' => $tabla, 'modelo' => $modelo] + $grupo;
            }
        }

        if ($grupos === []) {
            $this->info('No hay duplicados que fusionar.');

            return self::SUCCESS;
        }

        $plan = [];

        foreach ($grupos as $grupo) {
            $plan[] = $this->planificar($grupo, $columnas);
        }

        if (!$ejecutar) {
            $this->newLine();
            $this->warn('Simulacion terminada. Revise las referencias listadas y vuelva con --ejecutar.');

            return self::SUCCESS;
        }

        // Las referencias se detectan por nombre de columna, y ese criterio
        // puede acertar de mas: ventas_detalles.medida y .unidad se llaman
        // igual que el catalogo pero no apuntan a el. Mover datos por
        // coincidencia de nombre exige una autorizacion aparte.
        $conReferencias = array_filter($plan, fn ($p) => $p['referencias'] !== []);

        if ($conReferencias !== [] && !$this->option('repuntar')) {
            $this->newLine();
            $this->error('Hay referencias a los sobrantes y no se paso --repuntar.');
            $this->line('Verifique una por una que esa columna apunte de verdad a este catalogo');
            $this->line('y no sea un nombre parecido. Luego repita con --ejecutar --repuntar.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->aplicar($plan);

        return self::SUCCESS;
    }

    /**
     * Columnas que pueden contener un id de catalogo, como [tabla, columna].
     *
     * Solo tipos enteros. Una llave foranea a estas tablas SIEMPRE es entera, y
     * filtrar por tipo evita un falso positivo grave: esquema_dtes.tipoDte es
     * varchar y guarda el CODIGO del CAT-002 ('01', '14', 'INV'), no el id. Al
     * comparar varchar contra entero, MySQL convierte el texto a numero y '14'
     * empataba con el id 14. Repuntar eso habria destruido la asociacion del
     * esquema de Sujeto Excluido.
     */
    private function columnasDelEsquema(): array
    {
        $base = DB::connection()->getDatabaseName();

        $filas = DB::select(
            "SELECT TABLE_NAME AS t, COLUMN_NAME AS c, DATA_TYPE AS d
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND DATA_TYPE IN ('int','bigint','mediumint','smallint','tinyint')",
            [$base]
        );

        return array_map(fn ($f) => [$f->t, $f->c], $filas);
    }

    /** Grupos de filas que comparten codigo normalizado. */
    private function duplicados(string $modelo): array
    {
        $query = $modelo::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelo), true)) {
            $query->withTrashed();
        }

        $porClave = [];

        foreach ($query->get() as $fila) {
            $clave = CatalogoDteImportador::claveCodigo($fila->codigo);

            if ($clave !== '') {
                $porClave[$clave][] = $fila;
            }
        }

        $grupos = [];

        foreach ($porClave as $clave => $filas) {
            if (count($filas) < 2) {
                continue;
            }

            $ordenado = collect($filas)->sortBy('id')->values();

            $grupos[] = [
                'clave' => $clave,
                'conservar' => $ordenado->first(),
                'sobrantes' => $ordenado->slice(1)->values(),
            ];
        }

        return $grupos;
    }

    private function planificar(array $grupo, array $columnas): array
    {
        $tabla = $grupo['tabla'];
        $conservar = $grupo['conservar'];
        $sobrantes = $grupo['sobrantes'];
        $ids = $sobrantes->pluck('id')->all();

        $this->line("<fg=yellow>{$grupo['catalogo']} / {$tabla}</> codigo '{$grupo['clave']}'");
        $this->line("  conservar id={$conservar->id} (codigo=" . var_export($conservar->codigo, true) . ")");
        $this->line('  eliminar  ids=' . implode(', ', $ids));

        $referencias = [];
        $pistas = $this->pistas($tabla);

        foreach ($columnas as [$t, $c]) {
            if ($t === $tabla) {
                continue;
            }

            $nombre = strtolower(str_replace('_', '', $c));
            $coincide = false;

            foreach ($pistas as $pista) {
                if (str_contains($nombre, str_replace('_', '', $pista))) {
                    $coincide = true;
                    break;
                }
            }

            if (!$coincide) {
                continue;
            }

            try {
                $n = DB::table($t)->whereIn($c, $ids)->count();
            } catch (\Throwable $e) {
                // Columna no comparable (texto libre, fecha). No es referencia.
                continue;
            }

            if ($n > 0) {
                $referencias[] = ['tabla' => $t, 'columna' => $c, 'filas' => $n];
                $this->line("  <fg=red>referencia</> {$t}.{$c} -> {$n} fila(s) apuntan al sobrante");
            }
        }

        if ($referencias === []) {
            $this->line('  <fg=green>sin referencias</>: el sobrante se puede eliminar directo');
        }

        // Si alguna fila del grupo esta activa, la conservada tiene que quedar activa.
        $activar = $conservar->status !== 'Activo'
            && ($sobrantes->contains(fn ($f) => $f->status === 'Activo'));

        if ($activar) {
            $this->line("  <fg=cyan>reactivar</> id={$conservar->id}: esta '{$conservar->status}' y Hacienda lo publica");
        }

        $this->newLine();

        return $grupo + ['referencias' => $referencias, 'activar' => $activar, 'ids' => $ids];
    }

    private function aplicar(array $plan): void
    {
        $repuntadas = 0;
        $eliminadas = 0;
        $reactivadas = 0;

        DB::transaction(function () use ($plan, &$repuntadas, &$eliminadas, &$reactivadas) {
            foreach ($plan as $p) {
                foreach ($p['referencias'] as $ref) {
                    $repuntadas += DB::table($ref['tabla'])
                        ->whereIn($ref['columna'], $p['ids'])
                        ->update([$ref['columna'] => $p['conservar']->id]);
                }

                if ($p['activar']) {
                    $p['conservar']->status = 'Activo';
                    $p['conservar']->save();
                    $reactivadas++;
                }

                foreach ($p['sobrantes'] as $sobrante) {
                    // forceDelete: con SoftDeletes un delete normal dejaria la
                    // fila viva y el duplicado seguiria estorbando al importador.
                    method_exists($sobrante, 'forceDelete')
                        ? $sobrante->forceDelete()
                        : $sobrante->delete();
                    $eliminadas++;
                }
            }
        });

        $this->info("Referencias repuntadas: {$repuntadas}");
        $this->info("Filas reactivadas: {$reactivadas}");
        $this->info("Duplicados eliminados: {$eliminadas}");
        $this->newLine();
        $this->line('Ahora reimporte el Excel de Hacienda para normalizar los textos.');
    }
}
