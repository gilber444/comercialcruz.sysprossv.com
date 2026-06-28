<?php

namespace App\Console\Commands;

use App\Models\CapitalDiario;
use App\Services\InventoryValuationQuery;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerarCapitalDiario extends Command
{
    protected $signature   = 'capital:generar-diario {--fecha= : Fecha específica Y-m-d (por defecto hoy)}';
    protected $description = 'Genera snapshot diario del capital por sucursal (inventario × costo/precio)';

    public function handle(): int
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->toDateString()
            : now()->toDateString();

        $this->info("[capital:generar-diario] Fecha: {$fecha}");

        // Obtener combinaciones empresa/sucursal activas que tienen inventario
        $combinaciones = DB::table('inventarios')
            ->select('empresa', 'sucursal')
            ->whereNull('deleted_at')
            ->groupBy('empresa', 'sucursal')
            ->get();

        if ($combinaciones->isEmpty()) {
            $this->warn('No se encontraron inventarios activos.');
            return 0;
        }

        $bar = $this->output->createProgressBar($combinaciones->count());
        $bar->start();

        foreach ($combinaciones as $combo) {
            // ── Inventario: total_productos y total_costo (sin IVA) ──────────
            $inventario = DB::query()
                ->fromSub(InventoryValuationQuery::build((int) $combo->sucursal, 0), 'inv')
                ->where('empresa', $combo->empresa)
                ->selectRaw('
                    COUNT(DISTINCT producto_id) AS total_productos,
                    SUM(total_costo)            AS total_costo,
                    SUM(total_costociva)        AS total_costo_coniva
                ')
                ->first();

            // ── Total de ventas del día $fecha (excluye anuladas, incluye créditos) ──
            $totalVenta = DB::table('ventas')
                ->where('empresa', $combo->empresa)
                ->where('sucursal', $combo->sucursal)
                ->whereDate('fecha', $fecha)
                ->whereNotIn('estado', ['Anulada', 'Anulado'])
                ->sum('total');

            $valores = [
                'total_productos' => (int) ($inventario->total_productos ?? 0),
                'total_costo'     => round($inventario->total_costo ?? 0, 4),
                'total_costo_coniva' => round($inventario->total_costo_coniva ?? 0, 4),
                'total_venta'     => round($totalVenta ?? 0, 4),
            ];

            // Buscar registro del día de hoy
            $registro = CapitalDiario::where('empresa', $combo->empresa)
                ->where('sucursal', $combo->sucursal)
                ->where('fecha', $fecha)
                ->first();

            // Si no existe hoy, buscar el mismo día del año anterior para reutilizar el slot
            if (!$registro) {
                $mismodiaPasado = Carbon::parse($fecha)->subYear()->toDateString();
                $registro = CapitalDiario::where('empresa', $combo->empresa)
                    ->where('sucursal', $combo->sucursal)
                    ->where('fecha', $mismodiaPasado)
                    ->first();
            }

            if ($registro) {
                // Actualizar slot existente (puede cambiar la fecha al nuevo año)
                $registro->update(array_merge(['fecha' => $fecha], $valores));
            } else {
                // Primera vez que se registra este día
                CapitalDiario::create(array_merge([
                    'empresa'  => $combo->empresa,
                    'sucursal' => $combo->sucursal,
                    'fecha'    => $fecha,
                ], $valores));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Snapshot generado correctamente.');
        return 0;
    }
}
