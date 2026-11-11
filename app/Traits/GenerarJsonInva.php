<?php
namespace App\Traits;

use App\Models\AmbienteDestino;
use App\Models\dte;
use App\Models\Firmador as FirmaDte;
use App\Models\InvalidacionDte;
use App\Support\NormalizadorCorreoDte;
use App\Support\VersionEventoDte;
use Carbon\Carbon;

/**
 * Evento de invalidacion conforme al schema oficial v3 (Normativa 2.0).
 *
 * Cambios frente al v2: identificacion usa fecEmi/horEmi/fusion en lugar de
 * fecAnula/horAnula; el emisor ya no lleva tipoEstablecimiento ni
 * nomEstablecimiento y codEstableMH/codPuntoVentaMH son obligatorios; el
 * documento ya no lleva montoIva.
 */
trait GenerarJsonInva
{
    public function generarJsonInva($id)
    {
        $invalidacion = InvalidacionDte::with('tipoInvalidacion')->select('fecAnula', 'horAnula', 'codigoGeneracion', 'ambiente', 'dte', 'motivoAnulacion', 'tipoAnulacion', 'codigoGeneracionR')->find($id);

        if (!$invalidacion) {
            throw new \RuntimeException('No se encontro la invalidacion ' . $id . '.');
        }

        $ambiente = AmbienteDestino::find($invalidacion->ambiente);

        if (!$ambiente) {
            throw new \RuntimeException('No se pudo generar la invalidacion: falta el ambiente de destino id ' . $invalidacion->ambiente . ' (catalogo ambiente_destinos).');
        }

        $identificacionn = dte::join('tipo_documentos as tdoc', 'tdoc.id', 'dtes.tipoDte')->select('tdoc.codigo as tipoDte', 'dtes.numeroControl', 'dtes.codigoGeneracion', 'dtes.fecEmi as FechaEmi', 'dtes.horEmi as horaEmi', 'dtes.sello', 'dtes.jsonDte')->find($invalidacion->dte);

        if (!$identificacionn) {
            throw new \RuntimeException('No se encontro el DTE a invalidar o su tipo de documento (catalogo tipo_documentos).');
        }

        $emisorr = dte::join('empresas as e', 'e.id', 'dtes.empresa')->leftJoin('sucursales as s', 's.id', 'dtes.sucursal')->select('e.nit', 'e.razon as nombre', 'e.empresa as nombreComercial', 's.telefono', 'e.telefono as telefonoEmpresa', 'e.correo')->find($invalidacion->dte);

        // dtes.receptor apunta al cliente en los cuatro flujos (tambien en
        // Sujeto Excluido, donde dtes.venta no es una venta).
        $receptor = dte::join('clientes as c', 'c.id', 'dtes.receptor')
            ->leftJoin('identificacion_receptors as irec', 'irec.id', 'c.idenReceptor')
            ->select(
                'irec.codigo as tipoDocumentoConfigurado',
                'c.dui',
                'c.nit',
                'c.nombreCliente as nombre',
                'c.telefono',
                'c.email as correo'
            )
            ->find($invalidacion->dte);

        // Factura permite receptor nulo. El evento conserva los campos
        // requeridos por el schema v3 aunque sus valores sean null.
        $receptor = $receptor ?: (object) [
            'tipoDocumentoConfigurado' => null,
            'dui' => null,
            'nit' => null,
            'nombre' => null,
            'telefono' => null,
            'correo' => null,
        ];

        $codigoTipoDte = str_pad((string) $identificacionn->tipoDte, 2, '0', STR_PAD_LEFT);
        $tipoDocumentoFallback = $receptor->tipoDocumentoConfigurado;
        $numDocumentoFallback = $receptor->dui ?: $receptor->nit;

        if ($codigoTipoDte === '01' && $tipoDocumentoFallback === '36' && !empty($receptor->dui)) {
            $tipoDocumentoFallback = str_starts_with(ltrim((string) $receptor->dui), '0') ? '13' : '37';
            $numDocumentoFallback = $receptor->dui;
        } elseif (in_array($codigoTipoDte, ['03', '05'], true)) {
            $tipoDocumentoFallback = '36';
            $numDocumentoFallback = $receptor->nit ?: $receptor->dui;
        } elseif ($codigoTipoDte === '14') {
            $tipoDocumentoFallback = '37';
            $numDocumentoFallback = $receptor->dui ?: $receptor->nit;
        }

        // Los datos del evento deben coincidir exactamente con el DTE que
        // recibio MH. La ficha del cliente puede haber cambiado despues de
        // emitir, por eso el JSON original sellado es la fuente principal.
        $dteOriginal = $this->decodificarDteOriginalInva($identificacionn->jsonDte);
        $receptorDteOriginal = $dteOriginal['receptor'] ?? $dteOriginal['sujetoExcluido'] ?? null;

        $jsonGuardadoEsDte = isset(
            $dteOriginal['identificacion']['tipoDte'],
            $dteOriginal['identificacion']['numeroControl'],
            $dteOriginal['emisor']
        ) && is_array($receptorDteOriginal) && strcasecmp(
            (string) ($dteOriginal['identificacion']['codigoGeneracion'] ?? ''),
            (string) $identificacionn->codigoGeneracion
        ) === 0;

        if (!$jsonGuardadoEsDte) {
            // Una invalidacion antigua pudo reemplazar jsonDte; la firma
            // historica del DTE conserva el documento exacto.
            $dteOriginal = $this->dteDesdeFirmaHistoricaInva($invalidacion->dte, $identificacionn);
        }

        $emisorOriginal = $dteOriginal['emisor'] ?? [];
        $receptorOriginal = $dteOriginal['receptor'] ?? $dteOriginal['sujetoExcluido'] ?? [];
        $receptorOriginal = is_array($receptorOriginal) ? $receptorOriginal : [];

        if (array_key_exists('tipoDocumento', $receptorOriginal)) {
            $tipoDocumentoReceptor = $receptorOriginal['tipoDocumento'];
        } elseif (array_key_exists('nit', $receptorOriginal)) {
            // CCF identifica al receptor mediante el campo nit.
            $tipoDocumentoReceptor = '36';
        } else {
            $tipoDocumentoReceptor = $tipoDocumentoFallback;
        }

        if (array_key_exists('numDocumento', $receptorOriginal)) {
            $numDocumentoReceptor = $receptorOriginal['numDocumento'];
        } elseif (array_key_exists('nit', $receptorOriginal)) {
            $numDocumentoReceptor = $receptorOriginal['nit'];
        } else {
            $numDocumentoReceptor = $numDocumentoFallback;
        }

        $numeroControl = $identificacionn->numeroControl;
        $codEstableMH = $emisorOriginal['codEstableMH'] ?? null;
        $codPuntoVentaMH = $emisorOriginal['codPuntoVentaMH'] ?? null;

        // En DTE antiguos estos codigos quedaron nulos en jsonDte, pero forman
        // parte del numero de control: DTE-03-M001P001-000000000000001.
        if (preg_match('/^DTE-[0-9]{2}-([A-Z0-9]{4})([A-Z0-9]{4})-/', strtoupper((string) $numeroControl), $matches)) {
            if (!is_string($codEstableMH) || strlen(trim($codEstableMH)) !== 4) {
                $codEstableMH = $matches[1];
            }

            if (!is_string($codPuntoVentaMH) || strlen(trim($codPuntoVentaMH)) !== 4) {
                $codPuntoVentaMH = $matches[2];
            }
        }

        // Son campos string obligatorios (4 caracteres) en el schema v3.
        $codEstableMH = is_string($codEstableMH) && strlen(trim($codEstableMH)) === 4
            ? strtoupper(trim($codEstableMH))
            : 'M001';
        $codPuntoVentaMH = is_string($codPuntoVentaMH) && strlen(trim($codPuntoVentaMH)) === 4
            ? strtoupper(trim($codPuntoVentaMH))
            : 'P001';

        $nombreEmisor = $emisorOriginal['nombre'] ?? $emisorr->nombre;

        $identificacion = [
            // Misma version que el sobre de RecepcionInvalidacion.
            'version' => VersionEventoDte::invalidacion(),
            'ambiente' => $ambiente->codigo,
            'codigoGeneracion' => strtoupper((string) $invalidacion->codigoGeneracion),
            // fecAnula guarda la fecha valida del evento calculada por
            // PlazoInvalidacionDteService::fechaEvento (CCF/NC: la fecha de
            // generacion del DTE; FE/FSE: dentro de los tres meses).
            'fecEmi' => Carbon::parse($invalidacion->fecAnula)->format('Y-m-d'),
            'horEmi' => Carbon::parse($invalidacion->horAnula)->format('H:i:s'),
            'fusion' => null,
        ];

        $emisor = [
            'nit' => $emisorOriginal['nit'] ?? $emisorr->nit,
            'nombre' => $nombreEmisor,
            'codEstableMH' => $codEstableMH,
            'codEstable' => $emisorOriginal['codEstable'] ?? null,
            'codPuntoVentaMH' => $codPuntoVentaMH,
            'codPuntoVenta' => $emisorOriginal['codPuntoVenta'] ?? null,
            'telefono' => (string) ($emisorOriginal['telefono'] ?? ($emisorr->telefono ?: $emisorr->telefonoEmpresa)),
            'correo' => NormalizadorCorreoDte::normalizar($emisorOriginal['correo'] ?? $emisorr->correo),
        ];

        $correoReceptor = array_key_exists('correo', $receptorOriginal) ? $receptorOriginal['correo'] : $receptor->correo;
        $correoReceptor = ($correoReceptor === null || trim((string) $correoReceptor) === '')
            ? null
            : NormalizadorCorreoDte::normalizar($correoReceptor);

        // Motivo 2 nunca lleva documento de reemplazo; NC tampoco (su
        // sustituta se emite despues de aceptar la invalidacion).
        $codigoMotivo = (int) optional($invalidacion->tipoInvalidacion)->codigo;
        $codigoGeneracionR = $invalidacion->codigoGeneracionR;

        if ($codigoMotivo === 2 || $codigoTipoDte === '05' || trim((string) $codigoGeneracionR) === '') {
            $codigoGeneracionR = null;
        } else {
            $codigoGeneracionR = strtoupper(trim((string) $codigoGeneracionR));
        }

        $documento = [
            'tipoDte' => $codigoTipoDte,
            'codigoGeneracion' => strtoupper((string) $identificacionn->codigoGeneracion),
            'selloRecibido' => $dteOriginal['selloRecibido'] ?? $identificacionn->sello,
            'numeroControl' => $numeroControl,
            'fecEmi' => Carbon::parse($identificacionn->FechaEmi)->format('Y-m-d'),
            'codigoGeneracionR' => $codigoGeneracionR,
            'tipoDocumento' => $this->textoONuloInva($tipoDocumentoReceptor),
            'numDocumento' => $this->textoONuloInva($numDocumentoReceptor),
            'nombre' => $this->textoONuloInva($receptorOriginal['nombre'] ?? $receptor->nombre),
            'telefono' => $this->textoONuloInva(array_key_exists('telefono', $receptorOriginal) ? $receptorOriginal['telefono'] : $receptor->telefono),
            'correo' => $correoReceptor,
        ];

        $motivoAnulacion = trim((optional($invalidacion->tipoInvalidacion)->valor ?? '') . ' ' . ($invalidacion->motivoAnulacion ?? ''));

        if ($motivoAnulacion === '') {
            $motivoAnulacion = 'Rescindir de la operacion realizada';
        }

        $motivo = [
            'tipoAnulacion' => $codigoMotivo,
            'motivoAnulacion' => mb_substr($motivoAnulacion, 0, 200),
            'nombreResponsable' => mb_substr((string) $nombreEmisor, 0, 100),
            'tipDocResponsable' => '36',
            'numDocResponsable' => (string) $emisorr->nit,
            'nombreSolicita' => mb_substr((string) $nombreEmisor, 0, 100),
            'tipDocSolicita' => '36',
            'numDocSolicita' => (string) $emisorr->nit,
        ];

        $json = [
            'identificacion' => $identificacion,
            'emisor' => $emisor,
            'documento' => $documento,
            'motivo' => $motivo,
        ];

        return json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Cadena vacia o null quedan en null (el schema v3 los admite nulos). */
    private function textoONuloInva($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    /** jsonDte puede venir como arreglo, JSON plano o comprimido historico. */
    private function decodificarDteOriginalInva($guardado): array
    {
        if (is_array($guardado)) {
            return $guardado;
        }

        if (!is_string($guardado) || $guardado === '') {
            return [];
        }

        $candidatos = [$guardado];
        $php = @gzuncompress($guardado);

        if ($php !== false) {
            $candidatos[] = $php;
        }

        if (strlen($guardado) > 4) {
            $mysql = @gzuncompress(substr($guardado, 4));

            if ($mysql !== false) {
                $candidatos[] = $mysql;
            }
        }

        foreach ($candidatos as $candidato) {
            $decodificado = json_decode($candidato, true);

            if (is_array($decodificado)) {
                return $decodificado;
            }
        }

        return [];
    }

    /**
     * Recupera el DTE exacto desde su firma historica (tabla firmadors) y, si
     * lo encuentra, repara jsonDte para las siguientes consultas.
     */
    private function dteDesdeFirmaHistoricaInva($dteId, $identificacionn): array
    {
        try {
            $firmas = FirmaDte::where('dte', $dteId)->orderByDesc('id')->pluck('firmador');
        } catch (\Throwable $e) {
            return [];
        }

        foreach ($firmas as $documentoFirmado) {
            $segmentos = explode('.', (string) $documentoFirmado);

            if (count($segmentos) !== 3) {
                continue;
            }

            $payloadBase64 = strtr($segmentos[1], '-_', '+/');
            $payloadBase64 .= str_repeat('=', (4 - strlen($payloadBase64) % 4) % 4);
            $payloadJson = base64_decode($payloadBase64, true);
            $payload = $payloadJson === false ? null : json_decode($payloadJson, true);

            if (!is_array($payload) || !isset($payload['identificacion']['tipoDte'])) {
                continue;
            }

            $coincideCodigo = strcasecmp(
                (string) ($payload['identificacion']['codigoGeneracion'] ?? ''),
                (string) $identificacionn->codigoGeneracion
            ) === 0;
            $coincideControl = (string) ($payload['identificacion']['numeroControl'] ?? '')
                === (string) $identificacionn->numeroControl;

            if (!$coincideCodigo || !$coincideControl) {
                continue;
            }

            try {
                $jsonRestaurado = $payload;
                $jsonRestaurado['documento'] = (string) $documentoFirmado;
                $jsonRestaurado['selloRecibido'] = $identificacionn->sello;

                $dteReparado = dte::find($dteId);
                $dteReparado->jsonDte = json_encode($jsonRestaurado);
                $dteReparado->save();
            } catch (\Throwable $e) {
                // La reparacion es opcional: el evento se arma igual.
            }

            return $payload;
        }

        return [];
    }
}
