<?php
namespace App\Traits;
use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Firmador;
use App\Models\lotedte;
use App\Models\lotedteDetalles;
use App\Traits\Firmador2;
use App\Traits\FirmadorDTE;
use App\Traits\GeneraJsonC;
use App\Traits\GeneraJsonF;
use App\Traits\GenerarToken;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
trait RecepcionLote
{
    use GenerarToken;
    use GeneraJsonC;
    use GeneraJsonF;
    use Firmador2;
    public function RecepcionLote($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $lote = lotedte::find($id);
        $empresa = Empresas::find($lote->empresa);
        try {
            $client = new Client();
            if($empresa->ambiente == 1)
            {
                $urls = Apis::where('nombre', 'Lote')->where('tipo', 'Prueba')->first();
            }
            else
            {
                $urls = Apis::where('nombre', 'Lote')->where('tipo', 'Produccion')->first();
            }
            //$urls = Apis::where('nombre', 'Lote')->where('estado', 'Activo')->first();
            $url = $urls->url;
            $token = $this->generarToken();
            $ambiente = AmbienteDestino::find($empresa->ambiente);
            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' =>  $lote->idEnvio,
                'version' => 1,
                'nitEmisor' => $empresa->nit,
                'documentos' => [] // Inicializamos documentos como un arreglo vacío
            ];
            $detalle = lotedteDetalles::where('lote', $lote->id)->get();
            foreach ($detalle as $deta) {
                $dte = dte::find($deta->dte);
                if($dte->tipoDte == 1)
                {
                    $json = $this->GeneraJsonF($deta->dte);
                }
                else
                {
                    $json = $this->GeneraJsonC($deta->dte);
                }
                $firma = $this->Firmador2($json);
                // Agregamos el ID de la firma al arreglo documentos
                $data['documentos'][] = $firma;
            }
            // Convertimos $data a JSON
            $jsonActualizado = json_encode($data);
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $token,
            ];
            $response = $client->post($url,[
                'headers' => $headers,
                'body' => $jsonActualizado
            ]);
            $status_code = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);
            $lote->estado = $responseData['estado'];
            $lote->codigoLote = $responseData['codigoLote'];
            $lote->codigoMsg = $responseData['codigoMsg'];
            $lote->fhProcesamiento = $responseData['fhProcesamiento'];
            $lote->descripcionMsg = $responseData['descripcionMsg'];
            $lote->jsonRespuesta = $response->getBody();
            $lote->save();
            //return $responseData['estado'];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $errorResponseData = json_decode($response->getBody(), true);
            $lote->estado = $responseData['estado'];
            $lote->codigoMsg = $responseData['codigoMsg'];
            $lote->descripcionMsg = $responseData['descripcionMsg'];
            $lote->jsonRespuesta = $response->getBody();
            $lote->save();
            //return $errorResponseData['estado'];
        }
    }
}
