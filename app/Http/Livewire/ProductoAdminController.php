<?php

namespace App\Http\Livewire;

use App\Models\Categorias;
use App\Models\Familias;
use App\Models\Medidas;
use App\Models\Productos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductoAdminController extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $producto, $selected_id, $pageTitle, $componentName, $barcode, $exento, $marca, $costo, $stock, $alerts, $categoriaId, $pv1, $cant1, $pv2, $cant2, $medidaId, $pv3, $cant3, $pv4, $cant4, $image;

    public $search = '';
    public $page = 1;
    public $producto_seleccionado = null;


    private $pagination = 100;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Productos';
        $this->categoriaId = 'Elegir Categoria';
        $this->medidaId = 'Elegir Unidad Medida';

        $this->search = session()->get('producto_search', '');
        $this->page = session()->get('producto_pagina', 1);
        $this->producto_seleccionado = session()->get('producto_seleccionado', null);
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $query = Productos::join('categorias as c', 'c.id', 'productos.categoria')
            ->join('medidas as m', 'm.id', 'productos.medida')
            ->join('familias as f', 'f.id', 'productos.familia')
            ->select('productos.*', 'c.categoria as categoria', 'm.unidad as medida', 'f.familia')
            ->orderBy('productos.nombreProducto', 'asc');

        if (strlen($this->search) > 0) {
            $query->where(function ($q) {
                $q->where('productos.nombreProducto', 'like', $this->search . '%') // Coincidencia al inicio
                    ->orWhere('productos.codebar1', 'like', $this->search . '%')
                    ->orWhere('productos.codebar2', 'like', $this->search . '%')
                    ->orWhere('productos.codebar3', 'like', $this->search . '%')
                    ->orWhere('productos.codealternativo', 'like',  $this->search . '%')
                    ->orWhere('c.categoria', 'like',  $this->search . '%')
                    ->orWhere('f.familia', 'like',  $this->search . '%');
            });
        } //else {
            //$query->where('productos.nombreProducto', 'like', 'C%')->where('c.id', 1);
        //}

        $data = $query->paginate($this->pagination);

        return view('livewire.productos.productoAdmin', [
            'productos' => $data,
            'categorias' => Categorias::orderBy('categoria', 'asc')->get(),
            'medidas' => Medidas::orderBy('unidad', 'asc')->get(),
            'search'     => $this->search,
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function resetUI()
    {
        $this->producto = '';
        $this->barcode = '';
        $this->costo = '';
        $this->pv1 = '';
        $this->pv2 = '';
        $this->pv3 = '';
        $this->pv4 = '';
        $this->cant1 = '';
        $this->cant2 = '';
        $this->cant3 = '';
        $this->cant4 = '';
        $this->stock = '';
        $this->alerts = '';
        $this->categoriaId = 'Elegir Categoria';
        $this->medidaId = 'Elegir Unidad Medida';
        $this->marca = '';
        $this->exento = 'No';
        $this->image = null;
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'guardarEstadoSeleccion' => 'guardarEstadoSeleccion'
    ];




    public function Destroy($id)
    {
        $product = Productos::find($id);
        $imageTemp = $product->img;
        $product->delete();

        if ($imageTemp != null) {
            if (file_exists('storage/productos' . $imageTemp)) {
                unlink('storage/productos' . $imageTemp);
            }
        }

        $this->resetUI();
        //$this->emit('item-deleted', 'Producto Eliminado');
    }

    protected $queryString = ['search', 'page'];


    public function guardarEstadoSeleccion($data)
    {
        session([
            'producto_seleccionado' => $data['id'] ?? null,
            'producto_search' => $data['search'] ?? '',
            'producto_pagina' => $data['page'] ?? 1,
        ]);
    }
}
