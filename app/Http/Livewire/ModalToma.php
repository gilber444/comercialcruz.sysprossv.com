<?php

namespace App\Http\Livewire;

use App\Models\Precios;
use App\Models\Productos;
use Livewire\Component;

class ModalToma extends Component
{
    public $search, $products = [];

    public function liveSearch()
    {
        if(strlen($this->search) > 0)
        {
            $this->products = Precios::with([
                'Rproductos:id,nombreProducto'
                ])
            ->whereHas('Rproductos', function ($query) {
                $query->where('activo', 1) // Solo productos activos
                      ->where('nombreProducto', 'like', '%' . $this->search . '%');
            })
            ->orWhere(function ($query) {
                $query->whereHas('Rproductos', function ($q) {
                    $q->where('activo', 1); // Solo productos activos
                })
                ->where('codebar', 'like', '%' . $this->search . '%');
            })
            ->get()
            ->take(20);

        }
        else
        {
            $this->products = [];
        }
    }
    public function render()
    {
        $this->liveSearch();
        return view('livewire.toma_ordens.modal-toma');
    }

    public function addAll()
    {
        if(count($this->products) > 0)
        {
           foreach($this->products as $product)
           {
            $this->emit('scan-code-byid', $product->id);
           }
        }
    }

    public function Add($id)
    {
        //dd($id);
        $this->emit('scan-code-byid', $id);
        //$this->search = '';
    }
}
