<?php

namespace App\Http\Livewire;

use App\Models\Cortes;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\User;
use Livewire\Component;

class ReportCortesZController extends Component
{
    public $componentName, $data, $details, $sumDetails, $countDetails, $dateFrom, $dateTo, $sucursal, $caja;

    public function mount()
    {
        $this->componentName = 'Reporte de Cortes Z';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
    }

    public function render(){

        $this->generarReporte();

        // Obtener las sucursales ordenadas por id
        $sucursales = Sucursales::orderBy('id', 'asc')->get();
        // Inicializar la variable de cajas
        $cajas = collect();
        // Si $this->sucursal es 0, obtener todos los parámetros
        if ($this->sucursal == 0) {
            $cajas = Parametros::all();
        } else {
            // Si $this->sucursal no es 0, filtrar por sucursal
            $cajas = Parametros::where('sucursal', $this->sucursal)->get();
        }

        return view('livewire.reports.report-cortes-z', [
            'sucursales' => $sucursales,
            'cajas' => $cajas
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function generarReporte()
    {
        $query = Cortes::join('parametros as p', 'p.id', 'cortes.caja')
        ->select(
            'cortes.id', 'cortes.corte', 'cortes.fecha', 'cortes.hora', 'p.caja', 'cortes.totalGlobal', 'cortes.totalEfectivo', 'cortes.diferencia'
        );
        $query->where('cortes.estado', 'Cerrado');
        if ($this->sucursal != 0) {
            $query->where('cortes.sucursal', $this->sucursal);
        }

        if ($this->caja != 0) {
            $query->where('p.id', $this->caja);
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $query->whereBetween('cortes.fecha', [$this->dateFrom, $this->dateTo]);
        }

        $this->data = $query->get();
    }
}
