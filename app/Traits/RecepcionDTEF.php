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
            $tipoDte = TipoDocumento::where('status', 'Activo')->where('valor', 'FACTURA')->first();

            if (!$ambiente || !$tipoDte) {
                throw new \Exception('Faltan configuraciones de ambiente o tipo de DTE.');
            }

            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                'version' => 1,
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

        $contenido = @gzuncompress($dte->jsonDte);

        if ($contenido === false) {
            $contenido = $dte->jsonDte;
        }

        $arrayDtes = json_decode($contenido, true);

        if (is_array($arrayDtes)) {
            $arrayDtes['selloRecibido'] = $responseData['selloRecibido'] ?? null;
            $dte->jsonDte = gzcompress(json_encode($arrayDtes));
        }

        $dte->estado = $responseData['estado'] ?? 'ERROR';
        $dte->sello = $responseData['selloRecibido'] ?? null;
        $dte->save();

        $venta = Ventas::find($dte->venta);
        if ($venta) {
            $venta->sello = $responseData['selloRecibido'] ?? null;
            $venta->save();
        }

        $caja = Caja::where('venta', $dte->venta)->first();
        if ($caja) {
            $caja->sello = $responseData['selloRecibido'] ?? null;
            $caja->save();
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

        $dte = dte::find($id);
        if ($dte) {
            $dte->estado = $errorResponseData['estado'] ?? 'RECHAZADO';
            $dte->save();
        }
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

        $dte = dte::find($id);
        if ($dte) {
            $dte->estado = 'ERROR';
            $dte->save();
        }
    }
}
