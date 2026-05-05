<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Inventarios;
use App\Models\Sucursales;
use App\Models\Ubicaciones;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class ExistenciasController extends Component
{
    use WithPagination;

    public $search, $selected_id, $pageTitle, $componentName, $sucursalSeleccionada, $ubicacion;

    private $pagination = 100;

    public function mount()
    {
        $user = Auth::user();

        $this->pageTitle = 'Listado';
        $this->componentName = 'Existencias de Productos';

        if ($user->profile == 'Administrador' || $user->profile == 'Super' || $user->profile == 'GERENTE') {
            $this->sucursalSeleccionada = 'Global';
        } else {
            $this->sucursalSeleccionada = $user->sucursal;
        }
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();

        $query = Inventarios::query()
            ->join('productos', 'inventarios.producto', '=', 'productos.id')
            ->whereNull('productos.deleted_at')
            ->with(['Rsucursales:id,nombre', 'Rempresas:id,razon', 'Rproductos:id,nombreProducto,contenedor,maximo,minimo,codebar3,medida', 'Rproductos.Rmedidas:id,unidad'])
            ->select('inventarios.*'); // asegúrate de seleccionar solo campos válidos

        // Aplicar filtros como ya lo haces
        if ($user->profile == 'Administrador' || $user->profile == 'Super' || $user->profile == 'GERENTE') {
            if ($this->sucursalSeleccionada !== 'Global') {
                $query->where('inventarios.sucursal', $this->sucursalSeleccionada);
            }

            if (!empty($this->search)) {
                $query->where(function ($subQuery) {
                    $subQuery->where('productos.nombreProducto', 'like', '%' . $this->search . '%')->orWhere('productos.codebar3', 'like', '%' . $this->search . '%');
                });
            }
        } else {
            $query->where('inventarios.sucursal', $user->sucursal);

            if (!empty($this->search)) {
                $query->where(function ($subQuery) {
                    $subQuery->where('productos.nombreProducto', 'like', '%' . $this->search . '%')->orWhere('productos.codebar3', 'like', '%' . $this->search . '%');
                });
            }
        }

        // Aquí sí puedes ordenar por nombreProducto
        $query->orderBy('productos.nombreProducto', 'asc');

        $data = $query->paginate($this->pagination);

        return view('livewire.existencias.existencias', [
            'data' => $data,
            'rol' => $user->profile,
            'sucursales' => Sucursales::all(),
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
