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

use App\Traits\enviarCorreoDTE;

use App\Traits\FirmadorLocal;

use App\Traits\GeneraJsonC;

use Carbon\Carbon;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;



trait RecepcionDTEC

{

    use GeneraJsonC;

    use FirmadorDTE;

    use GenerarToken;

    use enviarCorreoDTE;

    use FirmadorLocal;



    public function RecepcionDTEC($id)

    {

        $user = Auth::user();



        $empresa = Empresas::find(1);



        $json = $this->GeneraJsonC($id);

        //$firma = $this->FirmadorDTE($id, $json);

        $firma = $this->FirmadorLocal($id, $json);



        try {

            $client = new Client();



            if ($empresa->ambiente == 1) {

                $urls = Apis::where('nombre', 'Recepcion')->where('tipo', 'Prueba')->first();
            } else {

                $urls = Apis::where('nombre', 'Recepcion')->where('tipo', 'Produccion')->first();
            }

            //$urls = Apis::where('nombre', 'Recepcion')->where('estado', 'Activo')->first();

            $url = $urls->url;



            $dte = dte::where('estado', 'Firmado')->find($id);

            $arrayDte = json_decode($json, true);



            $ambiente = AmbienteDestino::find($empresa->ambiente);

            $tipoDte = TipoDocumento::where('status', 'Activo')->where('valor', 'COMPROBANTE DE CREDITO FISCAL')->first();

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



            // --- Descomprimir el JSON ya almacenado ---

            $contenido = gzuncompress($dte->jsonDte); // ojo: jsonDte en DB es LONGBLOB

            $arrayDtes = json_decode($contenido, true);



            //$arrayDtes = json_decode($dte->jsonDte, true);

            $arrayDtes['selloRecibido'] = $responseData['selloRecibido'];



            $jsonActualizado = json_encode($arrayDtes);



            $dte->estado = $responseData['estado'];

            $dte->sello = $responseData['selloRecibido'];

            $dte->jsonDte = gzcompress($jsonActualizado);

            $dte->save();



            $venta = Ventas::find($dte->venta);

            $venta->sello = $responseData['selloRecibido'];

            $venta->save();



            $caja = Caja::where('venta', $dte->venta)->first();

            $caja->sello = $responseData['selloRecibido'];

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
