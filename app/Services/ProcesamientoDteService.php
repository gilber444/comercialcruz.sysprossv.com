<?php

namespace App\Services;

use App\Models\dte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class ProcesamientoDteService
{
    public const EN_PROCESO = 'EN_PROCESO';
    public const YA_PROCESADO = 'YA_PROCESADO';
    public const CONSULTA_NO_DISPONIBLE = 'CONSULTA_NO_DISPONIBLE';

    /**
     * Ejecuta firma y transmision de forma exclusiva para un DTE.
     * La segunda peticion nunca alcanza a firmar ni a enviar a Hacienda.
     */
    public function ejecutar(int $id, callable $procesar): string
    {
        $lock = Cache::lock('dte:procesamiento:' . $id, 600);

        if (!$lock->get()) {
            return self::EN_PROCESO;
        }

        try {
            $dte = dte::find($id);
            if (!$dte) {
                throw new RuntimeException('El DTE solicitado no existe.');
            }

            if (Auth::check() && (int) $dte->empresa !== (int) Auth::user()->empresa) {
                abort(404);
            }

            if ($this->estaFinalizado($dte)) {
                return self::YA_PROCESADO;
            }

            // Solo se consulta antes de retransmitir un DTE que ya tuvo una
            // firma o un rechazo. Un DTE CREADO es un primer envio y debe
            // pasar directamente a la generacion, firma y transmision.
            $estado = strtoupper((string) $dte->estado);
            if (in_array($estado, ['FIRMADO', 'RECHAZADO'], true)) {
                try {
                    $consulta = app(ConsultaDteHaciendaService::class)->consultarYReconciliar($dte);
                } catch (Throwable $e) {
                    report($e);
                    $consulta = ConsultaDteHaciendaService::INDETERMINADO;
                }

                if ($consulta === ConsultaDteHaciendaService::ENCONTRADO) {
                    return self::YA_PROCESADO;
                }

                // Una respuesta indeterminada nunca autoriza a retransmitir:
                // podria existir un DTE aceptado cuyo sello aun no devolvio la
                // consulta. Se deja pendiente para reintentar la consulta,
                // sin marcarlo como procesado ni perder el estado real.
                if ($consulta === ConsultaDteHaciendaService::INDETERMINADO) {
                    return self::CONSULTA_NO_DISPONIBLE;
                }
            }

            $resultadoProceso = $procesar();

            if (in_array($resultadoProceso, [self::YA_PROCESADO, self::CONSULTA_NO_DISPONIBLE], true)) {
                return $resultadoProceso;
            }

            $actualizado = dte::find($id);

            return $actualizado ? strtoupper((string) $actualizado->estado) : 'ERROR';
        } finally {
            $lock->release();
        }
    }

    private function estaFinalizado(dte $dte): bool
    {
        return strtoupper((string) $dte->estado) === 'PROCESADO'
            && trim((string) $dte->sello) !== '';
    }

    /** Una respuesta tardia nunca puede degradar un DTE ya aceptado. */
    public function guardarEstadoNoFinalizado(int $id, string $estado): bool
    {
        return dte::query()
            ->whereKey($id)
            ->whereNotIn('estado', ['PROCESADO', 'INVALIDADO'])
            ->where(function ($query) {
                $query->whereNull('sello')->orWhere('sello', '');
            })
            ->update(['estado' => $estado]) === 1;
    }

    public function resolverRespuestaRechazada(int $id, array $respuesta): string
    {
        $mensaje = strtoupper((string) ($respuesta['descripcionMsg'] ?? $respuesta['mensaje'] ?? ''));
        $esDuplicado = str_contains($mensaje, 'YA EXISTE')
            || str_contains($mensaje, 'DUPLIC')
            || str_contains($mensaje, 'DTE EXISTE')
            || str_contains($mensaje, 'DOCUMENTO EXISTE');

        if ($esDuplicado) {
            try {
                $dte = dte::findOrFail($id);
                $consulta = app(ConsultaDteHaciendaService::class)->consultarYReconciliar($dte);

                return $consulta === ConsultaDteHaciendaService::ENCONTRADO
                    ? self::YA_PROCESADO
                    : self::CONSULTA_NO_DISPONIBLE;
            } catch (Throwable $e) {
                report($e);
                return self::CONSULTA_NO_DISPONIBLE;
            }
        }

        $estado = (string) ($respuesta['estado'] ?? 'RECHAZADO');

        return $this->guardarEstadoNoFinalizado($id, $estado)
            ? 'RECHAZADO'
            : self::YA_PROCESADO;
    }
}
