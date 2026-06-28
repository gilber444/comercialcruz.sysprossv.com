<?php

namespace App\Http\Livewire;

use App\Models\IdentificacionReceptor;
use Livewire\Component;
use Livewire\WithPagination;

class IdentificacionReceptorController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Identificacion receptor';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = IdentificacionReceptor::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = IdentificacionReceptor::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.identificacion_receptor.identificacion-receptor', ['identificacion_receptors' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:identificacion_receptors|min:1',
            'valor' => 'required|unique:identificacion_receptors|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La identificacion del receptor es requerido',
            'valor.unique' => 'Ya existe la identificacion del receptor',
            'valor.min'=> 'La identificacion del receptor debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $receptor = IdentificacionReceptor::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Identificacion Receptor registrado');
    }

    public function Edit($id)
    {
        $record = IdentificacionReceptor::find($id);
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
            'codigo' => "required|unique:identificacion_receptors,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:identificacion_receptors,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La identificacion del receptor es requerido',
            'valor.unique' => 'Ya existe la identificacion del receptor',
            'valor.min'=> 'La identificacion del receptor debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $receptor = IdentificacionReceptor::find($this->selected_id);
        $receptor->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Identificacion Receptor Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(IdentificacionReceptor $receptor /*$id*/)
    {
        $receptor->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Identificacion Receptor Eliminado');
    }
}