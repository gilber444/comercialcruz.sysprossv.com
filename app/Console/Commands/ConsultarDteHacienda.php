<?php

namespace App\Console\Commands;

use App\Models\dte;
use App\Services\ConsultaDteHaciendaService;
use Illuminate\Console\Command;

/**
 * Consulta DTE en Hacienda y toma el sello de los que Hacienda ya tiene.
 *
 * - Con ids o codigos: muestra la respuesta de cada uno; con --tomar aplica el
 *   sello si Hacienda lo tiene.
 * - Sin ids: recorre todos los DTE sin sello (Creado, Firmado, RECHAZADO,
 *   ERROR) y toma el sello de los que Hacienda tiene procesados. Los que
 *   Hacienda no tiene no se tocan (los envia app:procesar-dtes-pendientes).
 *
 * Tomar un sello confirmado por Hacienda es seguro: deja el DTE PROCESADO y
 * pone el sello en la venta y la caja, igual que una recepcion exitosa.
 */
class ConsultarDteHacienda extends Command
{
    protected $signature = 'dte:consultar-hacienda
        {ids?* : Id(s) o codigo(s) de generacion; sin ids revisa todos los DTE sin sello}
        {--tomar : Con ids: si Hacienda tiene el sello, actualizar DTE, venta y caja}
        {--limit=500 : Sin ids: maximo de DTE a revisar por corrida}';

    protected $description = 'Consulta DTE en Hacienda y toma el sello de los que ya estan procesados (sin ids revisa todos los pendientes)';

    public function handle(ConsultaDteHaciendaService $consulta): int
    {
        $ids = $this->argument('ids');

        if (empty($ids)) {
            return $this->revisarPendientes($consulta);
        }

        foreach ($ids as $id) {
            // Acepta el id o el codigo de generacion (UUID)
            $dte = ctype_digit((string) $id)
                ? dte::find((int) $id)
                : dte::where('codigoGeneracion', strtoupper(trim((string) $id)))->first();
            if (!$dte) {
                $this->error("DTE {$id}: no existe.");
                continue;
            }

            $this->info("DTE {$dte->id} | {$dte->codigoGeneracion} | estado local: {$dte->estado} | sello local: " . ($dte->sello ?: '—'));

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

    private function revisarPendientes(ConsultaDteHaciendaService $consulta): int
    {
        $pendientes = dte::whereIn('estado', ['Creado', 'Firmado', 'RECHAZADO', 'ERROR'])
            ->where(function ($q) {
                $q->whereNull('sello')->orWhere('sello', '');
            })
            ->whereNotNull('codigoGeneracion')
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $this->info("Revisando {$pendientes->count()} DTE sin sello en Hacienda...");
        $tomados = $noEstan = $sinRespuesta = 0;

        foreach ($pendientes as $dte) {
            try {
                $resultado = $consulta->consultarYReconciliar($dte);
            } catch (\Throwable $e) {
                $sinRespuesta++;
                $this->warn("  DTE {$dte->id}: no se pudo consultar ({$e->getMessage()})");
                continue;
            }

            if ($resultado === ConsultaDteHaciendaService::ENCONTRADO) {
                $tomados++;
                $this->line("  DTE {$dte->id} {$dte->codigoGeneracion}: sello tomado ({$dte->fresh()->sello})");
            } elseif ($resultado === ConsultaDteHaciendaService::NO_ENCONTRADO) {
                $noEstan++;
            } else {
                $sinRespuesta++;
            }
        }

        $this->info("Listo: {$tomados} con sello tomado, {$noEstan} que Hacienda no tiene (se envian en pendientes), {$sinRespuesta} sin respuesta clara.");

        return self::SUCCESS;
    }
}
