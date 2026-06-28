<?php

namespace App\Console\Commands;

use App\Helpers\Convertidor;
use App\Models\dte;
use App\Models\resumenDte;
use App\Models\Tributo;
use App\Models\VentasDetalles;
use App\Traits\RecepcionDTEC;
use App\Traits\RecepcionDTEF;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesarDtesPendientes extends Command
{
    use RecepcionDTEF, RecepcionDTEC;

    /**
     * El nombre y firma del comando de consola.
     *
     * php artisan app:procesar-dtes-pendientes --limit=50
     */
    protected $signature = 'app:procesar-dtes-pendientes
                            {--limit=0 : Limitar cantidad de DTE a procesar (0 = todos)}
                            {--rechazados : Forzar reproceso de RECHAZADO aunque no sea VPS}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Procesa DTE en estado Creado/Firmado. En VPS también reprocesa RECHAZADO.';

    public function handle(): int
    {
        $limit     = (int) $this->option('limit');
        $esVps     = config('app.modo', 'local') === 'vps';
        $incluirRechazados = $esVps || $this->option('rechazados');

        $estados = ['Creado', 'Firmado'];
        if ($incluirRechazados) {
            $estados[] = 'RECHAZADO';
        }

        $baseQuery = dte::whereIn('estado', $estados)
            ->orderBy('id', 'asc');

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info('✅ No hay DTE pendientes para procesar.');
            return Command::SUCCESS;
        }

        $labelEstados = implode(', ', $estados);
        if ($limit > 0 && $limit < $total) {
            $this->info("🔎 Hay {$total} DTE en [{$labelEstados}], se procesarán {$limit} (por --limit).");
            $baseQuery->limit($limit);
            $total = $limit;
        } else {
            $this->info("🔎 Se procesarán {$total} DTE en [{$labelEstados}].");
        }

        $procesados = 0;
        $errores    = 0;

        // Usamos chunk para no reventar memoria si son muchos
        $baseQuery->chunkById(100, function ($dtes) use (&$procesados, &$errores, $incluirRechazados) {
            /** @var \App\Models\dte $dte */
            foreach ($dtes as $dte) {
                try {
                    $this->line("➡️ Procesando DTE ID {$dte->id} | tipoDte={$dte->tipoDte} | estado={$dte->estado}");

                    // Si el DTE fue rechazado, resetear a Creado para que pase
                    // por el flujo completo de firma y reenvío al DGII
                    if ($dte->estado === 'RECHAZADO' && $incluirRechazados) {
                        $dte->update(['estado' => 'Creado', 'selloRecibido' => null]);
                        $dte->refresh();
                        $this->line("   ↩️  RECHAZADO → reset a Creado para reprocesar");
                    }

                    $this->actualizarResumenDesdeDetalles($dte);

                    if ((int) $dte->tipoDte === 1) {
                        // FACTURA
                        $this->RecepcionDTEF($dte->id);
                    } else {
                        // CRÉDITO FISCAL u otro tipo (ajusta según tu lógica)
                        $this->RecepcionDTEC($dte->id);
                    }

                    $procesados++;
                } catch (\Throwable $e) {
                    $errores++;
                    $this->error("❌ Error procesando DTE ID {$dte->id}: " . $e->getMessage());

                    Log::error('Error en app:procesar-dtes-pendientes', [
                        'dte_id'  => $dte->id,
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ]);
                }
            }
        });

        $this->info("✅ Proceso terminado. OK: {$procesados}, Errores: {$errores}");

        return $errores > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function actualizarResumenDesdeDetalles(dte $dte): void
    {
        // Detalles de la venta asociada a este DTE
        $detalles = VentasDetalles::where('venta', $dte->venta)->get();

        if ($detalles->isEmpty()) {
            return;
        }

        // Sumas desde la tabla de detalles
        $totalGravada   = (float) $detalles->sum('subtotal'); // sin IVA
        $totalIva       = (float) $detalles->sum('iva');      // IVA
        $montoTotal     = (float) $detalles->sum('total');    // con IVA
        $totalDescuento = (float) $detalles->sum('descuento'); // según cómo lo guardes

        // Si no manejas no gravado/exento, todo va como gravado
        $totalNoSuj     = 0;
        $totalExenta    = 0;

        // Condición de pago / método pago: ajusta según tu lógica
        $condipago      = 1;        // 1 = contado, 2 = crédito, etc. (ejemplo)
        $metodoPago     = '01';     // 01 = Efectivo (ejemplo)
        $referenciaPago = null;

        $now     = now();
        $tributo = Tributo::where('codigo', '20')->first(); // 20 = IVA

        resumenDte::updateOrCreate(
            ['dte' => $dte->id], // clave
            [
                'totalNoSuj'          => $totalNoSuj,
                'totalExenta'         => $totalExenta,
                'totalGravada'        => $totalGravada,
                'totalIva'            => $totalIva,

                'subTotalVentas'      => $totalGravada, // subtotal sin IVA
                'descuNoSuj'          => 0,
                'descuExenta'         => 0,
                'descuGravada'        => $totalDescuento,
                'porcentajeDescuento' => 0,
                'totalDescu'          => $totalDescuento,

                'tributo'             => optional($tributo)->id,
                'codigo'              => optional($tributo)->codigo,
                'descripcion'         => optional($tributo)->descripcion,
                'valor'               => $totalIva,

                'subTotal'            => $totalGravada,
                'ivaPerci1'           => 0,
                'ivaRete1'            => 0,
                'reteRenta'           => 0,

                'montoTotalOperacion' => $montoTotal,
                'totalNoGravado'      => 0,
                'totalPagar'          => $montoTotal,
                'totalLetras'         => strtoupper(
                    Convertidor::montoALetras(round($montoTotal, 2))
                ),
                'saldoFavor'          => 0,

                'condicionOperacion'  => $condipago,
                'pagos'               => $metodoPago,
                'montoPagado'         => $montoTotal,
                'refencia'            => $referenciaPago,
                'palzo'               => null,
                'periodo'             => null,
                'numPagoElectronico'  => null,

                'created_at'          => $now,
                'updated_at'          => $now,
            ]
        );
    }
}
