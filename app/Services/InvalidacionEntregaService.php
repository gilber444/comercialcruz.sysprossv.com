<?php

namespace App\Services;

use App\Models\dte;
use App\Models\Clientes;
use App\Models\Empresas;
use App\Models\InvalidacionDte;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvalidacionEntregaService
{
    /**
     * Genera en memoria los archivos que deben entregarse al receptor.
     *
     * No persiste el Archivo DTE ni la representacion grafica: ambos se
     * reconstruyen desde el evento firmado y el sello ya almacenados.
     */
    public function generar(int $invalidacionId): array
    {
        $invalidacion = InvalidacionDte::with('tipoInvalidacion')->findOrFail($invalidacionId);

        if (empty($invalidacion->selloRecibido)) {
            throw new RuntimeException('La invalidacion no tiene Sello de Recepcion del Ministerio de Hacienda.');
        }

        if (empty($invalidacion->docFirmado)) {
            throw new RuntimeException('La invalidacion no tiene la firma electronica requerida para su entrega.');
        }

        $estructura = $this->extraerEstructuraFirmada($invalidacion);
        $json = $this->construirArchivoJson($invalidacion);

        $dte = dte::findOrFail($invalidacion->dte);
        // dtes.tipoDte es columna y relacion: la vista no puede resolverlo sola.
        $tipoDteNombre = optional($dte->tipoDte()->first())->valor;
        $empresa = Empresas::findOrFail($invalidacion->empresa);
        // El receptor se guarda directamente en dtes para FE, CCFE, NCE y FSEE.
        $cliente = Clientes::select('id', 'nombreCliente', 'email')->find($dte->receptor);

        if (!$cliente) {
            throw new RuntimeException('No se encontro el receptor asociado al DTE invalidado.');
        }

        $pdf = Pdf::loadView('pdf.invalidacionDte', [
            'invalidacion' => $invalidacion,
            'estructura' => $estructura,
            'dte' => $dte,
            'empresa' => $empresa,
            'cliente' => $cliente,
            'logo' => $this->logoDataUri($empresa),
            'qrCode' => $this->qrCodeDataUri($invalidacion),
            'qrHacienda' => $this->qrHaciendaDataUri($invalidacion, $estructura),
            'tipoDteNombre' => $tipoDteNombre,
        ])->setPaper('letter', 'portrait')->output();

        return [
            'invalidacion' => $invalidacion,
            'estructura' => $estructura,
            'dte' => $dte,
            'empresa' => $empresa,
            'cliente' => $cliente,
            'json' => $json,
            'pdf' => $pdf,
        ];
    }

    public function construirArchivoJson(InvalidacionDte $invalidacion): string
    {
        $estructura = $this->extraerEstructuraFirmada($invalidacion);
        $estructura['firmaElectronica'] = (string) $invalidacion->docFirmado;
        $estructura['selloRecibido'] = (string) $invalidacion->selloRecibido;

        return json_encode(
            $estructura,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR
        );
    }

    private function extraerEstructuraFirmada(InvalidacionDte $invalidacion): array
    {
        $firmada = $this->decodificarPayloadJws((string) $invalidacion->docFirmado);

        if ($this->esEstructuraInvalidacion($firmada)) {
            return $firmada;
        }

        $guardada = $this->decodificarJson($invalidacion->json);

        if ($this->esEstructuraInvalidacion($guardada)) {
            return $guardada;
        }

        throw new RuntimeException('No se pudo reconstruir la estructura del evento de invalidacion.');
    }

    private function decodificarPayloadJws(string $jws): array
    {
        $segmentos = explode('.', $jws);

        if (count($segmentos) !== 3) {
            return [];
        }

        $payload = strtr($segmentos[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $contenido = base64_decode($payload, true);

        if ($contenido === false) {
            return [];
        }

        $decodificado = json_decode($contenido, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    private function decodificarJson($json): array
    {
        if (is_array($json)) {
            return $json;
        }

        if (!is_string($json) || $json === '') {
            return [];
        }

        $decodificado = json_decode($json, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    private function esEstructuraInvalidacion(array $estructura): bool
    {
        return isset(
            $estructura['identificacion']['codigoGeneracion'],
            $estructura['emisor'],
            $estructura['documento']['codigoGeneracion'],
            $estructura['motivo']
        );
    }

    private function logoDataUri(Empresas $empresa): ?string
    {
        if (empty($empresa->image)) {
            return null;
        }

        $ruta = public_path('logo/' . basename((string) $empresa->image));

        if (!is_file($ruta) || !is_readable($ruta)) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';
        $contenido = file_get_contents($ruta);

        return $contenido === false
            ? null
            : 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }

    private function qrCodeDataUri(InvalidacionDte $invalidacion): string
    {
        // Misma forma que dteFactura: URL publica fija que permite al receptor
        // consultar la representacion grafica sin iniciar sesion.
        $url = rtrim((string) config('app.url'), '/')
            . '/report/invalidacion/' . rawurlencode($invalidacion->codigoGeneracion);
        $qr = QrCode::size(300)->generate($url);

        return 'data:image/png;base64,' . base64_encode($qr);
    }

    private function qrHaciendaDataUri(InvalidacionDte $invalidacion, array $estructura): string
    {
        $url = rtrim((string) config('dte.consulta_publica_url'), '?&') . '?' . http_build_query([
            'ambiente' => (string) ($estructura['identificacion']['ambiente'] ?? '00'),
            'codGen' => strtoupper((string) $invalidacion->codigoGeneracion),
            'fechaEmi' => (string) ($estructura['identificacion']['fecEmi'] ?? $invalidacion->fecAnula),
        ], '', '&', PHP_QUERY_RFC3986);
        $qr = QrCode::size(300)->generate($url);

        return 'data:image/png;base64,' . base64_encode($qr);
    }
}
