<?php

namespace App\Http\Livewire;

use App\Models\Departamentos;
use App\Models\Municipios;
use Livewire\Component;
use Livewire\WithPagination;

class MunicipiosController extends Component
{
    use WithPagination;

    public  $codigo, $municipio, $status, $departamento, $departamentos, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Municipios';
        $this->departamentos = Departamentos::all();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Municipios::join('departamentos as d', 'd.id', 'municipios.departamento')
            ->select('municipios.*', 'd.departamento as depto')
            ->orwhere('distrito', 'like', '%' . $this->search. '%')
            ->orwhere('d.departamento', 'like', '%' . $this->search. '%')
            //->where('codigo', 'like', '' . $this->search . '')
            ->orderby('municipios.distrito', 'asc')
            ->paginate($this->pagination);
        else
            $data = Municipios::join('departamentos as d', 'd.id', 'municipios.departamento')
            ->select('municipios.*', 'd.departamento as depto')
            ->orderby('municipios.municipio', 'asc')->paginate($this->pagination);

        return view('livewire.municipios.municipio', ['municipios' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => "required|min:1",
            'municipio' => "required|unique:municipios|min:3",
            'municipio' => 'required',
            'status' => 'required'
        ];
        $messages = [
            'codigo.required' => 'El codigo del municipio es requerido',
            'codigo.min' => 'El codigo del municipio tiene que tener al menos 1 caracteres',
            'municipio.required' => 'El nombre del municipio es requerido',
            'municipio.unique' => 'El Nombre del municipio ya existe',
            'municipio.min' => 'El nombre del municipio tiene que tener al menos 3 caracteres',
            'departamento.required' => 'El nombre del departamento es requerido',
            'status.required' => 'El estado del municipio es requerido'
        ];

        $this->validate($rules, $messages);

        $municipio = Municipios::create([
            'codigo' => $this->codigo,
            'municipio' => $this->municipio,
            'departamento' => $this->departamento,
            'status' => $this->status,
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Municipio registrado');
    }

    public function Edit(Municipios $municipio)
    {
        $this->selected_id = $municipio->id;
        $this->codigo = $municipio->codigo;
        $this->municipio = $municipio->municipio;
        $this->departamento = $municipio->departamento;
        $this->status = $municipio->status;

        $this->emit('show-modal', 'Show Modal');
    }

    public function Update(Municipios $municipio)
    {
        $rules = [
            'codigo' => "required",
            'municipio' => "required|unique:municipios,municipio,{$this->selected_id}|min:3",
            'departamento' => 'required',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo del municipio es requerido',
            'municipio.required' => 'El nombre del municipio es requerido',
            'municipio.unique' => 'El Nombre del municipio ya existe',
            'municipio.min' => 'El nombre del municipio tiene que tener al menos 3 caracteres',
            'departamento.required' => 'El nombre del departamento es requerido',
            'status.required' => 'El estado del municipio es requerido'
        ];

        $this->validate($rules, $messages);


        $municipio = Municipios::find($this->selected_id);
        $municipio->update([
            'codigo' => $this->codigo,
            'municipio' => $this->municipio,
            'departamento' => $this->departamento,
            'status' => $this->status,
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Municipio Actualizado');
    }

    public function resetUI()
    {
        $this->codigo = '';
        $this->municipio = '';
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

    public function Destroy(Municipios $municipio /*$id*/)
    {
        $municipio->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Municipio Eliminado');
    }
}
