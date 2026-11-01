<?php

namespace App\Services;

use App\Models\Clientes;
use App\Models\dte;
use App\Models\EsquemaDte;
use App\Models\Parametros;
use App\Models\resumenDte;
use App\Models\SujetoExcluido;
use App\Models\Sucursales;
use App\Models\Ventas;
use RuntimeException;

/**
 * Generador de la Normativa DTE 2.0 para comercialcruz.
 *
 * No recalcula montos: arma el JSON con el generador actual (GeneraJsonF/C/Nota/S),
 * que conserva las reglas propias de este sistema (descuento por caja, retencion,
 * codigos MH), y lo pasa a la estructura de los esquemas publicados por Hacienda
 * el 27-07-2026: Factura v2, CCF v4, Nota de Credito v4 y Sujeto Excluido v2.
 */
class GeneradorDteV2Service
{
    /** Version por tipo cuando no hay esquema activo en esquema_dtes. */
    public const VERSIONES = [
        '01' => 2,
        '03' => 4,
        '05' => 4,
        '14' => 2,
    ];

    private const FLAGS_JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * Mientras construir() llama a los generadores actuales, el interruptor no
     * aplica: asi se obtiene siempre el JSON actual para transformarlo.
     */
    private static bool $omitirInterruptor = false;

    /**
     * Interruptor por tipo (DTE_GENERADOR_V2=01,03 en .env). Apagado por defecto:
     * si el tipo no esta en la lista, los generadores actuales no cambian nada.
     */
    public static function activo(string $tipo): bool
    {
        if (self::$omitirInterruptor) {
            return false;
        }

        $tipos = array_map(
            fn ($t) => str_pad(trim($t), 2, '0', STR_PAD_LEFT),
            explode(',', (string) config('dte.generador_v2', ''))
        );

        return isset(self::VERSIONES[$tipo]) && in_array($tipo, $tipos, true);
    }

    /**
     * Version para el sobre de recepcion. Con el interruptor apagado devuelve la
     * de siempre; encendido, la del documento firmado (asi coincide aunque la
     * firma se haya hecho antes de encender o apagar el interruptor).
     */
    public static function versionEnvio(string $tipo, $firma, int $actual): int
    {
        if (!self::activo($tipo)) {
            return $actual;
        }

        $partes = explode('.', (string) $firma);
        if (count($partes) === 3) {
            $payload = json_decode(base64_decode(strtr($partes[1], '-_', '+/')), true);
            if (isset($payload['identificacion']['version'])) {
                return (int) $payload['identificacion']['version'];
            }
        }

        return $actual;
    }

    /**
     * Lo llaman los generadores actuales cuando el interruptor esta encendido:
     * recibe el JSON que acaban de armar, lo pasa a 2.0, lo guarda y lo devuelve.
     */
    public function desdeActual(int $dteId, string $tipo, string $jsonActual): string
    {
        $dte = dte::findOrFail($dteId);
        $actual = json_decode($jsonActual, true);
        if (!is_array($actual)) {
            throw new RuntimeException("El generador actual no devolvio un JSON valido para el DTE {$dteId}.");
        }

        $v2 = $this->transformar($dte, $tipo, $actual);
        $json = json_encode($v2, self::FLAGS_JSON | JSON_THROW_ON_ERROR);

        $dte->version = $v2['identificacion']['version'];
        $dte->jsonDte = $json;
        $dte->save();

        return $json;
    }

