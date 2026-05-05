<?php
namespace App\Traits;

use App\Models\Apis;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Firmador;
use App\Traits\FirmadorTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;


trait FirmadorLocal
{
    use FirmadorTrait;

    public function FirmadorLocal($id, $json)
    {
        $dte = dte::find($id);
        $contenidoJSON  = json_decode($json, true);

         // JSON a firmar
        try {

            $resultado = $this->signJson($contenidoJSON);

            if ($resultado) {
                // Guardar la firma en la tabla 'firmador'
                $firma = new Firmador();
                $firma->firmador = $resultado;
                $firma->fecha = date('Y-m-d');
                $firma->dte = $id;
                $firma->json = json_encode($contenidoJSON);
                $firma->estado = 'Firmado'; // Asegúrate de usar un estado válido
                $firma->save();

                // Preparar el JSON actualizado
                $contenidoJSON['documento'] = $resultado;
                $jsonActualizado = json_encode($contenidoJSON);

                // Actualizar el modelo DTE
                $dte->estado = 'Firmado';
                $dte->jsonDte = $jsonActualizado;
                $dte->save();

                return $resultado;
            }

            return $resultado;


        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}