<?php

namespace App\Http\Livewire;

use App\Models\Inventarios;
use App\Models\Sucursales;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportInventarioController extends Component
{
    public $componentName, $data, $sucursal;


    public function mount()
    {
        $this->componentName = 'Reporte de Inventario';
        $this->data = [];
    }

    public function render()
    {
        $this->reporteGenerar();

        return view('livewire.reports.report-inventario', ['sucursales' => Sucursales::orderBy('id', 'asc')->get()])->extends('layouts.theme.app')->section('content');
    }

    public function reporteGenerar()
    {
        if($this->sucursal == 0)
        {
            $this->data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')
            ->join('categorias as c', 'c.id', 'p.categoria')
            ->join('medidas as m', 'm.id', 'p.medida')
            ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))
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
            ->where('inventarios.sucursal', $this->sucursal)
            ->get();
        }
    }
}
