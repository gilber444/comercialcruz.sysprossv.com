<?php

namespace App\Http\Livewire;

use App\Models\TipoPersona;
use Livewire\Component;
use Livewire\WithPagination;

class TipoPersonaController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo Persona';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoPersona::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoPersona::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_persona.tipo-persona', ['tipo_personas' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_personas|min:1',
            'valor' => 'required|unique:tipo_personas|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de persona es requerido',
            'valor.unique' => 'Ya existe el tipo de persona',
            'valor.min'=> 'El tipo de persona debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $persona = TipoPersona::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tipo Persona registrado');
    }

    public function Edit($id)
    {
        $record = TipoPersona::find($id);
        $this->codigo = $record->codigo;
        $this->valor = $record->valor;
        $this->status = $record->status;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->codigo = '';
        $this->valor = '';
        $this->status = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'codigo' => "required|unique:tipo_personas,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_personas,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de persona es requerido',
            'valor.unique' => 'Ya existe el tipo de persona',
            'valor.min'=> 'El tipo de persona debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $persona = TipoPersona::find($this->selected_id);
        $persona->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tipo Persona Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoPersona $persona /*$id*/)
    {
        $persona->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tipo Persona Eliminado');
    }
}
