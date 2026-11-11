<?php

return [
    'consulta_publica_url' => env(
        'DTE_CONSULTA_PUBLICA_URL',
        'https://admin.factura.gob.sv/consultaPublica'
    ),

    /*
     * Días no laborables nacionales usados para calcular el décimo día hábil
     * de la Normativa DTE 2.0. Jueves y Viernes Santo se calculan por año.
     */
    'feriados_fijos' => [
        '01-01',
        '05-01',
        '05-10',
        '06-17',
        '08-06',
        '09-15',
        '11-02',
        '12-25',
    ],

    /*
     * Validacion del JSON contra el schema oficial guardado en esquema_dtes,
     * antes de firmar y transmitir.
     *
     *   off      no valida
     *   aviso    valida y deja el detalle en el log, pero deja pasar
     *   bloqueo  valida e impide transmitir un documento que no cumple
     *            (todavia NO implementado: por ahora se comporta como "aviso")
     *
     * Lo aplica App\Services\RevisionEsquemaDte en FirmadorDTE, FirmadorLocal
     * y Firmador2; el detalle queda en storage/logs/dte_esquema-AAAA-MM-DD.log.
     *
     * El valor por defecto es "aviso" a proposito: hoy el CCF se emite con
     * version 3 mientras Hacienda habilita la 4, y su estructura ya es v4, asi
     * que en "bloqueo" no saldria ninguna factura.
     */
    'validar_esquema' => env('DTE_VALIDAR_ESQUEMA', 'aviso'),

    /*
     * Tipos que se emiten con el generador de la Normativa 2.0, separados por
     * coma: 01 factura, 03 CCF, 05 nota de credito, 14 sujeto excluido.
     * Vacio (por defecto) = todos con el generador actual.
     * Ejemplo: DTE_GENERADOR_V2=01
     * Lo aplica App\Services\GeneradorDteV2Service; tambien cambia la version
     * del sobre de recepcion a la del documento firmado.
     */
    'generador_v2' => env('DTE_GENERADOR_V2', ''),

    /*
     * Conversion automatica en las PC de las ventas "Fisico" de consumidor
     * final a factura DTE, con su fecha original (ventas:convertir-fisicos-dte).
     * Se enciende aqui (llega a las cajas por git) despues de probar a mano con
     * --limit=3 que Hacienda acepta las fechas anteriores.
     */
    'convertir_fisicos' => [
        'activo' => false,
        'desde' => '2026-10-01',
        'lote' => 100,
    ],

    /* Fechas YYYY-MM-DD adicionales, separadas por coma. */
    'feriados_adicionales' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DTE_FERIADOS_ADICIONALES', ''))
    ))),
];
