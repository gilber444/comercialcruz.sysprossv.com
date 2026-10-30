<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Decide si en esta PC corre el daemon de sync (sync:daemon) o los comandos
 * de sync separados del scheduler. Nunca deben correr los dos a la vez.
 */
class SyncDaemon
{
    private const CACHE_LATIDO = 'sync_daemon:latido';

    /**
     * Se activa por PC con SYNC_DAEMON=on en el .env (solo en PCs locales).
     */
    public static function activo(): bool
    {
        return config('app.modo', 'local') === 'local'
            && strtolower((string) config('sync.daemon', 'off')) === 'on';
    }

    /**
     * Los comandos separados solo corren si el daemon no está activo ni
     * quedó corriendo un ciclo anterior (latido de los últimos 90 s).
     */
    public static function usarComandosSeparados(): bool
    {
        return !self::activo() && !Cache::has(self::CACHE_LATIDO);
    }

    public static function latido(): void
    {
        Cache::put(self::CACHE_LATIDO, time(), 90);
    }

    public static function terminarLatido(): void
    {
        Cache::forget(self::CACHE_LATIDO);
    }
}
