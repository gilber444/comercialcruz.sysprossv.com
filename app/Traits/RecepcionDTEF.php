<?php

namespace App\Traits;

use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\Caja;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Firmador;
use App\Models\RecepcionDte;
use App\Models\TipoDocumento;
use App\Models\Ventas;
use App\Traits\enviarCorreoDTE;
use App\Traits\FirmadorDTE;
use App\Traits\FirmadorLocal;
use App\Traits\GeneraJsonF;
use App\Traits\GenerarToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait RecepcionDTEF
{
    use GeneraJsonF;
    use FirmadorDTE;
    use GenerarToken;
    use enviarCorreoDTE;
    use FirmadorLocal;

    /**
     * Envía un DTE de Factura a recepción de Hacienda.
     * Si el DTE aún no está firmado, lo firma antes de enviar.
     *
     * @param int|string $id ID del DTE.
     * @throws \Throwable Si ocurre un error no manejado por Hacienda.
     */
    public function RecepcionDTEF($id)
    {
        try {
            $empresa = Empresas::find(1);

            if (!$empresa) {
                throw new \Exception('No se encontró la configuración de la empresa.');
            }

            $dte = dte::find($id);

            if (!$dte) {
                throw new \Exception("No se encontró el DTE con ID {$id}.");
            }

            $firma = $this->obtenerOFirmarDTE($id, $dte);

            if (!$firma) {
                throw new \Exception("No se pudo obtener la firma del DTE {$id}.");
            }

            $client = new Client();

            if ($empresa->ambiente == 1) {
                $urls = Apis::where('nombre', 'Recepcion')->where('tipo', 'Prueba')->first();
            } else {
                $urls = Apis::where('nombre', 'Recepcion')->where('tipo', 'Produccion')->first();
            }

            if (!$urls || empty($urls->url)) {
                throw new \Exception('No está configurada la URL de recepción de Hacienda.');
            }

            $url = $urls->url;

            $ambiente = AmbienteDestino::find($empresa->ambiente);
            // Por codigo del CAT-002, no por texto: el importador de catalogos
            // reescribe el valor publicado por Hacienda y esta consulta, que
            // alimenta el sobre de recepcion, devolveria null en plena transmision.
            $tipoDte = TipoDocumento::where('status', 'Activo')->whereIn('codigo', ['01', '1'])->first();

            if (!$ambiente || !$tipoDte) {
                throw new \Exception('Faltan configuraciones de ambiente o tipo de DTE.');
            }

            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                'version' => \App\Services\GeneradorDteV2Service::versionEnvio('01', $firma, 1),
                'tipoDte' => $tipoDte->codigo,
                'documento' => $firma,
                'codigoGeneracion' => $dte->codigoGeneracion,
            ];

            $jsonActualizado = json_encode($data);
            $tocken = $this->GenerarToken();

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $tocken,
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $jsonActualizado,
                'timeout' => 60,
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (!is_array($responseData)) {
                throw new \Exception('La respuesta de Hacienda no es un JSON válido.');
            }

            $this->guardarRespuestaExitosaF($id, $dte, $responseData);

        } catch (ClientException $e) {
            $this->guardarRespuestaRechazadaF($id, $e);
            return;
        } catch (RequestException $e) {
            $this->guardarErrorRecepcionF($id, 'Error de conexión con Hacienda: ' . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->guardarErrorRecepcionF($id, 'Error inesperado: ' . $e->getMessage());
            throw $e;
        }

        // El envío de correo es secundario; no debe afectar el estado del DTE.
        try {
            $this->enviarCorreoDTE($id);
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo del DTE', [
                'dte_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene la firma existente o firma el DTE si aún no lo está.
     */
    private function obtenerOFirmarDTE($id, $dte): ?string
    {
        if ($dte->estado === 'Firmado') {
            $firmaRecord = Firmador::where('dte', $id)
                ->where('estado', 'Firmado')
                ->latest()
                ->first();

            if ($firmaRecord && !empty($firmaRecord->firmador)) {
                return $firmaRecord->firmador;
            }
        }

        $json = $this->GeneraJsonF($id);
        return $this->FirmadorLocal($id, $json);
    }

    /**
     * Guarda una respuesta exitosa de Hacienda y actualiza el DTE.
     */
    private function guardarRespuestaExitosaF($id, dte $dte, array $responseData): void
    {
        $fecha = $responseData['fhProcesamiento'] ?? null;
        $fechaFormateada = $fecha
            ? Carbon::createFromFormat('d/m/Y H:i:s', $fecha)->format('Y-m-d H:i:s')
            : now()->format('Y-m-d H:i:s');

        RecepcionDte::create([
            'dte' => $id,
            'fechaRecepcion' => Carbon::now()->toDateString(),
            'horaRecepcion' => Carbon::now()->format('H:i:s'),
            'selloRecibido' => $responseData['selloRecibido'] ?? null,
            'fhProcesamiento' => $fechaFormateada,
            'estado' => $responseData['estado'] ?? 'ERROR',
            'observaciones' => $responseData['descripcionMsg'] ?? 'Sin descripción',
            'josn' => json_encode($responseData),
        ]);

        // El accessor del modelo ya devuelve JSON normal, y el mutator vuelve a
        // comprimir al guardar. Comprimir aqui a mano dejaba el valor
        // comprimido dos veces y el JSON no se podia volver a leer.
        $arrayDtes = json_decode((string) $dte->jsonDte, true);

        if (is_array($arrayDtes)) {
            $arrayDtes['selloRecibido'] = $responseData['selloRecibido'] ?? null;
            $dte->jsonDte = json_encode($arrayDtes);
        }

        $estadoMh = (string) ($responseData['estado'] ?? 'ERROR');
        $sello = $responseData['selloRecibido'] ?? null;

        if (strtoupper($estadoMh) === 'PROCESADO' && trim((string) $sello) !== '') {
            $dte->estado = $estadoMh;
            $dte->sello = $sello;
            $dte->save();
        } else {
            // Hacienda respondio 200 pero sin sellar. Si el motivo es que el
            // documento "ya existe", se consulta y reconcilia en vez de dejar
            // el sistema en RECHAZADO; y el estado solo se escribe si el DTE
            // sigue sin sello.
            $dte->save();

            app(\App\Services\ProcesamientoDteService::class)
                ->resolverRespuestaRechazada((int) $id, $responseData);
        }

        // Solo se propaga un sello real: con una respuesta sin sellar esto
        // borraba el sello que la venta y la caja ya tuvieran.
        if (trim((string) $sello) !== '') {
            $venta = Ventas::find($dte->venta);
            if ($venta) {
                $venta->sello = $sello;
                $venta->save();
            }

            $caja = Caja::where('venta', $dte->venta)->first();
            if ($caja) {
                $caja->sello = $sello;
                $caja->save();
            }
        }
    }

    /**
     * Guarda una respuesta rechazada por Hacienda (HTTP 4xx).
     */
    private function guardarRespuestaRechazadaF($id, ClientException $e): void
    {
        $response = $e->getResponse();
        $errorResponseData = json_decode($response->getBody(), true);

        if (!is_array($errorResponseData)) {
            $errorResponseData = [];
        }

        $fecha = $errorResponseData['fhProcesamiento'] ?? null;
        $fechaFormateada = $fecha
            ? Carbon::createFromFormat('d/m/Y H:i:s', $fecha)->format('Y-m-d H:i:s')
            : now()->format('Y-m-d H:i:s');

        RecepcionDte::create([
            'dte' => $id,
            'fechaRecepcion' => Carbon::now()->toDateString(),
            'horaRecepcion' => Carbon::now()->format('H:i:s'),
            'selloRecibido' => null,
            'fhProcesamiento' => $fechaFormateada,
            'estado' => $errorResponseData['estado'] ?? 'RECHAZADO',
            'observaciones' => $errorResponseData['descripcionMsg'] ?? $e->getMessage(),
            'josn' => json_encode($errorResponseData),
        ]);

        // Una respuesta tardia no puede degradar un DTE que Hacienda ya sello.
        // La actualizacion es condicional: solo escribe si sigue sin sello y en
        // un estado no final. Antes bastaba con que llegara tarde un RECHAZADO
        // para marcar como rechazado un documento ya procesado. Si el rechazo
        // (HTTP 400) es "ya existe", se consulta y se toma el sello (apartado 28).
        app(\App\Services\ProcesamientoDteService::class)
            ->resolverRespuestaRechazada((int) $id, $errorResponseData);

    }

    /**
     * Guarda un error de recepción que no proviene de una respuesta controlada de Hacienda.
     */
    private function guardarErrorRecepcionF($id, string $mensaje): void
    {
        RecepcionDte::create([
            'dte' => $id,
            'fechaRecepcion' => Carbon::now()->toDateString(),
            'horaRecepcion' => Carbon::now()->format('H:i:s'),
            'selloRecibido' => null,
            'fhProcesamiento' => now()->format('Y-m-d H:i:s'),
            'estado' => 'ERROR',
            'observaciones' => $mensaje,
            'josn' => json_encode(['error' => $mensaje]),
        ]);

        // Una respuesta tardia no puede degradar un DTE que Hacienda ya sello.
        // La actualizacion es condicional: solo escribe si sigue sin sello y en
        // un estado no final. Antes bastaba con que llegara tarde un RECHAZADO
        // para marcar como rechazado un documento ya procesado.
        app(\App\Services\ProcesamientoDteService::class)
            ->guardarEstadoNoFinalizado((int) $id, 'ERROR');
    }
}
