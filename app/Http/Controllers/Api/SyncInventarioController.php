<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Recibe en el VPS la existencia (inventarios) y el kardex que suben las cajas
 * por API (comando sync:inventario-api). Reemplaza la subida por conexion
 * directa de sync:local-vps y sync:existencias-vps para esas tablas.
 *
 * - inventarios: busca la fila por sincro_id y, si no, por producto + sucursal
 *   (igual que push:inventario-vps). Si existe solo cambia la existencia; si no,
 *   la crea. Nunca crea un segundo inventario del mismo producto y sucursal.
 * - kardexes / kardexes2: upsert por sincro_id con producto e inventario
 *   traducidos al id del VPS.
 * Una caja solo puede subir filas de la sucursal que declara.
 */
class SyncInventarioController extends Controller
{
    public const TABLAS = ['inventarios', 'kardexes', 'kardexes2'];

    public function recibir(Request $request)
    {
        if ($error = $this->autorizar($request)) {
            return $error;
        }

        $tabla = (string) $request->input('tabla');
        $sucursal = (int) $request->input('sucursal');
        if (!in_array($tabla, self::TABLAS, true) || $sucursal <= 0) {
            return response()->json(['error' => 'Tabla o sucursal no valida.'], 422);
        }

        $items = array_values(array_filter((array) $request->input('filas', []), fn ($i) => !empty($i['fila']['sincro_id'])));
        $resultado = ['guardados' => 0, 'errores' => [], 'pendientes' => [], 'ids' => []];

        if ($items) {
            if ($tabla === 'inventarios') {
                $this->inventarios($items, $sucursal, $resultado);
            } else {
                // Mismo criterio que push:inventario-vps si el VPS solo tiene una de las dos
                $destino = Schema::hasTable($tabla) ? $tabla : ($tabla === 'kardexes' ? 'kardexes2' : 'kardexes');
                if (!Schema::hasTable($destino)) {
                    return response()->json(['error' => "El VPS no tiene la tabla {$tabla}."], 422);
                }
                $this->kardex($destino, $items, $sucursal, $resultado);
            }
        }

        if ($resultado['errores']) {
            Log::warning('API sync inventario', ['tabla' => $tabla, 'sucursal' => $sucursal, 'errores' => array_slice($resultado['errores'], 0, 10)]);
        }

        return response()->json($resultado);
    }

    private function inventarios(array $items, int $sucursal, array &$resultado): void
    {
        $columnas = array_flip(Schema::getColumnListing('inventarios'));
        $productos = $this->idsPorSincro('productos', array_map(fn ($i) => $i['fk']['producto'] ?? null, $items));

        $porSincro = DB::table('inventarios')
            ->whereIn('sincro_id', array_map(fn ($i) => $i['fila']['sincro_id'], $items))
            ->get(['id', 'sincro_id', 'sucursal', 'producto'])->keyBy('sincro_id');
        // Si hubiera dos del mismo producto en la sucursal, se usa el primero (el mas viejo)
        $porProducto = DB::table('inventarios')->where('sucursal', $sucursal)
            ->whereIn('producto', array_values(array_unique($productos)))
            ->orderByDesc('id')->get(['id', 'sincro_id', 'producto'])->keyBy('producto');

        DB::transaction(function () use ($items, $sucursal, $columnas, $productos, $porSincro, $porProducto, &$resultado) {
            foreach ($items as $item) {
                $fila = (array) $item['fila'];
                $sincro = $fila['sincro_id'];
                try {
                    if ((int) ($fila['sucursal'] ?? 0) !== $sucursal) {
                        throw new \RuntimeException('es de la sucursal ' . ($fila['sucursal'] ?? '?') . ", no de la {$sucursal}");
                    }
                    $productoVps = $productos[$item['fk']['producto'] ?? ''] ?? null;
                    if (!$productoVps) {
                        // El producto aun no esta en el VPS: la caja lo reintenta luego
                        $resultado['pendientes'][] = $sincro;
                        continue;
                    }

                    $existe = $porSincro[$sincro] ?? null;
                    if ($existe && ((int) $existe->sucursal !== $sucursal || (int) $existe->producto !== $productoVps)) {
                        throw new \RuntimeException("en el VPS ese sincro_id es de otro producto o sucursal (id {$existe->id})");
                    }
                    $existe = $existe ?: ($porProducto[$productoVps] ?? null);

                    if ($existe) {
                        $cambios = ['existencia' => $fila['existencia'] ?? 0, 'updated_at' => now()];
                        if (isset($columnas['sincronizacion'])) {
                            $cambios['sincronizacion'] = now();
                        }
                        if ((string) $existe->sincro_id !== $sincro) {
                            $cambios['sincro_id'] = $sincro; // queda con el de la caja, como push:inventario-vps
                        }
                        DB::table('inventarios')->where('id', $existe->id)->update($cambios);
                        $id = (int) $existe->id;
                    } else {
                        $fila['producto'] = $productoVps;
                        $fila = $this->limpiar($fila, $columnas);
                        if (isset($columnas['sincronizacion'])) {
                            $fila['sincronizacion'] = now();
                        }
                        $fila['created_at'] = $fila['created_at'] ?? now();
                        $id = (int) DB::table('inventarios')->insertGetId($fila);
                    }

                    $resultado['ids'][$sincro] = $id;
                    $resultado['guardados']++;
                } catch (\Throwable $e) {
                    $resultado['errores'][] = "inventarios {$sincro}: " . mb_substr($e->getMessage(), 0, 300);
                }
            }
        });
    }

