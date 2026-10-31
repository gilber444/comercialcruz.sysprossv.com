<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

/**
 * Lee los JSON Schemas publicados por el Ministerio de Hacienda.
 *
 * Acepta el ZIP completo (svfe-json-schemas) o un .json suelto. De cada archivo
 * saca el tipo de DTE y, sobre todo, la VERSION: los esquemas la declaran como
 * "const" dentro de identificacion, asi que no hay que digitarla a mano.
 *
 *   fe-ccf-v4.json -> properties.identificacion.properties.version.const = 4
 */
class EsquemaDteLector
{
    /**
     * Nombre de archivo (sin el sufijo -vN) contra el codigo del CAT-002.
     * Invalidacion y contingencia son eventos y no tienen codigo de CAT-002.
     */
    public const TIPOS = [
        // Hacienda nombra la Factura "fe-fc", no "fe-f": con la clave corta el
        // archivo del ZIP no calzaba y la Factura se descartaba en silencio,
        // por eso solo entraba el Credito Fiscal. Se dejan ambas por si algun
        // paquete viejo trae el nombre corto.
        'fe-fc' => '01',
        'fe-f' => '01',
        'fe-ccf' => '03',
        'fe-nr' => '04',
        'fe-nc' => '05',
        'fe-nd' => '06',
        'fe-cr' => '07',
        'fe-cl' => '08',
        'fe-dcl' => '09',
        'fe-fex' => '11',
        'fe-fse' => '14',
        'fe-cd' => '15',
        'fe-eop' => '17',
        'fe-eret' => '18',
        // El evento de invalidacion viaja como "anulacion-schema" en el ZIP.
        'anulacion-schema' => 'INV',
        'invalidacion-schema' => 'INV',
        'contingencia-schema' => 'CON',
    ];

    /**
     * @return array<int, array{tipoDte:string,nombre:string,version:int,archivo:string,hash:string,contenido:string}>
     */
    public function leer(string $ruta): array
    {
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

        $archivos = $extension === 'zip'
            ? $this->desdeZip($ruta)
            : [basename($ruta) => file_get_contents($ruta)];

        $esquemas = [];

        foreach ($archivos as $nombre => $contenido) {
            $esquema = $this->interpretar($nombre, $contenido);

            if ($esquema !== null) {
                $esquemas[] = $esquema;
            }
        }

        if ($esquemas === []) {
            throw new RuntimeException(
                'No se encontraron esquemas validos. Suba el ZIP "svfe-json-schemas" ' .
                'publicado por Hacienda o un archivo .json de esquema.'
            );
        }

        // Un ZIP puede incluir varias versiones del mismo tipo. Se procesan de
        // menor a mayor para conservar el historial y dejar activa la version
        // mas reciente, sin depender del orden interno del archivo ZIP.
        usort($esquemas, function ($a, $b) {
            $porTipo = strcmp($a['tipoDte'], $b['tipoDte']);

            if ($porTipo !== 0) {
                return $porTipo;
            }

            $porVersion = $a['version'] <=> $b['version'];

            return $porVersion !== 0
                ? $porVersion
                : strcmp($a['archivo'], $b['archivo']);
        });

        return $esquemas;
    }

    /** @return array<string,string> nombre => contenido */
    private function desdeZip(string $ruta): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extension ZIP de PHP no esta habilitada en el servidor.');
        }

        $zip = new ZipArchive();

        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo ZIP.');
        }

        $archivos = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);

            if (substr($nombre, -1) === '/' || strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) !== 'json') {
                continue;
            }

            $archivos[basename($nombre)] = $zip->getFromIndex($i);
        }

        $zip->close();

        return $archivos;
    }

    private function interpretar(string $nombre, $contenido): ?array
    {
        if (!is_string($contenido) || $contenido === '') {
            return null;
        }

        $json = json_decode($contenido, true);

        if (!is_array($json)) {
            return null;
        }

        $slug = strtolower(preg_replace('/-v\d+$/', '', pathinfo($nombre, PATHINFO_FILENAME)));

        if (!isset(self::TIPOS[$slug])) {
            return null;
        }

        $version = $this->version($json, $nombre);

        if ($version === null) {
            return null;
        }

        return [
            'tipoDte' => self::TIPOS[$slug],
            'nombre' => (string) ($json['title'] ?? $json['description'] ?? $slug),
            'version' => $version,
            'archivo' => $nombre,
            'hash' => hash('sha256', $contenido),
            'contenido' => $contenido,
        ];
    }

    /**
     * La version sale del propio esquema. El nombre del archivo es solo el
     * ultimo recurso: es justamente lo que se presta a equivocarse.
     */
    private function version(array $json, string $nombre): ?int
    {
        $campo = $json['properties']['identificacion']['properties']['version'] ?? null;

        if (is_array($campo)) {
            if (isset($campo['const'])) {
                return (int) $campo['const'];
            }

            if (isset($campo['enum'][0])) {
                return (int) $campo['enum'][0];
            }
        }

        if (preg_match('/-v(\d+)$/', pathinfo($nombre, PATHINFO_FILENAME), $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
