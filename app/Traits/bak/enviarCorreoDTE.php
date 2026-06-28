<?php
namespace App\Traits;

use App\Http\Controllers\ExportController;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Ventas;
use PHPMailer\PHPMailer\PHPMailer;

trait enviarCorreoDTE
{
    public function enviarCorreoDTE($id)
    {
        $dte = dte::find($id);
        $empresa = Empresas::find($dte->empresa);
        $cliente = Ventas::join('clientes as c', 'c.id', 'ventas.cliente')
            ->select('c.nombreCliente', 'c.email')
            ->find($dte->venta);
        $imagenUrl = asset('logo/' . $empresa->image);

        // Generar el PDF
        $exportController = new ExportController();
        $pdfContent = $exportController->generarPDF($id);
        // Convertir el DTE a formato JSON
        //$jsonContent = $dte->jsonDte;
        $jsonRaw = $this->inflateJsonFromBin($dte->jsonDte);
        if ($jsonRaw === null) {
            throw new \RuntimeException('No se pudo descomprimir jsonDte.');
        }
        $jsonPretty = $this->prettyJson($jsonRaw);
        // Configurar PHPMailer
        $mail = new PHPMailer(true);
        // Configurar el servidor SMTP, credenciales, etc.

        try {
            // Adjuntar el PDF y el archivo JSON al correo electrónico
            $mail->addStringAttachment($pdfContent, $dte->numeroControl. '.pdf', 'base64', 'application/pdf');
            $mail->addStringAttachment($jsonPretty, $dte->numeroControl . '.json', 'base64', 'application/json');

            // Configurar el resto del correo electrónico (destinatario, asunto, cuerpo, etc.)
            $mail->setFrom($empresa->correo, $empresa->razon . " Facturacion DTE");
            $mail->addAddress($cliente->email, $cliente->nombreCliente);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Notificación de Factura Tributaria Electronica';
            $contenidoCorreo = '<html><head><meta charset="UTF-8"></head><body style="font-family: sans-serif, Arial; background-color: #f5f5f9;">';
            $contenidoCorreo .= '<div style="text-align: center;"><img src="' . $imagenUrl . '" alt="Logo de la empresa" width: "150px" height: "100px" style="width: 150px; height: 100px; margin: 0 auto;"></div>';
            $contenidoCorreo .= '<h1 style="text-align: center;">Factura Electrónica</h1>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">Estimado cliente ' . $cliente->nombreCliente . ',</p>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">Adjunto encontrarás tu factura electrónica correspondiente a ' . $empresa->razon . '.</p>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">De fecha: ' . $dte->fecEmi . '</p>';
            $contenidoCorreo .= '<br>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">Numero de Control: ' . $dte->numeroControl . '</p>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">Sello de Recepción: ' . $dte->sello . '</p>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">Codigo de Generación: ' . $dte->codigoGeneracion . '</p>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em;"">Por favor, revisa la factura y no dudes encontrarnos si tienes alguna pregunta o inquietud.</p>';
            $contenidoCorreo .= '<p>Gracias por tu preferencia.</p>';
            $contenidoCorreo .= '<p style="margin: 0; padding: 0.3em; margin-top: 20px; background-color: #dcdcdc; font-size: smaller; text-align: center;">Este es un mensaje automático. Por favor, no respondas a este correo electrónico.</p>';
            $contenidoCorreo .= '</body></html>';

            $mail->Body = $contenidoCorreo;

            // Enviar el correo electrónico
            $mail->send();
            $ms =  'Correo enviado con Exito';
            return $ms;

        } catch (Exception $e) {
            return $e;
        }
    }

    private function inflateJsonFromBin(?string $bin): ?string
    {
        if ($bin === null) return null;

        $out = @gzuncompress($bin);            // PHP gzcompress
        if ($out !== false) return $out;

        $out = @gzuncompress(substr($bin, 4)); // MySQL COMPRESS()
        if ($out !== false) return $out;

        // Por si quedaron filas antiguas sin comprimir
        $trim = ltrim($bin);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            return $bin;
        }
        return null;
    }

    /** JSON bonito (si no es válido, lo deja tal cual). */
    private function prettyJson(string $json): string
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) return $json;

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /** Sanitiza nombre de archivo. */
    private function safeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
        return $name !== '' ? $name : 'dte_'.now()->format('Ymd_His');
    }
}
