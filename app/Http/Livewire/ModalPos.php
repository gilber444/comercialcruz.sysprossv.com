<?php

namespace App\Http\Livewire;

use App\Models\Inventarios;
use App\Models\Precios;
use App\Models\Productos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ModalPos extends Component
{
    public $search, $products = [];

    public function liveSearch()
    {
        $user = Auth::user();
        $sucursalId = $user->sucursal;

        if (strlen($this->search) > 2) {
            $subquery = Precios::selectRaw('MIN(cantidad)')
                ->whereColumn('producto', 'productos.id')
                ->whereNull('deleted_at');

            $this->products = Precios::select(
                    'precios.id',
                    'precios.pvventa',
                    'precios.presentacion',
                    'precios.cantidad',
                    'productos.nombreProducto',
                    'productos.codebar1',
                    'productos.codebar2',
                    'productos.codebar3',
                    'productos.codealternativo',
                    'inventarios.existencia'
                )
                ->join('productos', 'productos.id', '=', 'precios.producto')
                ->join('inventarios', function ($join) use ($sucursalId) {
                    $join->on('inventarios.producto', '=', 'productos.id')
                        ->where('inventarios.sucursal', $sucursalId)
                        ->where('inventarios.existencia', '>', 0);
                })
                ->join('sucursal_precios as sp', function ($join) use ($sucursalId) {
                    $join->on('sp.precio', '=', 'precios.id')
                        ->where('sp.sucursal', $sucursalId)
                        ->where('sp.activo', 1);
                })
                ->where('productos.activo', 1)
                ->whereNull('productos.deleted_at')
                ->whereNull('precios.deleted_at')
                ->where(function ($q) {
                    $q->where('productos.nombreProducto', 'like', $this->search . '%')
                    ->orWhere('productos.codebar1', 'like', $this->search . '%')
                    ->orWhere('productos.codebar2', 'like', $this->search . '%')
                    ->orWhere('productos.codebar3',  $this->search )
                    ->orWhere('productos.codealternativo', 'like', $this->search . '%');
                })
                ->where('precios.cantidad', $subquery) // ✅ solo el precio de menor cantidad
                ->orderBy('productos.nombreProducto')
                ->limit(50)
                ->get();
        } else {
            $this->products = [];
        }



    }


    public function render()
    {
        $this->liveSearch();
        return view('livewire.pos.modal-pos');
    }

    public function Add($barcode)
    {
        //dd($barcode);
        $this->dispatch('scan-code', $barcode);
        //$this->search = '';

    }
}
