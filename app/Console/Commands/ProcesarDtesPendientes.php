<?php



namespace App\Console\Commands;



use App\Helpers\Convertidor;

use App\Models\dte;

use App\Models\resumenDte;

use App\Models\Tributo;

use App\Models\Ventas;

use App\Models\VentasDetalles;

use App\Traits\RecepcionDTEC;

use App\Traits\RecepcionDTEF;
use App\Traits\RecepcionDTENota;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;



class ProcesarDtesPendientes extends Command

{

    use RecepcionDTEF, RecepcionDTEC, RecepcionDTENota;



    /**

     * El nombre y firma del comando de consola.

     *

     * php artisan app:procesar-dtes-pendientes --limit=50

     */

    protected $signature = 'app:procesar-dtes-pendientes
                            {--limit=0 : Limitar cantidad de DTE a procesar (0 = todos)}
                            {--rechazados : (Compatibilidad) los RECHAZADO y ERROR ya se reintentan siempre}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Envia a Hacienda los DTE sin sello (Creado, Firmado, RECHAZADO, ERROR). Antes de cada envio consulta a Hacienda: si ya tiene el DTE con sello, lo toma y no lo reenvia.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        // Se reintenta todo lo que no tenga sello; solo se detiene cuando
        // Hacienda devuelve el sello (al enviarlo o al consultarlo).
        $estados = ['Creado', 'Firmado', 'RECHAZADO', 'ERROR'];

        // Solo los tipos que este comando sabe enviar (01, 03 y 05). Un sujeto
        // excluido (14) u otro tipo pendiente no debe ocupar el lote: como los
        // nuevos van primero, llenaban el limite y tapaban la cola.
        $baseQuery = dte::whereIn('estado', $estados)
            ->where(function ($q) {
                $q->whereNull('sello')->orWhere('sello', '');
            })
            ->whereHas('RtipoDte', function ($q) {
                $q->whereIn('codigo', ['01', '1', '03', '3', '05', '5']);
            });

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info('✅ No hay DTE pendientes para procesar.');
            return Command::SUCCESS;
        }

        // Primero los nuevos (Creado/Firmado) y despues los rechazados, del que
        // lleva mas tiempo sin intentarse al mas reciente: un rechazo que se
        // repite no tapa la cola de los DTE del dia.
        $pendientes = $baseQuery
            ->orderByRaw("CASE WHEN estado IN ('RECHAZADO', 'ERROR') THEN 1 ELSE 0 END")
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit($limit > 0 ? $limit : 500)
            ->get();

        $labelEstados = implode(', ', $estados);
        $this->info("🔎 Hay {$total} DTE en [{$labelEstados}] sin sello, se procesarán {$pendientes->count()}.");

        $procesados = 0;
        $errores    = 0;
        $servicio   = app(\App\Services\ProcesamientoDteService::class);

        foreach ($pendientes as $dte) {
            /** @var \App\Models\dte $dte */
            try {
                $this->line("➡️ Procesando DTE ID {$dte->id} | tipoDte={$dte->tipoDte} | estado={$dte->estado}");

                // Por codigo del CAT-002, no por id: cada tipo tiene su generador.
                // Se decide ANTES de tocar el estado: un tipo que no se envia
                // desde aqui no debe cambiar de RECHAZADO a Creado.
                $codigo = str_pad((string) optional($dte->RtipoDte()->first())->codigo, 2, '0', STR_PAD_LEFT);
                if (!in_array($codigo, ['01', '03', '05'], true)) {
                    // El sujeto excluido (14) se envia desde su pantalla: su
                    // recepcion necesita el usuario conectado.
                    $this->warn("   ⏭  Tipo {$codigo}: se envia desde su pantalla, se omite.");
                    continue;
                }

                // Consulta previa a Hacienda y candado por DTE: si otro proceso
                // ya lo esta enviando, este no lo toca.
                $resultado = $servicio->ejecutar((int) $dte->id, function () use ($dte, $codigo) {
                    // Un rechazado vuelve a Creado (dentro del candado y solo si
                    // sigue sin sello) para pasar por el flujo completo de
                    // generacion, firma y reenvio.
                    $reiniciado = dte::whereKey($dte->id)
                        ->whereIn('estado', ['RECHAZADO', 'ERROR'])
                        ->where(function ($q) {
                            $q->whereNull('sello')->orWhere('sello', '');
                        })
                        ->update(['estado' => 'Creado']);
                    if ($reiniciado) {
                        $dte->refresh();
                        $this->line("   ↩️  Sin sello → reset a Creado para reprocesar");
                    }

                    if ($codigo === '05') {
                        // La nota tiene su propio resumen: no se recalcula desde la venta.
                        $this->RecepcionDTENota($dte->id);
                        return null;
                    }

                    $this->actualizarResumenDesdeDetalles($dte);

                    if ($codigo === '01') {
                        $this->RecepcionDTEF($dte->id);
                    } else {
                        $this->RecepcionDTEC($dte->id);
                    }

                    return null;
                });

                $this->line("   ⇒ {$resultado}");
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



        // Retencion del 1% (Art. 162 CT). La calcula el POS al vender a un

        // cliente GRANDE y la cobra de menos: sale de la venta, que es lo que

        // realmente se cobro. Antes aqui iba ivaRete1 => 0 y totalPagar = total

        // con IVA, asi que todo DTE que pasaba por este comando (no automatico,

        // fallido o rechazado) perdia la retencion y no cuadraba con la caja.

        $venta      = Ventas::find($dte->venta);

        $retenido   = round((float) ($venta->percepcion ?? 0), 2);

        $totalPagar = round($montoTotal - $retenido, 2);



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

                'ivaRete1'            => $retenido,

                'reteRenta'           => 0,



                'montoTotalOperacion' => $montoTotal,

                'totalNoGravado'      => 0,

                'totalPagar'          => $totalPagar,

                'totalLetras'         => strtoupper(

                    Convertidor::montoALetras($totalPagar)

                ),

                'saldoFavor'          => 0,



                'condicionOperacion'  => $condipago,

                'pagos'               => $metodoPago,

                'montoPagado'         => $totalPagar,

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

