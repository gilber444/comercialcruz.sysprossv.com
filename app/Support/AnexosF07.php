<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Construccion de las filas de los anexos del F07 (CSV que se carga en el
 * portal de Hacienda). Clase pura, sin base de datos: recibe las filas ya
 * consultadas por App\Services\LibrosIvaService y devuelve texto.
 *
 * Fuente: "Manual de usuario para carga de archivo de los anexos de la
 * declaracion del IVA, F-07 V14 ENERO 2025" (700-DGII-MN-2021-26031).
 *
 * Reglas generales del manual que se aplican aqui:
 *  - separador punto y coma (;), SIN encabezados, sin celdas combinadas;
 *  - fechas DD/MM/AAAA dentro del mes declarado;
 *  - montos con punto decimal, dos decimales, sin separador de miles ni
 *    simbolo de moneda, y 0.00 cuando no hay operacion (nunca celda vacia);
 *  - la ultima columna es el numero de anexo;
 *  - NIT/NRC/DUI sin guiones;
 *  - DTE: codigo de generacion sin guiones desde el periodo noviembre 2022,
 *    numero de control sin guiones para los anteriores.
 *
 * Los montos se redondean a 2 decimales con redondeo medio hacia arriba
 * (App\Support\Decimal) y los totales suman los valores ya redondeados.
 */
final class AnexosF07
{
    /** Primer periodo en el que el manual pide el codigo de generacion. */
    public const PERIODO_CODIGO_GENERACION = '2022-11';

    /** Columnas de Renta de los anexos 1 y 2 (R,S / U,V): desde enero 2025. */
    public const PERIODO_RENTA_VENTAS = '2025-01';

    /** Columnas de Renta del anexo 3 (Q,R,S,T): desde febrero 2024. */
    public const PERIODO_RENTA_COMPRAS = '2024-02';

    /**
     * Codigos de Renta por defecto (manual F-07 V14). El negocio es un
     * comercio: venta gravada de actividades comerciales y compras gravadas
     * de mercaderia local.
     */
    public const RENTA_VENTA_TIPO_OPERACION = '1';   // 1 Gravada
    public const RENTA_VENTA_TIPO_INGRESO = '3';     // 3 Actividades Comerciales
    public const RENTA_COMPRA_TIPO_OPERACION = '1';  // 1 Gravada
    public const RENTA_COMPRA_CLASIFICACION = '1';   // 1 Costo
    public const RENTA_COMPRA_SECTOR = '2';          // 2 Comercio
    public const RENTA_COMPRA_TIPO_COSTO = '5';      // 5 Costo articulos producidos/comprados interno

    /**
     * Tipo de documento del anexo 7. El manual (seccion IX, literal C) solo
     * admite 05 Nota de credito, 06 Nota de debito y 07 Comprobante de
     * retencion: un '03' lo rechaza el portal. La retencion del cliente GRANDE
     * queda documentada en el CCF/factura que emite la empresa (ivaRete1),
     * asi que se informa como 07 con el sello y el codigo de generacion de ese
     * DTE. Validarlo con el contador antes de declarar.
     */
    public const ANEXO7_TIPO_DOCUMENTO = '07';

    /** Tipos de documento que admite el anexo 3 (columna C). */
    public const ANEXO3_TIPOS_DOCUMENTO = ['03', '05', '06', '11', '12', '13'];

    // ------------------------------------------------------------------
    // Reglas por periodo
    // ------------------------------------------------------------------

