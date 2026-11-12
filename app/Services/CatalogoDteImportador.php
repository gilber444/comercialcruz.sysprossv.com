<?php

namespace App\Services;

use App\Models\ActividadEconomica;
use App\Models\AmbienteDestino;
use App\Models\catalagos;
use App\Models\catalagosEstructura;
use App\Models\CondicionOperacion;
use App\Models\DocumentosAsociados;
use App\Models\DomicilioFiscal;
use App\Models\Donacion;
use App\Models\FormaPagos;
use App\Models\IdentificacionReceptor;
use App\Models\Incoterms;
use App\Models\ModeloFacturacion;
use App\Models\Pais;
use App\Models\Plazo;
use App\Models\RecintoFiscal;
use App\Models\Regimen;
use App\Models\Retencion;
use App\Models\ServicioMedico;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\TipoEstablecimiento;
use App\Models\TipoGeneracionDocumento;
use App\Models\TipoInvalidacion;
use App\Models\TipoItem;
use App\Models\TipoPersona;
use App\Models\TipoTransmision;
use App\Models\TituloRemiteBienes;
use App\Models\Transporte;
use App\Models\Tributo;
use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Compara el Excel de Hacienda contra las tablas del sistema y aplica los cambios.
 *
 * Reglas que NO se negocian:
 *  - El match es por "codigo" y el update es in place. Nunca se borra ni se
 *    reinserta: los DTE historicos referencian estas tablas por "id", asi que
 *    reemplazar filas corromperia el historico (reimpresiones, invalidaciones,
 *    libros de IVA).
 *  - Un codigo que Hacienda retira se marca 'Desactivado', no se elimina.
 *  - Las filas SIN codigo son internas del sistema (el tributo y la contingencia
 *    comodin que la facturacion busca con whereNull('codigo')). Hacienda no las
 *    publica nunca, asi que quedan fuera del match y fuera del retiro.
 *  - El match ignora los ceros a la izquierda: si la base tiene "1" y Hacienda
 *    publica "01" es el mismo registro. Comparar el texto crudo insertaba un
 *    duplicado y ademas desactivaba el original por "no publicado".
 *
 * Departamentos, Municipios y Distritos (CAT-012, CAT-013, CAT-008) NO siguen
 * estas reglas de codigo: los delega a CatalogoTerritorialImportador, que empata
 * por nombre dentro del departamento deducido y solo actualiza en sitio.
 */
class CatalogoDteImportador
{
    /** Catalogos territoriales: se analizan y aplican con su propio importador. */
    public const TERRITORIALES = CatalogoTerritorialImportador::CATALOGOS;

    /** Catalogos con tabla propia. El resto cae al mecanismo generico. */
    public const MAPA = [
        'CAT-001' => AmbienteDestino::class,
        'CAT-002' => TipoDocumento::class,
        'CAT-003' => ModeloFacturacion::class,
        'CAT-004' => TipoTransmision::class,
        'CAT-005' => TipoContigencia::class,
        'CAT-006' => Retencion::class,
        'CAT-007' => TipoGeneracionDocumento::class,
        'CAT-009' => TipoEstablecimiento::class,
        'CAT-010' => ServicioMedico::class,
        'CAT-011' => TipoItem::class,
        'CAT-014' => UnidadMedida::class,
        'CAT-015' => Tributo::class,
        'CAT-016' => CondicionOperacion::class,
        'CAT-017' => FormaPagos::class,
        'CAT-018' => Plazo::class,
        'CAT-019' => ActividadEconomica::class,
        'CAT-020' => Pais::class,
        'CAT-021' => DocumentosAsociados::class,
        'CAT-022' => IdentificacionReceptor::class,
        'CAT-024' => TipoInvalidacion::class,
        'CAT-025' => TituloRemiteBienes::class,
        'CAT-026' => Donacion::class,
        'CAT-027' => RecintoFiscal::class,
        'CAT-028' => Regimen::class,
        'CAT-029' => TipoPersona::class,
        'CAT-030' => Transporte::class,
        'CAT-031' => Incoterms::class,
        'CAT-032' => DomicilioFiscal::class,
    ];

    /** Columnas que el importador lee y escribe en cada tabla del MAPA. */
    private const COLUMNAS = ['codigo', 'valor', 'status'];

    private CatalogoTerritorialImportador $territorial;

    public function __construct(?CatalogoTerritorialImportador $territorial = null)
    {
        $this->territorial = $territorial ?? new CatalogoTerritorialImportador();
    }