    /**
     * Arma el JSON actual y el 2.0 de un DTE. OJO: los generadores actuales de
     * factura, CCF y nota guardan su jsonDte; quien solo quiera comparar debe
     * llamar esto dentro de una transaccion y revertirla.
     *
     * @return array{tipo: string, actual: array, v2: array}
     */
    public function construir(int $dteId): array
    {
        $dte = dte::with('RtipoDte:id,codigo')->findOrFail($dteId);
        $tipo = str_pad((string) $dte->RtipoDte?->codigo, 2, '0', STR_PAD_LEFT);

        if (!isset(self::VERSIONES[$tipo])) {
            throw new RuntimeException("El DTE {$dteId} es tipo {$tipo}; el generador 2.0 solo soporta 01, 03, 05 y 14.");
        }

        $actual = json_decode($this->jsonActual($dte, $tipo), true);
        if (!is_array($actual)) {
            throw new RuntimeException("El generador actual no devolvio un JSON valido para el DTE {$dteId}.");
        }

        return ['tipo' => $tipo, 'actual' => $actual, 'v2' => $this->transformar($dte, $tipo, $actual)];
    }

    private function transformar(dte $dte, string $tipo, array $actual): array
    {
        return match ($tipo) {
            '01' => $this->factura($dte, $actual),
            '03' => $this->creditoFiscal($dte, $actual),
            '05' => $this->notaCredito($dte, $actual),
            '14' => $this->sujetoExcluido($dte, $actual),
        };
    }

    /** Genera el JSON 2.0, lo guarda en el DTE y lo devuelve. */
    public function generar(int $dteId): string
    {
        $resultado = $this->construir($dteId);
        $json = json_encode($resultado['v2'], self::FLAGS_JSON | JSON_THROW_ON_ERROR);

        $dte = dte::findOrFail($dteId);
        $dte->version = $resultado['v2']['identificacion']['version'];
        $dte->jsonDte = $json;
        $dte->save();

        return $json;
    }

    /** Version vigente: la del esquema activo, o la de la normativa 2.0. */
    public function version(string $tipo): int
    {
        try {
            $version = EsquemaDte::versionVigente($tipo);
            if ($version !== null) {
                return (int) $version;
            }
        } catch (\Throwable $e) {
            // Sin tabla o sin conexion se usa la version de respaldo
        }

        return self::VERSIONES[$tipo];
    }

    private function jsonActual(dte $dte, string $tipo): string
    {
        $legacy = new GeneradorDteLegacy();
        self::$omitirInterruptor = true;

        try {
            return match ($tipo) {
                '01' => $legacy->GeneraJsonF($dte->id),
                '03' => $legacy->GeneraJsonC($dte->id),
                '05' => $legacy->GeneraJsonNota($dte->id),
                // El de sujeto excluido recibe el id de la operacion, no el del DTE
                '14' => $legacy->GeneraJsonS($dte->venta),
            };
        } finally {
            self::$omitirInterruptor = false;
        }
    }

