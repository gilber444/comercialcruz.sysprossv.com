<?php

namespace App\Http\Livewire;

use App\Models\Facturadores;
use App\Models\Proveedores;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportVentasProveedorController extends Component
{
    public $componentName,
        $data,
        $details,
        $sumDetails,
        $countDetails,
        $reporteType,
        $proveedor,
        $sucursal,
        $dateFrom,
        $dateTo,
        $ventaId,
        $cajas = [],
        $caja,
        $totalSales,
        $facturador,
        $facturadores;

    public function mount()
    {
        $this->componentName = 'Reporte de Ventas / Proveedor';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reporteType = 0;
        $this->ventaId = 0;
        $this->facturador = 0;

        $this->facturadores = Facturadores::all();
    }

    public function render()
    {
        return view('livewire.reports.report-ventas-proveedor', [
            'proveedores' => Proveedores::orderBy('id', 'asc')->get()
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function SalesByDate()
    {
        // Asignación de fechas
        $from = $this->reporteType == 0 ? date('Y-m-d') : $this->dateFrom;
        $to = $this->reporteType == 0 ? date('Y-m-d') : $this->dateTo;

        $query = DB::table('ventas_detalles as dv')
            ->join('ventas as v', 'v.id', '=', 'dv.venta')
            ->join('productos as p', 'p.id', '=', 'dv.producto')
            ->leftJoin('compras_detalles as dc', 'dc.producto', '=', 'p.id')
            ->leftJoin('compras as c', 'c.id', '=', 'dc.compra')
            ->leftJoin('proveedores as pr', 'pr.id', '=', 'c.proveedor')
            ->where('v.estado', '=', 'cancelado')
            ->whereBetween('v.fecha', [$from, $to])
            ->select(
                'pr.nombre as proveedor',
                'p.nombreProducto as productos',
                DB::raw('SUM(dv.cantidad) as total_vendido'),
                DB::raw('MIN(dv.costo) as costo_unitario'),
                DB::raw('SUM(dv.cantidad * dv.costo) as total_monto_vendido')
            )
            ->groupBy('pr.nombre', 'p.nombreProducto')
            ->orderBy('pr.nombre')
            ->orderBy('p.nombreProducto');


        // Filtro por proveedor si es diferente de 0
        if ($this->proveedor != 0 && $this->proveedor != null) {
            $query->where('pr.id', '=', $this->proveedor);
        }

        // Filtro por facturador si es diferente de 0
        if ($this->facturador != 0 && $this->facturador != null) {
            $query->where('v.facturador', $this->facturador);
        }

        // Obtener resultados
        $this->data = $query->get();
    }
}
