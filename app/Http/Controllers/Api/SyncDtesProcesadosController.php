<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Recibe en el VPS los DTE PROCESADOS (con sello) que suben las cajas por API
 * (comando sync:dtes-procesados-api).
 *
 * Hace lo mismo que sync:local-vps para estas tablas: upsert por sincro_id,
 * nunca empuja el id local y traduce las llaves al id del VPS por el sincro_id
 * del padre. Por eso convive con la sincronizacion normal sin duplicar filas.
 */
class SyncDtesProcesadosController extends Controller
{
    /** Llaves foraneas a traducir: columna => tabla (igual que sync:local-vps). */
    private const FK = [
        'ventas_detalles' => ['producto' => 'productos'],
        'cajas' => ['corte' => 'cortes'],
        'dtes' => ['tocken' => 'tockens'],
    ];

    private array $columnas = [];

    /** Devuelve los sincro_id de DTE que el VPS no tiene, o que tiene sin ese sello. */
    public function faltantes(Request $request)
    {
        if ($error = $this->autorizar($request)) {
            return $error;
        }

        $items = collect($request->input('items', []))
            ->filter(fn ($i) => !empty($i['sincro_id']))
            ->take(1000);

        $enVps = DB::table('dtes')
            ->whereIn('sincro_id', $items->pluck('sincro_id')->all())
            ->pluck('sello', 'sincro_id');

        $faltan = $items->filter(function ($i) use ($enVps) {
            $sello = $enVps[$i['sincro_id']] ?? null;
            return trim((string) $sello) === '' || $sello !== ($i['sello'] ?? null);
        })->pluck('sincro_id')->values();

        return response()->json(['faltantes' => $faltan]);
    }

    /**
     * Para sync:dtes-estado: de los codigos / sincro_id que manda la caja, devuelve
     * los DTE que en el VPS ya estan PROCESADO o INVALIDADO (estado, sello, numero
     * de control, codigo y JSON).
     */
    public function resueltos(Request $request)
    {
        if ($error = $this->autorizar($request)) {
            return $error;
        }

        $codigos = array_slice(array_values(array_filter((array) $request->input('codigos', []))), 0, 1000);
        $sincros = array_slice(array_values(array_filter((array) $request->input('sincros', []))), 0, 1000);
        if (!$codigos && !$sincros) {
            return response()->json(['dtes' => []]);
        }
        $conSincro = Schema::hasColumn('dtes', 'sincro_id');

        $filas = DB::table('dtes')
            ->select(array_values(array_filter(['codigoGeneracion', 'numeroControl', 'estado', 'sello', 'jsonDte', $conSincro ? 'sincro_id' : null])))
            ->whereIn(DB::raw('UPPER(TRIM(estado))'), ['PROCESADO', 'INVALIDADO'])
            ->where(function ($q) use ($codigos, $sincros, $conSincro) {
                $q->whereIn('codigoGeneracion', $codigos ?: ['-']);
                if ($conSincro && $sincros) {
                    $q->orWhereIn('sincro_id', $sincros);
                }
            })
            ->get();

        return response()->json(['dtes' => $filas]);
    }

