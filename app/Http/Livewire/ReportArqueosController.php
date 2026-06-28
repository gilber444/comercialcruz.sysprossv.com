<?php

namespace App\Http\Livewire;

use App\Models\Arqueos;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\User;
use Livewire\Component;

class ReportArqueosController extends Component
{
    public $componentName, $data, $details, $sumDetails, $countDetails, $dateFrom, $dateTo, $sucursal, $caja, $user;

    public function mount()
    {
        $this->componentName = 'Reporte de Arqueos';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
    }

    public function render()
    {
        $this->generarReporte();

        // Obtener las sucursales ordenadas por id
        $sucursales = Sucursales::orderBy('id', 'asc')->get();
        // Obtener los cajeros con el perfil "Cajeros"
        $cajeros = User::where('profile', 'Cajeros')->get();
        // Inicializar la variable de cajas
        $cajas = collect();
        // Si $this->sucursal es 0, obtener todos los parámetros
        if ($this->sucursal == 0) {
            $cajas = Parametros::all();
        } else {
            // Si $this->sucursal no es 0, filtrar por sucursal
            $cajas = Parametros::where('sucursal', $this->sucursal)->get();
        }

        return view('livewire.reports.report-arqueos', [
            'sucursales' => $sucursales,
            'cajas' => $cajas,
            'cajeros' => $cajeros,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function generarReporte()
    {

        $query = Arqueos::join('parametros as p', 'p.id', 'arqueos.caja')->join('users as u', 'u.id', 'arqueos.cajero')->select('arqueos.id', 'arqueos.numero', 'arqueos.fecha', 'arqueos.hora', 'p.caja', 'u.name as cajero', 'arqueos.totalGlobal', 'arqueos.totalEfectivo', 'arqueos.diferencia', 'arqueos.remesas');

        if ($this->sucursal != 0) {
            $query->where('arqueos.sucursal', $this->sucursal);
        }

        if ($this->caja != 0) {
            $query->where('p.id', $this->caja);
        }

        if ($this->user != 0) {
            $query->where('arqueos.cajero', $this->user);
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $query->whereBetween('arqueos.fecha', [$this->dateFrom, $this->dateTo]);
        }

        $this->data = $query->get();
    }
}
