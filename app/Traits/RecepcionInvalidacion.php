<?php
namespace App\Traits;
use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\Empresas;
use App\Models\InvalidacionDte;
use App\Models\ModeloFacturacion;
use App\Models\TipoDocumento;
use App\Models\TipoTransmision;
use App\Traits\Firmador2;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
trait RecepcionInvalidacion
{
    use GenerarJsonInva;
    use GenerarToken;
    use Firmador;
    use Firmador2;
    public function RecepcionInvalidacion($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $inva = InvalidacionDte::find($id);
        try {
            $client = new Client();
            if($empresa->ambiente == 1)
            {
                $urls = Apis::where('nombre', 'Invalidacion')->where('tipo', 'Prueba')->first();
            }
            else
            {
                $urls = Apis::where('nombre', 'Invalidacion')->where('tipo', 'Produccion')->first();
            }
            //$urls = Apis::where('nombre', 'Invalidacion')->where('estado', 'Activo')->first();
            $url = $urls->url;
            $token = $this->generarToken();
            $ambiente = AmbienteDestino::find($empresa->ambiente);
            $tipoDte = TipoDocumento::where('status', 'Activo')->where('valor', 'FACTURA')->first();
            $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
            $tipoOpera =TipoTransmision::where('status', 'Activo')->first();
            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                'version' => 2,
                'documento' => $inva->docFirmado
            ];
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
            $inva->estado = $responseData['estado'];
            $inva->selloRecibido = $responseData['selloRecibido'];
            $inva->fhProcesamiento = $responseData['fhProcesamiento'];
            $inva->descripcionMsg = $responseData['descripcionMsg'];
            $inva->jsonRespuesta = $response->getBody();
            $inva->save();
            return $responseData['estado'];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $errorResponseData = json_decode($response->getBody(), true);
            $inva->estado = $errorResponseData['estado'];
            $inva->descripcionMsg = $errorResponseData['descripcionMsg'];
            $inva->jsonRespuesta = $response->getBody();
            $inva->save();
            return $errorResponseData['estado'];
        }
    }
}