    public static function periodo($fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->format('Y-m');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Sin periodo se asume el vigente (columnas de Renta presentes). */
    public static function usaRentaVentas($periodo): bool
    {
        $p = self::periodo($periodo);

        return $p === null || $p >= self::PERIODO_RENTA_VENTAS;
    }

    public static function usaRentaCompras($periodo): bool
    {
        $p = self::periodo($periodo);

        return $p === null || $p >= self::PERIODO_RENTA_COMPRAS;
    }

    /** Desde noviembre 2022 se informa el codigo de generacion. */
    public static function usaCodigoGeneracion($fechaDocumento): bool
    {
        $p = self::periodo($fechaDocumento);

        return $p === null || $p >= self::PERIODO_CODIGO_GENERACION;
    }

    /**
     * Numero de documento de un DTE (regla unica para todos los anexos):
     * codigo de generacion sin guiones desde noviembre 2022, numero de
     * control sin guiones antes de esa fecha.
     */
    public static function documentoDte($fechaDocumento, $codigoGeneracion, $numeroControl): string
    {
        $valor = self::usaCodigoGeneracion($fechaDocumento) ? $codigoGeneracion : $numeroControl;

        return self::texto(self::sinGuiones($valor), 100);
    }

    /**
     * Anexo 1, columna D (resolucion) para DTE: es el inverso de la columna F,
     * numero de control desde noviembre 2022 y codigo de generacion antes.
     */
    public static function resolucionDte($fechaDocumento, $codigoGeneracion, $numeroControl): string
    {
        $valor = self::usaCodigoGeneracion($fechaDocumento) ? $numeroControl : $codigoGeneracion;

        return self::texto(self::sinGuiones($valor), 100);
    }

    // ------------------------------------------------------------------
    // Formato de campos
    // ------------------------------------------------------------------

    /** Monto: 2 decimales, punto, sin miles, redondeo medio hacia arriba. */
    public static function monto($valor): string
    {
        return Decimal::round($valor ?? 0, 2);
    }

    /** Suma de valores ya redondeados a 2 decimales. */
    public static function suma(array $valores): string
    {
        $redondeados = array_map(fn ($v) => self::monto($v), $valores);

        return self::monto($redondeados ? Decimal::add(...$redondeados) : 0);
    }

    public static function sinGuiones($valor): string
    {
        return str_replace('-', '', trim((string) $valor));
    }

    public static function soloDigitos($valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    /**
     * Texto limpio: el archivo se escribe SIN encapsulador, asi que un ';' o
     * un salto de linea dentro de un nombre partiria la fila. $max = null
     * significa "sin limite" (asi lo indica el manual para los nombres).
     */
    public static function texto($valor, ?int $max = null): string
    {
        $valor = str_replace([';', '"', "\r", "\n", "\t"], [' ', '', ' ', ' ', ' '], (string) $valor);
        $valor = trim(preg_replace('/\s+/u', ' ', $valor));

        return $max === null ? $valor : mb_substr($valor, 0, $max);
    }

    public static function fecha($fecha): string
    {
        return Carbon::parse($fecha)->format('d/m/Y');
    }

    /**
     * Identificacion de cliente/proveedor/agente para las columnas NIT o NRC
     * y DUI. El manual dice que si se completa el DUI la columna NIT/NRC debe
     * quedar completamente vacia, y viceversa; por eso se devuelve solo uno:
     *  - NIT de 14 digitos, si lo hay;
     *  - si no, el NRC (sin guion) cuando se acepta NRC en la columna;
     *  - si no hay ninguno, DUI de 9 digitos (tambien sirve el NIT homologado
     *    de 9 digitos de una persona natural).
     *
     * @return array{0:string,1:string} [nitONrc, dui]
     */
    public static function identificacion($nit, $nrc, $dui, bool $aceptaNrc = true): array
    {
        $nit = self::soloDigitos($nit);
        $nrc = self::soloDigitos($nrc);

        if (strlen($nit) === 14) {
            return [$nit, ''];
        }

        if ($aceptaNrc && $nrc !== '') {
            return [mb_substr($nrc, 0, 14), ''];
        }

        foreach ([$dui, $nit] as $candidato) {
            $digitos = self::soloDigitos($candidato);
            if (strlen($digitos) === 9) {
                return ['', $digitos];
            }
        }

        return ['', ''];
    }

    // ------------------------------------------------------------------
    // Anexo 1 - Detalle de ventas a contribuyentes (20 columnas, A..T)
    // ------------------------------------------------------------------

    /**
     * @param array $f Fila de LibrosIvaService::ventasContribuyentes()
     */
    public static function filaAnexo1(array $f, $periodo): array
    {
        [$nitNrc, $dui] = self::identificacion($f['nit'] ?? '', $f['nrc'] ?? '', $f['dui'] ?? '');
        $renta = self::usaRentaVentas($periodo);

        return [
            self::fecha($f['fecha']),                                                   // A Fecha de emision
            '4',                                                                        // B Clase: 4 DTE
            '03',                                                                       // C Tipo: 03 CCF
            self::resolucionDte($f['fecha'], $f['codigo'] ?? '', $f['numero_control'] ?? ''), // D Resolucion: numero de control sin guiones
            self::texto(self::sinGuiones($f['sello'] ?? ''), 100),                      // E Serie: sello de recepcion
            self::documentoDte($f['fecha'], $f['codigo'] ?? '', $f['numero_control'] ?? ''),  // F Numero de documento: codigo de generacion
            '',                                                                         // G Control interno: en blanco para DTE
            $nitNrc,                                                                    // H NIT o NRC del cliente
            self::texto($f['cliente'] ?? ''),                                           // I Nombre o razon social
            self::monto(0),                                                             // J Ventas exentas (el negocio no genera)
            self::monto(0),                                                             // K Ventas no sujetas (el negocio no genera)
            self::monto($f['gravada'] ?? 0),                                            // L Ventas gravadas locales (sin IVA)
            self::monto($f['debito'] ?? 0),                                             // M Debito fiscal
            self::monto(0),                                                             // N Ventas a cuenta de terceros no domiciliados
            self::monto(0),                                                             // O Debito fiscal por cuenta de terceros
            self::monto($f['total'] ?? 0),                                              // P Total ventas (L + M)
            $dui,                                                                       // Q DUI del cliente
            $renta ? self::RENTA_VENTA_TIPO_OPERACION : '0',                            // R Tipo de operacion (Renta)
            $renta ? self::RENTA_VENTA_TIPO_INGRESO : '0',                              // S Tipo de ingreso (Renta)
            '1',                                                                        // T Numero de anexo
        ];
    }

    // ------------------------------------------------------------------
    // Anexo 2 - Detalle de ventas a consumidor final (23 columnas, A..W)
    // ------------------------------------------------------------------

    /**
     * Una fila por RANGO (dia + caja), no por ticket.
     *
     * @param array $g Grupo de LibrosIvaService::ventasConsumidor()
     */
    public static function filaAnexo2(array $g, $periodo): array
    {
        $renta = self::usaRentaVentas($periodo);

        return [
            self::fecha($g['fecha']),                                                   // A Fecha de emision
            '4',                                                                        // B Clase: SIEMPRE 4 (DTE)
            '01',                                                                       // C Tipo: SIEMPRE 01 (Factura consumidor final)
            'N/A',                                                                      // D Resolucion: N/A para DTE (se agrupa por dia)
            'N/A',                                                                      // E Serie: N/A para DTE
            'N/A',                                                                      // F Control interno (del): N/A para DTE
            'N/A',                                                                      // G Control interno (al): N/A para DTE
            self::documentoDte($g['fecha'], $g['del_codigo'] ?? '', $g['del_numero'] ?? ''), // H Documento (del): primer DTE del dia
            self::documentoDte($g['fecha'], $g['al_codigo'] ?? '', $g['al_numero'] ?? ''),   // I Documento (al): ultimo DTE del dia
            '',                                                                         // J Maquina registradora: en blanco para DTE
            self::monto(0),                                                             // K Ventas exentas (el negocio no genera)
            self::monto(0),                                                             // L Internas exentas no sujetas a proporcionalidad
            self::monto(0),                                                             // M Ventas no sujetas (el negocio no genera)
            self::monto($g['gravadas'] ?? 0),                                           // N Ventas gravadas locales (IVA incluido)
            self::monto(0),                                                             // O Exportaciones dentro de CA
            self::monto(0),                                                             // P Exportaciones fuera de CA
            self::monto(0),                                                             // Q Exportaciones de servicios
            self::monto(0),                                                             // R Zonas francas y DPA (tasa cero)
            self::monto(0),                                                             // S Ventas a cuenta de terceros no domiciliados
            self::monto($g['total'] ?? 0),                                              // T Total ventas
            $renta ? self::RENTA_VENTA_TIPO_OPERACION : '0',                            // U Tipo de operacion (Renta)
            $renta ? self::RENTA_VENTA_TIPO_INGRESO : '0',                              // V Tipo de ingreso (Renta)
            '2',                                                                        // W Numero de anexo
        ];
    }

    // ------------------------------------------------------------------
    // Anexo 3 - Detalle de compras (21 columnas, A..U)
    // ------------------------------------------------------------------

    /**
     * @param array $c Fila de LibrosIvaService::compras()
     */
    public static function filaAnexo3(array $c, $periodo): array
    {
        [$nitNrc, $dui] = self::identificacion($c['nit'] ?? '', $c['nrc'] ?? '', '');
        $renta = self::usaRentaCompras($periodo);

        $g = self::monto($c['compras_exentas'] ?? 0);
        $h = self::monto($c['internaciones_exentas'] ?? 0);
        $i = self::monto($c['importaciones_exentas'] ?? 0);
        $j = self::monto($c['compras_gravadas'] ?? 0);
        $k = self::monto($c['internaciones_gravadas'] ?? 0);
        $l = self::monto($c['importaciones_gravadas'] ?? 0);
        $m = self::monto($c['importaciones_servicios'] ?? 0);

        return [
            self::fecha($c['fecha']),                                                   // A Fecha de emision
            (string) ($c['clase_documento'] ?: '1'),                                    // B Clase de documento
            (string) $c['tipo_documento'],                                              // C Tipo de documento
            self::texto($c['documento'] ?? '', 100),                                    // D Numero de documento
            $nitNrc,                                                                    // E NIT o NRC del proveedor
            self::texto($c['proveedor'] ?? ''),                                         // F Nombre del proveedor
            $g,                                                                         // G Compras internas exentas y/o no sujetas
            $h,                                                                         // H Internaciones exentas y/o no sujetas
            $i,                                                                         // I Importaciones exentas y/o no sujetas
            $j,                                                                         // J Compras internas gravadas
            $k,                                                                         // K Internaciones gravadas de bienes
            $l,                                                                         // L Importaciones gravadas de bienes
            $m,                                                                         // M Importaciones gravadas de servicios
            self::monto($c['credito_fiscal'] ?? 0),                                     // N Credito fiscal
            self::suma([$g, $h, $i, $j, $k, $l, $m]),                                   // O Total compras = G..M (literal del manual)
            $dui,                                                                       // P DUI del proveedor
            $renta ? (string) ($c['renta_tipo_operacion'] ?: self::RENTA_COMPRA_TIPO_OPERACION) : '0', // Q Tipo de operacion
            $renta ? (string) ($c['renta_clasificacion'] ?: self::RENTA_COMPRA_CLASIFICACION) : '0',   // R Clasificacion
            $renta ? (string) ($c['renta_sector'] ?: self::RENTA_COMPRA_SECTOR) : '0',                 // S Sector
            $renta ? (string) ($c['renta_tipo_costo_gasto'] ?: self::RENTA_COMPRA_TIPO_COSTO) : '0',   // T Tipo de costo / gasto
            '3',                                                                        // U Numero de anexo
        ];
    }

    // ------------------------------------------------------------------
    // Anexo 7 - Retencion 1% IVA efectuada AL declarante (9 columnas, A..I)
    // ------------------------------------------------------------------

    /**
     * El agente es el CLIENTE categoria GRANDE que retuvo el 1% (Art. 162
     * CT). En el sistema la columna se llama ventas.percepcion, pero el POS la
     * RESTA del total y el DTE la emite en ivaRete1: es una retencion.
     *
     * @param array $r Fila de LibrosIvaService::retencionesClientes()
     */
    public static function filaAnexo7(array $r): array
    {
        [$nit, $dui] = self::identificacion($r['nit'] ?? '', '', $r['dui'] ?? '', false);

        return [
            $nit,                                                                       // A NIT del agente (cliente)
            self::fecha($r['fecha']),                                                   // B Fecha de emision
            self::ANEXO7_TIPO_DOCUMENTO,                                                // C Tipo de documento
            self::texto(self::sinGuiones($r['sello'] ?? ''), 100),                      // D Serie: sello de recepcion del DTE
            self::documentoDte($r['fecha'], $r['codigo'] ?? '', $r['numero_control'] ?? ''), // E Numero de documento
            self::monto($r['monto_sujeto'] ?? 0),                                       // F Monto sujeto (base sin IVA)
            self::monto($r['retencion'] ?? 0),                                          // G Monto retencion 1%
            $dui,                                                                       // H DUI del agente
            '7',                                                                        // I Numero de anexo
        ];
    }

    // ------------------------------------------------------------------
    // Archivo
    // ------------------------------------------------------------------

    /**
     * CSV separado por ';', sin encabezados, sin encapsulador y con fin de
     * linea CRLF (el portal lo espera como lo guarda Excel en Windows).
     */
    public static function csv(iterable $filas): string
    {
        $lineas = [];

        foreach ($filas as $fila) {
            $lineas[] = implode(';', array_map(fn ($v) => self::texto($v), $fila));
        }

        return $lineas ? implode("\r\n", $lineas) . "\r\n" : '';
    }

    /** Nombre predecible y de 25 caracteres o menos (limite del manual). */
    public static function nombreArchivo(int $anexo, $desde): string
    {
        return 'anexo' . $anexo . '_' . Carbon::parse($desde)->format('Ym') . '.csv';
    }

    /** La carga en Hacienda es por mes: desde y hasta deben caer en el mismo. */
    public static function mismoPeriodo($desde, $hasta): bool
    {
        return self::periodo($desde) !== null && self::periodo($desde) === self::periodo($hasta);
    }

    // ------------------------------------------------------------------
    // Desglose fiscal de una compra
    // ------------------------------------------------------------------

    /**
     * Desglose que se guarda al crear/editar la compra.
     *
     * En el destino la pantalla de compras captura el costo SIN IVA cuando el
     * documento es credito fiscal (y le suma el 13%), y CON IVA cuando es
     * factura de consumidor final. Por eso:
     *  - CCF: gravada = subtotal; credito = 13% de la gravada; total = suma.
     *  - Factura: no hay credito fiscal; todo el monto (IVA incluido) queda
     *    como compra gravada sin derecho a credito y la compra no entra al
     *    anexo 3 (que solo admite 03, 05, 06, 11, 12 y 13).
     * En ambos casos la suma del desglose es exactamente igual al total.
     *
     * @return array<string,string|null>
     */
    public static function desgloseCompra(bool $esCreditoFiscal, $subtotal, $correlativo = null): array
    {
        $base = self::monto($subtotal);

        if ($esCreditoFiscal) {
            $credito = self::monto(Decimal::mul($base, '0.13'));
            $total = self::monto(Decimal::add($base, $credito));
        } else {
            $credito = '0.00';
            $total = $base;
        }

        // El correlativo de un DTE del proveedor es su codigo de generacion
        // (UUID): se reconoce por formato y se guarda como clase 4.
        $codigo = self::esCodigoGeneracion($correlativo) ? strtoupper(trim((string) $correlativo)) : null;

        return [
            'tipo_documento' => $esCreditoFiscal ? '03' : '01',
            'clase_documento' => $codigo ? '4' : '1',
            'codigo_generacion' => $codigo,
            'compras_exentas' => '0.00',
            'internaciones_exentas' => '0.00',
            'importaciones_exentas' => '0.00',
            'compras_gravadas' => $base,
            'internaciones_gravadas' => '0.00',
            'importaciones_gravadas' => '0.00',
            'importaciones_servicios' => '0.00',
            'credito_fiscal' => $credito,
            'total_compra' => $total,
        ];
    }

    public static function esCodigoGeneracion($valor): bool
    {
        return (bool) preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', trim((string) $valor));
    }
}
