<?php

namespace App\Http\Livewire;

use App\Models\Compras;
use App\Models\ComprasDetalles;
use App\Models\Proveedores;
use App\Models\Sucursales;
use App\Models\tipoCompras;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportComprasController extends Component
{
    public $componentName, $data, $details, $sumDetails, $countDetails, $reporteType, $proveedor, $dateFrom, $dateTo, $ventaId, $totalCompras;
    public $sucursal   = 0;
    public $facturador = 0;
    public $totalSubtotal   = 0;
    public $totalIva        = 0;
    public $totalPercepcion = 0;
    public $totalGeneral    = 0;

    public function mount()
    {
        $this->componentName = 'Reporte de Compras';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reporteType = 0;
        $this->ventaId = 0;
        $this->totalCompras = 0;
    }

    public function render()
    {
        $user = Auth::user();

        $this->ComprasByDate();

        return view('livewire.reports.report-compras', [
            'proveedores' => Proveedores::orderBy('id', 'asc')->get(),
            'sucursales'  => Sucursales::where('empresa', $user->empresa)->orderBy('nombre')->get(),
            'facturadores'=> tipoCompras::orderBy('id')->get(),
        ])
        ->extends('layouts.theme.app')->section('content');
    }

    public function ComprasByDate()
    {
        if ($this->reporteType == 0) {
            $from = Carbon::now()->format('Y-m-d');
            $to   = Carbon::now()->format('Y-m-d');
        } else {
            $from = $this->dateFrom;
            $to   = $this->dateTo;
        }

        $query = Compras::join('proveedores as p', 'p.id', 'compras.proveedor')
            ->join('tipo_compras as tc', 'tc.id', 'compras.tipo')
            ->select(
                'compras.correlativo',
                'compras.serie',
                'compras.fecha',
                'tc.tipo as facturadors',
                'p.nombre',
                'compras.id',
                DB::raw('(SELECT SUM(cd.cantidad) FROM compras_detalles as cd WHERE cd.compra = compras.id) as items'),
                DB::raw('(SELECT SUM(cd.total) / 1.13 FROM compras_detalles as cd WHERE cd.compra = compras.id) as subtotal'),
                DB::raw('(SELECT SUM(cd.total) - SUM(cd.total) / 1.13 FROM compras_detalles as cd WHERE cd.compra = compras.id) as iva'),
                DB::raw('0 as percepcion'),
                DB::raw('(SELECT SUM(cd.total) FROM compras_detalles as cd WHERE cd.compra = compras.id) as total')
            )
            ->whereBetween('compras.fecha', [$from, $to]);

        if ($this->proveedor && $this->proveedor != 0) {
            $query->where('compras.proveedor', $this->proveedor);
        }

        if ($this->sucursal && $this->sucursal != 0) {
            $query->where('compras.sucursal', $this->sucursal);
        }

        if ($this->facturador && $this->facturador != 0) {
            $query->where('compras.tipo', $this->facturador);
        }

        $this->data = $query->get();

        $this->totalCompras    = collect($this->data)->sum('total');
        $this->totalSubtotal   = collect($this->data)->sum('subtotal');
        $this->totalIva        = collect($this->data)->sum('iva');
        $this->totalPercepcion = 0;
        $this->totalGeneral    = collect($this->data)->sum('total');
    }

    public function getDetails($id)
    {
        $this->details = ComprasDetalles::join('productos as p', 'p.id', 'compras_detalles.producto')
        ->select('compras_detalles.costo as precio', 'compras_detalles.total', 'p.nombreProducto', 'p.codebar3', 'compras_detalles.cantidad')
        ->where('compras_detalles.compra', $id)
        ->get();

        $suma = $this->details->sum(function ($item) {
            return $item->total;
        });
        $this->sumDetails   = $suma;
        $this->countDetails = ComprasDetalles::where('compra', $id)->sum('cantidad');

        $this->ventaId = Compras::where('id', $id)->value('correlativo');

        $this->emit('show-modal', '');
    }
}
