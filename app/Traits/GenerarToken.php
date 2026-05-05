<?php

namespace App\Traits;

use App\Models\Apis;
use App\Models\Empresas;
use App\Models\Tocken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;

trait GenerarToken
{
    public function GenerarToken()
    {
        $fechaHoy = Carbon::now()->toDateString();
        $tokenActivo = Tocken::where('estado', 'ok')->whereDate('fecha', $fechaHoy)->first();

        $empresa = Empresas::find(1);

        if ($tokenActivo)
        {
            return $tokenActivo->tocken;
        }
        else
        {
            $nuevoToken = $this->generarNuevoToken();
            return $nuevoToken;
        }
    }

    protected function generarNuevoToken()
    {
        $client = new Client();
        $user = Auth::user();

        $empresa = Empresas::find(1);

        $api = Apis::where('nombre', 'Autenticacion')->where('estado', 'Activo')->first();
        $url = $api->url;

        $data = [
            'user' => $empresa->nit,
            'pwd' => $empresa->apiPassword, // Asegúrate de usar la contraseña correcta aquí
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => 'Tu Agente de Usuario', // Puedes personalizar esto
        ];

        $response = $client->post($url, [
            'headers' => $headers,
            'form_params' => $data,
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        // Verifica la estructura real de la respuesta y ajusta esta línea según sea necesario
        $token = $responseData['body']['token'];

        Tocken::create([
            'tocken' => $token,
            'fecha' => now(), // Insertar la fecha actual
            'json' => json_encode($responseData), // JSON completo de la respuesta de la API
            'estado' => $responseData['status'] // Estado del JSON
        ]);

        return $token;
    }
}
