<?php

namespace App\Services;

use App\Helpers\SistemaHelper;
use App\Models\Inventarios;
use App\Models\Sucursales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Obtiene el registro de inventario de un producto en una sucursal.
 *
 * Reglas:
 *  - En el VPS (APP_MODO=vps) se puede crear el inventario (con empresa de la sucursal).
 *  - En una PC local NUNCA se crea inventario: si falta, se jala del VPS
 *    (mismo sincro_id) para no generar registros que choquen al sincronizar.
 */
class InventarioSucursalService
{
    public const CONN_LOCAL = 'localmysql';
    public const CONN_VPS   = 'vpsmysql';

    protected static array $columnas = [];

    public static function obtener(int $productoId, int $sucursalId, bool $lock = false): Inventarios
    {
        $inventario = static::buscarLocal($productoId, $sucursalId, $lock);
        if ($inventario) {
            return $inventario;
        }

        if (SistemaHelper::estaEnVps()) {
            return static::crearEnVps($productoId, $sucursalId);
        }

        $resultado = static::jalarDelVps($productoId, $sucursalId);
        if ($resultado === 'sin_vps') {
            throw new \RuntimeException("El producto #{$productoId} no tiene inventario en la sucursal #{$sucursalId} y no hay conexión con el VPS para descargarlo. Intente de nuevo cuando haya conexión.");
        }
        if ($resultado === 'no_existe') {
            throw new \RuntimeException("El producto #{$productoId} no tiene inventario en la sucursal #{$sucursalId} en el VPS. El inventario debe crearse desde el VPS (al crear el producto) y luego sincronizarse.");
        }

        $inventario = static::buscarLocal($productoId, $sucursalId, $lock);
        if (!$inventario) {
            throw new \RuntimeException("No se pudo obtener el inventario del producto #{$productoId} en la sucursal #{$sucursalId}.");
        }

        return $inventario;
    }

    /**
     * Copia a la BD local el inventario del VPS (producto + sucursal).
     * Devuelve: 'existe' (ya estaba local), 'insertado', 'no_existe' (tampoco está en VPS) o 'sin_vps'.
     */
    public static function jalarDelVps(int $productoId, int $sucursalId, ?object $filaVps = null): string
    {
        $local = DB::connection(static::CONN_LOCAL);

        if ($local->table('inventarios')->where('producto', $productoId)->where('sucursal', $sucursalId)->exists()) {
            return 'existe';
        }

        if (!$filaVps) {
            try {
                $filaVps = DB::connection(static::CONN_VPS)->table('inventarios')
                    ->where('producto', $productoId)
                    ->where('sucursal', $sucursalId)
                    ->orderBy('id')
                    ->first();
            } catch (\Throwable $e) {
                Log::warning('InventarioSucursalService: VPS no disponible', ['error' => $e->getMessage()]);
                return 'sin_vps';
            }
        }

        if (!$filaVps) {
            return 'no_existe';
        }

        $columnasLocal = static::columnas(static::CONN_LOCAL);
        $fila = array_intersect_key((array) $filaVps, array_flip($columnasLocal));
        unset($fila['id']);

        if (in_array('id_vps', $columnasLocal, true)) {
            $fila['id_vps'] = $filaVps->id;
        }
        if (in_array('empresa', $columnasLocal, true) && empty($fila['empresa'])) {
            $fila['empresa'] = Sucursales::on(static::CONN_LOCAL)->whereKey($sucursalId)->value('empresa');
        }

        // Si el sincro_id ya está en otra fila local, no duplicarlo
        if (!empty($fila['sincro_id'])
            && $local->table('inventarios')->where('sincro_id', $fila['sincro_id'])->exists()) {
            Log::warning('InventarioSucursalService: sincro_id duplicado, se omite', [
                'producto' => $productoId, 'sucursal' => $sucursalId, 'sincro_id' => $fila['sincro_id'],
            ]);
            return 'no_existe';
        }

        $local->table('inventarios')->insert($fila);

        Log::info('Inventario descargado del VPS', [
            'producto' => $productoId, 'sucursal' => $sucursalId, 'id_vps' => $filaVps->id,
        ]);

        return 'insertado';
    }

    protected static function buscarLocal(int $productoId, int $sucursalId, bool $lock): ?Inventarios
    {
        $query = Inventarios::where('producto', $productoId)->where('sucursal', $sucursalId);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

    protected static function crearEnVps(int $productoId, int $sucursalId): Inventarios
    {
        $empresa = Sucursales::whereKey($sucursalId)->value('empresa');
        if (empty($empresa)) {
            throw new \RuntimeException("La sucursal #{$sucursalId} no existe o no tiene empresa asignada.");
        }

        return Inventarios::create([
            'producto'   => $productoId,
            'empresa'    => $empresa,
            'sucursal'   => $sucursalId,
            'existencia' => 0,
        ]);
    }

    public static function columnas(string $conexion): array
    {
        return static::$columnas[$conexion] ??= Schema::connection($conexion)->getColumnListing('inventarios');
    }
}
