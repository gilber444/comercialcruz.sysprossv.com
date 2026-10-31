<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Revisa el JSON de un DTE contra el esquema vigente de Hacienda justo antes
 * de firmarlo. Solo AVISA: escribe en storage/logs/dte_esquema.log y nunca
 * detiene la emision (DTE_VALIDAR_ESQUEMA=off la apaga del todo).
 */
class RevisionEsquemaDte
{
    public static function revisar($json, $dteId = null): void
    {
        try {
            if ((string) config('dte.validar_esquema', 'aviso') === 'off') {
                return;
            }

            $datos = is_string($json) ? json_decode($json, true) : $json;
            if (!is_array($datos)) {
                return;
            }

            $tipo = static::tipo($datos);
            if ($tipo === null) {
                return;
            }

            // En reprocesos el jsonDte ya trae la firma y el sello: no son parte del esquema
            if (isset($datos['identificacion']['tipoDte'])) {
                unset($datos['documento'], $datos['selloRecibido']);
            }

            $resultado = app(ValidadorEsquemaDte::class)->validar($tipo, $datos);

            // Sin esquema cargado para ese tipo no hay nada que revisar
            if (!$resultado['evaluado'] && $resultado['version'] === null) {
                return;
            }

            $contexto = [
                'tipoDte' => $tipo,
                'dte_id' => $dteId,
                'codigoGeneracion' => $datos['identificacion']['codigoGeneracion'] ?? null,
                'version_json' => $datos['identificacion']['version'] ?? null,
                'version_esquema' => $resultado['version'],
            ];

            if (!$resultado['evaluado']) {
                static::log()->warning('No se pudo revisar el DTE contra el esquema', $contexto + ['motivo' => $resultado['motivo']]);
            } elseif (!$resultado['valido']) {
                $errores = collect($resultado['errores'])
                    ->map(fn ($e) => $e['campo'] . ': ' . $e['mensaje'])
                    ->take(15)
                    ->implode(' | ');
                static::log()->warning('DTE fuera del esquema oficial', $contexto + ['errores' => $errores]);
            } else {
                static::log()->info('DTE cumple el esquema', $contexto);
            }
        } catch (\Throwable $e) {
            try {
                static::log()->error('Fallo la revision de esquema', ['dte_id' => $dteId, 'error' => $e->getMessage()]);
            } catch (\Throwable $ignorado) {
            }
        }
    }

    /** Tipo de documento: el CAT-002 del DTE, o INV/CON para los eventos. */
    protected static function tipo(array $datos): ?string
    {
        $tipo = $datos['identificacion']['tipoDte'] ?? null;
        if ($tipo !== null && $tipo !== '') {
            return str_pad((string) $tipo, 2, '0', STR_PAD_LEFT);
        }
        if (isset($datos['motivo'], $datos['documento'])) {
            return 'INV';
        }
        if (isset($datos['motivo'], $datos['detalleDTE'])) {
            return 'CON';
        }
        return null;
    }

    protected static function log()
    {
        // Canal propio para que LOG_LEVEL=error no descarte los avisos
        return Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/dte_esquema.log'),
            'level' => 'info',
            'days' => 30,
        ]);
    }
}
