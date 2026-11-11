<?php

namespace App\Console\Commands;

use App\Models\AmbienteDestino;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\ModeloFacturacion;
use App\Models\Sucursales;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\Tocken;
use App\Models\TipoTransmision;
use App\Models\Ventas;
use App\Traits\NumeroControlTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Convierte en factura DTE las ventas a consumidor final que se guardaron como
 * "Fisico", con la fecha y hora original de cada venta.
 *
 * Solo crea el DTE en estado "Creado" (igual que el POS) y marca la venta como
 * DTE; el envio a Hacienda lo hace app:procesar-dtes-pendientes, y la subida al
 * VPS la sincronizacion normal. Trabaja por lotes y espera mientras la cola de
 * pendientes este llena, para no atrasar los DTE del dia.
 */
class ConvertirVentasFisicasDte extends Command
{
    use NumeroControlTrait;

    protected $signature = 'ventas:convertir-fisicos-dte
        {--desde= : Fecha inicial (por defecto config dte.convertir_fisicos.desde)}
        {--hasta= : Fecha final (por defecto hoy)}
        {--limit=100 : Maximo de ventas por corrida}
        {--sucursal= : Sucursal (por defecto APP_SUCURSAL_ID)}
        {--dry-run : Solo cuenta, no convierte}';

    protected $description = 'Convierte ventas Fisico de consumidor final en factura DTE con su fecha original';

    /** Si hay mas DTE pendientes que esto, la corrida espera. */
    private const COLA_MAXIMA = 200;

    public function handle(): int
    {
        if (config('app.modo', 'local') === 'vps') {
            $this->error('Este comando es para las PC locales (APP_MODO=local).');
            return self::FAILURE;
        }

        $sucursalId = (int) ($this->option('sucursal') ?: config('app.sucursal_id', 0));
        $desde = $this->option('desde') ?: config('dte.convertir_fisicos.desde');
        $hasta = $this->option('hasta') ?: now()->toDateString();
        $limit = max(1, (int) $this->option('limit'));

        if ($sucursalId <= 0 || !$desde) {
            $this->error('Falta la sucursal (APP_SUCURSAL_ID o --sucursal) o la fecha --desde.');
            return self::FAILURE;
        }

        $pendientes = Ventas::where('sucursal', $sucursalId)
            ->where('tipo', 'Fisico')
            ->where('facturador', 1)
            ->whereIn('estado', ['Cancelado', 'Credito'])
            ->where('total', '<>', 0)
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('dtes')->whereColumn('dtes.venta', 'ventas.id');
            });

        $total = (clone $pendientes)->count();
        $this->info("Sucursal {$sucursalId}, {$desde} a {$hasta}: {$total} ventas Fisico por convertir.");

        if ($total === 0 || $this->option('dry-run')) {
            return self::SUCCESS;
        }

        // Freno: si Hacienda rechazo alguna venta ya convertida (DTE con fecha de
        // emision anterior al dia en que se creo), no se convierten mas.
        $rechazadas = dte::where('estado', 'RECHAZADO')
            ->where('sucursal', $sucursalId)
            ->where('fecEmi', '>=', $desde)
            ->whereRaw('fecEmi < DATE(created_at)')
            ->count();
        if ($rechazadas > 0) {
            $this->error("Hacienda rechazo {$rechazadas} ventas convertidas; se detiene la conversion. Revise el motivo en recepcion_dtes.");
            return self::FAILURE;
        }

        $enCola = dte::whereIn('estado', ['Creado', 'Firmado'])->count();
        if ($enCola >= self::COLA_MAXIMA) {
            $this->warn("Hay {$enCola} DTE pendientes de enviar; se espera a que baje la cola.");
            return self::SUCCESS;
        }

        $sucursal = Sucursales::findOrFail($sucursalId);
        $empresa = Empresas::findOrFail($sucursal->empresa);
        $catalogos = [
            'ambiente' => AmbienteDestino::find($empresa->ambiente),
            'tipoDte' => TipoDocumento::where('status', 'Activo')->whereIn('codigo', ['01', '1'])->first(),
            'tipoModelo' => ModeloFacturacion::where('status', 'Activo')->first(),
            'tipoOperacion' => TipoTransmision::where('status', 'Activo')->first(),
            'tipoContingencia' => TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first(),
        ];
        foreach ($catalogos as $nombre => $fila) {
            if (!$fila) {
                $this->error("Falta el catalogo {$nombre}; no se convierte nada.");
                return self::FAILURE;
            }
        }
        $tocken = Tocken::latest('id')->first();

        $convertidas = 0;
        foreach ($pendientes->orderBy('id')->limit($limit)->get() as $venta) {
            try {
                DB::transaction(function () use ($venta, $catalogos, $tocken) {
                    // obtenerCodigoDTE toma la sucursal y la caja de la sesion
                    session(['sucursal' => $venta->sucursal, 'caja' => $venta->caja]);
                    $control = $this->obtenerCodigoDTE(1);
                    $codigoGeneracion = strtoupper(Str::uuid());

                    dte::create([
                        'motivoContin' => null,
                        'version' => 1,
                        'ambiente' => $catalogos['ambiente']->id,
                        'tipoDte' => $catalogos['tipoDte']->id,
                        'numeroControl' => $control,
                        'codigoGeneracion' => $codigoGeneracion,
                        'tipoModelo' => $catalogos['tipoModelo']->id,
                        'tipoOperacion' => $catalogos['tipoOperacion']->id,
                        'tipoContingencia' => $catalogos['tipoContingencia']->id,
                        'fecEmi' => $venta->fecha,
                        'horEmi' => $venta->hora,
                        'tipoMoneda' => 'USD',
                        'documentoRelacionado' => null,
                        'emisor' => $venta->sucursal,
                        'receptor' => $venta->cliente ?: 1,
                        'otrosDocuentos' => null,
                        'ventaTercero' => null,
                        'venta' => $venta->id,
                        'tocken' => $tocken?->id,
                        'sello' => null,
                        'estado' => 'Creado',
                        'jsonDte' => null,
                        'caja' => $venta->caja,
                        'sucursal' => $venta->sucursal,
                        'empresa' => $venta->empresa,
                    ]);

                    $venta->update(['tipo' => 'DTE', 'codigo' => $codigoGeneracion, 'numero' => $control]);
                });
                $convertidas++;
            } catch (\Throwable $e) {
                $this->error("Venta {$venta->id}: " . $e->getMessage());
            }
        }

        $this->info("Convertidas {$convertidas} de " . min($limit, $total) . '. Quedan ' . ($total - $convertidas) . '. Las envia app:procesar-dtes-pendientes.');

        return self::SUCCESS;
    }
}