    /** Guarda los paquetes (venta + detalles + caja + dte + resumen + firma + recepcion). */
    public function recibir(Request $request)
    {
        if ($error = $this->autorizar($request)) {
            return $error;
        }

        $sucursal = (int) $request->input('sucursal');
        $resultado = ['guardados' => 0, 'errores' => [], 'ids' => []];

        foreach ((array) $request->input('paquetes', []) as $n => $p) {
            $dteSincro = $p['dte']['fila']['sincro_id'] ?? ('#' . $n);
            try {
                // Solo la sucursal que declara la caja (regla de AGENTS.md: una PC
                // no sube datos de otra sucursal).
                $sucVenta = (int) ($p['venta']['fila']['sucursal'] ?? 0);
                $sucDte = (int) ($p['dte']['fila']['sucursal'] ?? 0);
                if ($sucursal <= 0 || $sucVenta !== $sucursal || $sucDte !== $sucursal) {
                    throw new \RuntimeException("sucursal no coincide (caja {$sucursal}, venta {$sucVenta}, dte {$sucDte})");
                }
                if (trim((string) ($p['dte']['fila']['sello'] ?? '')) === '') {
                    throw new \RuntimeException('el DTE no trae sello');
                }

                $ids = DB::transaction(function () use ($p) {
                    $ids = [];
                    $ventaId = $this->upsert('ventas', $p['venta']);
                    $ids['ventas'][$p['venta']['fila']['sincro_id']] = $ventaId;

                    foreach ((array) ($p['detalles'] ?? []) as $d) {
                        $d['fila']['venta'] = $ventaId;
                        $ids['ventas_detalles'][$d['fila']['sincro_id']] = $this->upsert('ventas_detalles', $d);
                    }
                    foreach ((array) ($p['cajas'] ?? []) as $c) {
                        $c['fila']['venta'] = $ventaId;
                        $ids['cajas'][$c['fila']['sincro_id']] = $this->upsert('cajas', $c);
                    }

                    $dte = $p['dte'];
                    $dte['fila']['venta'] = $ventaId;
                    $dteId = $this->upsert('dtes', $dte);
                    $ids['dtes'][$dte['fila']['sincro_id']] = $dteId;

                    foreach (['resumen_dtes', 'firmadors', 'recepcion_dtes'] as $tabla) {
                        foreach ((array) ($p[$tabla] ?? []) as $h) {
                            $h['fila']['dte'] = $dteId;
                            $ids[$tabla][$h['fila']['sincro_id']] = $this->upsert($tabla, $h);
                        }
                    }

                    return $ids;
                });

                $resultado['guardados']++;
                $resultado['ids'][] = $ids;
            } catch (\Throwable $e) {
                $resultado['errores'][] = "DTE {$dteSincro}: " . $e->getMessage();
                Log::error('API sync DTE procesado', ['dte' => $dteSincro, 'error' => $e->getMessage()]);
            }
        }

        return response()->json($resultado);
    }

    /**
     * Upsert por sincro_id con solo las columnas que existen en la tabla del VPS.
     * $item = ['fila' => [...], 'fk' => [columna => sincro_id del padre]].
     */
    private function upsert(string $tabla, array $item): int
    {
        $fila = (array) ($item['fila'] ?? []);
        $sincro = $fila['sincro_id'] ?? null;
        if (!$sincro) {
            throw new \RuntimeException("falta sincro_id en {$tabla}");
        }

        // Traduce llaves por el sincro_id del padre; si no esta en el VPS se
        // conserva el valor original (igual que sync:local-vps cuando no es requerida).
        foreach (self::FK[$tabla] ?? [] as $col => $tablaPadre) {
            $padre = $item['fk'][$col] ?? null;
            if ($padre) {
                $idVps = DB::table($tablaPadre)->where('sincro_id', $padre)->value('id');
                if ($idVps) {
                    $fila[$col] = (int) $idVps;
                }
            }
        }

        unset($fila['id'], $fila['id_vps']);
        foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
            if (isset($fila[$ts]) && ($fila[$ts] === '?' || $fila[$ts] === '')) {
                $fila[$ts] = null;
            }
        }

        $columnas = $this->columnas[$tabla] ??= Schema::getColumnListing($tabla);
        $fila = array_intersect_key($fila, array_flip($columnas));
        $fila['updated_at'] = $fila['updated_at'] ?? now();

        $existente = DB::table($tabla)->where('sincro_id', $sincro)->first(['id']);
        if ($existente) {
            DB::table($tabla)->where('id', $existente->id)->update($fila);
            return (int) $existente->id;
        }

        $fila['created_at'] = $fila['created_at'] ?? now();

        return (int) DB::table($tabla)->insertGetId($fila);
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
