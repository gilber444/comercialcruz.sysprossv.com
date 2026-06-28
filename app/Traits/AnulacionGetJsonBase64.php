<?php
namespace App\Traits;
use App\Models\Anulaciones;
use App\Models\AnulacionesDetalle;
use App\Models\Empresas;
use App\Models\Sucursales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
trait AnulacionGetJsonBase64
{
    public function AnulacionGetJsonBase64($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $sucursal = Sucursales::find($user->sucursal);
        $tipo = 'Anulacion';
        $impresora = '80';
        $details = AnulacionesDetalle::join('productos as p', 'p.id', 'anulaciones_detalles.producto')->join('medidas as m', 'm.id', 'p.medida')->select('p.nombreProducto as product', 'm.unidad as medida', 'anulaciones_detalles.cantidad', 'anulaciones_detalles.precio as costo', 'anulaciones_detalles.total')->where('anulaciones_detalles.anulacion', $id)->get();
        $anulacion = Anulaciones::join('parametros as p', 'p.id', 'anulaciones.caja')->join('cajas as ca', 'ca.id', 'anulaciones.cajas')->join('cortes as c', 'c.id', 'ca.corte')->join('users as s', 's.id', 'ca.cajero')->select('anulaciones.fecha', 'anulaciones.hora', 'anulaciones.venta', 'anulaciones.numero as correlativo', 'p.caja', 'c.corte', DB::raw('ROUND(anulaciones.subtotal, 2) as subtotal'), DB::raw('ROUND(anulaciones.descuento, 2) as descuento'), DB::raw('ROUND(anulaciones.total, 2) as total'), 's.name as cajero', DB::raw('ROUND(anulaciones.efectivo, 2) as efectivo'), DB::raw('ROUND(anulaciones.cambio, 2) as cambio'))->find($id);
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
        $json = "{\"tipo\":\"$tipo\",\"impresora\":\"$impresora\"}|" . json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($anulacion, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);
        $comprimir = gzcompress($json);
        $crypted = base64_encode($comprimir);
        //dd($crypted );
        //dd($json);
        //return $json;
        return $crypted;
    }
}
