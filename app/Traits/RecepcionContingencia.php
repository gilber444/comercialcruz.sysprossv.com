<?php
namespace App\Traits;
use App\Models\Apis;
use GuzzleHttp\Client;
use App\Models\Empresas;
use App\Traits\GenerarToken;
use App\Models\ContingenciaDte;
use Illuminate\Support\Facades\Auth;
trait RecepcionContingencia
{
    use GenerarToken;
    public function RecepcionContingencia($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $contingencia = ContingenciaDte::find($id);
        try
        {
            $client = new Client();
            if($empresa->ambiente == 1)
            {
                $urls = Apis::where('nombre', 'Contingencia')->where('tipo', 'Prueba')->first();
            }
            else
            {
                $urls = Apis::where('nombre', 'Contingencia')->where('tipo', 'Produccion')->first();
            }
            //$urls = Apis::where('nombre', 'Contingencia')->where('estado', 'Activo')->first();
            $url = $urls->url;
            $token = $this->generarToken();
            //$empresa = Empresas::find(session('empresa'));
            $data = [
                'nit' => $empresa->nit,
                'documento' => $contingencia->jsonFirmado
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
            $contingencia->estado = $responseData['estado'];
            $contingencia->fechaHora = $responseData['fechaHora'];
            $contingencia->mensaje = $responseData['mensaje'];
            $contingencia->selloRecibido = $responseData['selloRecibido'];
            $contingencia->observaciones = $responseData['observaciones'];
            $contingencia->jsonRespuesta = $response->getBody();
            $contingencia->save();
            //return $responseData['estado'];
            return $responseData['observaciones'];
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $errorResponseData = json_decode($response->getBody(), true);
            $contingencia->estado = 'ERROR';
            $contingencia->fechaHora = $errorResponseData['fechaHora'];
            $contingencia->mensaje = $errorResponseData['mensaje'];
            $contingencia->observaciones = $errorResponseData['observaciones'];
            $contingencia->jsonRespuesta = $response->getBody();
            $contingencia->save();
            return $errorResponseData['estado'];
        }
    }
}
