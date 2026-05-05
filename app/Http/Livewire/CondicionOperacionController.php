<?php

namespace App\Http\Livewire;

use App\Models\CondicionOperacion;
use Livewire\Component;
use Livewire\WithPagination;

class CondicionOperacionController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Condicion Operacion';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = CondicionOperacion::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = CondicionOperacion::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.condicion_operacion.condicion-operacion', ['condicion_operacions' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:condicion_operacions|min:1',
            'valor' => 'required|unique:condicion_operacions|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La condicion de la operacion es requerido',
            'valor.unique' => 'Ya existe condicion de la operacion',
            'valor.min'=> 'La condicion de la operacion debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $operacion = CondicionOperacion::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Condicion Operacion registrado');
    }

    public function Edit($id)
    {
        $record = CondicionOperacion::find($id);
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
            'codigo' => "required|unique:condicion_operacions,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:condicion_operacions,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La condicion de la operacion es requerido',
            'valor.unique' => 'Ya existe la condicion de la operacion',
            'valor.min'=> 'La condicion de la operacion debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $operacion = CondicionOperacion::find($this->selected_id);
        $operacion->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Condicion Operacion Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(CondicionOperacion $operacion /*$id*/)
    {
        $operacion->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Condicion Operacion Eliminada');
    }
}
