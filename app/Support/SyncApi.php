<?php

namespace App\Support;

/**
 * Estado de la subida por API desde las cajas al VPS (config/sync.php).
 */
class SyncApi
{
    /** Tablas que sube sync:ventas-api (y que entonces deja de subir sync:local-vps). */
    public const TABLAS_VENTAS = [
        'ventas', 'cajas', 'ventas_detalles', 'tockens',
        'dtes', 'resumen_dtes', 'firmadors', 'recepcion_dtes',
    ];

    /** true en una caja con SYNC_API_TOKEN puesto y SYNC_API_VENTAS distinto de off. */
    public static function ventasActiva(): bool
    {
        return config('app.modo', 'local') !== 'vps'
            && (string) config('sync.api_token') !== ''
            && strtolower((string) config('sync.api_ventas', 'on')) !== 'off';
    }
}