    private function factura(dte $dte, array $a): array
    {
        $cliente = $this->clienteVenta($dte);
        $r = $a['receptor'] ?? [];
        $tipoDoc = $this->nullable($r['tipoDocumento'] ?? null);
        $s = $a['resumen'] ?? [];

        return [
            'identificacion' => $this->identificacion($a['identificacion'], '01'),
            'documentoRelacionado' => null,
            'emisor' => $this->emisor($dte, $a['emisor'], true, true),
            'receptor' => [
                'tipoDocumento' => $tipoDoc,
                'numDocumento' => $this->documento($tipoDoc, $r['numDocumento'] ?? null),
                'nrc' => $this->nullable($r['nrc'] ?? null),
                'nombre' => $this->nullable($r['nombre'] ?? null),
                'codActividad' => $this->nullable($r['codActividad'] ?? null),
                'descActividad' => $this->nullable($r['descActividad'] ?? null),
                'direccion' => $this->direccion($cliente, $r['direccion'] ?? null),
                'telefono' => $this->nullable($r['telefono'] ?? null),
                'correo' => $this->nullable($r['correo'] ?? null),
            ],
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => $this->items($a['cuerpoDocumento'] ?? [], [
                'numItem', 'tipoItem', 'numeroDocumento', 'cantidad', 'codigo', 'codTributo', 'uniMedida',
                'descripcion', 'precioUni', 'montoDescu', 'ventaNoSuj', 'ventaExenta', 'ventaGravada',
                'tributos', 'psv', 'noGravado', 'ivaItem',
            ]),
            'resumen' => [
                'totalNoSuj' => $this->dinero($s['totalNoSuj'] ?? 0),
                'totalExenta' => $this->dinero($s['totalExenta'] ?? 0),
                'totalGravada' => $this->dinero($s['totalGravada'] ?? 0),
                'subTotalVentas' => $this->dinero($s['subTotalVentas'] ?? 0),
                'descuNoSuj' => $this->dinero($s['descuNoSuj'] ?? 0),
                'descuExenta' => $this->dinero($s['descuExenta'] ?? 0),
                'descuGravada' => $this->dinero($s['descuGravada'] ?? 0),
                'porcentajeDescuento' => $this->dinero($s['porcentajeDescuento'] ?? 0),
                'totalDescu' => $this->dinero($s['totalDescu'] ?? 0),
                'tributos' => $s['tributos'] ?? null,
                'subTotal' => $this->dinero($s['subTotal'] ?? 0),
                'ivaRete' => $this->dinero($s['ivaRete1'] ?? 0),
                'montoTotalOperacion' => $this->dinero($s['montoTotalOperacion'] ?? 0),
                'totalNoGravado' => $this->dinero($s['totalNoGravado'] ?? 0),
                'totalPagar' => $this->dinero($s['totalPagar'] ?? 0),
                'totalLetras' => $this->nullable($s['totalLetras'] ?? null),
                'totalIva' => $this->dinero($s['totalIva'] ?? 0),
                'saldoFavor' => $this->dinero($s['saldoFavor'] ?? 0),
                'condicionOperacion' => (int) ($s['condicionOperacion'] ?? 1),
                'pagos' => $this->pagos($s['pagos'] ?? null),
                'numPagoElectronico' => $this->nullable($s['numPagoElectronico'] ?? null),
                'observaciones' => null,
            ],
            'apendice' => null,
        ];
    }

    private function creditoFiscal(dte $dte, array $a): array
    {
        $cliente = $this->clienteVenta($dte);
        $r = $a['receptor'] ?? [];
        $s = $a['resumen'] ?? [];

        return [
            'identificacion' => $this->identificacion($a['identificacion'], '03'),
            'documentoRelacionado' => null,
            'emisor' => $this->emisor($dte, $a['emisor'], true, true),
            'receptor' => [
                'nit' => $this->documento('36', $r['nit'] ?? null),
                'nrc' => $this->nullable($r['nrc'] ?? null),
                'nombre' => trim((string) ($r['nombre'] ?? '')),
                'codActividad' => $this->nullable($r['codActividad'] ?? null),
                'descActividad' => $this->nullable($r['descActividad'] ?? null),
                'nombreComercial' => $this->nullable($r['nombreComercial'] ?? null),
                'direccion' => $this->direccion($cliente, $r['direccion'] ?? null),
                'telefono' => $this->nullable($r['telefono'] ?? null),
                'correo' => $this->nullable($r['correo'] ?? null),
            ],
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => $this->items($a['cuerpoDocumento'] ?? [], [
                'numItem', 'tipoItem', 'numeroDocumento', 'codigo', 'codTributo', 'descripcion', 'cantidad',
                'uniMedida', 'precioUni', 'montoDescu', 'ventaNoSuj', 'ventaExenta', 'ventaGravada',
                'tributos', 'psv', 'noGravado',
            ]),
            'resumen' => [
                'totalNoSuj' => $this->dinero($s['totalNoSuj'] ?? 0),
                'totalExenta' => $this->dinero($s['totalExenta'] ?? 0),
                'totalGravada' => $this->dinero($s['totalGravada'] ?? 0),
                'subTotalVentas' => $this->dinero($s['subTotalVentas'] ?? 0),
                'descuNoSuj' => $this->dinero($s['descuNoSuj'] ?? 0),
                'descuExenta' => $this->dinero($s['descuExenta'] ?? 0),
                'descuGravada' => $this->dinero($s['descuGravada'] ?? 0),
                'porcentajeDescuento' => $this->dinero($s['porcentajeDescuento'] ?? 0),
                'totalDescu' => $this->dinero($s['totalDescu'] ?? 0),
                'tributos' => $this->tributos($s['tributos'] ?? null),
                'subTotal' => $this->dinero($s['subTotal'] ?? 0),
                'ivaPerci' => $this->dinero($s['ivaPerci1'] ?? 0),
                'ivaRete' => $this->dinero($s['ivaRete1'] ?? 0),
                'montoTotalOperacion' => $this->dinero($s['montoTotalOperacion'] ?? 0),
                'totalNoGravado' => $this->dinero($s['totalNoGravado'] ?? 0),
                'totalPagar' => $this->dinero($s['totalPagar'] ?? 0),
                'totalLetras' => $this->nullable($s['totalLetras'] ?? null),
                'saldoFavor' => $this->dinero($s['saldoFavor'] ?? 0),
                'condicionOperacion' => (int) ($s['condicionOperacion'] ?? 1),
                'pagos' => $this->pagos($s['pagos'] ?? null),
                'numPagoElectronico' => $this->nullable($s['numPagoElectronico'] ?? null),
                'observaciones' => null,
            ],
            'apendice' => null,
        ];
    }

