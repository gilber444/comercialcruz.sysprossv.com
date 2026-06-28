<?php
namespace App\Traits;
use App\Models\AmbienteDestino;
use App\Models\ContingenciaDte;
use App\Models\ContingenciadteDetalles;
use Illuminate\Support\Facades\Auth;
trait GenerarJsonContingencia
{
    public function GenerarJsonContingencia($id)
    {
        $user = Auth::user();
        $contingencia = ContingenciaDte::with('ambienteDestino')->with(['sucursal' => function($query) {
            $query->with('tipoEstable');
        }])->with('empresas')->with('tipoContin')->find($id);
        $detalleContin = ContingenciadteDetalles::with(['dtes' => function($query){ $query->with('RtipoDte');}])->where('contingencia', $contingencia->id)->get();
        $identificacion = [
            'version' => $contingencia->version,
            'ambiente' => $contingencia->ambienteDestino->codigo,
            'codigoGeneracion' => $contingencia->codigoGeneracion,
            'fTransmision' => $contingencia->fTransmision,
            'hTransmision' => $contingencia->hTransmision,
        ];
        $emisor = [
            'nit' => $contingencia->empresas->nit,
            'nombre' => $contingencia->empresas->empresa,
            'nombreResponsable' => $contingencia->empresas->responsable,
            'tipoDocResponsable' => "36",
            'numeroDocResponsable' => $contingencia->empresas->nit,
            'tipoEstablecimiento' => $contingencia->sucursal->tipoEstable->codigo,
            'codEstableMH' => NULL,
            'codPuntoVenta' =>  NULL,
            'telefono' => $contingencia->sucursal->telefono,
            'correo' => $contingencia->empresas->correo
        ];
        $detalleDTE = [];
        foreach ($detalleContin as $d) {
            $detalleDTE[] = [
                'noItem' => $d->noItem,
                'codigoGeneracion' => $d->dtes->codigoGeneracion,
                'tipoDoc' => $d->dtes->RtipoDte->codigo
            ];
        }
        $motivo = [
            'fInicio' => $contingencia->fInicio,
            'fFin' => $contingencia->fFin,
            'hInicio' => $contingencia->hInicio,
            'hFin' => $contingencia->hFin,
            'tipoContingencia' => (int)$contingencia->tipoContin->codigo,
            'motivoContingencia' => $contingencia->motivoContingencia ?? $contingencia->tipoContin->valor,
        ];
        $json = [
            'identificacion' => $identificacion,
            'emisor' => $emisor,
            'detalleDTE' => $detalleDTE,
            'motivo' => $motivo,
        ];
        $contingencia->json = json_encode($json);
        $contingencia->save();
        return json_encode($json);
    }
}
