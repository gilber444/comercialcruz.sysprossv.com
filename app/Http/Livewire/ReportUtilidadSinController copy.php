<?php

namespace App\Http\Livewire;

use App\Models\Facturadores;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportUtilidadSinController extends Component
{
    public $componentName,
        $data,
        $details,
        $sumDetails,
        $countDetails,
        $reporteType,
        $sucursal,
        $dateFrom,
        $dateTo,
        $ventaId,
        $cajas = [],
        $caja,
        $totalSales,
        $totalUtilidad,
        $totalCosto,
        $facturador,
        $facturadores;

    public function mount()
    {
        $this->componentName = 'Reporte de Utilidades Sintetizado';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reporteType = 0;
        $this->ventaId = 0;

        $this->facturadores = Facturadores::all();
    }

    public function render()
    {
        //$this->SalesByDate();

        return view('livewire.reports.report-utilidadsin', ['sucursales' => Sucursales::orderBy('id', 'asc')->get()])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    // public function SalesByDate()
    // {
    //     if ($this->reporteType == 0) {
    //         $this->dateFrom = now()->format('Y-m-d');
    //         $this->dateTo   = now()->format('Y-m-d');
    //     }

    //     $from = $this->dateFrom;
    //     $to   = $this->dateTo;

    //     $base = VentasDetalles::query()
    //     ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
    //     ->join('sucursales as s', 's.id', '=', 'ventas.sucursal')
    //     ->join('precios', function($join) {
    //         $join->on('precios.producto', '=', 'ventas_detalles.producto')
    //             ->where('precios.cantidad', '=', 1);
    //     })
    //     ->whereBetween('ventas.fecha', [$from, $to])
    //     ->where('ventas.estado', 'Cancelado')
    //     ->whereNotIn('ventas.sucursal', [11, 12, 14]);

    //     if ($this->sucursal != 0) $base->where('ventas.sucursal', $this->sucursal);
    //     if ($this->caja != 0)     $base->where('ventas.caja', $this->caja);

    //     $groupCols = [];
    //     if ($this->caja != 0) {
    //         $groupCols = ['ventas.caja'];
    //     } elseif ($this->sucursal != 0) {
    //         $groupCols = ['ventas.caja'];
    //     } else {
    //         $groupCols = ['ventas.sucursal', 'ventas.caja'];
    //     }


    //     $this->data = (clone $base)
    //         ->selectRaw('
    //             s.nombre as nombre_sucursal,
    //             ventas.sucursal,
    //             ventas.caja,
    //             SUM(precios.costociva * ventas_detalles.descargar)   as total_costo,
    //             SUM(ventas_detalles.total)         as total_venta,
    //             SUM(ventas_detalles.cantidad)      as total_cantidad,
    //             SUM(ventas_detalles.utilidad_uni)  as total_utilidad_monto
    //         ')
    //         ->groupBy('s.nombre', 'ventas.sucursal', 'ventas.caja')
    //         ->orderBy('s.nombre')
    //         ->orderBy('ventas.caja')
    //         ->get();

    //     // Totales globales desde el agregado
    //     $this->totalCosto     = $this->data->sum('total_costo');
    //     $this->totalSales     = $this->data->sum('total_venta');
    //     // IMPORTANTE: sumar el MONTO de utilidad (utilidad_uni), no el %
    //     $this->totalUtilidad  = $this->data->sum('total_utilidad_monto');
    // }

    public function SalesByDate()
    {
        if ($this->reporteType == 0) {
            $this->dateFrom = now()->format('Y-m-d');
            $this->dateTo   = now()->format('Y-m-d');
        }

        $from = $this->dateFrom;
        $to   = $this->dateTo;

        // ---------------------------
        // 1. TOTAL REAL DE VENTAS
        // ---------------------------
        $totalesVentas = Ventas::whereBetween('fecha', [$from, $to])
            ->whereRaw("TRIM(LOWER(estado)) = 'cancelado'")
            ->whereNotIn('sucursal', [11,12,14]);

        if ($this->sucursal != 0) $totalesVentas->where('sucursal', $this->sucursal);
        if ($this->caja != 0)     $totalesVentas->where('caja', $this->caja);

        $this->totalSales = $totalesVentas->sum('total');   // ESTE ES EL REAL EXACTO

        // ---------------------------
        // 2. DETALLES (CANTIDAD / COSTO / UTILIDAD)
        // ---------------------------
        $base = VentasDetalles::query()
            ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
            ->join('sucursales as s', 's.id', '=', 'ventas.sucursal')
            /*->join(DB::raw("
                (
                    SELECT producto, MIN(costociva) AS costociva
                    FROM precios
                    WHERE cantidad = 1
                    GROUP BY producto
                ) as px
            "), 'px.producto', '=', 'ventas_detalles.producto')*/
            ->whereBetween('ventas.fecha', [$from, $to])
            ->whereRaw("TRIM(LOWER(ventas.estado)) = 'cancelado'");
            /->whereNotIn('ventas.sucursal', [11,12,14]);

        if ($this->sucursal != 0) $base->where('ventas.sucursal', $this->sucursal);
        if ($this->caja != 0)     $base->where('ventas.caja', $this->caja);

        $this->data = (clone $base)
        ->selectRaw('
            s.nombre as nombre_sucursal,
            ventas.sucursal,
            ventas.caja,
            SUM(ventas_detalles.costo * ventas_detalles.descargar) as total_costo,
            SUM(ventas_detalles.total) as total_venta,
            SUM(ventas_detalles.cantidad) as total_cantidad,
            SUM(ventas_detalles.utilidad_uni) as total_utilidad_monto
        ')

        ->groupBy('s.nombre','ventas.sucursal','ventas.caja')
        ->orderBy('s.nombre')
        ->orderBy('ventas.caja')
        ->get();

        // Totales derivados de los detalles
        $this->totalCosto    = $this->data->sum('total_costo');
        $this->totalUtilidad = $this->data->sum('total_utilidad_monto');
    }

    public function getCaja()
    {
        $this->cajas = Parametros::where('sucursal', $this->sucursal)->get();
    }
}