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

    /** Tablas que sube sync:inventario-api (y que entonces deja de subir sync:local-vps). */
    public const TABLAS_INVENTARIO = ['inventarios', 'kardexes', 'kardexes2'];

    /** true en una caja con SYNC_API_TOKEN puesto y SYNC_API_INVENTARIO distinto de off. */
    public static function inventarioActiva(): bool
    {
        return config('app.modo', 'local') !== 'vps'
            && (string) config('sync.api_token') !== ''
            && strtolower((string) config('sync.api_inventario', 'on')) !== 'off';
    }

    /**
     * Manda al VPS el resultado de un comando de sync de esta caja (pantalla
     * monitor-sync-api). Nunca detiene al comando si falla.
     */
    public static function latido(string $comando, int $sucursal, bool $ok, array $lineas): void
    {
        if (config('app.modo', 'local') === 'vps' || (string) config('sync.api_token') === '' || $sucursal <= 0) {
            return;
        }
        try {
            \Illuminate\Support\Facades\Http::withHeaders(['X-Sync-Token' => (string) config('sync.api_token'), 'Accept' => 'application/json'])
                ->timeout(10)
                ->post(rtrim((string) config('sync.api_url'), '/') . '/api/sync/latido', [
                    'sucursal' => $sucursal,
                    'comando' => $comando,
                    'ok' => $ok ? 1 : 0,
                    'resumen' => mb_substr(implode("\n", array_slice($lineas, 0, 40)), 0, 4000),
                    'version' => (string) config('version.current'),
                    'equipo' => (string) gethostname(),
                ]);
        } catch (\Throwable $e) {
            // sin conexion: el monitor lo mostrara como "sin contacto"
        }
    }

    /** true en una caja con SYNC_API_TOKEN puesto y SYNC_API_VENTAS distinto de off. */
    public static function ventasActiva(): bool
    {
        return config('app.modo', 'local') !== 'vps'
            && (string) config('sync.api_token') !== ''
            && strtolower((string) config('sync.api_ventas', 'on')) !== 'off';
    }
}
