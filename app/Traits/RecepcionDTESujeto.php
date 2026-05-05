<?php

namespace App\Traits;

use App\Models\AmbienteDestino;
use App\Models\Apis;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\ModeloFacturacion;
use App\Models\RecepcionDte;
use App\Models\SujetoExcluido;
use App\Models\TipoDocumento;
use App\Models\TipoTransmision;
use App\Traits\enviarCorreoDTE;
use App\Traits\FirmadorLocal;
use App\Traits\GeneraJsonS;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;

trait RecepcionDTESujeto
{
    use GeneraJsonS;
    use FirmadorDTE;
    use GenerarToken;
    use enviarCorreoDTE;
    use FirmadorLocal;

    public function RecepcionDTESujeto($id)
    {
        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $json = $this->GeneraJsonS($id);

        $dte = dte::where('venta', $id)
            ->where('tipoDte', 10) // Asegura que es tipo 14
            ->first();
        //$firma = $this->FirmadorDTE($id, $json);
        $firma = $this->FirmadorLocal($dte->id, $json);

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
            //dd($urls);
            $url = $urls->url;

            $dte = dte::where('estado', 'Firmado')->find($dte->id);
            $arrayDte = json_decode($json, true);

            $ambiente = AmbienteDestino::find($empresa->ambiente);
            $tipoDte = TipoDocumento::find(10);
            $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
            $tipoOpera = TipoTransmision::where('status', 'Activo')->first();

            $data = [
                'ambiente' => $ambiente->codigo,
                'idEnvio' => 1,
                'version' => 1,
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
                'dte' => $dte->id,
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

            $caja = SujetoExcluido::find($id);
            $caja->sello_recepcion = $responseData['selloRecibido'];
            $caja->save();
            
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
                'dte' => $dte->id,
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