    public static function esTerritorial(string $codigo): bool
    {
        return isset(self::TERRITORIALES[$codigo]);
    }

    /**
     * Resumen por catalogo, sin tocar la base.
     */
    public function comparar(array $catalogos): array
    {
        $territoriales = $this->territorial->analizar($catalogos);
        $resumen = [];

        foreach ($catalogos as $codigo => $cat) {
            $resumen[] = self::esTerritorial($codigo)
                ? $this->resumenTerritorial($codigo, $cat, $territoriales[$codigo])
                : $this->compararUno($codigo, $cat);
        }

        return $resumen;
    }

    /**
     * Detalle fila por fila de UN catalogo, para revisar antes de aplicar.
     * Recibe todos los catalogos del archivo porque los territoriales dependen
     * del CAT-012 para deducir el departamento.
     */
    public function detalle(string $codigo, array $catalogos): array
    {
        if (!isset($catalogos[$codigo])) {
            return [];
        }

        if (self::esTerritorial($codigo)) {
            $plan = $this->territorial->analizar($catalogos)[$codigo];

            return array_values(array_filter($plan['filas'], fn ($f) => $f['accion'] !== 'igual'));
        }

        $cat = $catalogos[$codigo];

        if (isset(self::MAPA[$codigo]) && $this->columnasFaltantes(self::MAPA[$codigo]) !== []) {
            return [];
        }

        $actuales = $this->actuales($codigo);
        $publicados = $this->publicados($cat['registros']);
        $filas = [];

        foreach ($cat['registros'] as $cod => $valor) {
            $clave = self::claveCodigo($cod);

            if ($clave === '') {
                continue;
            }

            $previo = $actuales[$clave] ?? null;

            if ($previo === null) {
                $filas[] = ['accion' => 'nuevo', 'codigo' => (string) $cod, 'antes' => null, 'despues' => $valor];
            } elseif ($this->cambiaria($previo, $valor)) {
                $antes = $previo['valor'];

                // Misma regla que aplicar(): si Hacienda lo publica y la fila
                // esta apagada o borrada logicamente, se reactiva.
                if ($previo['borrado']) {
                    $antes .= ' (borrado: se restaura)';
                } elseif (!$previo['activo']) {
                    $antes .= ' (desactivado: se reactiva)';
                }

                $filas[] = ['accion' => 'actualizar', 'codigo' => (string) $cod, 'antes' => $antes, 'despues' => $valor];
            }
        }

        foreach ($actuales as $clave => $previo) {
            // Solo se retira lo que sigue activo; lo ya desactivado no cambia.
            if (!array_key_exists($clave, $publicados) && $previo['activo']) {
                $filas[] = ['accion' => 'retirar', 'codigo' => (string) $previo['codigo'], 'antes' => $previo['valor'], 'despues' => null];
            }
        }

        return $filas;
    }

    /**
     * Aplica solo los catalogos indicados, todo dentro de una transaccion.
     */
    public function aplicar(array $catalogos, array $seleccion): array
    {
        $aplicado = [];

        DB::transaction(function () use ($catalogos, $seleccion, &$aplicado) {
            foreach ($seleccion as $codigo) {
                if (!isset($catalogos[$codigo]) || self::esTerritorial($codigo)) {
                    continue;
                }

                if (isset(self::MAPA[$codigo])) {
                    $modelo = self::MAPA[$codigo];
                    $faltan = $this->columnasFaltantes($modelo);

                    // Una tabla con otra estructura se salta y se informa; no
                    // impide actualizar el resto de catalogos.
                    if ($faltan !== []) {
                        $aplicado[$codigo] = [
                            'error' => 'La tabla ' . (new $modelo)->getTable()
                                . ' no tiene la(s) columna(s) ' . implode(', ', $faltan) . '; no se actualizo.',
                        ];
                        continue;
                    }

                    $aplicado[$codigo] = $this->aplicarTabla($codigo, $catalogos[$codigo]);
                } else {
                    $aplicado[$codigo] = $this->aplicarGenerico($codigo, $catalogos[$codigo]);
                }
            }

            $territoriales = array_values(array_filter($seleccion, fn ($c) => self::esTerritorial((string) $c)));

            if ($territoriales !== []) {
                $aplicado += $this->territorial->aplicar($catalogos, $territoriales);
            }
        });

        return $aplicado;
    }

    // ---------------------------------------------------------------- privados

