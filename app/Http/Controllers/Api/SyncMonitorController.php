<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Recibe en el VPS el "latido" de cada comando de sync por API de las cajas:
 * cuando corrio, si termino bien y su resumen (pantalla monitor-sync-api).
 */
class SyncMonitorController extends Controller
{
    public function latido(Request $request)
    {
        if ($error = $this->autorizar($request)) {
            return $error;
        }

        $sucursal = (int) $request->input('sucursal');
        $comando = mb_substr(trim((string) $request->input('comando')), 0, 100);
        if ($sucursal <= 0 || $comando === '') {
            return response()->json(['error' => 'Falta sucursal o comando.'], 422);
        }

        $ok = filter_var($request->input('ok'), FILTER_VALIDATE_BOOLEAN);
        $previo = DB::table('api_sync_latidos')->where('sucursal', $sucursal)->where('comando', $comando)->first();
        $datos = [
            'ok' => $ok,
            'fallos_seguidos' => $ok ? 0 : ((int) ($previo->fallos_seguidos ?? 0) + 1),
            'resumen' => mb_substr((string) $request->input('resumen'), 0, 4000),
            'version' => mb_substr((string) $request->input('version'), 0, 20) ?: null,
            'equipo' => mb_substr((string) $request->input('equipo'), 0, 100) ?: null,
            'ip' => $request->ip(),
            'ultimo_at' => now(),
            'ultimo_ok_at' => $ok ? now() : ($previo->ultimo_ok_at ?? null),
            'updated_at' => now(),
        ];

        if ($previo) {
            DB::table('api_sync_latidos')->where('id', $previo->id)->update($datos);
        } else {
            DB::table('api_sync_latidos')->insert($datos + ['sucursal' => $sucursal, 'comando' => $comando, 'created_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    private function autorizar(Request $request)
    {
        $token = (string) config('sync.api_token');
        if ($token === '') {
            return response()->json(['error' => 'La API de sincronizacion no esta habilitada (SYNC_API_TOKEN vacio en el VPS).'], 503);
        }
        if (!hash_equals($token, (string) $request->header('X-Sync-Token'))) {
            return response()->json(['error' => 'Token invalido.'], 401);
        }
        if (config('app.modo', 'local') !== 'vps') {
            return response()->json(['error' => 'Esta API solo recibe en el VPS.'], 403);
        }

        return null;
    }
}
