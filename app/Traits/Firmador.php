<?php
namespace App\Traits;
use App\Models\Apis;
use App\Models\Empresas;
use GuzzleHttp\Client;
trait Firmador
{
    public function Firmador($json)
    {
        $client = new Client();
        $tocken = Apis::where('nombre', 'Firmador')->where('estado', 'Activo')->first();
        $url = $tocken->url;
        $empresa = Empresas::find(1);
        $arrayDte = json_decode($json, true);
        $data = [
            'nit' => $empresa->nit,
            'activo' => true,
            'passwordPri' => $empresa->apiPassword,
            'dteJson' =>$arrayDte
        ];
        $response = $client->post($url, [
            'json' => $data,
        ]);
        $responseData = json_decode($response->getBody(), true);
        return $responseData['body'];
    }
}
