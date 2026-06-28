<?php

namespace App\Traits;

use App\Models\Empresas;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;
use phpseclib3\Crypt\RSA;
use SimpleXMLElement;

trait FirmadorTrait
{
    /**
     * Firma un JSON utilizando una clave privada desde un archivo .key.
     *
     * @param array $data Los datos en formato JSON que se deben firmar.
     *
     * @return string|null El JWS generado o null si falla.
     * @throws \Exception Si ocurre algún error durante la firma.
     */
    public function signJson(array $data): ?string
    {
        /*$usuario = Auth::user();

        if (!$usuario) {
            return 'Usuario no autenticado.';
        }*/

        $empresa = Empresas::find(1);

        if (!$empresa || !$empresa->certificado) {
            return 'Empresa o credenciales del certificado no disponibles.';
        }

        // Ruta a las claves
        $filePath = public_path('storage/empresas/certificados/' . $empresa->certificado);
        //$keyPrivatePath = public_path('storage/empresas/certificados/PrivateKey_' . $empresa->nit . '.key');

        $xmlContent = file_get_contents($filePath);

        // Parsear como XML
        $xml = new SimpleXMLElement($xmlContent);

        // Extraer información relevante (ajustar según la estructura real)
        $publicKey = isset($xml->PublicKey) ? (string)$xml->PublicKey : null;
        $privateKey = isset($xml->privateKey->encodied) ? (string)$xml->privateKey->encodied : null;

        try {



            $rsa = RSA::loadPrivateKey($privateKey);

            // Exportar la clave privada en formato PKCS8 (si es necesario para JWT)
            $privateKeyPKCS8 = $rsa->toString('PKCS8');

            // Crear el JWT usando Firebase JWT
            $jwt = JWT::encode($data, $privateKeyPKCS8, 'RS512');

            return $jwt;
        } catch (Exception $e) {
            return 'Error al generar el JWT: ' . $e->getMessage();
        }
    }
}
