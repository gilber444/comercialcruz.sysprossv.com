<?php

namespace App\Console\Commands;

use App\Models\dte;
use App\Services\ConsultaDteHaciendaService;
use Illuminate\Console\Command;

/**
 * Diagnostico: consulta en Hacienda uno o varios DTE y muestra la respuesta.
 * Con --tomar, si Hacienda tiene el DTE con sello lo reconcilia (DTE
 * PROCESADO, sello en venta y caja). Sin --tomar no modifica nada.
 */
class ConsultarDteHacienda extends Command
{
    protected $signature = 'dte:consultar-hacienda
        {ids* : Id(s) o codigo(s) de generacion del DTE}
        {--tomar : Si Hacienda tiene el sello, actualizar DTE, venta y caja}';

    protected $description = 'Consulta DTE en Hacienda y muestra la respuesta (con --tomar reconcilia el sello)';

    public function handle(ConsultaDteHaciendaService $consulta): int
    {
        foreach ($this->argument('ids') as $id) {
            // Acepta el id o el codigo de generacion (UUID)
            $dte = ctype_digit((string) $id)
                ? dte::find((int) $id)
                : dte::where('codigoGeneracion', strtoupper(trim((string) $id)))->first();
            if (!$dte) {
                $this->error("DTE {$id}: no existe.");
                continue;
            }

            $this->info("DTE {$id} | {$dte->codigoGeneracion} | estado local: {$dte->estado} | sello local: " . ($dte->sello ?: '—'));

            try {
                $r = $consulta->consultar($dte);
            } catch (\Throwable $e) {
                $this->error('  No se pudo consultar: ' . $e->getMessage());
                continue;
            }

            $this->line("  URL: {$r['url']} | HTTP {$r['http']} | resultado: {$r['estado']}" . ($r['sello'] ? " | sello: {$r['sello']}" : ''));
            $this->line('  Respuesta: ' . mb_substr(is_string($r['respuesta']) ? $r['respuesta'] : json_encode($r['respuesta'], JSON_UNESCAPED_UNICODE), 0, 600));

            if ($this->option('tomar') && $r['estado'] === ConsultaDteHaciendaService::ENCONTRADO) {
                $consulta->consultarYReconciliar($dte);
                $dte->refresh();
                $this->info("  Tomado: estado {$dte->estado}, sello {$dte->sello} (venta y caja actualizadas).");
            }
        }

        return self::SUCCESS;
    }
}
