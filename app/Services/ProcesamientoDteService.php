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

            // Antes de firmar o transmitir se consulta SIEMPRE a Hacienda, en
            // cualquier estado no finalizado. Si ya tiene el DTE con sello, se
            // toma ese sello (DTE, venta y caja) y no se vuelve a enviar.
            try {
                $consulta = app(ConsultaDteHaciendaService::class)->consultarYReconciliar($dte);
            } catch (Throwable $e) {
                report($e);
                $consulta = ConsultaDteHaciendaService::INDETERMINADO;
            }

            if ($consulta === ConsultaDteHaciendaService::ENCONTRADO) {
                return self::YA_PROCESADO;
            }

            // Sin sello confirmado se continua con la generacion y el envio,
            // incluso si la consulta no respondio: Hacienda rechaza un
            // codigoGeneracion repetido como "ya existe" y ese caso se
            // reconcilia en resolverRespuestaRechazada.

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

    /**
     * Lo que manda es el sello: un DTE invalidado tambien lo tiene y no se
     * vuelve a transmitir.
     */
    private function estaFinalizado(dte $dte): bool
    {
        return trim((string) $dte->sello) !== '';
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

                if ($consulta === ConsultaDteHaciendaService::ENCONTRADO) {
                    return self::YA_PROCESADO;
                }
            } catch (Throwable $e) {
                report($e);
            }
            // Sin sello confirmado queda como rechazado y el proceso de
            // pendientes lo vuelve a intentar.
        }

        $estado = (string) ($respuesta['estado'] ?? 'RECHAZADO');

        return $this->guardarEstadoNoFinalizado($id, $estado)
            ? 'RECHAZADO'
            : self::YA_PROCESADO;
    }
}
