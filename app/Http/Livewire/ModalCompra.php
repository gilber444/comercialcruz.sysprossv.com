<?php

namespace App\Http\Livewire;

use App\Models\Precios;
use App\Models\Productos;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ModalCompra extends Component
{
    public $search,
        $products = [];

    public function liveSearch()
    {
        if (strlen($this->search) > 0) {
            $precios = Precios::query()
                ->withoutTrashed() // excluye precios soft-deleted
                ->with([
                    'Rproductos' => function ($q) {
                        $q->select('id', 'nombreProducto')->withoutTrashed(); // excluye productos soft-deleted
                    },
                ])
                ->whereHas('Rproductos', function ($q) {
                    $q->withoutTrashed()
                        ->where('activo', 1)
                        ->where(function ($q2) {
                            $q2->where('nombreProducto', 'like', '%' . $this->search . '%')
                                ->orWhere('codebar1', 'like', '%' . $this->search . '%')
                                ->orWhere('codebar2', 'like', '%' . $this->search . '%')
                                ->orWhere('codebar3', 'like', '%' . $this->search . '%')
                                ->orWhere('codealternativo', 'like', '%' . $this->search . '%');
                        });
                })
                ->orderBy('producto') // para que unique() sea estable
                ->orderBy('cantidad', 'asc') // menor cantidad primero
                ->get();

            // Quedarse con el precio de menor cantidad por producto
            $this->products = $precios->unique('producto')->values()->take(30);
        } else {
            $this->products = [];
        }
    }
    public function render()
    {
        $this->liveSearch();
        return view('livewire.compras.modal-compra');
    }

    public function addAll()
    {
        if (count($this->products) > 0) {
            foreach ($this->products as $product) {
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
