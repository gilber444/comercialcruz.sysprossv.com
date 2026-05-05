<?php
namespace App\Traits;

use App\Models\Caja;
use App\Models\Empresas;
use App\Models\Remesas;
use App\Models\Sucursales;
use Illuminate\Support\Facades\Auth;

trait RemesagetJsonBase64
{
    public function RemesagetJsonBase64($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $sucursal = Sucursales::find($user->sucursal);

        $tipo = 'Remesa';
        $impresora = '80';

        $details = Remesas::selectRaw("fecha, hora, numero, FORMAT(monto, 2) as monto, estado, concepto")
        ->where('id', $id)
        ->get();

        $r = Remesas::find($id);

        $encabezado = Caja::join('parametros as p', 'p.id', 'cajas.caja')
            ->join('cortes as c', 'c.id', 'cajas.corte')
            ->join('users as s', 's.id', 'cajas.cajero')
            ->select('p.caja', 'c.corte', 's.name as cajero')
            //->where('cajas.fecha', date('Y-m-d'))
            ->where('cajas.cajero', $r->cajero)
            ->first();

        /*$company = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->select('e.empresa', 'e.registro', 'e.giro', 'e.nit', 'sucursales.direccion', 'e.razon', 'sucursales.telefono', 'e.correo')->find($sucursal);

        $data = [
            'tipo' => $tipo,
        ];
        //convertir a json
        $json = "{\"tipo\":\"$tipo\"}|" . $details->toJson() . '|' . $encabezado->toJson() . '|' . $company->toJson(); */

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
        json_encode($encabezado, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);

        $comprimir = gzcompress($json);
        $crypted = base64_encode($comprimir);

        //dd($crypted );
        //dd($json);
        //return $json;
        return $crypted;
    }
}
