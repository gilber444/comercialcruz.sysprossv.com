<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Recibe en el VPS, por lotes, las filas de ventas, cajas, ventas_detalles,
 * tockens, dtes, resumen_dtes, firmadors y recepcion_dtes que suben las cajas
 * (comando sync:ventas-api).
 *
 * Hace lo mismo que sync:local-vps para esas tablas: upsert por sincro_id, nunca
 * usa el id local y traduce las llaves al id del VPS por el sincro_id del padre.
 * La diferencia es que todo se resuelve aqui con pocas consultas por lote, en
 * vez de varias idas y vueltas por fila contra el MySQL del VPS.
 */
class SyncTablasVentasController extends Controller
{
    /** Tabla => [columna FK => tabla padre]. Igual que los planes de sync:local-vps. */
    public const PLANES = [
        'ventas' => [],
        'cajas' => ['venta' => 'ventas', 'corte' => 'cortes'],
        'ventas_detalles' => ['venta' => 'ventas', 'producto' => 'productos'],
        'tockens' => [],
        'dtes' => ['venta' => 'ventas', 'tocken' => 'tockens'],
        'resumen_dtes' => ['dte' => 'dtes'],
        'firmadors' => ['dte' => 'dtes'],
        'recepcion_dtes' => ['dte' => 'dtes'],
    ];

    /** FK sin la cual la fila no se guarda. */
    private const REQUERIDAS = [
        'ventas_detalles' => ['venta'],
        'resumen_dtes' => ['dte'],
        'firmadors' => ['dte'],
        'recepcion_dtes' => ['dte'],
    ];

    /** Tablas donde un sello que ya tiene el VPS no se borra con uno vacio. */
    private const CON_SELLO = ['ventas', 'cajas', 'dtes'];

    public function recibir(Request $request)
    {
        if ($error = $this->autorizar($request)) {
            return $error;
        }

        $tabla = (string) $request->input('tabla');
        $sucursal = (int) $request->input('sucursal');
        if (!array_key_exists($tabla, self::PLANES) || $sucursal <= 0) {
            return response()->json(['error' => 'Tabla o sucursal no valida.'], 422);
        }

        $items = array_values(array_filter((array) $request->input('filas', []), fn ($i) => !empty($i['fila']['sincro_id'])));
        $columnas = array_flip(Schema::getColumnListing($tabla));
        $plan = self::PLANES[$tabla];

        // Pocas consultas por lote: filas existentes y padres, todo con whereIn.
        $sincros = array_map(fn ($i) => $i['fila']['sincro_id'], $items);
        $existentes = DB::table($tabla)->whereIn('sincro_id', $sincros)
            ->get(array_values(array_filter(['id', 'sincro_id', isset($columnas['sello']) ? 'sello' : null])))
            ->keyBy('sincro_id');

        $padres = [];
        foreach ($plan as $col => $tablaPadre) {
            $ids = array_values(array_unique(array_filter(array_map(fn ($i) => $i['fk'][$col] ?? null, $items))));
            $cols = ['id', 'sincro_id'];
            if ($tablaPadre === 'ventas') {
                $cols[] = 'sucursal';
            }
            $padres[$col] = $ids ? DB::table($tablaPadre)->whereIn('sincro_id', $ids)->get($cols)->keyBy('sincro_id') : collect();
        }

        $resultado = ['guardados' => 0, 'errores' => [], 'pendientes' => [], 'ids' => []];

        DB::transaction(function () use ($items, $tabla, $sucursal, $columnas, $plan, $existentes, $padres, &$resultado) {
            foreach ($items as $item) {
                $fila = (array) $item['fila'];
                $sincro = $fila['sincro_id'];
                try {
                    // Regla de AGENTS.md: una PC no sube datos de otra sucursal.
                    if (isset($columnas['sucursal']) && array_key_exists('sucursal', $fila) && (int) $fila['sucursal'] !== $sucursal) {
                        throw new \RuntimeException("es de la sucursal {$fila['sucursal']}, no de la {$sucursal}");
                    }

                    foreach ($plan as $col => $tablaPadre) {
                        $padre = ($item['fk'][$col] ?? null) ? ($padres[$col][$item['fk'][$col]] ?? null) : null;
                        if ($padre) {
                            if ($tablaPadre === 'ventas' && (int) $padre->sucursal !== $sucursal) {
                                throw new \RuntimeException("su venta es de la sucursal {$padre->sucursal}");
                            }
                            $fila[$col] = (int) $padre->id;
                        } elseif (in_array($col, self::REQUERIDAS[$tabla] ?? [], true)) {
                            // El padre aun no llega: la caja la reintenta en la siguiente corrida
                            $resultado['pendientes'][] = $sincro;
                            continue 2;
                        }
                        // FK no requerida sin padre en el VPS: conserva el valor (igual que sync:local-vps)
                    }

                    unset($fila['id'], $fila['id_vps']);
                    foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
                        if (isset($fila[$ts]) && ($fila[$ts] === '?' || $fila[$ts] === '')) {
                            $fila[$ts] = null;
                        }
                    }
                    $fila = array_intersect_key($fila, $columnas);
                    $fila['updated_at'] = $fila['updated_at'] ?? now();

                    $existe = $existentes[$sincro] ?? null;
                    if ($existe) {
                        // No borrar un sello que el VPS ya tiene (p. ej. lo tomo de Hacienda)
                        if (in_array($tabla, self::CON_SELLO, true) && trim((string) ($existe->sello ?? '')) !== '' && trim((string) ($fila['sello'] ?? '')) === '') {
                            unset($fila['sello']);
                            if ($tabla === 'dtes') {
                                unset($fila['estado']);
                            }
                        }
                        DB::table($tabla)->where('id', $existe->id)->update($fila);
                        $id = (int) $existe->id;
                    } else {
                        $fila['created_at'] = $fila['created_at'] ?? now();
                        $id = (int) DB::table($tabla)->insertGetId($fila);
                    }

                    $resultado['ids'][$sincro] = $id;
                    $resultado['guardados']++;
                } catch (\Throwable $e) {
                    $resultado['errores'][] = "{$tabla} {$sincro}: " . mb_substr($e->getMessage(), 0, 300);
                }
            }
        });

        if ($resultado['errores']) {
            Log::warning('API sync tablas ventas', ['tabla' => $tabla, 'sucursal' => $sucursal, 'errores' => array_slice($resultado['errores'], 0, 10)]);
        }

        return response()->json($resultado);
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
