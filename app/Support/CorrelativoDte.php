<?php

namespace App\Support;

use App\Models\Empresas;
use App\Models\Sucursales;
use Illuminate\Support\Facades\DB;

/**
 * Evita que control_numeraciones de un numero de control ya usado.
 *
 * Si el contador quedo atrasado (por ejemplo, numeros ya emitidos en enero), el
 * siguiente DTE repite un numero que Hacienda ya tiene y lo rechaza. Esto sube el
 * contador al numero mas alto que ya existe en dtes para esa sucursal, tipo y
 * anio. No toca el POS: lo usan la conversion de ventas fisicas y la reparacion.
 */
class CorrelativoDte
{
    /** Codigo CAT-002 en el numero de control segun el facturador del sistema. */
    private const COD = [1 => '01', 2 => '03', 4 => '05'];

    /** Devuelve el contador que quedo (o null si no habia registro que ajustar). */
    public static function asegurar(int $sucursalId, int $facturador, ?int $anio = null): ?int
    {
        $anio = $anio ?: (int) date('Y');
        $cod = self::COD[$facturador] ?? null;
        $sucursal = Sucursales::find($sucursalId);
        if (!$cod || !$sucursal) {
            return null;
        }
        $empresa = Empresas::find((int) $sucursal->empresa);
        $ambiente = (int) ($empresa->ambiente ?? 1);

        // Numero de control: DTE-01-S001P001-AAAA + 11 digitos (los ultimos 15 son fijos)
        $ultimo = DB::table('dtes')
            ->where('sucursal', $sucursalId)
            ->where('numeroControl', 'like', "DTE-{$cod}-%-{$anio}%")
            ->orderByRaw('SUBSTR(numeroControl, -15) DESC')
            ->value('numeroControl');
        $maximo = $ultimo ? (int) substr($ultimo, -11) : 0;

        return DB::transaction(function () use ($sucursal, $facturador, $ambiente, $anio, $maximo) {
            $registro = DB::table('control_numeraciones')
                ->where('empresa', (int) $sucursal->empresa)
                ->where('sucursal', (int) $sucursal->id)
                ->where('tipoDte', $facturador)
                ->where('ambiente', $ambiente)
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();
            if (!$registro) {
                return null; // nextCorrelativo lo crea leyendo el ultimo DTE
            }
            if ((int) $registro->correlativo < $maximo) {
                DB::table('control_numeraciones')->where('id', $registro->id)
                    ->update(['correlativo' => $maximo, 'updated_at' => now()]);
                return $maximo;
            }

            return (int) $registro->correlativo;
        });
    }
}
