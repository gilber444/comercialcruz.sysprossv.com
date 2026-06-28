<?php
namespace App\Traits;
use App\Models\AmbienteDestino;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\InvalidacionDte;
use App\Models\resumenDte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
trait GenerarJsonInva
{
    public function generarJsonInva($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $invalidacion = InvalidacionDte::with('tipoInvalidacion')->select('fecAnula', 'horAnula', 'codigoGeneracion', 'ambiente', 'dte', 'motivoAnulacion', 'tipoAnulacion', 'codigoGeneracionR')->find($id);
        $ambiente = AmbienteDestino::find($invalidacion->ambiente);
        $identificacionn = dte::join('tipo_documentos as tdoc', 'tdoc.id', 'dtes.tipoDte')->select('tdoc.codigo as tipoDte', 'dtes.numeroControl', 'dtes.codigoGeneracion', 'dtes.fecEmi as FechaEmi', 'dtes.horEmi as horaEmi', 'dtes.sello')->find($invalidacion->dte);
        $emisorr = dte::join('empresas as e', 'e.id', 'dtes.empresa')->join('sucursales as s', 's.id', 'dtes.sucursal')->join('tipo_establecimientos as testa', 'testa.id', 's.tipo')->select('e.nit', 'e.razon as nombre', 'e.empresa as nombreComercial', 'testa.codigo as tipoEstablecimiento', 's.telefono', 'e.correo')->find($invalidacion->dte);
        if($identificacionn->tipoDte == 1)
        {
            $receptor = dte::join('ventas as v', 'v.id', 'dtes.venta')->join('clientes as c', 'c.id', 'v.cliente')->join('identificacion_receptors as irec', 'irec.id', 'c.idenReceptor')->select('irec.codigo as tipoDocumento', 'c.dui as numDocumento', 'c.nombreCliente as nombre', 'c.telefono', 'c.email as correo')->find($invalidacion->dte);
        }
        else
        {
            $receptor = dte::join('ventas as v', 'v.id', 'dtes.venta')->join('clientes as c', 'c.id', 'v.cliente')->join('identificacion_receptors as irec', 'irec.id', 'c.idenReceptor')->select('irec.codigo as tipoDocumento', 'c.nit as numDocumento', 'c.nombreCliente as nombre', 'c.telefono', 'c.email as correo')->find($invalidacion->dte);
        }
        $detalleDte = resumenDte::where('dte', $invalidacion->dte)->select('resumen_dtes.totalIva' )->first();
        $identificacion = [
            'version' => 2,
            'ambiente'=>$ambiente->codigo,
            'codigoGeneracion' => $invalidacion->codigoGeneracion,
            'fecAnula' => $invalidacion->fecAnula,
            'horAnula' => Carbon::parse($invalidacion->horAnula)->format('H:i:s')
        ];
        $emisor = [
            'nit' => $emisorr->nit,
            'nombre' => $emisorr->nombre,
            'tipoEstablecimiento' => $emisorr->tipoEstablecimiento,
            'nomEstablecimiento' => $emisorr->nombreComercial,
            'codEstableMH' => $emisorr->codEstableMH,
            'codEstable' => NULL,
            'codPuntoVentaMH' => $emisorr->codPuntoVentaMH,
            'codPuntoVenta' => $emisorr->codPuntoVenta,
            'telefono' => $emisorr->telefono,
            'correo' => $emisorr->correo,
        ];
        $documento = [
            'tipoDte' => $identificacionn->tipoDte,
            'codigoGeneracion' => $identificacionn->codigoGeneracion,
            'selloRecibido' => $identificacionn->sello,
            'numeroControl' => $identificacionn->numeroControl,
            'fecEmi' => $identificacionn->FechaEmi,
            'montoIva' => (float)$detalleDte->totalIva,
            'codigoGeneracionR' => $invalidacion->codigoGeneracionR,
            'tipoDocumento' => $receptor->tipoDocumento,
            'numDocumento' => $receptor->numDocumento,
            'nombre' => $receptor->nombre,
            'telefono' => $receptor->telefono,
            'correo' =>  $receptor->correo
        ];
        $motivo = [
            'tipoAnulacion' => (int)$invalidacion->tipoInvalidacion->codigo,
            'motivoAnulacion' => $invalidacion->valor . ' ' . $invalidacion->motivoAnulacion,
            'nombreResponsable' => $emisorr->nombre,
            'tipDocResponsable' => '36',
            'numDocResponsable' => $emisorr->nit,
            'nombreSolicita' => $emisorr->nombre,
            'tipDocSolicita' => '36',
            'numDocSolicita' => $emisorr->nit
        ];
        // Combinar las estructuras en un arreglo asociativo
        $json = [
            'identificacion' => $identificacion,
            'emisor' => $emisor,
            'documento' => $documento,
            'motivo' => $motivo,
        ];
        // Convertir el arreglo asociativo a JSON
        return json_encode($json);
    }
}
