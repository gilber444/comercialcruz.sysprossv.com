<?php

namespace App\Http\Livewire;

use App\Models\Empresas;
use App\Models\Parametros;
use App\Models\Sucursales;
use Livewire\Component;
use App\Models\Ubicaciones;
use App\Models\User;
use Livewire\WithPagination;

class UbicacionesController extends Component
{
    use WithPagination;

    public $selectedEmpresa = null, $selectedSucursal = null, $selectedParametro = null;
    public  $empresa = null, $sucursales = null, $parametros = null, $search, $selected_id, $pageTitle, $componentName, $usuario, $s, $c;

    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Ubiaciones de Usuarios en Sistema';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Ubicaciones::join('empresas as e', 'e.id', 'ubicaciones.empresa')
            ->join('sucursales as s', 's.id', 'ubicaciones.sucursal')
            ->join('parametros as p', 'p.id', 'ubicaciones.caja')
            ->join('users as u', 'u.id', 'ubicaciones.usuario')
            ->select('ubicaciones.*', 'e.empresa as empresas', 's.nombre as sucursales', 'p.caja as cajas', 'u.name')
            ->where('e.empresa', 'like', '%' . $this->search . '%')
            ->orWhere('s.nombre', 'like', '%' . $this->search . '%')
            ->orWhere('p.caja', 'like', '%' . $this->search . '%')
            ->orWhere('u.name', 'like', '%' . $this->search . '%')
            ->orderBy('u.name', 'asc')
            ->paginate($this->pagination);
        else
            $data = Ubicaciones::join('empresas as e', 'e.id', 'ubicaciones.empresa')
            ->join('sucursales as s', 's.id', 'ubicaciones.sucursal')
            ->join('parametros as p', 'p.id', 'ubicaciones.caja')
            ->join('users as u', 'u.id', 'ubicaciones.usuario')
            ->select('ubicaciones.*', 'e.empresa as empresas', 's.nombre as sucursales', 'p.caja as cajas', 'u.name')
            ->orderBy('u.name', 'asc')
            ->paginate($this->pagination);

        return view('livewire.ubicaciones.ubicaciones',
        [
            'ubicaciones' => $data,
            'empresas' => Empresas::all(),
            'usuarios' => User::where('profile', '<>', 'Super')->get()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function updatedselectedEmpresa($empresa_id)
    {
        $this->sucursales = Sucursales::where('empresa', $empresa_id)->get();
    }

    public function updatedselectedSucursal($sucursal_id)
    {
        $this->parametros = Parametros::where('sucursal', $sucursal_id)->get();
    }

    public function Store()
    {
        $rules = [
            'selectedEmpresa' => 'not_in:Elegir',
            'selectedSucursal' => 'not_in:Elegir',
            'selectedParametro' => 'not_in:Elegir'
        ];

        $messages = [
            'selectedEmpresa.not_in' => 'Elige un nombre de empresa diferente de Elegir',
            'selectedSucursal.not_in' => 'Elige un nombre de sucursal diferente de Elegir',
            'selectedParametro.not_in' => 'Elige ua caja diferente de Elegir',
            'usuario.not_in' => 'Elige un nombre de usuario diferente de Elegir'
        ];

        $this->validate($rules, $messages);

        $existingUbicacion = Ubicaciones::where('usuario', $this->usuario)
        ->where('estado', 'Activo')
        ->first();

        if ($existingUbicacion) {
            $existingUbicacion->update(['estado' => 'Inactivo']);
        }

        $ubi = Ubicaciones::create([
            'usuario' => $this->usuario,
            'empresa' => $this->selectedEmpresa,
            'sucursal' => $this->selectedSucursal,
            'caja' => $this->selectedParametro,
            'estado' => 'Activo'
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Ubicacion de Usuario registrado');
    }


    public function Edit($id)
    {
        $record = Ubicaciones::join('sucursales as s', 's.id', 'ubicaciones.sucursal')
        ->join('parametros as p', 'p.id', 'ubicaciones.caja')->select()->select('ubicaciones.*', 's.nombre as sucursales', 'p.caja as cajas')->find($id);
        $this->selectedEmpresa = $record->empresa;
        $this->selected_id = $record->id;
        $this->selectedSucursal = $record->sucursal;
        $this->selectedParametro = $record->caja;
        $this->usuario = $record->usuario;
        $this->s = $record->sucursales;
        $this->c= $record->cajas;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $rules = [
            'selectedEmpresa' => 'not_in:Elegir',
            'selectedSucursal' => 'not_in:Elegir',
            'selectedParametro' => 'not_in:Elegir',
        ];

        $messages = [
            'selectedEmpresa.not_in' => 'Elige un nombre de empresa diferente de Elegir',
            'selectedSucursal.not_in' => 'Elige un nombre de sucursal diferente de Elegir',
            'selectedParametro.not_in' => 'Elige ua caja diferente de Elegir',
        ];

        $this->validate($rules, $messages);

        $ubis = Ubicaciones::find($this->selected_id);
        $ubis->update([
            'empresa' => $this->selectedEmpresa,
            'sucursal' => $this->selectedSucursal,
            'caja' => $this->selectedParametro
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Ubicacion Actualizada');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Ubicaciones $ubica )
    {
        $ubica->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'La Ubicacion del usuario ha sido Eliminada');
    }

    public function resetUI()
    {
        $this->selectedEmpresa = '';
        $this->selectedSucursal = '';
        $this->selectedParametro = '';
        $this->sucursales = null;
        $this->parametros = null;
        $this->s = null;
        $this->c = null;
        $this->resetValidation();
        $this->resetPage();
    }
}