    private function notaCredito(dte $dte, array $a): array
    {
        $cliente = $this->clienteVenta($dte);
        $r = $a['receptor'] ?? [];
        $s = $a['resumen'] ?? [];

        // La nota v4 identifica al receptor con tipo y numero, ya no con "nit"
        $tipoDoc = (int) ($cliente?->idenReceptor) === 2 ? '13' : '36';
        $numero = $tipoDoc === '13' ? ($cliente?->dui ?: ($r['nit'] ?? null)) : ($r['nit'] ?? $cliente?->nit);

        $items = [];
        foreach (array_values($a['cuerpoDocumento'] ?? []) as $i => $item) {
            $gravada = (float) ($item['ventaGravada'] ?? 0);
            $items[] = [
                'numItem' => $i + 1,
                'tipoItem' => (int) ($item['tipoItem'] ?? 1),
                'numeroDocumento' => strtoupper(trim((string) ($item['numeroDocumento'] ?? ''))),
                'cantidad' => $this->numero($item['cantidad'] ?? 0),
                'codigo' => $this->nullable($item['codigo'] ?? null),
                'codTributo' => $this->nullable($item['codTributo'] ?? null),
                'uniMedida' => (int) ($item['uniMedida'] ?? 59),
                'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                'precioUni' => $this->numero($item['precioUni'] ?? 0),
                'montoDescu' => $this->numero($item['montoDescu'] ?? 0),
                'ventaNoSuj' => $this->numero($item['ventaNoSuj'] ?? 0),
                'ventaExenta' => $this->numero($item['ventaExenta'] ?? 0),
                'ventaGravada' => $this->numero($gravada),
                'tributos' => $item['tributos'] ?? ['20'],
                'noGravado' => 0.0,
                'ivaPerci' => 0.0,
                // La v4 pide el IVA por item; la gravada de la nota va sin IVA
                'totalIva' => $this->numero($gravada * 0.13),
                'ivaRete' => 0.0,
            ];
        }

        $tributos = $this->tributos($s['tributos'] ?? null);
        $totalIva = $this->dinero($tributos[0]['valor'] ?? 0);
        $ivaPerci = $this->dinero($s['ivaPerci1'] ?? 0);
        $ivaRete = $this->dinero($s['ivaRete1'] ?? 0);
        $subTotal = $this->dinero($s['subTotal'] ?? ($s['subTotalVentas'] ?? 0));

        $identificacion = $this->identificacion($a['identificacion'], '05');
        $identificacion['fusion'] = null;

        $relacionados = [];
        foreach ($a['documentoRelacionado'] ?? [] as $rel) {
            $relacionados[] = [
                'tipoDocumento' => (string) $rel['tipoDocumento'],
                'tipoGeneracion' => (int) $rel['tipoGeneracion'],
                'numeroDocumento' => strtoupper(trim((string) $rel['numeroDocumento'])),
                'fechaEmision' => (string) $rel['fechaEmision'],
            ];
        }

        return [
            'identificacion' => $identificacion,
            'documentoRelacionado' => $relacionados,
            // La nota v4 no lleva codigos de establecimiento ni punto de venta
            'emisor' => $this->emisor($dte, $a['emisor'], true, false),
            'receptor' => [
                'tipoDocumento' => $tipoDoc,
                'numDocumento' => $this->documento($tipoDoc, $numero),
                'nrc' => $this->nullable($r['nrc'] ?? null),
                'nombre' => trim((string) ($r['nombre'] ?? '')),
                'codActividad' => $this->nullable($r['codActividad'] ?? null),
                'descActividad' => $this->nullable($r['descActividad'] ?? null),
                'nombreComercial' => $this->nullable($r['nombreComercial'] ?? null),
                'direccion' => $this->direccion($cliente, $r['direccion'] ?? null),
                'telefono' => $this->nullable($r['telefono'] ?? null),
                'correo' => $this->nullable($r['correo'] ?? null),
            ],
            'ventaTercero' => null,
            'cuerpoDocumento' => $items,
            'resumen' => [
                'totalNoSuj' => $this->dinero($s['totalNoSuj'] ?? 0),
                'totalExenta' => $this->dinero($s['totalExenta'] ?? 0),
                'totalGravada' => $this->dinero($s['totalGravada'] ?? 0),
                'subTotalVentas' => $this->dinero($s['subTotalVentas'] ?? 0),
                'totalDescu' => $this->dinero($s['totalDescu'] ?? 0),
                'tributos' => $tributos,
                // v4: operacion antes de retencion; el neto va en totalPagar
                'montoTotalOperacion' => $this->dinero($subTotal + $totalIva + $ivaPerci),
                'ivaPerci' => $ivaPerci,
                'totalIva' => $totalIva,
                'ivaRete' => $ivaRete,
                'totalNoGravado' => 0.0,
                'totalPagar' => $this->dinero($s['montoTotalOperacion'] ?? 0),
                'totalLetras' => $this->nullable($s['totalLetras'] ?? null),
                'condicionOperacion' => (int) ($s['condicionOperacion'] ?? 1),
                'observaciones' => null,
                'codigoRetencionMH' => null,
            ],
            'apendice' => null,
        ];
    }

