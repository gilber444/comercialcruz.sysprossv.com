<?php
namespace App\Traits;
use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\Empresas;
use App\Models\InvalidacionDte;
use App\Support\VersionEventoDte;
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
            if (!$urls || empty($urls->url)) {
                throw new \RuntimeException('No se pudo transmitir la invalidacion: falta la URL de la API "Invalidacion" para el ambiente ' . ($empresa->ambiente == 1 ? 'Prueba' : 'Produccion') . ' (catalogo apis).');
            }
            $url = $urls->url;
            $token = $this->generarToken();
            $ambiente = AmbienteDestino::find($empresa->ambiente);
            if (!$ambiente) {
                throw new \RuntimeException('No se pudo transmitir la invalidacion: falta el ambiente de destino id ' . $empresa->ambiente . ' (catalogo ambiente_destinos).');
            }
            // La version del sobre sale del mismo lugar que identificacion.version:
            // primero la que va dentro del documento firmado (asi nunca difieren)
            // y, si no se puede leer, la version vigente del evento (v3).
            $versionEvento = VersionEventoDte::desdeDocumento($inva->docFirmado) ?? VersionEventoDte::invalidacion();
            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                'version' => $versionEvento,
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
            $cuerpo = (string) $response->getBody();
            $responseData = json_decode($cuerpo, true);
            if (!is_array($responseData)) {
                // Sin JSON no hay estado ni sello: nunca cuenta como aceptada.
                return $this->guardarRespuestaInvalidaInva($inva, $cuerpo);
            }
            $inva->estado = $responseData['estado'] ?? 'ERROR';
            $inva->selloRecibido = $responseData['selloRecibido'] ?? null;
            $inva->fhProcesamiento = $responseData['fhProcesamiento'] ?? null;
            $inva->descripcionMsg = $responseData['descripcionMsg'] ?? null;
            $inva->jsonRespuesta = $cuerpo;
            $inva->save();
            return $inva->estado;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $cuerpo = (string) $response->getBody();
            $errorResponseData = json_decode($cuerpo, true);
            if (!is_array($errorResponseData)) {
                return $this->guardarRespuestaInvalidaInva($inva, $cuerpo, $status_code);
            }
            $inva->estado = $errorResponseData['estado'] ?? 'RECHAZADO';
            $inva->descripcionMsg = $errorResponseData['descripcionMsg'] ?? ('HTTP ' . $status_code);
            $inva->jsonRespuesta = $cuerpo;
            $inva->save();
            return $inva->estado;
        }
    }

    /**
     * Respuesta de Hacienda que no es JSON: se guarda como ERROR (la columna
     * jsonRespuesta es json, el texto crudo va envuelto) para que el usuario
     * vea el motivo y pueda reprocesar.
     */
    private function guardarRespuestaInvalidaInva(InvalidacionDte $inva, string $cuerpo, ?int $status = null): string
    {
        $inva->estado = 'ERROR';
        $inva->descripcionMsg = mb_substr('Respuesta de Hacienda no valida' . ($status ? ' (HTTP ' . $status . ')' : '') . ': ' . trim($cuerpo), 0, 250);
        $inva->jsonRespuesta = json_encode(['respuesta' => mb_substr($cuerpo, 0, 2000)], JSON_UNESCAPED_UNICODE);
        $inva->save();

        return 'ERROR';
    }
}
