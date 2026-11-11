<?php

namespace App\Services;

use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Municipios;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza Departamentos (CAT-012), Municipios (CAT-013) y Distritos (CAT-008)
 * desde el Excel de Hacienda.
 *
 * El Excel NO trae la columna del padre y los codigos se repiten entre padres
 * (el "14" es Ahuachapan Centro y tambien Santa Ana Norte). Por eso aqui NO se
 * empata por codigo como en los demas catalogos, sino asi:
 *
 *  - Departamentos: por codigo normalizado (sin ceros a la izquierda), y solo si
 *    el nombre tambien corresponde.
 *  - Municipios: el departamento se deduce del nombre ("SANTA ANA NORTE" empieza
 *    con "SANTA ANA") y se empata por nombre contra los municipios QUE YA TIENE
 *    ese departamento en la base.
 *  - Distritos: el departamento se deduce de la secuencia (el codigo reinicia en
 *    1 al cambiar de departamento, en el mismo orden del CAT-012) y se empata por
 *    nombre contra los distritos cuyo municipio pertenece a ese departamento.
 *
 * Reglas:
 *  - Update in place: nunca se cambia el id ni el padre de una fila, nunca se
 *    inserta ni se borra, y el status no se toca.
 *  - Lo que no empata, o empata con mas de una fila, se REPORTA y no se toca.
 *  - Las filas de la base que no aparecen en el Excel solo se reportan.
 *  - El codigo almacenado solo se reescribe si cambia de verdad: "01" y "1"
 *    son el mismo codigo y se conserva el formato que ya tenia la fila.
 */
class CatalogoTerritorialImportador
{
    public const DEPARTAMENTOS = 'CAT-012';
    public const MUNICIPIOS = 'CAT-013';
    public const DISTRITOS = 'CAT-008';

    /** Orden obligatorio: los hijos se analizan contra los departamentos. */
    public const CATALOGOS = [
        self::DEPARTAMENTOS => 'Departamentos',
        self::MUNICIPIOS => 'Municipios',
        self::DISTRITOS => 'Distritos',
    ];

