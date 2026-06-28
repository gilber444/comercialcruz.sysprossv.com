<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Sincro SEPARADA de ESTADO de DTEs (reconciliación VPS → local).
 *
 * Problema: un DTE puede quedar RECHAZADO en la LOCAL aunque en el VPS terminó PROCESADO/INVALIDADO
 * (reenvío/retry central). El pull general (sync:vps-local) solo trae DTEs dentro de su ventana de
 * watermark, así que un RECHAZADO viejo se queda sin corregir.
 *
 * Esta sincro va al revés (local-driven): busca en la LOCAL los DTE RECHAZADO, los busca en el VPS
 * por `codigoGeneracion` (UUID de Hacienda, único y estable en ambas bases) y, si en el VPS están
 * PROCESADO/INVALIDADO, actualiza en la local SOLO `estado` (+`sello` si PROCESADO). NUNCA toca FKs
 * (venta/tocken/caja) ni el jsonDte. Propaga el sello a ventas/cajas (como el pull).
 *
 * Corre SOLO en terminales locales (APP_MODO != vps).
 */
class SyncDtesEstado extends Command
{
    protected $signature = 'sync:dtes-estado
        {--local=localmysql : Conexión local}
        {--vps=vpsmysql : Conexión VPS}
        {--limit=500 : Máximo de DTE a revisar por corrida}';

    protected $description = 'Reconcilia los DTE RECHAZADO de la local contra el VPS: si allá están PROCESADO/INVALIDADO, actualiza estado+sello en la local.';

    public function handle()
    {
        if (env('APP_MODO', 'local') === 'vps') {
            $this->error('❌ sync:dtes-estado NO debe ejecutarse en VPS (APP_MODO=vps). Abortando.');
            return self::FAILURE;
        }

        $connLocal = (string) $this->option('local');
        $connVps   = (string) $this->option('vps');
        $limit     = (int) $this->option('limit');

        if (!Schema::connection($connLocal)->hasTable('dtes') || !Schema::connection($connVps)->hasTable('dtes')) {
            $this->error('Tabla dtes no existe en local o VPS. Abortando.');
            return self::FAILURE;
        }

        $this->info('== RECONCILIAR ESTADO DTEs (local RECHAZADO ↔ VPS) == ' . now()->toDateTimeString());

        $pendientes = DB::connection($connLocal)->table('dtes')
            ->select('id', 'codigoGeneracion', 'estado', 'venta')
            ->where('estado', 'RECHAZADO')
            ->whereNotNull('codigoGeneracion')
            ->where('codigoGeneracion', '!=', '')
            ->limit($limit)
            ->get();

        if ($pendientes->isEmpty()) {
            $this->line('   • Sin DTE RECHAZADO en la local. Nada que reconciliar.');
            return self::SUCCESS;
        }

        $actualizados = 0;
        $revisados    = 0;

        foreach ($pendientes as $dteLocal) {
            $revisados++;

            try {
                $vps = DB::connection($connVps)->table('dtes')
                    ->select('estado', 'sello')
                    ->where('codigoGeneracion', $dteLocal->codigoGeneracion)
                    ->first();

                if (!$vps) {
                    continue; // no está en el VPS → no se puede reconciliar
                }

                $estadoVps = strtoupper((string) $vps->estado);
                if (!in_array($estadoVps, ['PROCESADO', 'INVALIDADO'], true)) {
                    continue; // el VPS tampoco lo resolvió
                }

                $data = ['estado' => $estadoVps, 'updated_at' => now()];
                if ($estadoVps === 'PROCESADO' && !empty($vps->sello)) {
                    $data['sello'] = $vps->sello;
                }

                DB::connection($connLocal)->transaction(function () use ($connLocal, $dteLocal, $data, $estadoVps, $vps) {
                    DB::connection($connLocal)->table('dtes')
                        ->where('id', $dteLocal->id)
                        ->update($data);

                    // Propagar el sello a la venta y la caja (igual que el pull)
                    if ($estadoVps === 'PROCESADO' && !empty($vps->sello) && !empty($dteLocal->venta)) {
                        DB::connection($connLocal)->table('ventas')
                            ->where('id', $dteLocal->venta)->update(['sello' => $vps->sello]);
                        DB::connection($connLocal)->table('cajas')
                            ->where('venta', $dteLocal->venta)->update(['sello' => $vps->sello]);
                    }
                });

                $this->line("   ✓ DTE local id={$dteLocal->id} ({$dteLocal->codigoGeneracion}): RECHAZADO → {$estadoVps}");
                $actualizados++;
            } catch (Throwable $e) {
                $this->error("   ✘ DTE local id={$dteLocal->id}: " . $e->getMessage());
            }
        }

        $this->info("✔ Reconciliación finalizada. Revisados: {$revisados}, corregidos: {$actualizados}.");
        return self::SUCCESS;
    }
}
