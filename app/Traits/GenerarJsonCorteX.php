<?php
namespace App\Traits;
use App\Models\Aperturas;
use App\Models\Arqueos;
use App\Models\Sucursales;
use Illuminate\Support\Facades\DB;
trait GenerarJsonCorteX
{
    public function GenerarJsonCorteX($id)
    {
        $empresa = session('empresa');
        $sucursal = session('sucursal');
        $tipo = 'CorteX';
        $impresora = '80';
        $details = Arqueos::where('id', $id)
            ->select(
                DB::raw('ROUND(numero, 2) as numero'),
                DB::raw('ROUND(efectivo, 2) as efectivo'),
                DB::raw('ROUND(tarjeta, 2) as tarjeta'),
                DB::raw('ROUND(cheque, 2) as cheque'),
                DB::raw('ROUND(credito, 2) as credito'),
                DB::raw('ROUND(subtotalPagos, 2) as subtotalPagos'),
                DB::raw('ROUND(devoluciones, 2) as devoluciones'),
                DB::raw('ROUND(anulaciones, 2) as anulaciones'),
                DB::raw('ROUND(percepcion, 2) as percepcion'),
                DB::raw('ROUND(remesas, 2) as remesas'),
                DB::raw('ROUND(sumaTotales, 2) as sumaTotales'),
                DB::raw('ROUND(ticketDesde, 2) as ticketDesde'),
                DB::raw('ROUND(ticketHasta, 2) as ticketHasta'),
                DB::raw('ROUND(gravadosT, 2) as gravadosT'),
                DB::raw('ROUND(ivaT, 2) as ivaT'),
                DB::raw('ROUND(subT, 2) as subT'),
                DB::raw('ROUND(totalT, 2) as totalT'),
                'consumidorDesde',
                'consumidorHasta',
                DB::raw('ROUND(gravadosCon, 2) as gravadosCon'),
                DB::raw('ROUND(ivaCon, 2) as ivaCon'),
                DB::raw('ROUND(subCon, 2) as subCon'),
                DB::raw('ROUND(totalCon, 2) as totalCon'),
                'CreDesde',
                'CreHasta',
                DB::raw('ROUND(gravadosCre, 2) as gravadosCre'),
                DB::raw('ROUND(ivaCre, 2) as ivaCre'),
                DB::raw('ROUND(subCre, 2) as subCre'),
                DB::raw('ROUND(totalCre, 2) as totalCre'),
                'dteDesde',
                'dteHasta',
                DB::raw('ROUND(gravadosDTE, 2) as gravadosDTE'),
                DB::raw('ROUND(ivaDTE, 2) as ivaDTE'),
                DB::raw('ROUND(subDTE, 2) as subDTE'),
                DB::raw('ROUND(totalDTE, 2) as totalDTE'),
                'creditosDesde',
                'creditosHasta',
                DB::raw('ROUND(gravadosCredi, 2) as gravadosCredi'),
                DB::raw('ROUND(ivaCredi, 2) as ivaCredi'),
                DB::raw('ROUND(subCredi, 2) as subCredi'),
                DB::raw('ROUND(totalCredi, 2) as totalCredi'),
                DB::raw('ROUND(totalGeneral, 2) as totalGeneral'),
                DB::raw('ROUND(ivaGeneral, 2) as ivaGeneral'),
                DB::raw('ROUND(subGeneral, 2) as subGeneral'),
                DB::raw('ROUND(totalPercepcion, 2) as totalPercepcion'),
                DB::raw('ROUND(totalGlobal, 2) as totalGlobal'),
                DB::raw('ROUND(totalEfectivo, 2) as totalEfectivo'),
                DB::raw('ROUND(diferencia, 2) as diferencia'),
            )
            ->get();
        $apertura = Arqueos::join('empresas as e', 'e.id', 'arqueos.empresa')->join('sucursales as s', 'e.id', 'arqueos.empresa')->join('parametros as p', 'p.id', 'arqueos.caja')->select('arqueos.fecha as fecha', 'arqueos.hora as hora', 'p.caja', 'arqueos.sucursal')->find($id);
        //$company = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->select('e.empresa', 'e.registro', 'e.giro', 'e.nit', 'sucursales.direccion', 'e.razon')->find($sucursal);
        //$json = "{\"tipo\":\"$tipo\"}|" . $details->toJson() . '|' . $apertura->toJson() . '|' . $company->toJson();
        //$crypted = base64_encode(gzdeflate($json));
        $company = Sucursales::with(['Rempresa.RmensajeriaTickets'])
        ->where('id', $apertura->sucursal)
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
        //$json = "{\"tipo\":\"$tipo\"}|" . $details->toJson() . '|' . $apertura->toJson() . '|' . $company->toJson();
        //$crypted = base64_encode(gzdeflate($json));
        //dd($json);
         // Generar el JSON asegurando que `apertura` no sea null
         $json = "{\"tipo\":\"$tipo\",\"impresora\":\"$impresora\"}|" .
         json_encode($details, JSON_UNESCAPED_UNICODE) . '|' .
         json_encode($apertura, JSON_UNESCAPED_UNICODE) . '|' .
         json_encode($datosEmpresa, JSON_UNESCAPED_UNICODE);
        // Codificar en JSON, comprimir y codificar en Base64
        $comprimir = gzcompress($json);
        $crypted = base64_encode($comprimir);
        //dd($crypted );
        //dd($json);
        //return $json;
        return $crypted;
    }
}
