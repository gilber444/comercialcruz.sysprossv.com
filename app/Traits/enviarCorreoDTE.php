<?php
namespace App\Traits;
use App\Http\Controllers\ExportDteController;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\InvalidacionDte;
use App\Models\Ventas;
use App\Services\InvalidacionEntregaService;
use PHPMailer\PHPMailer\PHPMailer;
trait enviarCorreoDTE
{
    /**
     * Reenvio manual del evento de invalidacion a partir del id del DTE.
     */
    public function enviarCorreoInvalidado($dteId)
    {
        $invalidacion = InvalidacionDte::where('dte', $dteId)
            ->whereNotNull('selloRecibido')
            ->orderByDesc('id')
            ->first();

        if (!$invalidacion) {
            $resultado = 'Error: no existe una invalidacion aceptada por MH para este DTE.';

            if (method_exists($this, 'emit')) {
                $this->emit('item-error', $resultado);
            }

            return $resultado;
        }

        $resultado = $this->enviarCorreoEventoInvalidacion($invalidacion->id);

        if (method_exists($this, 'emit')) {
            $evento = str_starts_with($resultado, 'Error') ? 'item-error' : 'item-added';
            $this->emit($evento, $resultado);
        }

        return $resultado;
    }

    /**
     * Entrega el JSON firmado del evento y su PDF, mas el JSON y PDF del DTE
     * original cuando esten disponibles. Todo se arma en memoria. Si falla un
     * adjunto opcional (el DTE original) el correo del evento sale igual.
     */
    public function enviarCorreoEventoInvalidacion($invalidacionId)
    {
        try {
            $entrega = app(InvalidacionEntregaService::class)->generar((int) $invalidacionId);
            $invalidacion = $entrega['invalidacion'];
            $dte = $entrega['dte'];
            $empresa = $entrega['empresa'];
            $cliente = $entrega['cliente'];

            if (empty($cliente->email)) {
                throw new \RuntimeException('El receptor no tiene correo electronico registrado.');
            }

            $baseEvento = 'INVALIDACION-' . $invalidacion->codigoGeneracion;

            $mail = new PHPMailer(true);
            $mail->addStringAttachment($entrega['json'], $baseEvento . '.json', 'base64', 'application/json');
            $mail->addStringAttachment($entrega['pdf'], $baseEvento . '.pdf', 'base64', 'application/pdf');

            try {
                $jsonOriginal = $this->inflateJsonFromBin($dte->jsonDte);

                if ($jsonOriginal !== null && $jsonOriginal !== '') {
                    $mail->addStringAttachment($jsonOriginal, $dte->numeroControl . '.json', 'base64', 'application/json');
                }
            } catch (\Throwable $e) {
                // Adjunto opcional: no impide la entrega del evento.
            }

            try {
                // La representacion grafica se regenera por codigoGeneracion, nunca por id.
                $pdfOriginal = (new ExportDteController())->generarPDF($dte->codigoGeneracion);
                $mail->addStringAttachment($pdfOriginal, $dte->numeroControl . '.pdf', 'base64', 'application/pdf');
            } catch (\Throwable $e) {
                // Adjunto opcional: no impide la entrega del evento.
            }

            $mail->setFrom($empresa->correo, $empresa->razon . ' Facturacion DTE');
            $mail->addAddress(\App\Support\NormalizadorCorreoDte::normalizar($cliente->email), $cliente->nombreCliente);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Evento de Invalidación DTE - ' . $empresa->razon;
            $mail->Body = $this->contenidoCorreoInvalidacion($empresa, $cliente, $dte, $invalidacion);
            $mail->send();

            return 'Correo de invalidación enviado con JSON y representación gráfica.';
        } catch (\Throwable $e) {
            return 'Error al enviar correo de invalidación: ' . $e->getMessage();
        }
    }

    private function contenidoCorreoInvalidacion($empresa, $cliente, $dte, $invalidacion): string
    {
        $imagenUrl = asset('logo/' . $empresa->image);
        $contenido = '<html><head><meta charset="UTF-8"></head>';
        $contenido .= '<body style="font-family:Arial,sans-serif;background-color:#f5f5f9;margin:0;padding:0;">';
        $contenido .= '<div style="max-width:620px;margin:0 auto;padding:20px;">';
        $contenido .= '<div style="text-align:center;margin-bottom:16px;"><img src="' . e($imagenUrl) . '" alt="Logo" width="150" height="100" style="object-fit:contain;"></div>';
        $contenido .= '<div style="background:#f8d7da;border:1px solid #d99aa2;border-radius:6px;padding:16px;margin-bottom:16px;">';
        $contenido .= '<h2 style="color:#721c24;margin:0;text-align:center;">Documento Tributario INVALIDADO</h2></div>';
        $contenido .= '<p>Estimado/a <strong>' . e($cliente->nombreCliente) . '</strong>,</p>';
        $contenido .= '<p>Le informamos que el siguiente DTE fue invalidado ante el Ministerio de Hacienda. Se adjuntan el JSON firmado del Evento de Invalidación, su representación gráfica y, para referencia, el DTE relacionado.</p>';
        $contenido .= '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
        $contenido .= $this->filaCorreoInvalidacion('N.º de control del DTE', $dte->numeroControl);
        $contenido .= $this->filaCorreoInvalidacion('Código del DTE', $dte->codigoGeneracion);
        $contenido .= $this->filaCorreoInvalidacion('Código del evento', $invalidacion->codigoGeneracion);
        $contenido .= $this->filaCorreoInvalidacion('Sello del evento', $invalidacion->selloRecibido);
        $contenido .= $this->filaCorreoInvalidacion('Procesado por MH', $invalidacion->fhProcesamiento);
        $contenido .= $this->filaCorreoInvalidacion('Estado', 'INVALIDADO');
        $contenido .= '</table>';
        $contenido .= '<p>Conserve los archivos adjuntos para sus registros.</p>';
        $contenido .= '<p style="margin-top:20px;background:#dcdcdc;font-size:12px;text-align:center;padding:8px;">Este es un mensaje automático.</p>';
        $contenido .= '</div></body></html>';

        return $contenido;
    }

    private function filaCorreoInvalidacion(string $etiqueta, $valor): string
    {
        return '<tr><td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;width:38%;"><strong>'
            . e($etiqueta)
            . '</strong></td><td style="padding:8px;border:1px solid #dee2e6;word-break:break-all;">'
            . e((string) $valor)
            . '</td></tr>';
    }

    public function enviarCorreoDTE($id)
    {
        $dte = dte::find($id);
        $empresa = Empresas::find($dte->empresa);
        $cliente = Ventas::join('clientes as c', 'c.id', 'ventas.cliente')
            ->select('c.nombreCliente', 'c.email')
            ->find($dte->venta);
        $imagenUrl = asset('logo/' . $empresa->image);
        // Generar el PDF
        $exportController = new ExportDteController();
        // La representacion grafica se regenera por codigoGeneracion, nunca por id.
        $pdfContent = $exportController->generarPDF($dte->codigoGeneracion);
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
    /**
     * El accessor del modelo ya entrega JSON normal. Se conserva el metodo
     * porque hay llamadores, pero la logica de formatos vive en un solo lugar.
     */
    private function inflateJsonFromBin(?string $bin): ?string
    {
        return \App\Support\JsonComprimido::descomprimir($bin);
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