    /** Palabras que el Excel omite en los nombres abreviados ("SAN JOSE FLORES"). */
    private const CONECTORES = ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'EL', 'Y'];

    /**
     * Analiza los catalogos territoriales presentes en el archivo, sin escribir.
     *
     * @return array<string, array{error:?string, filas:array<int,array>, conteo:array<string,int>}>
     */
    public function analizar(array $catalogos): array
    {
        if (array_intersect_key($catalogos, self::CATALOGOS) === []) {
            return [];
        }

        $deptos = $this->departamentosDeReferencia($catalogos);
        $planes = [];

        if (isset($catalogos[self::DEPARTAMENTOS])) {
            $planes[self::DEPARTAMENTOS] = $this->analizarDepartamentos($catalogos[self::DEPARTAMENTOS]);
        }

        if (isset($catalogos[self::MUNICIPIOS])) {
            $planes[self::MUNICIPIOS] = $this->analizarMunicipios($catalogos[self::MUNICIPIOS], $deptos);
        }

        if (isset($catalogos[self::DISTRITOS])) {
            $planes[self::DISTRITOS] = $this->analizarDistritos($catalogos[self::DISTRITOS], $deptos);
        }

        return $planes;
    }

    /**
     * Aplica las filas "actualizar" de los catalogos seleccionados. El analisis
     * se hace dentro de la misma transaccion, contra el estado de ese momento.
     *
     * @return array<string, array{nuevos:int,actualizados:int,retirados:int,conservados:int}>
     */
    public function aplicar(array $catalogos, array $seleccion): array
    {
        $resultado = [];

        DB::transaction(function () use ($catalogos, $seleccion, &$resultado) {
            // Todos los planes se calculan ANTES de escribir, para que cambiar
            // un departamento no altere el analisis de sus hijos.
            $planes = $this->analizar($catalogos);

            foreach (array_keys(self::CATALOGOS) as $codigo) {
                if (!in_array($codigo, $seleccion, true) || !isset($planes[$codigo])) {
                    continue;
                }

                $plan = $planes[$codigo];
                $conteo = ['nuevos' => 0, 'actualizados' => 0, 'retirados' => 0, 'conservados' => 0];

                if ($plan['error'] === null) {
                    foreach ($plan['filas'] as $fila) {
                        if ($fila['accion'] === 'actualizar') {
                            $this->escribir($codigo, $fila);
                            $conteo['actualizados']++;
                        }
                    }
                }

                $conteo['conservados'] = $plan['conteo']['sinEmparejar'] + $plan['conteo']['ambiguos']
                    + $plan['conteo']['noEnExcel'];
                $resultado[$codigo] = $conteo;
            }
        });

        return $resultado;
    }

    // ------------------------------------------------------------ normalizacion

    /**
     * Mayusculas, sin tildes ni signos, espacios colapsados. "Cabañas" y
     * "CABANAS" quedan iguales.
     */
    public static function normalizar($texto): string
    {
        $texto = mb_strtoupper(trim((string) $texto), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
        ]);
        $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto);

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    /**
     * Comparacion tolerante para los nombres abreviados del Excel:
     * "STA ROSA GUACHI" ~ "SANTA ROSA GUACHIPILIN", "SAN JOSE FLORES" ~
     * "SAN JOSE LAS FLORES". Cada palabra del Excel debe ser abreviatura de la
     * palabra de la base en el mismo orden (misma inicial y letras en orden), y
     * solo se pueden saltar conectores (DE, DEL, LA, LAS, LOS, EL, Y).
     */
    public static function nombreCompatible(string $excel, string $base): bool
    {
        $e = $excel === '' ? [] : explode(' ', self::normalizar($excel));
        $b = $base === '' ? [] : explode(' ', self::normalizar($base));

        if ($e === [] || $b === []) {
            return false;
        }

        return self::alinear($e, 0, $b, 0);
    }

    private static function alinear(array $e, int $i, array $b, int $j): bool
    {
        if ($i === count($e)) {
            for (; $j < count($b); $j++) {
                if (!in_array($b[$j], self::CONECTORES, true)) {
                    return false;
                }
            }

            return true;
        }

        if ($j === count($b)) {
            return false;
        }

        if (self::abrevia($e[$i], $b[$j]) && self::alinear($e, $i + 1, $b, $j + 1)) {
            return true;
        }

        return in_array($b[$j], self::CONECTORES, true) && self::alinear($e, $i, $b, $j + 1);
    }

    /** "STGO" abrevia "SANTIAGO": misma inicial y sus letras aparecen en orden. */
    private static function abrevia(string $corta, string $larga): bool
    {
        if ($corta === $larga) {
            return true;
        }

        if ($corta === '' || strlen($corta) > strlen($larga) || $corta[0] !== $larga[0]) {
            return false;
        }

        $pos = 0;

        for ($k = 0; $k < strlen($corta); $k++) {
            $pos = strpos($larga, $corta[$k], $pos);

            if ($pos === false) {
                return false;
            }

            $pos++;
        }

        return true;
    }

    // ---------------------------------------------------------------- analisis

    /**
     * Departamentos en el orden del Excel (sin el "00 Otro"), con su id en la
     * base. Si el archivo no trae el CAT-012 se usan los de la base por codigo.
     *
     * @return array<int, array{clave:string,nombre:string,norm:string,id:?int}>
     */
    private function departamentosDeReferencia(array $catalogos): array
    {
        $base = Departamentos::query()->get(['id', 'codigo', 'departamento']);
        $porClave = [];

        foreach ($base as $d) {
            $porClave[CatalogoDteImportador::claveCodigo($d->codigo)][] = $d;
        }

        $lista = [];

        if (isset($catalogos[self::DEPARTAMENTOS])) {
            foreach ($catalogos[self::DEPARTAMENTOS]['filas'] as [$cod, $nombre]) {
                $clave = CatalogoDteImportador::claveCodigo($cod);

                if ($clave === '' || $clave === '0') {
                    continue;
                }

                $filas = $porClave[$clave] ?? [];
                $lista[] = [
                    'clave' => $clave,
                    'nombre' => $nombre,
                    'norm' => self::normalizar($nombre),
                    'id' => count($filas) === 1 ? (int) $filas[0]->id : null,
                ];
            }

            return $lista;
        }

        foreach ($base->sortBy(fn ($d) => (int) CatalogoDteImportador::claveCodigo($d->codigo)) as $d) {
            $clave = CatalogoDteImportador::claveCodigo($d->codigo);

            if ($clave === '' || $clave === '0') {
                continue;
            }

            $lista[] = [
                'clave' => $clave,
                'nombre' => (string) $d->departamento,
                'norm' => self::normalizar($d->departamento),
                'id' => count($porClave[$clave]) === 1 ? (int) $d->id : null,
            ];
        }

        return $lista;
    }

    private function analizarDepartamentos(array $cat): array
    {
        $base = Departamentos::query()->orderBy('id')->get();
        $porClave = [];

        foreach ($base as $d) {
            $porClave[CatalogoDteImportador::claveCodigo($d->codigo)][] = $d;
        }

        $filas = [];
        $usados = [];

        foreach ($cat['filas'] as [$cod, $nombre]) {
            $clave = CatalogoDteImportador::claveCodigo($cod);
            $fila = $this->filaVacia($cod, $nombre);
            $candidatos = $porClave[$clave] ?? [];

            if (count($candidatos) === 0) {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'No existe en la base. No se inserta automaticamente.';
            } elseif (count($candidatos) > 1) {
                $fila['accion'] = 'ambiguo';
                $fila['motivo'] = 'Hay ' . count($candidatos) . ' departamentos con el codigo ' . $cod . ' en la base.';
            } else {
                $d = $candidatos[0];
                $usados[$d->id] = true;
                $fila = $this->conActual($fila, $d->id, $d->codigo, $d->departamento);
                $exacto = self::normalizar($d->departamento) === self::normalizar($nombre);

                if (!$exacto && !self::nombreCompatible($nombre, $d->departamento)) {
                    $fila['accion'] = 'ambiguo';
                    $fila['motivo'] = 'El codigo coincide pero el nombre no ("' . $d->departamento
                        . '"). Revise a mano; no se modifica.';
                } else {
                    $fila = $this->decidir($fila, $cod, $nombre, $exacto);
                }
            }

            $filas[] = $fila;
        }

        foreach ($base as $d) {
            if (!isset($usados[$d->id])) {
                $filas[] = $this->noEnExcel($d->id, $d->codigo, $d->departamento, null, null);
            }
        }

        return $this->plan($filas, null);
    }

    private function analizarMunicipios(array $cat, array $deptos): array
    {
        $base = Municipios::query()->orderBy('id')->get();
        $porDepto = $base->groupBy('departamento');
        $nombreDepto = $this->nombresDepartamento();

        $filas = [];

        foreach ($cat['filas'] as [$cod, $nombre]) {
            $fila = $this->filaVacia($cod, $nombre);

            if (CatalogoDteImportador::claveCodigo($cod) === '0') {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'Hacienda lo publica sin departamento. No se inserta un municipio sin padre.';
                $filas[] = $fila;
                continue;
            }

            $depto = $this->deptoPorPrefijo($nombre, $deptos);

            if ($depto === null) {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'No se pudo deducir el departamento por el nombre.';
                $filas[] = $fila;
                continue;
            }

            $fila['departamento'] = $depto['nombre'];

            if ($depto['id'] === null) {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'El departamento ' . $depto['nombre'] . ' no existe (o esta repetido) en la base.';
                $filas[] = $fila;
                continue;
            }

            $candidatos = $porDepto->get($depto['id'], collect())->all();
            $filas[] = $this->emparejarFila($fila, $cod, $nombre, $candidatos, 'municipio');
        }

        $filas = $this->resolverConflictos($filas);
        $usados = $this->idsUsados($filas);

        foreach ($base as $m) {
            if (!isset($usados[$m->id])) {
                $filas[] = $this->noEnExcel($m->id, $m->codigo, $m->municipio, $nombreDepto[$m->departamento] ?? null, null);
            }
        }

        return $this->plan($filas, null);
    }

    private function analizarDistritos(array $cat, array $deptos): array
    {
        $municipios = Municipios::query()->get(['id', 'municipio', 'departamento'])->keyBy('id');
        $base = Distritos::query()->orderBy('id')->get();
        $nombreDepto = $this->nombresDepartamento();

        // Distritos de la base agrupados por el departamento de su municipio.
        $porDepto = [];

        foreach ($base as $d) {
            $mun = $municipios->get($d->municipio);

            if ($mun) {
                $porDepto[$mun->departamento][] = $d;
            }
        }

        // 1. Deducir el departamento de cada fila por la secuencia.
        $filas = [];
        $bloques = [];
        $bloque = -1;
        $previo = null;
        $error = null;

        foreach ($cat['filas'] as $i => [$cod, $nombre]) {
            $clave = CatalogoDteImportador::claveCodigo($cod);

            if ($clave === '0') {
                continue;
            }

            if (!ctype_digit($clave)) {
                $error = 'El distrito "' . $nombre . '" tiene un codigo no numerico (' . $cod . ').';
                break;
            }

            $n = (int) $clave;

            if ($previo === null || $n <= $previo) {
                $bloque++;

                if ($n !== 1) {
                    $error = 'El bloque de distritos que empieza en "' . $nombre . '" no inicia en 1.';
                    break;
                }
            }

            $previo = $n;
            $bloques[$i] = $bloque;
        }

        if ($error === null && ($bloque + 1) !== count($deptos)) {
            $error = 'El Excel trae ' . ($bloque + 1) . ' bloques de distritos y hay ' . count($deptos)
                . ' departamentos: no se puede deducir con seguridad a que departamento pertenece cada distrito.';
        }

        if ($error !== null) {
            $error .= ' CAT-008 no se aplicara.';
        }

        // 2. Emparejar por nombre dentro del departamento deducido.
        foreach ($cat['filas'] as $i => [$cod, $nombre]) {
            $fila = $this->filaVacia($cod, $nombre);

            if (CatalogoDteImportador::claveCodigo($cod) === '0') {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'Hacienda lo publica sin departamento ni municipio. No se inserta un distrito sin padre.';
                $filas[] = $fila;
                continue;
            }

            if ($error !== null || !isset($bloques[$i])) {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'No se pudo deducir el departamento.';
                $filas[] = $fila;
                continue;
            }

            $depto = $deptos[$bloques[$i]];
            $fila['departamento'] = $depto['nombre'];

            if ($depto['id'] === null) {
                $fila['accion'] = 'sin_emparejar';
                $fila['motivo'] = 'El departamento ' . $depto['nombre'] . ' no existe (o esta repetido) en la base.';
                $filas[] = $fila;
                continue;
            }

            $fila = $this->emparejarFila($fila, $cod, $nombre, $porDepto[$depto['id']] ?? [], 'distrito');

            // Si no esta en su departamento, se busca en toda la base para decir
            // por que: casi siempre es un distrito colgado del municipio de otro
            // departamento. Se reporta; corregir el padre es decision humana.
            if ($fila['accion'] === 'sin_emparejar') {
                $fuera = $base->filter(function ($d) use ($nombre) {
                    return self::normalizar($d->distrito) === self::normalizar($nombre)
                        || self::nombreCompatible($nombre, (string) $d->distrito);
                });

                if ($fuera->isNotEmpty()) {
                    $fila['motivo'] = 'Existe en la base pero colgado de otro departamento: '
                        . $fuera->map(function ($d) use ($municipios, $nombreDepto) {
                            $mun = $municipios->get($d->municipio);

                            return $d->distrito . ' (id ' . $d->id . ', municipio ' . ($mun?->municipio ?? '?')
                                . ', depto ' . ($mun ? ($nombreDepto[$mun->departamento] ?? '?') : '?') . ')';
                        })->implode('; ')
                        . '. Revise su municipio; no se modifica.';
                }
            }

            if ($fila['id'] !== null) {
                $d = $base->firstWhere('id', $fila['id']);
                $fila['municipio'] = $municipios->get($d->municipio)?->municipio;
            }

            $filas[] = $fila;
        }

        $filas = $this->resolverConflictos($filas);
        $usados = $this->idsUsados($filas);

        foreach ($base as $d) {
            // Con la secuencia rota nada empata: listar toda la base no aporta.
            if ($error === null && !isset($usados[$d->id])) {
                $mun = $municipios->get($d->municipio);
                $filas[] = $this->noEnExcel(
                    $d->id,
                    $d->codigo,
                    $d->distrito,
                    $mun ? ($nombreDepto[$mun->departamento] ?? null) : null,
                    $mun?->municipio
                );
            }
        }

        return $this->plan($filas, $error);
    }

    // ---------------------------------------------------------------- ayudas

    /** Departamento cuyo nombre es el prefijo MAS LARGO del nombre del municipio. */
    private function deptoPorPrefijo(string $nombre, array $deptos): ?array
    {
        $norm = self::normalizar($nombre);
        $elegido = null;

        foreach ($deptos as $d) {
            if ($d['norm'] === '') {
                continue;
            }

            if ($norm === $d['norm'] || str_starts_with($norm, $d['norm'] . ' ')) {
                if ($elegido === null || strlen($d['norm']) > strlen($elegido['norm'])) {
                    $elegido = $d;
                }
            }
        }

        return $elegido;
    }

    private function emparejarFila(array $fila, string $cod, string $nombre, array $candidatos, string $campo): array
    {
        $norm = self::normalizar($nombre);
        $exactos = array_values(array_filter($candidatos, fn ($c) => self::normalizar($c->{$campo}) === $norm));

        if (count($exactos) === 1) {
            return $this->decidir($this->conActual($fila, $exactos[0]->id, $exactos[0]->codigo, $exactos[0]->{$campo}), $cod, $nombre, true);
        }

        if (count($exactos) > 1) {
            $fila['accion'] = 'ambiguo';
            $fila['motivo'] = 'Hay ' . count($exactos) . ' registros con ese nombre en el departamento.';

            return $fila;
        }

        $tolerantes = array_values(array_filter($candidatos, fn ($c) => self::nombreCompatible($nombre, (string) $c->{$campo})));

        if (count($tolerantes) === 1) {
            return $this->decidir($this->conActual($fila, $tolerantes[0]->id, $tolerantes[0]->codigo, $tolerantes[0]->{$campo}), $cod, $nombre, false);
        }

        if (count($tolerantes) > 1) {
            $fila['accion'] = 'ambiguo';
            $fila['motivo'] = 'El nombre abreviado coincide con varios: '
                . implode(', ', array_map(fn ($c) => $c->{$campo}, $tolerantes)) . '.';

            return $fila;
        }

        $fila['accion'] = 'sin_emparejar';
        $fila['motivo'] = 'No hay un registro con ese nombre en el departamento.';

        return $fila;
    }

    /**
     * Decide si la fila emparejada cambia. El nombre solo se reemplaza cuando el
     * empate fue exacto (difieren tildes o mayusculas): si el Excel trae una
     * abreviatura ("STA ROSA GUACHI") se conserva el nombre completo actual.
     */
    private function decidir(array $fila, string $cod, string $nombre, bool $exacto): array
    {
        $fila['emparejado'] = $exacto ? 'exacto' : 'tolerante';

        $codigoNuevo = CatalogoDteImportador::claveCodigo($fila['codigoActual']) === CatalogoDteImportador::claveCodigo($cod)
            ? $fila['codigoActual']
            : self::formatearCodigo($cod);

        $nombreNuevo = $exacto ? $nombre : $fila['nombreActual'];

        $fila['codigoNuevo'] = $codigoNuevo;
        $fila['nombreNuevo'] = $nombreNuevo;

        $cambia = (string) $codigoNuevo !== (string) $fila['codigoActual']
            || trim((string) $nombreNuevo) !== trim((string) $fila['nombreActual']);

        $fila['accion'] = $cambia ? 'actualizar' : 'igual';

        if (!$exacto) {
            $fila['motivo'] = 'Nombre abreviado en el Excel: se conserva el nombre actual.';
        }

        return $fila;
    }

    /** Si dos filas del Excel apuntan al mismo registro, ninguna se aplica. */
    private function resolverConflictos(array $filas): array
    {
        $conteo = [];

        foreach ($filas as $f) {
            if ($f['id'] !== null && in_array($f['accion'], ['actualizar', 'igual'], true)) {
                $conteo[$f['id']] = ($conteo[$f['id']] ?? 0) + 1;
            }
        }

        foreach ($filas as &$f) {
            if ($f['id'] !== null && ($conteo[$f['id']] ?? 0) > 1) {
                $f['accion'] = 'ambiguo';
                $f['motivo'] = 'Varias filas del Excel emparejan con el mismo registro (id ' . $f['id'] . ').';
            }
        }
        unset($f);

        return $filas;
    }

    private function idsUsados(array $filas): array
    {
        $usados = [];

        foreach ($filas as $f) {
            if ($f['id'] !== null) {
                $usados[$f['id']] = true;
            }
        }

        return $usados;
    }

    /** Codigo numerico a 2 digitos, como lo emite el generador del DTE. */
    public static function formatearCodigo(string $codigo): string
    {
        $clave = CatalogoDteImportador::claveCodigo($codigo);

        return ctype_digit($clave) ? str_pad($clave, 2, '0', STR_PAD_LEFT) : $codigo;
    }

    private function nombresDepartamento(): array
    {
        return Departamentos::query()->pluck('departamento', 'id')->all();
    }

    private function filaVacia(string $cod, string $nombre): array
    {
        return [
            'accion' => null,
            'departamento' => null,
            'municipio' => null,
            'codigo' => $cod,
            'nombre' => $nombre,
            'id' => null,
            'codigoActual' => null,
            'nombreActual' => null,
            'codigoNuevo' => null,
            'nombreNuevo' => null,
            'emparejado' => null,
            'motivo' => null,
        ];
    }

    private function conActual(array $fila, $id, $codigo, $nombre): array
    {
        $fila['id'] = (int) $id;
        $fila['codigoActual'] = $codigo;
        $fila['nombreActual'] = $nombre;

        return $fila;
    }

    private function noEnExcel($id, $codigo, $nombre, ?string $depto, ?string $municipio): array
    {
        $fila = $this->conActual($this->filaVacia('', ''), $id, $codigo, $nombre);
        $fila['codigo'] = null;
        $fila['nombre'] = null;
        $fila['accion'] = 'no_en_excel';
        $fila['departamento'] = $depto;
        $fila['municipio'] = $municipio;
        $fila['motivo'] = 'No aparece en el Excel. Solo se reporta; no se desactiva.';

        return $fila;
    }

    private function plan(array $filas, ?string $error): array
    {
        $conteo = ['excel' => 0, 'actualizar' => 0, 'iguales' => 0, 'sinEmparejar' => 0, 'ambiguos' => 0, 'noEnExcel' => 0];

        foreach ($filas as $f) {
            if ($f['accion'] !== 'no_en_excel') {
                $conteo['excel']++;
            }

            $clave = [
                'actualizar' => 'actualizar',
                'igual' => 'iguales',
                'sin_emparejar' => 'sinEmparejar',
                'ambiguo' => 'ambiguos',
                'no_en_excel' => 'noEnExcel',
            ][$f['accion']];

            $conteo[$clave]++;
        }

        if ($error !== null) {
            $conteo['actualizar'] = 0;
        }

        return ['error' => $error, 'filas' => $filas, 'conteo' => $conteo];
    }

    private function escribir(string $catalogo, array $fila): void
    {
        $modelo = [
            self::DEPARTAMENTOS => [Departamentos::class, 'departamento'],
            self::MUNICIPIOS => [Municipios::class, 'municipio'],
            self::DISTRITOS => [Distritos::class, 'distrito'],
        ][$catalogo];

        [$clase, $campoNombre] = $modelo;
        $registro = $clase::query()->find($fila['id']);

        if (!$registro) {
            return;
        }

        // Solo codigo y nombre: el id, el padre y el status no se tocan.
        $registro->codigo = $fila['codigoNuevo'];
        $registro->{$campoNombre} = mb_substr((string) $fila['nombreNuevo'], 0, 100);
        $registro->save();
    }
}
