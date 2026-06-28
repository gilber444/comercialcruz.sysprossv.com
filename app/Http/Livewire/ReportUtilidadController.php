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

class ReportUtilidadController extends Component
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
        $this->componentName = 'Reporte de Utilidades';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reporteType = 0;
        $this->ventaId = 0;
        $this->sucursal = '';
        $this->caja = '';
        $this->facturador = '';
        $this->dateFrom = '';
        $this->dateTo = '';

        $this->facturadores = Facturadores::all();
    }

    public function render()
    {
        if (!\App\Models\Feature::isEnabled('reporte_utilidad_detallado')) {
            return view('livewire.features.disabled')
                ->extends('layouts.theme.app')
                ->section('content');
        }

        return view('livewire.reports.report-utilidad', ['sucursales' => Sucursales::orderBy('id', 'asc')->get()])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function SalesByDate()
    {
        if ($this->reporteType == 0) {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } else {
            $from = $this->dateFrom;
            $to = $this->dateTo;
        }

        $query = VentasDetalles::join('ventas', 'ventas.id', 'ventas_detalles.venta')
            ->join('sucursales as s', 's.id', 'ventas.sucursal')
            ->join('facturadores as f', 'f.id', 'ventas.facturador')
            ->join('productos as p', 'p.id', 'ventas_detalles.producto')
            /*->join('precios as pr', function($join) {
                $join->on('pr.producto', '=', 'p.id')
                    ->on('pr.medida', '=', 'ventas_detalles.medida')
                    ->on('pr.presentacion', '=', 'ventas_detalles.unidad')
                    ->whereNull('pr.deleted_at')
                    ->whereRaw('pr.costociva = (
                        SELECT MIN(p2.costociva)
                        FROM precios p2
                        WHERE p2.producto = p.id
                        AND p2.medida = ventas_detalles.medida
                        AND p2.presentacion = ventas_detalles.unidad
                    )');
            })*/
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'Cancelado');

        if ($this->sucursal != 0) $query->where('ventas.sucursal', $this->sucursal);
        if ($this->caja != 0) $query->where('ventas.caja', $this->caja);
        if ($this->facturador != 0) $query->where('ventas.facturador', $this->facturador);

        $this->data = $query->select(
            's.nombre as sucursal',
            'f.facturador as facturador',
            'p.nombreProducto',
            'ventas_detalles.cantidad',
            'ventas_detalles.costo',
            DB::raw('(ventas_detalles.cantidad * ventas_detalles.costo) as costo_total'),
            'ventas_detalles.precio as precio',
            'ventas_detalles.total as total_venta',
            'ventas_detalles.utilidad_uni as utilidad_uni',
            'ventas_detalles.utilidad as total_utilidad'
        )
            ->orderBy('s.nombre', 'asc')
            //->limit(2)
            ->get();


        //dd($this->data);

        $this->totalCosto = $this->data->sum('costo_total');
        $this->totalSales = $this->data->sum('total_venta');
        // utilidad como monto (venta - costo), no el % guardado en ventas_detalles.utilidad
        $this->totalUtilidad = $this->totalSales - $this->totalCosto;
    }


    public function getCaja()
    {
        $this->cajas = Parametros::where('sucursal', $this->sucursal)->get();
    }

    public function getDetails($id)
    {
        $this->details = VentasDetalles::join('productos as p', 'p.id', 'ventas_detalles.producto')->select('ventas_detalles.precio', 'ventas_detalles.total', 'p.nombreProducto', 'p.codebar3', 'ventas_detalles.cantidad')->where('ventas_detalles.venta', $id)->get();

        $suma = $this->details->sum(function ($item) {
            return $item->total;
        });
        $this->sumDetails = $suma;
        $this->countDetails = VentasDetalles::where('venta', $id)->sum('cantidad');
        $this->ventaId = Ventas::find($id)->value('numero');

        $this->emit('show-modal', '');
    }
}
