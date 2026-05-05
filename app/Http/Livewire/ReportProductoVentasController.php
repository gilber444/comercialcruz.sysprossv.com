<?php

namespace App\Http\Livewire;

use App\Models\Facturadores;
use App\Models\Parametros;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportProductoVentasController extends Component
{
    public $componentName,
        $data,
        $details,
        $sumDetails,
        $countDetails,
        $reporteType,
        $dateFrom,
        $dateTo,
        $productos,
        $producto,
        $totalSales;

    public function mount()
    {
        $this->componentName = 'Reporte de Producto';
        $this->data = [];
        $this->reporteType = 0;

        $this->productos = Productos::all();
    }

    public function render()
    {
        //$this->SalesByDate();

        return view('livewire.reports.report-producto-ventas')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function SalesByDate()
    {
        if ($this->reporteType == 0) {
            $from = Carbon::now()->format('Y-m-d');
            $to = Carbon::now()->format('Y-m-d');
        } else {
            $from = $this->dateFrom;
            $to = $this->dateTo;
        }
        //dd($this->producto);

        $query = Ventas::join('ventas_detalles', 'ventas.id', '=', 'ventas_detalles.venta')
            ->join('productos as p', 'p.id', '=', 'ventas_detalles.producto')
            ->join('medidas as u', 'u.id', '=', 'ventas_detalles.medida')
            ->select(
                'p.nombreProducto as producto',
                'p.codebar3 as codigo',
                'u.unidad as unidad',
                DB::raw('SUM(ventas_detalles.cantidad) as cantidad'),
                DB::raw('AVG(ventas_detalles.precio) as precio'), // Si el precio puede variar, puedes usar AVG o MAX según necesidad
                DB::raw('SUM(ventas_detalles.total) as total_detalle')
            )
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'Cancelado')
            ->groupBy('p.nombreProducto', 'u.unidad', 'p.codebar3')
            ->orderBy('p.nombreProducto', 'asc');

        if ($this->producto != 0) {
            $query->where('ventas_detalles.producto', $this->producto);
        }

        $this->data = $query->get();

        // Total acumulado de los productos vendidos
        $this->totalSales = $this->data->sum('total_detalle');
    }
}
