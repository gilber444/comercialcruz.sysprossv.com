<?php

namespace App\Http\Livewire;

use App\Models\Facturadores;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReportVentasController extends Component
{
    public $componentName,
        $data,
        $details,
        $sumDetails,
        $countDetails,
        $reporteType,
        $sucursal,
        $facturador,
        $dateFrom,
        $dateTo,
        $ventaId,
        $cajas = [],
        $caja,
        $totalSales;

    public function mount()
    {
        $this->componentName = 'Reporte de Ventas';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reporteType = 0;
        $this->ventaId = 0;
    }

    public function render()
    {
        $this->SalesByDate();

        $user = Auth::user();

        // Si es Super, Administrador o Gerente ve todas las sucursales
        if (in_array($user->profile, ['Super', 'Administrador', 'Gerente'])) {
            $sucursales = Sucursales::orderBy('id', 'asc')->get();

            $this->sucursal = 0;
        } else {
            // Solo su sucursal
            $sucursales = Sucursales::where('id', $user->sucursal)->orderBy('id', 'asc')->get();

            $this->sucursal = $user->sucursal;
        }

        return view('livewire.reports.report-ventas', [
            'sucursales'   => $sucursales,
            'facturadores' => Facturadores::orderBy('id')->get(),
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function SalesByDate()
    {
        // Rango de fechas
        if ($this->reporteType == 0) {
            $from = Carbon::now()->format('Y-m-d');
            $to = Carbon::now()->format('Y-m-d');
        } else {
            $from = $this->dateFrom;
            $to = $this->dateTo;
        }

        $user = Auth::user();
        $isAdmin = in_array($user->profile, ['Super', 'Administrador', 'Gerente']);

        // Base de la consulta
        $base = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')
            ->join('clientes as c', 'c.id', 'ventas.cliente')
            ->join('facturadores as f', 'f.id', 'ventas.facturador')
            ->select('ventas.id', 's.nombre', 'ventas.fecha', 'ventas.hora', 'ventas.numero', 'ventas.codigo', 'ventas.total', 'c.nombreCliente', 'f.facturador as facturadors', 'ventas.correlativo')
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'cancelado');

        // 🔒 Restricción por rol:
        // - Admins (Super/Administrador/Gerente): pueden ver todas (y filtrar por "Todas")
        // - Otros (por ejemplo Cajero): SIEMPRE limitados a su sucursal
        if (!$isAdmin) {
            $base->where('ventas.sucursal', $user->sucursal);
        }

        // Filtros seleccionados en UI
        if (!empty($this->sucursal) && (int) $this->sucursal !== 0) {
            $base->where('ventas.sucursal', (int) $this->sucursal);
        }

        if (!empty($this->caja) && (int) $this->caja !== 0) {
            $base->where('ventas.caja', (int) $this->caja);
        }

        if (!empty($this->facturador) && (int) $this->facturador !== 0) {
            $base->where('ventas.facturador', (int) $this->facturador);
        }

        // Clonar para no duplicar lógica y mantener filtros en ambos queries
        $forTable = (clone $base)
        ->orderBy('ventas.fecha', 'asc')
        ->orderBy('ventas.hora', 'asc');
        //->orderBy('ventas.fecha', 'asc');

        // Datos para la tabla
        $this->data = $forTable->get();

        // Total con los mismos filtros (rol + sucursal/caja + fechas + estado)
        $this->totalSales = $base->sum('ventas.total');
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