    private function resumenTerritorial(string $codigo, array $cat, array $plan): array
    {
        $c = $plan['conteo'];

        return [
            'codigo' => $codigo,
            'nombre' => $cat['nombre'],
            'total' => count($cat['filas'] ?? $cat['registros']),
            'descartadas' => $cat['descartadas'],
            // En territoriales los codigos repetidos entre departamentos son
            // normales; no se reportan como anomalia.
            'duplicados' => 0,
            'nuevos' => 0,
            'actualizar' => $c['actualizar'],
            'iguales' => $c['iguales'],
            'retirar' => 0,
            'sinEmparejar' => $c['sinEmparejar'],
            'ambiguos' => $c['ambiguos'],
            'noEnExcel' => $c['noEnExcel'],
            'error' => $plan['error'],
            'tipo' => 'territorial',
            'destino' => self::TERRITORIALES[$codigo] . ' (por nombre, sin insertar ni desactivar)',
            'catalogoNuevo' => false,
        ];
    }

    private function compararUno(string $codigo, array $cat): array
    {
        $base = [
            'codigo' => $codigo,
            'nombre' => $cat['nombre'],
            'total' => count($cat['registros']),
            'descartadas' => $cat['descartadas'],
            'duplicados' => $cat['duplicados'],
            'nuevos' => 0,
            'actualizar' => 0,
            'iguales' => 0,
            'retirar' => 0,
            'sinEmparejar' => 0,
            'ambiguos' => 0,
            'noEnExcel' => 0,
            'error' => null,
        ];

        if (isset(self::MAPA[$codigo])) {
            $tipo = 'tabla';
            $destino = class_basename(self::MAPA[$codigo]);
            $nuevo = false;

            $faltan = $this->columnasFaltantes(self::MAPA[$codigo]);

            if ($faltan !== []) {
                return array_merge($base, [
                    'error' => 'La tabla no tiene la(s) columna(s) ' . implode(', ', $faltan) . '.',
                    'tipo' => $tipo,
                    'destino' => $destino,
                    'catalogoNuevo' => false,
                ]);
            }
        } else {
            $tipo = 'generico';
            $destino = 'catalagos_estructuras';
            $nuevo = !catalagos::where('codigo', $codigo)->exists();
        }

        $actuales = $this->actuales($codigo);
        $publicados = $this->publicados($cat['registros']);

        // Los conteos deben coincidir con lo que hara aplicar(): reactivar o
        // restaurar cuenta como actualizar, y solo se retira lo que sigue activo.
        // Si no, la vista previa promete cambios que nunca ocurren (y al reves).
        foreach ($publicados as $clave => $valor) {
            if (!array_key_exists($clave, $actuales)) {
                $base['nuevos']++;
            } elseif ($this->cambiaria($actuales[$clave], $valor)) {
                $base['actualizar']++;
            } else {
                $base['iguales']++;
            }
        }

        foreach ($actuales as $clave => $previo) {
            if (!array_key_exists($clave, $publicados) && $previo['activo']) {
                $base['retirar']++;
            }
        }

        return $base + [
            'tipo' => $tipo,
            'destino' => $destino,
            'catalogoNuevo' => $nuevo,
        ];
    }

    /**
     * El MAPA asume columnas codigo/valor/status. Si alguna tabla del destino
     * difiere, el catalogo se reporta y no se toca.
     */
    private function columnasFaltantes(string $modelo): array
    {
        $tabla = (new $modelo)->getTable();

        if (!Schema::hasTable($tabla)) {
            return ['(la tabla ' . $tabla . ' no existe)'];
        }

        return array_values(array_filter(self::COLUMNAS, fn ($c) => !Schema::hasColumn($tabla, $c)));
    }

