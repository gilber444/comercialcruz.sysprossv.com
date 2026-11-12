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

    /**
     * Error de envio sin respuesta controlada (timeout, red): tambien se
     * consulta antes de marcarlo ERROR, por si Hacienda si lo recibio.
     */
    public function resolverError(int $id): string
    {
        if ($this->tomarSelloSiExiste($id)) {
            return self::YA_PROCESADO;
        }

        $this->guardarEstadoNoFinalizado($id, 'ERROR');

        return 'ERROR';
    }

    /** Consulta a Hacienda y, si tiene el DTE con sello, lo reconcilia. */
    private function tomarSelloSiExiste(int $id): bool
    {
        try {
            $dte = dte::find($id);
            if (!$dte) {
                return false;
            }

            return app(ConsultaDteHaciendaService::class)->consultarYReconciliar($dte)
                === ConsultaDteHaciendaService::ENCONTRADO;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
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
        // Ante CUALQUIER rechazo se consulta a Hacienda: si alla el DTE ya
        // tiene sello de recepcion, se toma (DTE PROCESADO, sello en venta y
        // caja) en vez de dejarlo rechazado.
        if ($this->tomarSelloSiExiste($id)) {
            return self::YA_PROCESADO;
        }

        // Sin sello confirmado queda como rechazado y el proceso de pendientes
        // lo vuelve a intentar.
        $estado = (string) ($respuesta['estado'] ?? 'RECHAZADO');

        return $this->guardarEstadoNoFinalizado($id, $estado)
            ? 'RECHAZADO'
            : self::YA_PROCESADO;
    }
}
