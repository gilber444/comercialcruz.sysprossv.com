<?php

namespace App\Http\Livewire;

use App\Models\Distritos;
use App\Models\Municipios;
use Livewire\Component;
use Livewire\WithPagination;

class DistritosController extends Component
{
    use WithPagination;

    public  $codigo, $distrito, $status, $municipio, $municipios, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Distritos';
        $this->municipios = Municipios::all();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Distritos::join('municipios as m', 'm.id', 'distritos.municipio')
            ->select('distritos.*', 'm.municipio as muni')
            ->orwhere('distrito', 'like', '%' . $this->search. '%')
            ->orwhere('m.municipio', 'like', '%' . $this->search. '%')
            ->orderby('distritos.distrito', 'asc')
            ->paginate($this->pagination);
        else
            $data = Distritos::join('municipios as m', 'm.id', 'distritos.municipio')
            ->select('distritos.*', 'm.municipio as muni')
            ->orderby('distritos.distrito', 'asc')->paginate($this->pagination);

        return view('livewire.distritos.distritos', ['distritos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => "required|min:1",
            'distrito' => "required|unique:distritos|min:3",
            'municipio' => 'required',
            'status' => 'required'
        ];
        $messages = [
            'codigo.required' => 'El codigo del distrito es requerido',
            'codigo.min' => 'El codigo del distrito tiene que tener al menos 1 caracteres',
            'distrito.required' => 'El nombre del distrito es requerido',
            'distrito.unique' => 'El Nombre del distrito ya existe',
            'distrito.min' => 'El nombre del distrito tiene que tener al menos 3 caracteres',
            'municipio.required' => 'El nombre del municipio es requerido',
            'status.required' => 'El estado del municipio es requerido'
        ];

        $this->validate($rules, $messages);

        $distrito = Distritos::create([
            'codigo' => $this->codigo,
            'distrito' => $this->distrito,
            'municipio' => $this->municipio,
            'status' => $this->status,
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Distrito registrado');
    }

    public function Edit(Distritos $distrito)
    {
        $this->selected_id = $distrito->id;
        $this->codigo = $distrito->codigo;
        $this->distrito = $distrito->distrito;
        $this->municipio = $distrito->municipio;
        $this->status = $distrito->status;

        $this->emit('show-modal', 'Show Modal');
    }

    public function Update(Distritos $distrito)
    {
        $rules = [
            'codigo' => "required",
            'distrito' => "required|unique:distritos,distrito,{$this->selected_id}|min:3",
            'municipio' => 'required',
            'status' => 'required'
        ];
        $messages = [
            'codigo.required' => 'El codigo del distrito es requerido',
            'distrito.required' => 'El nombre del distrito es requerido',
            'distrito.unique' => 'El Nombre del distrito ya existe',
            'distrito.min' => 'El nombre del distrito tiene que tener al menos 3 caracteres',
            'municipio.required' => 'El nombre del municipio es requerido',
            'status.required' => 'El estado del distrito es requerido'
        ];

        $this->validate($rules, $messages);

        $distrito = Distritos::find($this->selected_id);
        $distrito->update([
            'codigo' => $this->codigo,
            'distrito' => $this->distrito,
            'municipio' => $this->municipio,
            'status' => $this->status,
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Distrito Actualizado');
    }

    public function resetUI()
    {
        $this->codigo = '';
        $this->distrito = '';
        $this->municipio = '';
        $this->status = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Distritos $distrito /*$id*/)
    {
        $distrito->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Distrito Eliminado');
    }
}
