<?php

namespace App\Http\Livewire;

use App\Models\Facturadores;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use Livewire\Component;
use Carbon\Carbon;

class ReportVentasSintetizadoController extends Component
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
        $totalSales,
        $facturador,
        $facturadores;

    public function mount()
    {
        $this->componentName = 'Reporte de Ventas Sintetizado';
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

        return view('livewire.reports.report-ventas-sintetizado', ['sucursales' => Sucursales::orderBy('id', 'asc')->get()])
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

        $query = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')
            ->select(
                's.nombre as sucursal',
                \DB::raw('COUNT(ventas.id) as total_ventas'),
                \DB::raw('SUM(ventas.total) as total_monto')
            )
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'cancelado')
            ->whereNotIn('ventas.sucursal', [12,14])
            ->groupBy('s.nombre')
            ->orderBy('s.nombre');

        if ($this->sucursal != 0) {
            $query->where('ventas.sucursal', $this->sucursal);
        }

        if ($this->facturador != 0) {
            $query->where('ventas.facturador', $this->facturador);
        }

        $this->data = $query->get();
        $this->totalSales = $this->data->sum('total_monto');
    }
}
