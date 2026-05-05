<?php
namespace App\Traits;

use App\Models\Apis;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Firmador;
use App\Traits\FirmadorTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;


trait Firmador2
{
    use FirmadorTrait;

    public function Firmador2($json)
    {
        //$dte = dte::find($id);
        $contenidoJSON  = json_decode($json, true);

         // JSON a firmar
        try {

            $resultado = $this->signJson($contenidoJSON);

            return $resultado;


        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
