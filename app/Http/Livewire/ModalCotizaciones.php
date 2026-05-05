<?php

namespace App\Http\Livewire;

use App\Models\Productos;
use Livewire\Component;

class ModalCotizaciones extends Component
{
    public $search, $products = [];

    public function liveSearch()
    {
        if (strlen($this->search) > 0) {
            $this->products = Productos::join('medidas as m', 'm.id', '=', 'productos.medida')
                ->select('productos.id', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar2', 'productos.codebar3', 'productos.codealternativo', 'm.unidad as medida')
                ->where(function ($query) {
                    $query
                        ->where('productos.nombreProducto', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar1', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar2', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar3', 'like', "%{$this->search}%")
                        ->orWhere('productos.codealternativo', 'like', "%{$this->search}%");
                })
                ->orderBy('productos.nombreProducto', 'asc')
                ->take(20)
                ->get();
        } else {
            $this->products = [];
        }
    }

    public function render()
    {
        $this->liveSearch();
        return view('livewire.cotizaciones.modal-cotizaciones');
    }

    public function Add($barcode)
    {
        $this->emit('scan-code', $barcode);
        $this->search = '';
    }
}