    private function sujetoExcluido(dte $dte, array $a): array
    {
        $operacion = SujetoExcluido::find($dte->venta);
        $cliente = $operacion ? $this->cliente($operacion->cliente) : null;
        $r = $a['sujetoExcluido'] ?? ($a['receptor'] ?? []);
        $s = $a['resumen'] ?? [];
        $tipoDoc = (string) ($r['tipoDocumento'] ?? '13');

        return [
            'identificacion' => $this->identificacion($a['identificacion'], '14'),
            'emisor' => $this->emisor($dte, $a['emisor'], false, true),
            'receptor' => [
                'tipoDocumento' => $tipoDoc,
                'numDocumento' => $this->documento($tipoDoc, $r['numDocumento'] ?? null),
                'nombre' => trim((string) ($r['nombre'] ?? '')),
                'codActividad' => $this->nullable($r['codActividad'] ?? null),
                'descActividad' => $this->nullable($r['descActividad'] ?? null),
                'direccion' => $this->direccion($cliente, $r['direccion'] ?? null),
                'telefono' => $this->nullable($r['telefono'] ?? null),
                'correo' => $this->nullable($r['correo'] ?? null),
            ],
            'cuerpoDocumento' => $this->items($a['cuerpoDocumento'] ?? [], [
                'numItem', 'tipoItem', 'cantidad', 'codigo', 'uniMedida', 'descripcion',
                'precioUni', 'montoDescu', 'compra',
            ]),
            'resumen' => [
                'totalCompra' => $this->dinero($s['totalCompra'] ?? 0),
                'descu' => $this->dinero($s['descu'] ?? 0),
                'totalDescu' => $this->dinero($s['totalDescu'] ?? 0),
                'subTotal' => $this->dinero($s['subTotal'] ?? 0),
                'reteRenta' => $this->dinero($s['reteRenta'] ?? 0),
                'totalPagar' => $this->dinero($s['totalPagar'] ?? 0),
                'totalLetras' => $this->nullable($s['totalLetras'] ?? null),
                'condicionOperacion' => (int) ($s['condicionOperacion'] ?? 1),
                'pagos' => $this->pagos($s['pagos'] ?? null),
                'observaciones' => $this->nullable($s['observaciones'] ?? null),
            ],
            'apendice' => null,
        ];
    }

