<?php

namespace App\Http\Livewire;

use Livewire\Component;
use \DB;
use App\Models\Precios;
use App\Models\Productos;
class ModalNota extends Component
{
    public $search, $products = [];

    public function liveSearch()
    {
        if(strlen($this->search) > 0)
        {
            $this->products = Precios::with([
                'Rproductos:id,nombreProducto'
                ])
            ->where('cantidad', 1)
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
            //->select('codebar', 'costosiva', 'cantidad', 'presentacion', 'producto', \DB::raw('MIN(id) as id')) // 🔹 Seleccionamos solo el ID más bajo de cada producto
            //->groupBy('producto', 'codebar', 'presentacion', 'cantidad', 'costosiva') // 🔹 Agrupar por producto
            //->with(['Rproductos:id,nombreProducto']) // 🔹 Relacionamos para obtener el nombre del producto 🔹 Agrupa los precios por producto
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
        return view('livewire.nueva_nota_credito.modal_nota');
    }
}
