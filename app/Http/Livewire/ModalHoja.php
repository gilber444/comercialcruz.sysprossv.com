<?php

namespace App\Http\Livewire;

use App\Models\Inventarios;
use App\Models\Precios;
use App\Models\Productos;
use Livewire\Component;

class ModalHoja extends Component
{
    public $search, $products = [], $sucursal;

    public function liveSearch()
    {
        if(strlen($this->search) > 0)
        {
            $this->products = Precios::with([
                'Rproductos:id,nombreProducto'
            ])
            ->where('cantidad', 1)
            ->whereNull('deleted_at')
            ->whereHas('Rproductos', function ($query) {
                $query->where('activo', 1) // Solo productos activos
                    ->where(function ($q) {
                        $q->where('nombreProducto', 'like', $this->search . '%')
                            ->orWhere('codebar', 'like', $this->search . '%'); // Permite búsqueda por código de barras
                    });
            })
            ->selectRaw('MIN(id) as id, producto, cantidad, costosiva,  codebar, presentacion')
            ->groupBy('producto', 'cantidad', 'costosiva', 'codebar', 'presentacion')
            ->take(50)
            ->get();
        }
        else
        {
            $this->products = [];
        }
    }

    public function render()
    {
        $this->liveSearch();
        return view('livewire.hoja_inventarios.detalle');
    }

    public function Add($id)
    {
        //dd($id);
        $this->emit('scan-code-byid', $id);
        $this->search = '';
    }
}

