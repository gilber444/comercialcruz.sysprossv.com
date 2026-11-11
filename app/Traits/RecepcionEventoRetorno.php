<?php

namespace App\Traits;

use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\Empresas;
use App\Models\EventoRetorno;
use App\Services\GeneradorEventoRetorno;
use App\Support\VersionEventoDte;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Transmision del Evento de Retorno a Hacienda.
 *
 * Sigue el mismo camino que RecepcionInvalidacion: se arma el JSON, se firma con
 * Firmador2 y se envia el documento firmado dentro del sobre.
 *
 * La URL NO esta fija en el codigo: se lee de la tabla Apis con el nombre
 * "EventoRetorno" (registros Prueba y Produccion), igual que los demas
 * servicios. Si no existe el registro se avisa en vez de fallar en silencio.
 */
trait RecepcionEventoRetorno
{
    use GenerarToken;
    use Firmador;
    use Firmador2;

    public function RecepcionEventoRetorno($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $evento = EventoRetorno::findOrFail($id);

        $url = $this->urlEventoRetorno($empresa);

        try {
            $client = new Client();
            $token = $this->generarToken();
            $ambiente = AmbienteDestino::find($empresa->ambiente);

            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                // La version del sobre sale del documento firmado: si el esquema
                // vigente cambio entre generar y transmitir, Hacienda rechaza
                // por #/identificacion/version (mismo criterio que la invalidacion).
                'version' => VersionEventoDte::desdeDocumento($evento->docFirmado)
                    ?? app(GeneradorEventoRetorno::class)->version(),
                'documento' => $evento->docFirmado,
            ];

            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $token,
                ],
                'body' => json_encode($data),
            ]);

            $respuesta = json_decode($response->getBody(), true);

            $evento->estado = $respuesta['estado'] ?? 'DESCONOCIDO';
            $evento->selloRecibido = $respuesta['selloRecibido'] ?? null;
            $evento->fhProcesamiento = $respuesta['fhProcesamiento'] ?? null;
            $evento->descripcionMsg = $this->mensaje($respuesta);
            $evento->jsonRespuesta = (string) $response->getBody();
            $evento->save();

            return $evento->estado;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $cuerpo = (string) $e->getResponse()->getBody();
            $respuesta = json_decode($cuerpo, true) ?: [];

            $evento->estado = $respuesta['estado'] ?? 'RECHAZADO';
            $evento->descripcionMsg = $this->mensaje($respuesta);
            $evento->jsonRespuesta = $cuerpo;
            $evento->save();

            return $evento->estado;
        } catch (\Throwable $e) {
            // Un fallo de red no deja el evento en un estado enganoso.
            $evento->estado = 'Error de envio';
            $evento->descripcionMsg = mb_substr($e->getMessage(), 0, 255);
            $evento->save();

            return 'Error de envio';
        }
    }

    /**
     * Junta descripcionMsg con las observaciones, que es donde Hacienda explica
     * el motivo real del rechazo.
     */
    private function mensaje(array $respuesta): ?string
    {
        $partes = array_filter([
            $respuesta['descripcionMsg'] ?? null,
            !empty($respuesta['observaciones']) ? implode(' | ', (array) $respuesta['observaciones']) : null,
        ]);

        return $partes ? mb_substr(implode(' - ', $partes), 0, 255) : null;
    }

    /**
     * Los eventos comparten el mismo endpoint que la invalidacion. Se prefiere un
     * registro propio "EventoRetorno" por si Hacienda los separa mas adelante;
     * si no existe, se usa el de "Invalidacion" que ya esta configurado.
     */
    private function urlEventoRetorno(?Empresas $empresa): string
    {
        $tipo = ((int) optional($empresa)->ambiente === 1) ? 'Prueba' : 'Produccion';

        foreach (['EventoRetorno', 'Invalidacion'] as $nombre) {
            $api = Apis::where('nombre', $nombre)->where('tipo', $tipo)->first()
                ?: Apis::where('nombre', $nombre)->first();

            if ($api && trim((string) $api->url) !== '') {
                return $api->url;
            }
        }

        throw new RuntimeException(
            'No esta configurada la URL del servicio de eventos. Revise en '
            . 'Transmision > Apis el registro "Invalidacion" (tipo ' . $tipo . '), '
            . 'o cree uno llamado "EventoRetorno".'
        );
    }
}
