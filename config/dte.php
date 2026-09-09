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
     *
     * El valor por defecto es "aviso" a proposito: hoy el CCF se emite con
     * version 3 mientras Hacienda habilita la 4, y su estructura ya es v4, asi
     * que en "bloqueo" no saldria ninguna factura.
     */
    'validar_esquema' => env('DTE_VALIDAR_ESQUEMA', 'aviso'),

    /* Fechas YYYY-MM-DD adicionales, separadas por coma. */
    'feriados_adicionales' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DTE_FERIADOS_ADICIONALES', ''))
    ))),
];
