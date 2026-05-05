<?php

namespace App\Traits;

use App\Models\Caja;
use App\Models\dte;
use App\Models\Sucursales;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait TraitTiketsRe
{
    public function TraitTiketsRe($id)
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        $tipo = 'Reimpresion';
        $impresora = '80';

        $caja = Caja::find($id);

        $details = VentasDetalles::with(['Rproductos'])
            ->where('venta', $caja->venta)
            ->get()
            ->map(function ($item) {
                return [
                    'product' => trim(str_replace(' ', ' ', $item->Rproductos->nombreProducto)),
                    'medida' => $item->unidad,
                    'cantidad' => $item->cantidad,
                    'costo' => $item->precio,
                    'total' => $item->total,
                ];
            });

        // 🔹 Obtener datos de la venta
        $venta = Caja::with(['Rventas.Rclientes', 'Rcajas', 'Rcortes', 'Rcajeros'])->where('venta', $caja->venta)->first();

        //$cajero = Ventas::join('users as u', 'u.codigo', 'ventas.codigoVendedor')->where('ventas.id', $venta->venta)->first();

        $dte = dte::where('venta', $caja->venta)->first();

        if ($dte) {
            $qrCodeText = "https://supermercadojosue.sysprossv.com/report/pdf/" . $dte->codigoGeneracion;
        } else {
            $qrCodeText = "https://supermercadojosue.sysprossv.com/report/pdf";
        }

        $dtes = ($venta->codigo === null) ? 'No' : 'Si';



        $ventaData = [
            'fecha' => $venta->fecha,
            'hora' => $venta->hora,
            'correlativo' => $venta->correlativo,
            'caja' => $venta->Rcajas->caja,
            'dte' => $dtes,
            'corte' => $venta->Rcortes->corte,
            'subtotal' => number_format($venta->subtotal, 2),
            'descuento' => number_format($venta->descuento, 2),
            'total' => number_format($venta->total, 2),
            'iva' => number_format($venta->iva, 2),
            'cajero' => $venta->Rcajeros->name,
            //'vendedor' => $cajero->name,
            'efectivo' => number_format($venta->efectivo, 2),
            'cambio' => number_format($venta->cambio, 2),
            'resolucion' => $venta->Rcajas->tresolucion,
            'serie' => $venta->Rcajas->tserie,
            'numero' => $venta->numero,
            'codigo' => $venta->codigo,
            'sello' => $venta->sello,
            'url' => $qrCodeText,
            'facturador' => $venta->facturador,
            'cliente' => $venta->Rventas->Rclientes->nombreCliente,
            'direccion' => $venta->Rventas->Rclientes->direccion,
            'nit' => $venta->Rventas->Rclientes->nit,
            'dui' => $venta->Rventas->Rclientes->dui,
            'registro' => $venta->Rventas->Rclientes->registro,
            'telefono' => $venta->Rventas->Rclientes->telefono,
            'correo' => $venta->Rventas->Rclientes->email,
            'giro' => $venta->Rventas->Rclientes->desActividad,
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
            'impresora' => $impresora,
            'details' => $details,
            'venta' => $ventaData,
            'company' => $datosEmpresa
        ];
        //convertir a json
        //$json = 'Tipo:'.json_encode($tipo, JSON_UNESCAPED_UNICODE) . '|' .
        //json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
        //json_encode($ventaData, JSON_UNESCAPED_UNICODE) . '|' .
        //json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);
        $json = "{\"tipo\":\"$tipo\",\"impresora\":\"$impresora\"}|" .
            json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
            json_encode($ventaData, JSON_UNESCAPED_UNICODE) . '|' .
            json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);


        $comprimir = gzcompress($json);
        $crypted = base64_encode($comprimir);

        //dd($crypted );
        //dd($json);
        //return $json;
        return $crypted;
    }
}
