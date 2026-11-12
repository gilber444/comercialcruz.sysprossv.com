<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Anota en api_sync_registros cada peticion de sync por API que recibe el VPS
 * (para la pantalla monitor-sync-api). Toda ruta nueva dentro del grupo de
 * routes/api.php que usa este middleware aparece sola en el monitor.
 *
 * Lee del JSON de respuesta: guardados, errores, pendientes y error. Si algo
 * falla al anotar, la sincronizacion sigue igual.
 */
class RegistrarSyncApi
{
    private static ?bool $hayTabla = null;

    public function handle(Request $request, Closure $next)
    {
        $inicio = microtime(true);
        $respuesta = $next($request);

        try {
            if (config('app.modo', 'local') !== 'vps') {
                return $respuesta;
            }
            self::$hayTabla ??= Schema::hasTable('api_sync_registros');
            if (!self::$hayTabla) {
                return $respuesta;
            }

            $datos = $respuesta instanceof JsonResponse ? (array) $respuesta->getData(true) : [];
            $recibidas = 0;
            foreach (['filas', 'paquetes', 'items', 'codigos'] as $clave) {
                $recibidas += count((array) $request->input($clave, []));
            }
            $errores = (array) ($datos['errores'] ?? []);
            // Las consultas (faltantes, resueltos) devuelven listas en vez de "guardados"
            $guardados = $datos['guardados'] ?? count((array) ($datos['dtes'] ?? $datos['faltantes'] ?? []));
            $mensaje = $datos['error'] ?? ($errores ? implode(' | ', array_slice($errores, 0, 5)) : null);

            DB::table('api_sync_registros')->insert([
                'sucursal' => (int) $request->input('sucursal') ?: null,
                'servicio' => mb_substr(Str::after((string) ($request->route()?->getName() ?: $request->path()), 'api.sync.'), 0, 60),
                'tabla' => $request->input('tabla') ? mb_substr((string) $request->input('tabla'), 0, 60) : null,
                'recibidas' => $recibidas,
                'guardados' => (int) $guardados,
                'errores' => count($errores),
                'pendientes' => count((array) ($datos['pendientes'] ?? [])),
                'http' => $respuesta->getStatusCode(),
                'ms' => (int) round((microtime(true) - $inicio) * 1000),
                'mensaje' => $mensaje ? mb_substr((string) $mensaje, 0, 2000) : null,
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Monitor sync API: no se pudo anotar la peticion', ['error' => $e->getMessage()]);
        }

        return $respuesta;
    }
}
