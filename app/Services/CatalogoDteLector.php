<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Lee el Excel de catalogos publicado por el Ministerio de Hacienda.
 *
 * El archivo trae los 33 catalogos en UNA sola hoja, separados por filas
 * "CAT-0XX <nombre>" seguidas de un encabezado "Codigo | Valores".
 *
 * Ademas de los registros reales, el archivo mezcla dos tipos de filas que NO
 * son datos y que hay que descartar:
 *   - Notas al pie (CAT-014): el "codigo" es un parrafo completo.
 *   - Subtitulos de seccion (CAT-015, CAT-019): vienen sin codigo.
 *
 * Cada catalogo se devuelve de dos formas:
 *   - "registros": codigo => valor. Es lo que usan los catalogos normales.
 *   - "filas": lista ordenada [codigo, valor] tal como viene en el archivo.
 *     Municipios (CAT-013) y Distritos (CAT-008) repiten codigos entre
 *     departamentos y el departamento solo se deduce por el ORDEN de las filas,
 *     asi que indexar por codigo perderia registros.
 */
class CatalogoDteLector
{
    /**
     * El codigo real mas largo del catalogo son 8 caracteres ("1041.020").
     * Cualquier cosa mas larga es una nota al pie, no un registro.
     */
    private const LARGO_MAX_CODIGO = 12;

    /**
     * @return array<string, array{codigo:string,nombre:string,registros:array<string,string>,filas:array<int,array{0:string,1:string}>,descartadas:int,duplicados:int}>
     */
    public function leer(string $ruta): array
    {
        if (!is_readable($ruta)) {
            throw new RuntimeException('No se pudo leer el archivo subido.');
        }

        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException(
                'El servidor no tiene la extension zip de PHP; sin ella no se puede leer el Excel.'
            );
        }

        $lector = IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $hoja = $lector->load($ruta)->getSheet(0);

        $catalogos = [];
        $actual = null;

        foreach ($hoja->getRowIterator() as $fila) {
            $n = $fila->getRowIndex();
            $a = $this->celda($hoja, 'A', $n);
            $b = $this->celda($hoja, 'B', $n);

            if (preg_match('/^(CAT-\d+)\s*(.*)$/u', $a, $m)) {
                $actual = $m[1];
                $catalogos[$actual] = [
                    'codigo' => $actual,
                    'nombre' => trim($m[2]) !== '' ? trim($m[2]) : $b,
                    'registros' => [],
                    'filas' => [],
                    'descartadas' => 0,
                    'duplicados' => 0,
                ];
                continue;
            }

            if ($actual === null) {
                continue;
            }

            if ($a === '' && $b === '') {
                continue;
            }

            if ($this->esEncabezado($a, $b)) {
                continue;
            }

            if ($a === '' || $b === '' || mb_strlen($a) > self::LARGO_MAX_CODIGO) {
                $catalogos[$actual]['descartadas']++;
                continue;
            }

            if (isset($catalogos[$actual]['registros'][$a])) {
                $catalogos[$actual]['duplicados']++;
            }

            $catalogos[$actual]['registros'][$a] = $b;
            $catalogos[$actual]['filas'][] = [$a, $b];
        }

        if ($catalogos === []) {
            throw new RuntimeException(
                'El archivo no contiene catalogos con el formato "CAT-0XX". ' .
                'Verifique que sea el Excel de catalogos publicado por Hacienda.'
            );
        }

        return $catalogos;
    }

    private function celda($hoja, string $columna, int $fila): string
    {
        $valor = $hoja->getCell($columna . $fila)->getValue();

        if ($valor === null) {
            return '';
        }

        // Los codigos con ceros a la izquierda ("00", "01") vienen como texto en
        // el archivo de Hacienda; el cast preserva el valor tal cual. Los que
        // vienen como numero (CAT-008, CAT-013) llegan como entero.
        return trim((string) $valor);
    }

    private function esEncabezado(string $a, string $b): bool
    {
        $a = mb_strtolower($a);
        $b = mb_strtolower($b);

        return in_array($a, ['código', 'codigo'], true)
            || in_array($b, ['valores', 'valor'], true);
    }
}
