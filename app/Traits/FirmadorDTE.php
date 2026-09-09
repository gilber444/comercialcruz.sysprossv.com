<?php
namespace App\Traits;
use App\Models\Apis;
use App\Models\dte;
use App\Models\Empresas;
use GuzzleHttp\Client;
use App\Models\Firmador as ModelsFirmador;
use Illuminate\Support\Facades\Auth;
trait FirmadorDTE
{
    public function FirmadorDTE($id, $json)
    {
        $client = new Client();
        $user = Auth::user();
        $dte = dte::find($id);
       $empresa = Empresas::find(1);
        $tocken = Apis::where('nombre', 'Firmador')->where('estado', 'Activo')->first();
        $url = $tocken->url;
        $arrayDte = json_decode($json, true);
        $data = [
            'nit' => $empresa->nit,
            'activo' => true,
            'passwordPri' => $empresa->apiPassword,
            'dteJson' => $arrayDte,
        ];
        //dd($data);
        $response = $client->post($url, [
            'json' => $data,
        ]);
        $responseData = json_decode($response->getBody(), true);
        // Una sola fila de firma vigente por DTE. Antes se insertaba una
        // nueva en cada reintento, asi que un DTE rechazado y reprocesado
        // dejaba varias firmas y no habia forma de saber cual valia.
        $firma = ModelsFirmador::firstOrNew(['dte' => $id]);
        $firma->firmador = $responseData['body'];
        $firma->fecha = date('Y-m-d'); // Otra forma de obtener la fecha actual
        $firma->dte = $id;
        $firma->json = json_encode($responseData);
        $firma->estado = $responseData['status'];
        $firma->save();
        $arrayDte['documento'] = $responseData['body'];
        // Convertir el array modificado de nuevo a JSON
        $jsonActualizado = json_encode($arrayDte);
        $dte->estado = 'Firmado';
        $dte->jsonDte = $jsonActualizado;
        $dte->save();
        return $responseData['body'];
    }
}
