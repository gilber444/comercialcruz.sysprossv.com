<?php

namespace App\Http\Livewire;

use App\Models\Facturadores;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Ventas;
use Carbon\Carbon;
use Livewire\Component;

class ReporteVendedorController extends Component
{
    public $componentName,
        $facturadores,
        $sucursales = [],
        $users = [],
        $user,
        $reporteType,
        $sucursal,
        $dateFrom,
        $dateTo,
        $data,
        $details,
        $sumDetails,
        $countDetails,
        $ventaId,
        $cajas = [],
        $caja,
        $totalSales,
        $facturador;

    public function mount()
    {
        $this->componentName = 'Reporte de Ventas por Vendedor';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reporteType = 0;
        $this->ventaId = 0;

        $this->sucursales = Sucursales::all();

        $this->users = User::orderBy('name', 'asc')
        ->where('status', 'ACTIVE')
        ->whereIn('profile', ['Cajero', 'Vendedor', 'Gerente'])
        ->get();

        $this->facturadores = Facturadores::all();
    }
    public function render()
    {
        //$this->SalesByDate();

        return view('livewire.reports.reporte-vendedor')->extends('layouts.theme.app')->section('content');
    }

    public function SalesByDate()
    {
        $ventas = Ventas::select('ventas.codigoVendedor as codigo',
                'users.name as nombre_vendedor',
                \DB::raw('SUM(ventas.total) as total_vendido'))
                ->join('users', function($join) {
                    $join->on('ventas.codigoVendedor', '=', 'users.codigo')
                         ->on('ventas.sucursal', '=', 'users.sucursal');
                })
            ->where('estado', 'cancelado');

        // // Aplicar filtros solo si hay algún filtro activo
        $hayFiltroFechas = !($this->reporteType == 0 || empty($this->dateFrom) || empty($this->dateTo));
        $hayFiltroSucursal = $this->sucursal != 0 && $this->sucursal != '';
        $hayFiltroUsuario = $this->user != 0 && $this->user != '';

        if ($hayFiltroFechas) {
            $from = $this->dateFrom;
            $to = $this->dateTo;
            $ventas->whereBetween('fecha', [$from, $to]);
        }

        if ($hayFiltroSucursal) {
           $ventas->where('ventas.sucursal', $this->sucursal);
        }

        if ($hayFiltroUsuario) {
            $codigoUsuario = User::find($this->user);
            if ($codigoUsuario) {
                $ventas->where('ventas.codigoVendedor', $codigoUsuario->codigo)
                       ->where('ventas.sucursal', $codigoUsuario->sucursal); // este extra asegura exactitud
            }
        }

        // Agrupar y obtener los resultados
        $ventas->groupBy('ventas.codigoVendedor', 'users.name');

        $this->data = $ventas->get();

        $this->totalSales = $this->data->sum('total_vendido');
    }

    // public function SalesByDate()
    // {
    //     if ($this->reporteType == 0) {
    //         $from = Carbon::now()->format('Y-m-d');
    //         $to = Carbon::now()->format('Y-m-d');
    //     } else {
    //         $from = $this->dateFrom;
    //         $to = $this->dateTo;
    //     }

    //     $ventas = Ventas::select('vendedor', \DB::raw('SUM(total) as total_vendido'))
    //         ->whereBetween('fecha', [$from, $to])
    //         ->where('estado', 'cancelado');

    //     if ($this->sucursal != 0 && $this->sucursal != '') {
    //         $ventas->where('sucursal', $this->sucursal);
    //     }

    //     if ($this->user != 0 && $this->user != '') {
    //         $ventas->where('vendedor', $this->user);
    //     }

    //     $ventas->groupBy('vendedor');

    //     $this->data = $ventas->get()->map(function ($row) {
    //         $usuario = User::find($row->vendedor);
    //         $row->nombre_vendedor = $usuario?->name ?? 'Sin Nombre';
    //         return $row;
    //     });

    //     $this->totalSales = $this->data->sum('total_vendido');
    // }
}
