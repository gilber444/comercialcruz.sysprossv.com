<?php

namespace App\Http\Livewire;

use App\Models\Inventarios;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\VentasDetalles;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ModalSolicitud extends Component
{
    public $search,
        $products = [],
        $sucursales,
        $existencias = [],
        $selectedProduct,
        $productoSeleccionado,
        $ventas30 = [],
        $ventas90 = [],
        $origen;
    //cargar existencias


    public function mount()
    {
        $this->sucursales = Sucursales::all();
    }

    public function liveSearch()
    {
        dd('llega');
        $user = Auth::user();

        if (strlen($this->search) > 0) {
            $this->reset('products'); // Limpia la variable antes de actualizar
            $this->products = Productos::join('categorias as c', 'c.id', 'productos.categoria')
                ->join('medidas as m', 'm.id', 'productos.medida')
                ->join('inventarios as i', 'i.producto', 'productos.id')
                ->select('productos.id', 'm.unidad as medida', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                ->where('i.sucursal', $user->sucursal)
                ->where(function ($query) {
                    $query
                        ->where('productos.nombreProducto', 'like', $this->search . "%")
                        ->orWhere('productos.codebar1', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar2', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar3', 'like', "%{$this->search}%")
                        ->orWhere('productos.codealternativo', 'like', "%{$this->search}%");
                })
                ->groupBy('productos.id', 'm.unidad', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                ->orderBy('productos.nombreProducto', 'asc')
                ->take(50)
                ->get();
        } else {
            $this->products = [];
        }
    }
    public function render()
    {
        // $this->liveSearch();
        return view('livewire.existencias.modal-solicitud');
    }

    public function addAll()
    {
        if (count($this->products) > 0) {
            foreach ($this->products as $product) {
                $this->emit('scan-code-byid', $product->id);
            }
        }
    }

    protected $listeners = [
        //'loadExistencias' => 'loadExistencias',
        'resetSearch' => 'resetSearch',
    ];

    /*public function loadExistencias($productId = null)
    {
        // Asignar el producto seleccionado
        $this->productoSeleccionado = $productId;

        if ($productId) {
            // Obtener existencias en todas las sucursales para ese producto
            $this->existencias = Inventarios::where('producto', $productId)->get();
        } else {
            // Si no hay un producto, asignar 0 a las existencias
            $this->existencias = collect([['cantidad' => 0]]);
        }

        // Emitir evento para actualizar la fila seleccionada
        $this->emit('updateSelectedRow', $productId);
    }*/

    // public function resetSearch()
    // {
    //$this->search = ''; // Limpiar el campo de búsqueda en Livewire
    //$this->existencias = [];
    //$this->products = [];
    //}

    public function Add($id)
    {

        $this->emit('scan-code-byid', $id);
        //$this->search = '';
    }
}
