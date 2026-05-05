<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VentasResumen;
use App\Models\VentasDetalles;
use Carbon\Carbon;
use DB;

class ActualizarVentasResumen extends Command
{
    protected $signature = 'app:actualizar-ventas-resumen';
    protected $description = 'Actualiza ventas resumen de últimos 30 y 90 días';

    public function handle()
    {
        $this->info('Iniciando actualización de ventas resumen...');

         $productos = DB::table('ventas_detalles')
        ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
        ->select('ventas.empresa', 'ventas.sucursal', 'ventas_detalles.producto')
        ->groupBy('ventas.empresa', 'ventas.sucursal', 'ventas_detalles.producto')
        ->get();

        foreach ($productos as $producto) {

            $total30 = DB::table('ventas_detalles')
                ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
                ->where('ventas.fecha', '>=', Carbon::now()->subDays(30))
                ->where('ventas.sucursal', $producto->sucursal)
                ->where('ventas_detalles.producto', $producto->producto)
                ->sum('ventas_detalles.total');

            $cantidad30 = DB::table('ventas_detalles')
                ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
                ->where('ventas.fecha', '>=', Carbon::now()->subDays(30))
                ->where('ventas.sucursal', $producto->sucursal)
                ->where('ventas_detalles.producto', $producto->producto)
                ->sum('ventas_detalles.cantidad');

            $total90 = DB::table('ventas_detalles')
                ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
                ->where('ventas.fecha', '>=', Carbon::now()->subDays(90))
                ->where('ventas.sucursal', $producto->sucursal)
                ->where('ventas_detalles.producto', $producto->producto)
                ->sum('ventas_detalles.total');

            $cantidad90 = DB::table('ventas_detalles')
                ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
                ->where('ventas.fecha', '>=', Carbon::now()->subDays(90))
                ->where('ventas.sucursal', $producto->sucursal)
                ->where('ventas_detalles.producto', $producto->producto)
                ->sum('ventas_detalles.cantidad');

            VentasResumen::updateOrCreate([
                'empresa' => $producto->empresa,
                'sucursal' => $producto->sucursal,
                'producto' => $producto->producto,
            ], [
                'total_30' => $total30,
                'cantidad_30' => $cantidad30,
                'total_90' => $total90,
                'cantidad_90' => $cantidad90,
                'fecha_actualizacion' => now()->toDateString(),
            ]);
        }

        $this->info('✅ Ventas resumen actualizadas correctamente.');
    }
}
