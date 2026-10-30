<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Decide si en esta PC corre el daemon de sync (sync:daemon) o los comandos
 * de sync separados del scheduler. Nunca deben correr los dos a la vez.
 */
class SyncDaemon
{
    private const CACHE_ACTIVO = 'sync_daemon:activo';
    private const CACHE_ULTIMO = 'sync_daemon:activo_ultimo';
    private const CACHE_LATIDO = 'sync_daemon:latido';

    /**
     * El flag se consulta en el VPS (ahí lo controla el owner) y se guarda
     * 5 minutos. Si el VPS no responde se usa el último valor conocido.
     */
    public static function activo(): bool
    {
        $modo = strtolower((string) config('sync.daemon', 'auto'));

        if ($modo === 'on') {
            return true;
        }
        if ($modo === 'off' || config('app.modo', 'local') !== 'local') {
            return false;
        }

        $cacheado = Cache::get(self::CACHE_ACTIVO);
        if ($cacheado !== null) {
            return (bool) $cacheado;
        }

        $sucursal = (int) config('app.sucursal_id', 0);

        try {
            $activo = DB::connection('vpsmysql')->table('features')
                ->whereIn('codigo', ['sync_daemon', 'sync_daemon_suc_' . $sucursal])
                ->where('activo', 1)
                ->where('produccion', 1)
                ->exists();
        } catch (Throwable $e) {
            $activo = (bool) Cache::get(self::CACHE_ULTIMO, false);
        } finally {
            DB::disconnect('vpsmysql');
        }

        Cache::put(self::CACHE_ACTIVO, $activo, now()->addMinutes(5));
        Cache::forever(self::CACHE_ULTIMO, $activo);

        return $activo;
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
