<?php
namespace App\Traits;

use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\Caja;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\ModeloFacturacion;
use App\Models\RecepcionDte;
use App\Models\TipoDocumento;
use App\Models\TipoTransmision;
use App\Models\Ventas;
use App\Models\NotaCredito;
use App\Traits\GeneraJsonNota;
use App\Traits\enviarCorreoDTE;
use App\Traits\FirmadorLocal;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;

trait RecepcionDTENota
{
    use GeneraJsonNota;
    use FirmadorDTE;
    use GenerarToken;
    use enviarCorreoDTE;
    use FirmadorLocal;

    public function RecepcionDTENota($id)
    {
        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $json = $this->GeneraJsonNota($id);
        //$firma = $this->FirmadorDTE($id, $json);
        $firma = $this->FirmadorLocal($id, $json);

        try {
            $client = new Client();

            if($empresa->ambiente == 1)
            {
                $urls = Apis::where('nombre', 'Recepcion')->where('tipo', 'Prueba')->first();
            }
            else
            {
                $urls = Apis::where('nombre', 'Recepcion')->where('tipo', 'Produccion')->first();
            }
            //$urls = Apis::where('nombre', 'Recepcion')->where('estado', 'Activo')->first();
            $url = $urls->url;

            $dte = dte::where('estado', 'Firmado')->find($id);
            $arrayDte = json_decode($json, true);

            $ambiente = AmbienteDestino::find($empresa->ambiente);
            $tipoDte = TipoDocumento::find(4);
            $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
            $tipoOpera = TipoTransmision::where('status', 'Activo')->first();

            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                'version' => 3,
                'tipoDte' => $tipoDte->codigo,
                'documento' => $firma
            ];

            //$data = array_merge($data, $firma);

            $codigoGeneracion = $dte->codigoGeneracion;
            $data['codigoGeneracion'] = $codigoGeneracion;

            $jsonActualizado = json_encode($data);

            $jsonActualizado;
            $fechaHoy = Carbon::now()->toDateString();

            $tocken = $this->GenerarToken();

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $tocken,
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $jsonActualizado,
            ]);

            $status_code = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            $fechaRecepcion = Carbon::now()->toDateString();
            $horaRecepcion = Carbon::now()->format('H:i:s');
            $fecha = $responseData['fhProcesamiento'];
            $fechaFormateada = Carbon::createFromFormat('d/m/Y H:i:s', $fecha)->format('Y-m-d H:i:s');

            RecepcionDte::create([
                'dte' => $id,
                'fechaRecepcion' => $fechaRecepcion,
                'horaRecepcion' => $horaRecepcion,
                'selloRecibido' => $responseData['selloRecibido'],
                'fhProcesamiento' => $fechaFormateada,
                'estado' => $responseData['estado'],
                'observaciones' => $responseData['descripcionMsg'],
                'josn' => json_encode($responseData),
            ]);

            $arrayDtes = json_decode($dte->jsonDte, true);
            $arrayDtes['selloRecibido'] = $responseData['selloRecibido'];

            $dte->estado = $responseData['estado'];
            $dte->sello = $responseData['selloRecibido'];
            $dte->jsonDte = json_encode($arrayDtes);
            $dte->save();

            $venta = Ventas::find($dte->venta);
            $venta->sello = $responseData['selloRecibido'];
            $venta->save();

            $caja = Caja::where('venta', $dte->venta)->first();
            $caja->sello = $responseData['selloRecibido'];
            $caja->save();

            $nota = NotaCredito::where('codigo', $dte->codigoGeneracion)->first();
            $nota->estado = $responseData['estado'];
            $nota->sello = $responseData['selloRecibido'];
            $nota->save();
            $this->enviarCorreoDTE($id);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $errorResponseData = json_decode($response->getBody(), true);
            // Extraer los datos relevantes del JSON de error
            $fechaRecepcion = Carbon::now()->toDateString();
            $horaRecepcion = Carbon::now()->format('H:i:s');
            $fecha = $errorResponseData['fhProcesamiento'];
            $fechaFormateada = Carbon::createFromFormat('d/m/Y H:i:s', $fecha)->format('Y-m-d H:i:s');

            RecepcionDte::create([
                'dte' => $id,
                'fechaRecepcion' => $fechaRecepcion,
                'horaRecepcion' => $horaRecepcion,
                'selloRecibido' => null,
                'fhProcesamiento' => $fechaFormateada,
                'estado' => $errorResponseData['estado'],
                'observaciones' => $errorResponseData['descripcionMsg'],
                'josn' => json_encode($errorResponseData),
            ]);

            $dte->estado = $errorResponseData['estado'];
            $dte->save();

        }
    }
}