    private function identificacion(array $i, string $tipo): array
    {
        $contingencia = $i['tipoContingencia'] ?? null;
        $contingencia = ($contingencia === null || $contingencia === '') ? null : (int) $contingencia;

        return [
            'version' => $this->version($tipo),
            'ambiente' => (string) ($i['ambiente'] ?? ''),
            'tipoDte' => $tipo,
            'numeroControl' => strtoupper(trim((string) ($i['numeroControl'] ?? ''))),
            'codigoGeneracion' => strtoupper(trim((string) ($i['codigoGeneracion'] ?? ''))),
            'tipoModelo' => (int) ($i['tipoModelo'] ?? 1),
            'tipoOperacion' => (int) ($i['tipoOperacion'] ?? 1),
            'tipoContingencia' => $contingencia,
            // Sin contingencia el motivo debe ir nulo
            'motivoContin' => $contingencia === null ? null : $this->nullable($i['motivoContin'] ?? null),
            'fecEmi' => (string) ($i['fecEmi'] ?? ''),
            'horEmi' => (string) ($i['horEmi'] ?? ''),
            'tipoMoneda' => (string) ($i['tipoMoneda'] ?? 'USD'),
        ];
    }

    /**
     * Emisor 2.0: mismos datos que hoy, sin tipoEstablecimiento ni codigos MH.
     * codEstable/codPuntoVenta toman el codigo MH de la sucursal y de la caja;
     * si no hay, el numero de sucursal y de caja a 4 digitos.
     */
    private function emisor(dte $dte, array $e, bool $nombreComercial, bool $codigos): array
    {
        $sucursal = Sucursales::with(['Rdepartamento', 'Rmunicipio', 'Rdistrito'])->find($dte->sucursal);

        $resultado = [
            'nit' => trim((string) ($e['nit'] ?? '')),
            'nrc' => $this->nullable($e['nrc'] ?? null),
            'nombre' => trim((string) ($e['nombre'] ?? '')),
            'codActividad' => trim((string) ($e['codActividad'] ?? '')),
            'descActividad' => trim((string) ($e['descActividad'] ?? '')),
        ];
        if ($nombreComercial) {
            $resultado['nombreComercial'] = $this->nullable($e['nombreComercial'] ?? null);
        }
        $resultado['direccion'] = $this->direccion($sucursal, $e['direccion'] ?? null);
        $resultado['telefono'] = trim((string) ($e['telefono'] ?? ''));
        $resultado['correo'] = trim((string) ($e['correo'] ?? ''));

        if ($codigos) {
            $caja = Parametros::find($dte->caja);
            $resultado['codEstable'] = $this->nullable($sucursal?->codEstableMH)
                ?? ($sucursal?->numero !== null ? str_pad((string) $sucursal->numero, 4, '0', STR_PAD_LEFT) : null);
            $resultado['codPuntoVenta'] = $this->nullable($caja?->codPuntoVentaMH)
                ?? ($caja?->caja !== null ? str_pad((string) $caja->caja, 4, '0', STR_PAD_LEFT) : null);
        }

        return $resultado;
    }

