<?php
namespace App\Traits;

use App\Models\Ajustes;
use App\Models\AjustesDetalles;
use App\Models\Sucursales;

trait GenerarJsonAjuste
{
    public function GenerarJsonAjuste($id)
    {
        $tipo = 'Ajuste';

        $ajuste = Ajustes::with(['Rsucursal:id,nombre', 'Ruser:id,name'])->find($id);

        $details = AjustesDetalles::with(['Rproductos:id,nombreProducto', 'Rmedida:id,unidad'])
            ->select('producto', 'cantidad', 'costo', 'total', 'medida')
            ->where('ajuste', $id)
            ->get();

        $customDetails = $details->map(function ($item) {
            return [
                'product' => $item->Rproductos->nombreProducto,
                'medida' => $item->Rmedida->unidad,
                'cantidad' => $item->cantidad,
                'costo' => $item->costo,
                'total' => $item->total,
            ];
        });

        $venta = [
            'fecha' => $ajuste->fecha,
            'hora' => $ajuste->created_at->format('H:i:s'),
            'caja' => $ajuste->Rsucursal->nombre,
            'correlativo' => $ajuste->id,
            'corte' => $ajuste->tipo,
            'total' => number_format($details->sum('total'), 2),
            'cajero' => $ajuste->Ruser->name,
            'efectivo' => $ajuste->detalle
        ];

        $company = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')
            ->select('e.empresa', 'e.registro', 'e.giro', 'e.nit', 'sucursales.direccion', 'e.razon')
            ->find($ajuste->sucursal);

        $data = [
            'tipo' => $tipo,
        ];

        $ventas = json_encode($venta);

        $json = "{\"tipo\":\"$tipo\"}|" . $customDetails->toJson() . '|' . $ventas . '|' . $company->toJson();

        //dd($json);
        $crypted = base64_encode(gzdeflate($json));
        return $crypted;
    }
}
