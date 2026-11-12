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
        {--limit=5000 : Máximo de DTE a revisar por corrida}';

    protected $description = 'Baja del VPS los DTE que alla estan PROCESADO/INVALIDADO y en la local siguen sin sello (RECHAZADO, ELIMINADO, ERROR, Creado, Firmado): estado, sello, numero de control y JSON, con su venta y caja.';

    public function handle()
    {
        if (config('app.modo', 'local') === 'vps') {
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

        $this->info('== RECONCILIAR DTEs (local sin sello ↔ VPS) == ' . now()->toDateTimeString());

        // Locales sin sello (RECHAZADO, ELIMINADO, ERROR, Creado, Firmado). Se buscan en el
        // VPS por codigoGeneracion y, si no, por sincro_id (por si alla se cambio el codigo).
        $estados = ['RECHAZADO', 'ELIMINADO', 'ERROR', 'CREADO', 'FIRMADO'];
        $colsLocal = array_flip(Schema::connection($connLocal)->getColumnListing('dtes'));
        $colsVps = array_flip(Schema::connection($connVps)->getColumnListing('dtes'));
        $conSincro = isset($colsLocal['sincro_id']) && isset($colsVps['sincro_id']);

        $actualizados = 0;
        $revisados    = 0;
        $ultimoId     = 0;
        $sucursales   = [];

        while ($revisados < $limit) {
            $lote = DB::connection($connLocal)->table('dtes')
                ->select(array_values(array_filter(['id', 'codigoGeneracion', 'numeroControl', 'estado', 'venta', 'sucursal', $conSincro ? 'sincro_id' : null])))
                ->where('id', '>', $ultimoId)
                ->whereIn(DB::raw('UPPER(TRIM(estado))'), $estados)
                ->where(fn ($q) => $q->whereNull('sello')->orWhere('sello', ''))
                ->orderBy('id')
                ->limit(min(500, $limit - $revisados))
                ->get();
            if ($lote->isEmpty()) {
                break;
            }
            $ultimoId = (int) $lote->last()->id;
            $revisados += $lote->count();

            // Una consulta al VPS por lote
            $colsTraer = array_values(array_filter(['codigoGeneracion', 'numeroControl', 'estado', 'sello', 'jsonDte', $conSincro ? 'sincro_id' : null]));
            $vpsFilas = DB::connection($connVps)->table('dtes')->select($colsTraer)
                ->whereIn(DB::raw('UPPER(TRIM(estado))'), ['PROCESADO', 'INVALIDADO'])
                ->where(function ($q) use ($lote, $conSincro) {
                    $q->whereIn('codigoGeneracion', $lote->pluck('codigoGeneracion')->filter()->values()->all());
                    if ($conSincro) {
                        $q->orWhereIn('sincro_id', $lote->pluck('sincro_id')->filter()->values()->all());
                    }
                })
                ->get();
            $porCodigo = $vpsFilas->keyBy('codigoGeneracion');
            $porSincro = $conSincro ? $vpsFilas->filter(fn ($v) => !empty($v->sincro_id))->keyBy('sincro_id') : collect();

            foreach ($lote as $dteLocal) {
                try {
                    $vps = $porCodigo[$dteLocal->codigoGeneracion] ?? ($conSincro && $dteLocal->sincro_id ? ($porSincro[$dteLocal->sincro_id] ?? null) : null);
                    if (!$vps) {
                        continue; // el VPS no lo tiene resuelto
                    }
                    $estadoVps = strtoupper(trim((string) $vps->estado));
                    $sello = trim((string) $vps->sello);
                    if ($estadoVps === 'PROCESADO' && $sello === '') {
                        continue;
                    }

                    $data = ['estado' => $estadoVps, 'updated_at' => now()];
                    if ($sello !== '') {
                        $data['sello'] = $sello;
                    }
                    // Si alla se corrigio el numero de control (o el codigo), se baja tambien
                    foreach (['numeroControl', 'codigoGeneracion', 'jsonDte'] as $c) {
                        if (!empty($vps->{$c})) {
                            $data[$c] = $vps->{$c};
                        }
                    }

                    DB::connection($connLocal)->transaction(function () use ($connLocal, $dteLocal, $data) {
                        DB::connection($connLocal)->table('dtes')->where('id', $dteLocal->id)->update($data);

                        if (!empty($dteLocal->venta)) {
                            $venta = ['numero' => $data['numeroControl'] ?? $dteLocal->numeroControl, 'codigo' => $data['codigoGeneracion'] ?? $dteLocal->codigoGeneracion];
                            if (!empty($data['sello'])) {
                                $venta['sello'] = $data['sello'];
                            }
                            DB::connection($connLocal)->table('ventas')->where('id', $dteLocal->venta)->update($venta);
                            DB::connection($connLocal)->table('cajas')->where('venta', $dteLocal->venta)->update($venta);
                        }
                    });

                    $cambioNumero = isset($data['numeroControl']) && $data['numeroControl'] !== $dteLocal->numeroControl
                        ? " | numero {$dteLocal->numeroControl} → {$data['numeroControl']}" : '';
                    $this->line("   ✓ DTE local id={$dteLocal->id}: {$dteLocal->estado} → {$estadoVps}{$cambioNumero}");
                    $actualizados++;
                    $sucursales[(int) $dteLocal->sucursal] = true;
                } catch (Throwable $e) {
                    $this->error("   ✘ DTE local id={$dteLocal->id}: " . $e->getMessage());
                }
            }
        }

        // El contador de esta PC queda por encima de los numeros que bajaron del VPS
        if ($actualizados > 0) {
            foreach (array_keys($sucursales) as $suc) {
                foreach ([1, 2, 4] as $facturador) {
                    try {
                        \App\Support\CorrelativoDte::asegurar($suc, $facturador);
                    } catch (Throwable $e) {
                        $this->warn("   ⚠ No se pudo ajustar el contador (sucursal {$suc}): " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("✔ Reconciliación finalizada. Revisados: {$revisados}, corregidos: {$actualizados}.");
        return self::SUCCESS;
    }
}