    /**
     * Las 28 tablas usan SoftDeletes. Hay que incluir las borradas logicamente o
     * el importador insertaria un duplicado del mismo codigo.
     */
    private function consulta(string $modelo)
    {
        $query = $modelo::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelo), true)) {
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * Clave de comparacion de un codigo de catalogo.
     *
     * Quita los ceros a la izquierda para que "01", "1" y "001" sean el mismo
     * registro. Devuelve cadena vacia para las filas internas sin codigo, que
     * quedan excluidas tanto del match como del retiro.
     */
    /**
     * Inserta una fila con solo las columnas que existen en la tabla real. Los
     * modelos agregan sincro_id al crear, pero no todas las tablas de catalogo
     * tienen esa columna (p. ej. pais): con create() la insercion fallaba y la
     * transaccion revertia toda la importacion.
     */
    private function insertarFila(string $modelo, array $datos): void
    {
        $tabla = (new $modelo())->getTable();
        $columnas = $this->columnasTabla[$tabla] ??= Schema::getColumnListing($tabla);

        if (in_array('sincro_id', $columnas, true) && empty($datos['sincro_id'])) {
            $datos['sincro_id'] = (string) \Illuminate\Support\Str::uuid();
        }
        foreach (['created_at', 'updated_at'] as $fecha) {
            if (in_array($fecha, $columnas, true)) {
                $datos[$fecha] = now();
            }
        }

        DB::table($tabla)->insert(array_intersect_key($datos, array_flip($columnas)));
    }

    /** @var array<string, array<int, string>> columnas por tabla (memo por corrida) */
    private array $columnasTabla = [];

    public static function claveCodigo($codigo): string
    {
        $texto = trim((string) $codigo);

        if ($texto === '') {
            return '';
        }

        $sinCeros = ltrim($texto, '0');

        return $sinCeros === '' ? '0' : $sinCeros;
    }

    /**
     * Estado actual de la base, indexado por codigo normalizado.
     *
     * @return array<string,array{codigo:string,valor:string,activo:bool,borrado:bool}>
     */
    private function actuales(string $codigo): array
    {
        if (isset(self::MAPA[$codigo])) {
            $modelo = self::MAPA[$codigo];
            $columnas = ['id', 'codigo', 'valor', 'status'];

            if (in_array(SoftDeletes::class, class_uses_recursive($modelo), true)) {
                $columnas[] = (new $modelo)->getDeletedAtColumn();
            }

            $filas = $this->consulta($modelo)->get($columnas);

            return $this->indexarEstado($filas, 'valor', 'status');
        }

        $catalogo = catalagos::where('codigo', $codigo)->first();

        if (!$catalogo) {
            return [];
        }

        $filas = catalagosEstructura::where('catalago', $catalogo->id)->get(['id', 'codigo', 'valores', 'estado']);

        return $this->indexarEstado($filas, 'valores', 'estado');
    }

    /**
     * Mismo criterio que aplicarTabla()/aplicarGenerico(): ante codigos
     * repetidos manda la fila de id mas antiguo.
     */
    private function indexarEstado($filas, string $campoValor, string $campoEstado): array
    {
        $indice = [];

        foreach ($this->indexarFilas($filas, 'codigo') as $clave => $fila) {
            $indice[$clave] = [
                'codigo' => (string) $fila->codigo,
                'valor' => (string) $fila->{$campoValor},
                'activo' => $fila->{$campoEstado} === 'Activo',
                'borrado' => method_exists($fila, 'trashed') && $fila->trashed(),
            ];
        }

        return $indice;
    }

    /** true si aplicar() modificaria la fila existente para este valor publicado. */
    private function cambiaria(array $previo, string $valor): bool
    {
        return $previo['borrado'] || !$previo['activo'] || trim($previo['valor']) !== trim($valor);
    }

    /**
     * Filas de la base indexadas por codigo normalizado. Si hay duplicados se
     * conserva el primero -el id mas antiguo-, que es el que referencian los DTE
     * historicos. Limpiar el sobrante es tarea de catalogos:fusionar.
     *
     * @return array<string,object>
     */
    private function indexarFilas($filas, string $campoCodigo): array
    {
        $indice = [];

        foreach ($filas->sortBy('id') as $fila) {
            $clave = self::claveCodigo($fila->{$campoCodigo});

            if ($clave === '' || array_key_exists($clave, $indice)) {
                continue;
            }

            $indice[$clave] = $fila;
        }

        return $indice;
    }

    /**
     * Devuelve el codigo tal como lo publica Hacienda, con sus ceros a la
     * izquierda, para no crear la fila nueva con el formato normalizado.
     */
    private function codigoOriginal(array $registros, string $clave): string
    {
        foreach ($registros as $cod => $valor) {
            if (self::claveCodigo($cod) === $clave) {
                return (string) $cod;
            }
        }

        return $clave;
    }

    /**
     * Los registros del Excel, reindexados con la misma clave que la base.
     *
     * Si el mismo catalogo trajera "01" y "1", la normalizacion los vuelve el
     * mismo registro y uno pisaria al otro sin dejar rastro. Ante la ambiguedad
     * se detiene y lo dice.
     *
     * @return array<string,string>
     */
    private function publicados(array $registros): array
    {
        $indice = [];
        $origen = [];

        foreach ($registros as $cod => $valor) {
            $clave = self::claveCodigo($cod);

            if ($clave === '') {
                continue;
            }

            if (isset($origen[$clave]) && (string) $origen[$clave] !== (string) $cod) {
                throw new RuntimeException(
                    'El archivo trae los codigos "' . $origen[$clave] . '" y "' . $cod . '" en el mismo '
                    . 'catalogo, y el sistema los considera el mismo registro. No se puede decidir cual '
                    . 'aplicar: revise el Excel.'
                );
            }

            $origen[$clave] = $cod;
            $indice[$clave] = (string) $valor;
        }

        return $indice;
    }

    private function aplicarTabla(string $codigo, array $cat): array
    {
        $modelo = self::MAPA[$codigo];
        $conteo = ['nuevos' => 0, 'actualizados' => 0, 'retirados' => 0, 'conservados' => 0];

        $filas = $this->consulta($modelo)->get();
        $indice = $this->indexarFilas($filas, 'codigo');
        $publicados = $this->publicados($cat['registros']);

        foreach ($publicados as $clave => $valor) {
            $fila = $indice[$clave] ?? null;

            if (!$fila) {
                $this->insertarFila($modelo, [
                    'codigo' => $this->codigoOriginal($cat['registros'], $clave),
                    'valor' => $valor,
                    'status' => 'Activo',
                ]);
                $conteo['nuevos']++;
                continue;
            }

            $cambio = false;

            // Codigo que existe pero fue borrado logicamente: Hacienda lo sigue
            // publicando, asi que se restaura en vez de duplicarlo.
            if (method_exists($fila, 'trashed') && $fila->trashed()) {
                $fila->restore();
                $cambio = true;
            }

            // Hacienda lo sigue publicando: si una pasada anterior lo apago, se
            // reactiva en vez de dejarlo caido.
            if ($fila->status !== 'Activo') {
                $fila->status = 'Activo';
                $cambio = true;
            }

            if (trim((string) $fila->valor) !== trim($valor)) {
                $fila->valor = $valor;
                $cambio = true;
            }

            if ($cambio) {
                $fila->save();
                $conteo['actualizados']++;
            }
        }

        foreach ($filas as $fila) {
            $clave = self::claveCodigo($fila->codigo);

            // Fila interna sin codigo: la facturacion la busca con
            // whereNull('codigo') y Hacienda no la publica. Desactivarla dejaba
            // el POS con "Attempt to read property id on null".
            if ($clave === '') {
                $conteo['conservados']++;
                continue;
            }

            if (!array_key_exists($clave, $publicados) && $fila->status === 'Activo') {
                $fila->status = 'Desactivado';
                $fila->save();
                $conteo['retirados']++;
            }
        }

        return $conteo;
    }

    private function aplicarGenerico(string $codigo, array $cat): array
    {
        $conteo = ['nuevos' => 0, 'actualizados' => 0, 'retirados' => 0, 'conservados' => 0];

        $catalogo = catalagos::where('codigo', $codigo)->first();
        if (!$catalogo) {
            $this->insertarFila(catalagos::class, [
                'codigo' => $codigo,
                'catalago' => mb_substr($cat['nombre'], 0, 50),
                'descripcion' => mb_substr($cat['nombre'], 0, 300),
            ]);
            $catalogo = catalagos::where('codigo', $codigo)->firstOrFail();
        }

        $filas = catalagosEstructura::where('catalago', $catalogo->id)->get();
        $indice = $this->indexarFilas($filas, 'codigo');
        $publicados = $this->publicados($cat['registros']);

        foreach ($publicados as $clave => $valor) {
            $fila = $indice[$clave] ?? null;

            if (!$fila) {
                $this->insertarFila(catalagosEstructura::class, [
                    'catalago' => $catalogo->id,
                    'codigo' => mb_substr($this->codigoOriginal($cat['registros'], $clave), 0, 10),
                    'valores' => mb_substr($valor, 0, 255),
                    'dependencia' => null,
                    'estado' => 'Activo',
                ]);
                $conteo['nuevos']++;
                continue;
            }

            $cambio = false;

            if ($fila->estado !== 'Activo') {
                $fila->estado = 'Activo';
                $cambio = true;
            }

            if (trim((string) $fila->valores) !== trim($valor)) {
                $fila->valores = mb_substr($valor, 0, 255);
                $cambio = true;
            }

            if ($cambio) {
                $fila->save();
                $conteo['actualizados']++;
            }
        }

        foreach ($filas as $fila) {
            $clave = self::claveCodigo($fila->codigo);

            if ($clave === '') {
                $conteo['conservados']++;
                continue;
            }

            if (!array_key_exists($clave, $publicados) && $fila->estado === 'Activo') {
                $fila->estado = 'Desactivado';
                $fila->save();
                $conteo['retirados']++;
            }
        }

        return $conteo;
    }
}
