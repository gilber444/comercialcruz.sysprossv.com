<?php
namespace App\Traits;
use App\Models\Caja;
use App\Models\dte;
use App\Models\Sucursales;
use App\Models\VentasDetalles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
trait GenerarTicketgetJsonBase64
{
    public function GenerarTicketgetJsonBase64($id)
    {
        $user = Auth::user();
        $empresa = $user->empresa;
        $tipo = 'Tikect';
        $caja = Caja::find($id);
        $details = VentasDetalles::with(['Rproductos'])
            ->where('venta', $caja->venta)
            ->get()
            ->map(function ($item) {
                return [
                    'product' => $item->name,
                    'medida' => $item->unidad,
                    'cantidad' => $item->cantidad,
                    'costo' => $item->precio,
                    'total' => $item->total,
                ];
            });
        // 🔹 Obtener datos de la venta
        $venta = Caja::with(['Rventas', 'Rcajas', 'Rcortes', 'Rcajeros'])->find($id);
        $dte = dte::where('venta', $venta->venta)->first();
        if($dte)
        {
            $qrCodeText = "https://supermercadojosue.sysprossv.com/report/pdf/".$dte->id;
        }
        else
        {
            $qrCodeText = "https://supermercadojosue.sysprossv.com/report/pdf";
        }
        $ventaData = [
            'fecha' => $venta->fecha,
            'hora' => $venta->hora,
            'correlativo' => $venta->correlativo,
            'caja' => $venta->Rcajas->caja,
            'corte' => $venta->Rcortes->corte,
            'subtotal' => number_format($venta->subtotal, 2),
            'descuento' => number_format($venta->descuento, 2),
            'total' => number_format($venta->total, 2),
            'cajero' => $venta->Rcajeros->name,
            'efectivo' => number_format($venta->efectivo, 2),
            'cambio' => number_format($venta->cambio, 2),
            'resolucion' => $venta->Rcajas->tresolucion,
            'serie' => $venta->Rcajas->tserie,
            'numero'=> $venta->numero,
            'tiquet' => $venta->codigo,
            'solicitante' => $venta->sello,
            'sucursal' => $qrCodeText
        ];
        // 🔹 Obtener datos de la empresa
        $company = Sucursales::with(['Rempresa.RmensajeriaTickets'])
        ->where('id', $caja->sucursal)
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
        // 🔹 Construir JSON final
        $jsonData = [
            'tipo' => $tipo,
            'details' => $details,
            'venta' => $ventaData,
            'company' => $datosEmpresa
        ];
        //convertir a json
        //$json = 'Tipo:'.json_encode($tipo, JSON_UNESCAPED_UNICODE) . '|' .
        //json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
        //json_encode($ventaData, JSON_UNESCAPED_UNICODE) . '|' .
        //json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);
        $json = "{\"tipo\":\"$tipo\"}|" .
        json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($ventaData, JSON_UNESCAPED_UNICODE) . '|' .
        json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);
        $crypted = base64_encode(gzdeflate($json));
        //$comprimir = gzcompress($json);
        //$crypted = base64_encode($comprimir);
        //dd($crypted);
        //return $json;
        return $crypted;
    }
}
