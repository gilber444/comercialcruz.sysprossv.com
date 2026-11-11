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
            if (!$urls || empty($urls->url)) {
                throw new \RuntimeException('No se pudo transmitir la contingencia: falta la URL de la API "Contingencia" para el ambiente ' . ($empresa->ambiente == 1 ? 'Prueba' : 'Produccion') . ' (catalogo apis).');
            }
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
            $cuerpo = (string) $response->getBody();
            $responseData = json_decode($cuerpo, true);
            if (!is_array($responseData)) {
                $responseData = ['estado' => 'ERROR', 'mensaje' => 'Respuesta de Hacienda no valida: ' . trim($cuerpo)];
            }
            $contingencia->estado = mb_substr((string) ($responseData['estado'] ?? 'ERROR'), 0, 50);
            $contingencia->fechaHora = $responseData['fechaHora'] ?? null;
            $contingencia->mensaje = isset($responseData['mensaje']) ? mb_substr((string) $responseData['mensaje'], 0, 200) : null;
            $contingencia->selloRecibido = $responseData['selloRecibido'] ?? null;
            // Hacienda devuelve observaciones como arreglo; la columna es texto
            // y guardar el arreglo tal cual reventaba con "Array to string
            // conversion" despues de que el evento ya habia sido recibido.
            $contingencia->observaciones = $this->textoObservacionesContingencia($responseData['observaciones'] ?? null);
            $contingencia->jsonRespuesta = $cuerpo;
            $contingencia->save();
            //return $responseData['estado'];
            return $contingencia->observaciones;
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $cuerpo = (string) $response->getBody();
            $errorResponseData = json_decode($cuerpo, true);
            if (!is_array($errorResponseData)) {
                $errorResponseData = ['mensaje' => 'Respuesta de Hacienda no valida (HTTP ' . $status_code . '): ' . trim($cuerpo)];
            }
            $contingencia->estado = 'ERROR';
            $contingencia->fechaHora = $errorResponseData['fechaHora'] ?? null;
            $contingencia->mensaje = isset($errorResponseData['mensaje']) ? mb_substr((string) $errorResponseData['mensaje'], 0, 200) : null;
            $contingencia->observaciones = $this->textoObservacionesContingencia($errorResponseData['observaciones'] ?? null);
            $contingencia->jsonRespuesta = $cuerpo;
            $contingencia->save();
            return $errorResponseData['estado'] ?? 'ERROR';
        }
    }

    /** Observaciones de Hacienda (arreglo o texto) como texto para la columna. */
    private function textoObservacionesContingencia($observaciones): ?string
    {
        if ($observaciones === null || $observaciones === '' || $observaciones === []) {
            return null;
        }

        if (is_array($observaciones)) {
            return implode(' | ', array_map(
                fn ($o) => is_scalar($o) ? (string) $o : json_encode($o, JSON_UNESCAPED_UNICODE),
                $observaciones
            ));
        }

        return (string) $observaciones;
    }
}
