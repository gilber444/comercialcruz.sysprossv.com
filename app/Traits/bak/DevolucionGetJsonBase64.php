<?php
namespace App\Traits;

use App\Models\Devoluciones;
use App\Models\DevolucionesDetalles;
use App\Models\Empresas;
use App\Models\Sucursales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait DevolucionGetJsonBase64
{
    public function DevolucionGetJsonBase64($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $sucursal = Sucursales::find($user->sucursal);

        $tipo = 'Devolucion';
        $impresora = '80';

        $details = DevolucionesDetalles::join('productos as p', 'p.id', 'devoluciones_detalles.producto')->join('medidas as m', 'm.id', 'p.medida')->select('p.nombreProducto as product', 'm.unidad as medida', 'devoluciones_detalles.cantidad', 'devoluciones_detalles.precio as costo', 'devoluciones_detalles.total')->where('devoluciones_detalles.devolucion', $id)->get();

        $devolucion = Devoluciones::join('parametros as p', 'p.id', 'devoluciones.caja')->join('cajas as ca', 'ca.id', 'devoluciones.cajas')->join('cortes as c', 'c.id', 'ca.corte')->join('users as s', 's.id', 'ca.cajero')->select('devoluciones.fecha', 'devoluciones.hora', 'devoluciones.venta', 'devoluciones.numero as correlativo', 'p.caja', 'c.corte', DB::raw('ROUND(devoluciones.subtotal, 2) as subtotal'), DB::raw('ROUND(devoluciones.descuento, 2) as descuento'), DB::raw('ROUND(devoluciones.total, 2) as total'), 's.name as cajero', DB::raw('ROUND(devoluciones.efectivo, 2) as efectivo'), DB::raw('ROUND(devoluciones.cambio, 2) as cambio'))->find($id);

        $company = Sucursales::with(['Rempresa.RmensajeriaTickets'])
            ->where('id', $sucursal->id)
            ->first();

        $datosEmpresa = [
            'empresa' => $company->Rempresa->empresa,
            'registro' => $company->Rempresa->registro,
            'giro' => $company->Rempresa->giro,
            'nit' => $company->Rempresa->nit,
            'direccion' => $company->direccion,
            'razon' => $company->Rempresa->razon,
            'telefono' => $company->telefono,
            'correo' => $company->Rempresa->correo,

            ///mensajeria
            'lema' => $company->Rempresa->RmensajeriaTickets->lema,
            'mensaje' => $company->Rempresa->RmensajeriaTickets->mensaje,
            'aviso' => $company->Rempresa->RmensajeriaTickets->aviso,
            'notificacion' => $company->Rempresa->RmensajeriaTickets->notificacion,
        ];

        //convertir a json
        $json = "{\"tipo\":\"$tipo\",\"impresora\":\"$impresora\"}|" . json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($devolucion, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);

        $comprimir = gzcompress($json);
        $crypted = base64_encode($comprimir);

        //dd($crypted );
        //dd($json);
        //return $json;
        return $crypted;
    }
}
