<?php

namespace App\Http\Livewire;

use App\Models\Categorias;
use App\Models\Inventarios;
use App\Models\Sucursales;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportInventarioCategoriaController extends Component
{
    public $componentName, $data, $sucursal, $categoria;

    public function mount()
    {
        $this->componentName = 'Reporte de Inventario por Categorias';
        $this->data = [];
        //$this->sucursal = 0;
        $this->categoria = 0;
    }

    public function render()
    {
        $this->reporteGenerar();

        return view('livewire.reports.report-inventario-categoria', ['sucursales' => Sucursales::orderBy('id', 'asc')->get(), 'categorias' => Categorias::orderBy('categoria', 'asc' )->get()])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function reporteGenerar()
    {
        if($this->sucursal == 0)
        {
            $this->data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')
            ->join('sucursales as s', 's.id', 'inventarios.sucursal')
            ->join('categorias as c', 'c.id', 'p.categoria')
            ->join('medidas as m', 'm.id', 'p.medida')
            ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))
            ->when($this->categoria != 0, function ($query) {
                return $query->where('p.categoria', $this->categoria);
            })
            ->groupBy('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad')
            ->get();
        }
        else
        {
            $this->data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')
            ->join('sucursales as s', 's.id', 'inventarios.sucursal')
            ->join('categorias as c', 'c.id', 'p.categoria')
            ->join('medidas as m', 'm.id', 'p.medida')
            ->select('inventarios.existencia', 'p.codebar3', 'p.nombreProducto', 's.nombre as sucursal', 'c.categoria', 'm.unidad as medida')
            ->when($this->categoria != 0, function ($query) {
                return $query->where('p.categoria', $this->categoria);
            })
            ->where('inventarios.sucursal', $this->sucursal)
            ->get();
        }
    }
}