    /** Direccion 2.0 (departamento, municipio, distrito, complemento). */
    private function direccion($modelo, ?array $actual): ?array
    {
        if (!$modelo && !$actual) {
            return null;
        }

        return [
            'departamento' => $this->codigoGeografico($modelo?->Rdepartamento?->codigo ?? ($actual['departamento'] ?? null)),
            'municipio' => $this->codigoGeografico($modelo?->Rmunicipio?->codigo),
            'distrito' => $this->codigoGeografico($modelo?->Rdistrito?->codigo ?? ($actual['municipio'] ?? null)),
            'complemento' => trim((string) ($actual['complemento'] ?? $modelo?->direccion ?? '')),
        ];
    }

    private function clienteVenta(dte $dte): ?Clientes
    {
        $clienteId = Ventas::whereKey($dte->venta)->value('cliente');

        return $clienteId ? $this->cliente($clienteId) : null;
    }

    private function cliente($id): ?Clientes
    {
        return Clientes::withTrashed()->with(['Rdepartamento', 'Rmunicipio', 'Rdistrito'])->find($id);
    }

    /** Copia cada item en el orden del esquema, numerado desde 1. */
    private function items(array $items, array $campos): array
    {
        $salida = [];
        foreach (array_values($items) as $i => $item) {
            $fila = [];
            foreach ($campos as $campo) {
                $valor = $item[$campo] ?? null;
                $fila[$campo] = match ($campo) {
                    'numItem' => $i + 1,
                    'tipoItem' => (int) ($valor ?? 1),
                    'uniMedida' => (int) ($valor ?? 59),
                    'numeroDocumento', 'codigo', 'codTributo' => $this->nullable($valor),
                    'descripcion' => trim((string) $valor),
                    'tributos' => $valor,
                    default => $this->numero($valor ?? 0),
                };
            }
            $salida[] = $fila;
        }

        return $salida;
    }

    private function tributos($tributos): ?array
    {
        if (!is_array($tributos) || $tributos === []) {
            return null;
        }

        return array_map(fn ($t) => [
            'codigo' => (string) ($t['codigo'] ?? '20'),
            'descripcion' => (string) ($t['descripcion'] ?? 'Impuesto al Valor Agregado 13%'),
            'valor' => $this->dinero($t['valor'] ?? 0),
        ], array_values($tributos));
    }

    private function pagos($pagos): ?array
    {
        if (!is_array($pagos) || $pagos === []) {
            return null;
        }

        return array_map(fn ($p) => [
            'codigo' => (string) ($p['codigo'] ?? '01'),
            'montoPago' => $this->dinero($p['montoPago'] ?? 0),
            'referencia' => $this->nullable($p['referencia'] ?? null),
            'plazo' => $this->nullable($p['plazo'] ?? null),
            'periodo' => isset($p['periodo']) ? (int) $p['periodo'] : null,
        ], array_values($pagos));
    }

    /** DUI (13) y NIT (36) viajan solo con digitos en la 2.0; el resto tal cual. */
    private function documento(?string $tipo, $numero): ?string
    {
        $numero = $this->nullable($numero);
        if ($numero === null || !in_array($tipo, ['13', '36'], true)) {
            return $numero;
        }

        $digitos = preg_replace('/\D+/', '', $numero);

        return $digitos !== '' ? $digitos : $numero;
    }

    private function codigoGeografico($codigo): string
    {
        $codigo = trim((string) $codigo);

        return $codigo === '' ? '00' : (ctype_digit($codigo) ? str_pad($codigo, 2, '0', STR_PAD_LEFT) : $codigo);
    }

    private function nullable($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    /** Montos del resumen: el esquema exige multiplos de 0.01. */
    private function dinero($valor): float
    {
        return round((float) $valor, 2);
    }

    /** Montos por item: el esquema exige multiplos de 0.00000001. */
    private function numero($valor): float
    {
        return round((float) $valor, 8);
    }
}