    private function kardex(string $tabla, array $items, int $sucursal, array &$resultado): void
    {
        $columnas = array_flip(Schema::getColumnListing($tabla));
        $productos = $this->idsPorSincro('productos', array_map(fn ($i) => $i['fk']['producto'] ?? null, $items));

        $sincrosInv = array_values(array_unique(array_filter(array_map(fn ($i) => $i['fk']['inventario'] ?? null, $items))));
        $invPorSincro = $sincrosInv
            ? DB::table('inventarios')->whereIn('sincro_id', $sincrosInv)->get(['id', 'sincro_id', 'sucursal'])->keyBy('sincro_id')
            : collect();
        $invPorProducto = $productos
            ? DB::table('inventarios')->where('sucursal', $sucursal)->whereIn('producto', array_values(array_unique($productos)))
                ->orderByDesc('id')->get(['id', 'producto'])->keyBy('producto')
            : collect();

        $existentes = DB::table($tabla)->whereIn('sincro_id', array_map(fn ($i) => $i['fila']['sincro_id'], $items))
            ->pluck('id', 'sincro_id');

        DB::transaction(function () use ($tabla, $items, $sucursal, $columnas, $productos, $invPorSincro, $invPorProducto, $existentes, &$resultado) {
            foreach ($items as $item) {
                $fila = (array) $item['fila'];
                $sincro = $fila['sincro_id'];
                try {
                    if (isset($columnas['sucursal']) && isset($fila['sucursal']) && $fila['sucursal'] !== '' && (int) $fila['sucursal'] !== $sucursal) {
                        throw new \RuntimeException("es de la sucursal {$fila['sucursal']}, no de la {$sucursal}");
                    }

                    $productoVps = $productos[$item['fk']['producto'] ?? ''] ?? null;
                    $inv = $invPorSincro[$item['fk']['inventario'] ?? ''] ?? null;
                    if ($inv && (int) $inv->sucursal !== $sucursal) {
                        throw new \RuntimeException("su inventario es de la sucursal {$inv->sucursal}");
                    }
                    $invId = $inv->id ?? ($productoVps ? ($invPorProducto[$productoVps]->id ?? null) : null);
                    if (!$productoVps || !$invId) {
                        // El producto o su inventario aun no estan en el VPS
                        $resultado['pendientes'][] = $sincro;
                        continue;
                    }

                    $fila['producto'] = $productoVps;
                    $fila['inventario'] = (int) $invId;
                    $fila = $this->limpiar($fila, $columnas);

                    $id = $existentes[$sincro] ?? null;
                    if ($id) {
                        DB::table($tabla)->where('id', $id)->update($fila);
                    } else {
                        $fila['created_at'] = $fila['created_at'] ?? now();
                        $id = DB::table($tabla)->insertGetId($fila);
                    }

                    $resultado['ids'][$sincro] = (int) $id;
                    $resultado['guardados']++;
                } catch (\Throwable $e) {
                    $resultado['errores'][] = "{$tabla} {$sincro}: " . mb_substr($e->getMessage(), 0, 300);
                }
            }
        });
    }

    /** sincro_id => id del VPS para la tabla indicada. */
    private function idsPorSincro(string $tabla, array $sincros): array
    {
        $sincros = array_values(array_unique(array_filter($sincros)));

        return $sincros ? DB::table($tabla)->whereIn('sincro_id', $sincros)->pluck('id', 'sincro_id')->map(fn ($v) => (int) $v)->all() : [];
    }

    private function limpiar(array $fila, array $columnas): array
    {
        unset($fila['id'], $fila['id_vps']);
        foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
            if (isset($fila[$ts]) && ($fila[$ts] === '?' || $fila[$ts] === '')) {
                $fila[$ts] = null;
            }
        }
        $fila = array_intersect_key($fila, $columnas);
        $fila['updated_at'] = $fila['updated_at'] ?? now();

        return $fila;
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
