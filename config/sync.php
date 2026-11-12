<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daemon de sincronización (sync:daemon)
    |--------------------------------------------------------------------------
    |
    | Un solo proceso por PC que corre todas las sincronizaciones en orden,
    | una a la vez (máximo una conexión al VPS por PC).
    |
    | daemon (SYNC_DAEMON en el .env de cada PC):
    |   on  → corre el daemon y se apagan los comandos de sync separados.
    |   off → (por defecto) siguen los comandos de sync separados de siempre.
    |
    */
    'daemon' => env('SYNC_DAEMON', 'off'),

    // Minutos que corre cada ciclo del daemon antes de salir; el scheduler lo
    // vuelve a lanzar al minuto siguiente (libera memoria y toma código nuevo).
    'minutos_por_ciclo' => (int) env('SYNC_DAEMON_MINUTOS', 10),

    // Pausa entre vueltas del loop (segundos).
    'pausa' => (int) env('SYNC_DAEMON_PAUSA', 5),

    // Tiempo máximo de cada tarea antes de matarla (segundos).
    'timeout_tarea' => (int) env('SYNC_DAEMON_TIMEOUT_TAREA', 600),

    // Tareas en el orden en que se ejecutan. 'cada' = segundos entre corridas.
    // {sucursal} se reemplaza por APP_SUCURSAL_ID.
    'tareas' => [
        'push'         => ['comando' => 'sync:maestro',         'args' => ['--sucursal={sucursal}', '--limit=1000'], 'cada' => (int) env('SYNC_CADA_PUSH', 20)],
        'pull'         => ['comando' => 'sync:vps-local',       'args' => ['--sucursales={sucursal}'],              'cada' => (int) env('SYNC_CADA_PULL', 60)],
        'orden_pedido' => ['comando' => 'sync:orden-pedido',    'args' => [],                                       'cada' => (int) env('SYNC_CADA_ORDEN_PEDIDO', 60)],
        'producto'     => ['comando' => 'producto:nuevo',       'args' => ['{sucursal}'],                           'cada' => (int) env('SYNC_CADA_PRODUCTO', 120)],
        'existencias'  => ['comando' => 'sync:existencias-vps', 'args' => [],                                       'cada' => (int) env('SYNC_CADA_EXISTENCIAS', 600)],
        'dtes_estado'  => ['comando' => 'sync:dtes-estado',     'args' => ['--limit=500'],                          'cada' => (int) env('SYNC_CADA_DTES_ESTADO', 1800)],
    ],

    /*
    | Subida por API de los DTE PROCESADOS (sync:dtes-procesados-api).
    |   api_url   (SYNC_API_URL)   URL del VPS que recibe.
    |   api_token (SYNC_API_TOKEN) el mismo valor en el VPS y en cada caja.
    |                              Vacio = la API y el comando quedan apagados.
    */
    'api_url' => env('SYNC_API_URL', 'https://comercialcruz.sysprossv.com'),
    'api_token' => env('SYNC_API_TOKEN', ''),

    /*
    | Subida por API de ventas, cajas, ventas_detalles, tockens, dtes,
    | resumen_dtes, firmadors y recepcion_dtes (sync:ventas-api).
    | Con SYNC_API_TOKEN puesto y esto en 'on', sync:local-vps deja de subir
    | esas 8 tablas (las sube la API). 'off' = vuelve a como era antes.
    */
    'api_ventas' => env('SYNC_API_VENTAS', 'on'),
];
