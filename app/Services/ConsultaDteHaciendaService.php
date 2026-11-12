<?php

namespace App\Services;

use App\Models\Apis;
use App\Models\Caja;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\NotaCredito;
use App\Models\RecepcionDte;
use App\Models\SujetoExcluido;
use App\Models\Ventas;
use App\Traits\GenerarToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ConsultaDteHaciendaService
{
    use GenerarToken;

    public const ENCONTRADO = 'ENCONTRADO';
    public const NO_ENCONTRADO = 'NO_ENCONTRADO';
    public const INDETERMINADO = 'INDETERMINADO';

    public function consultarYReconciliar(dte $dte): string
    {
        $resultado = $this->consultar($dte);

        if ($resultado['estado'] !== self::ENCONTRADO) {
            // Queda en el log lo que respondio Hacienda, para saber por que no
            // se tomo el sello (respuesta con otro formato, URL, token...).
            \Illuminate\Support\Facades\Log::warning('Consulta DTE en Hacienda sin sello', [
                'dte' => $dte->id,
                'codigoGeneracion' => $dte->codigoGeneracion,
                'resultado' => $resultado['estado'],
                'url' => $resultado['url'] ?? null,
                'http' => $resultado['http'] ?? null,
                'respuesta' => mb_substr(is_string($resultado['respuesta']) ? $resultado['respuesta'] : json_encode($resultado['respuesta'], JSON_UNESCAPED_UNICODE), 0, 2000),
            ]);

            return $resultado['estado'];
        }

        $this->reconciliar($dte, $resultado['sello'], $resultado['respuesta']);

        return self::ENCONTRADO;
    }

    /**
     * Consulta el DTE en Hacienda sin modificar nada. Devuelve la
     * interpretacion y la respuesta cruda (para diagnostico).
     *
     * @return array{estado: string, sello: ?string, respuesta: mixed, http: ?int, url: string}
     */
    public function consultar(dte $dte): array
    {
        $empresa = Empresas::findOrFail($dte->empresa);
        $codigoTipo = optional($dte->tipoDte()->first())->codigo;

        if (!$codigoTipo) {
            throw new RuntimeException('No se pudo determinar el codigo del tipo de DTE.');
        }

        $api = $this->apiConsulta($empresa);
        $token = $this->GenerarToken();
        $response = (new Client())->post($api, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $token,
            ],
            'json' => [
                'nitEmisor' => (string) $empresa->nit,
                'tdte' => str_pad((string) $codigoTipo, 2, '0', STR_PAD_LEFT),
                'codigoGeneracion' => (string) $dte->codigoGeneracion,
            ],
            'connect_timeout' => 5,
            'timeout' => 10,
            'http_errors' => false,
        ]);

        $cuerpo = (string) $response->getBody();
        $respuesta = json_decode($cuerpo, true);
        if (!is_array($respuesta)) {
            return ['estado' => self::INDETERMINADO, 'sello' => null, 'respuesta' => $cuerpo, 'http' => $response->getStatusCode(), 'url' => $api];
        }

        $interpretacion = $this->interpretarRespuesta(
            $respuesta,
            (string) $dte->codigoGeneracion,
            $response->getStatusCode()
        );

        return $interpretacion + ['http' => $response->getStatusCode(), 'url' => $api];
    }

    public function interpretarRespuesta(array $respuesta, string $codigoEsperado, int $httpStatus = 200): array
    {
        $datos = isset($respuesta['body']) && is_array($respuesta['body'])
            ? $respuesta['body']
            : $respuesta;

        $codigo = strtoupper(trim((string) ($datos['codigoGeneracion'] ?? '')));
        $esperado = strtoupper(trim($codigoEsperado));
        if ($codigo !== '' && $codigo !== $esperado) {
            return ['estado' => self::INDETERMINADO, 'sello' => null, 'respuesta' => $respuesta];
        }

        $sello = trim((string) ($datos['selloRecibido'] ?? $datos['numValidacion'] ?? ''));
        if ($httpStatus >= 200 && $httpStatus < 300 && preg_match('/^[A-Za-z0-9]{40}$/', $sello)) {
            return ['estado' => self::ENCONTRADO, 'sello' => $sello, 'respuesta' => $respuesta];
        }

        $estado = strtoupper(trim((string) ($datos['estado'] ?? '')));
        $mensaje = strtoupper((string) ($datos['descripcionMsg'] ?? $datos['mensaje'] ?? ''));
        $codigoMsg = strtoupper((string) ($datos['codigoMsg'] ?? ''));

        if ($estado === 'RECHAZADO'
            || str_contains($mensaje, 'NO EXISTE')
            || str_contains($mensaje, 'NO ENCONTR')
            || str_contains($codigoMsg, 'NO_EXISTE')) {
            return ['estado' => self::NO_ENCONTRADO, 'sello' => null, 'respuesta' => $respuesta];
        }

        return ['estado' => self::INDETERMINADO, 'sello' => null, 'respuesta' => $respuesta];
    }

    /**
     * Sustituye el ultimo segmento de la URL de recepcion por consultadte.
     * Contempla las dos formas en que suele quedar configurada la recepcion:
     * .../recepcion/dte y .../recepciondte (un solo segmento, sin barra).
     */
    public function derivarUrlConsulta(string $urlRecepcion): string
    {
        $url = preg_replace('~/(?:recepcion)?dte/?$~i', '/consultadte/', trim($urlRecepcion));

        if (!$url || $url === trim($urlRecepcion)) {
            throw new RuntimeException(
                "No se pudo derivar la URL de consulta desde la recepcion configurada ({$urlRecepcion}). "
                    . 'Registra la API de Consulta en el catalogo de APIs.'
            );
        }

        return $url;
    }

    private function apiConsulta(Empresas $empresa): string
    {
        $tipo = (int) $empresa->ambiente === 1 ? 'Prueba' : 'Produccion';
        $consulta = Apis::query()
            ->whereIn('nombre', ['Consulta', 'Consulta DTE', 'ConsultaDTE'])
            ->where('tipo', $tipo)
            ->where('estado', 'Activo')
            ->first();

        if ($consulta?->url) {
            return $consulta->url;
        }

        $recepcion = Apis::query()
            ->where('nombre', 'Recepcion')
            ->where('tipo', $tipo)
            ->first();

        if (!$recepcion?->url) {
            throw new RuntimeException("No existe API de Consulta o Recepcion para el ambiente {$tipo}.");
        }

        return $this->derivarUrlConsulta($recepcion->url);
    }

    private function reconciliar(dte $dte, string $sello, array $respuesta): void
    {
        DB::transaction(function () use ($dte, $sello, $respuesta) {
            $actual = dte::query()->lockForUpdate()->findOrFail($dte->id);
            if (!empty($actual->sello) && strtoupper((string) $actual->estado) === 'PROCESADO') {
                return;
            }

            // Si el DTE aun no tiene JSON guardado (se transmitio desde otro
            // equipo o se perdio la respuesta), igual se toma el sello.
            $json = json_decode((string) $actual->jsonDte, true);
            $fechaProceso = $this->fechaProcesamiento($respuesta);

            $actual->estado = 'PROCESADO';
            $actual->sello = $sello;
            if (is_array($json)) {
                $json['selloRecibido'] = $sello;
                $actual->jsonDte = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $actual->save();

            RecepcionDte::updateOrCreate(
                ['dte' => $actual->id, 'selloRecibido' => $sello],
                [
                    'fechaRecepcion' => now()->toDateString(),
                    'horaRecepcion' => now()->format('H:i:s'),
                    'fhProcesamiento' => $fechaProceso,
                    'estado' => 'PROCESADO',
                    'observaciones' => $this->valorRespuesta($respuesta, 'descripcionMsg') ?: 'DTE reconciliado mediante consulta a Hacienda.',
                    'josn' => json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );

            $codigoTipo = str_pad((string) optional($actual->tipoDte()->first())->codigo, 2, '0', STR_PAD_LEFT);
            if ($codigoTipo === '14') {
                SujetoExcluido::whereKey($actual->venta)->update([
                    'sello_recepcion' => $sello,
                    'estado' => 'PROCESADO',
                ]);
                return;
            }

            Ventas::whereKey($actual->venta)->update(['sello' => $sello]);
            Caja::where('venta', $actual->venta)->update(['sello' => $sello]);

            if ($codigoTipo === '05') {
                NotaCredito::where('codigo', $actual->codigoGeneracion)->update([
                    'sello' => $sello,
                    'estado' => 'PROCESADO',
                ]);
            }
        });
    }

    private function fechaProcesamiento(array $respuesta): string
    {
        $valor = $this->valorRespuesta($respuesta, 'fhProcesamiento');
        if (!$valor) {
            return now()->format('Y-m-d H:i:s');
        }

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i.s', 'Y-m-d H:i:s'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor)->format('Y-m-d H:i:s');
            } catch (Throwable $e) {
                // Probar el siguiente formato conocido.
            }
        }

        return now()->format('Y-m-d H:i:s');
    }

    private function valorRespuesta(array $respuesta, string $campo): ?string
    {
        $datos = isset($respuesta['body']) && is_array($respuesta['body'])
            ? $respuesta['body']
            : $respuesta;

        $valor = $datos[$campo] ?? null;
        return $valor === null ? null : (string) $valor;
    }
}

