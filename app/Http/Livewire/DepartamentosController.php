<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Departamentos;
use Livewire\WithPagination;

class DepartamentosController extends Component
{
    use WithPagination;

    public  $codigo, $departamento, $status, $search, $selected_id, $pageTitle, $componentName, $codGeneracion;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Departamentos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Departamentos::where('codigo', 'like', '' . $this->search . '')
            ->orwhere('departamento', 'like', '%' . $this->search. '%')
            ->paginate($this->pagination);
        else
            $data = Departamentos::orderBy('departamento', 'asc')->paginate($this->pagination);

        return view('livewire.departamentos.departamentos', ['departamentos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:departamentos|min:1',
            'departamento' => 'required|unique:departamentos|min:3',
            'status' => 'required',
        ];
        $messages = [
        'codigo.required' => 'El codigo del departamento es requerido',
        'codigo.unique' => 'Ya existe el codigo',
        'codigo.min' => 'El codigo del departamento tiene que tener al menos 1 caracteres',
        'departamento.required' => 'El nombre del departamento es requerido',
        'departamento.unique' => 'El Nombre del departamento ya existe',
        'departamento.min' => 'El nombre del departamento tiene que tener al menos 3 caracteres',
        'status.required' => 'El estado del departamento es requerido',
        ];

        $this->validate($rules, $messages);

        $departamento = Departamentos::create([
            'codigo' => $this->codigo,
            'departamento' => $this->departamento,
            'status' => $this->status,
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Departamento registrado');
    }

    public function Edit(Departamentos $departamento)
    {
        $this->selected_id = $departamento->id;
        $this->codigo = $departamento->codigo;
        $this->departamento = $departamento->departamento;
        $this->status = $departamento->status;

        $this->emit('show-modal', 'Show Modal');
    }

    public function Update(Departamentos $departamento)
    {
        $rules = [
            'codigo' => "required|unique:departamentos,codigo,{$this->selected_id}|min:1",
            'departamento' => "required|unique:departamentos,departamento,{$this->selected_id}|min:3",
            'departamento' => 'required',
        ];
        $messages = [
        'codigo.required' => 'El codigo del departamento es requerido',
        'codigo.unique' => 'Ya existe el codigo',
        'codigo.min' => 'El codigo del departamento tiene que tener al menos 1 caracteres',
        'departamento.required' => 'El nombre del departamento es requerido',
        'departamento.unique' => 'El Nombre del departamento ya existe',
        'departamento.min' => 'El nombre del departamento tiene que tener al menos 3 caracteres',
        'status.required' => 'El estado del departamento es requerido',
        ];

        $this->validate($rules, $messages);

        $departamento = Departamentos::find($this->selected_id);
        $departamento->update([
            'codigo' => $this->codigo,
            'departamento' => $this->departamento,
            'status' => $this->status,
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Departamento Actualizado');
    }

    public function resetUI()
    {
        $this->codigo = '';
        $this->departamento = '';
        $this->status = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Departamentos $departamento /*$id*/)
    {
        $departamento->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Departamento Eliminada');
    }
}
