<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TipoContigencia;
use Livewire\WithPagination;

class TipoContigenciaController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo Contingencia';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoContigencia::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoContigencia::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_contingencias.tipo-contingencia', ['tipo_contingencias' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_contingencias|min:1',
            'valor' => 'required|unique:tipo_contingencias|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de contingencia es requerido',
            'valor.unique' => 'Ya existe el tipo de contingencia',
            'valor.min'=> 'El tipo de contingencia debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $contingencia = TipoContigencia::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tipo de contingencia registrado');
    }

    public function Edit($id)
    {
        $record = TipoContigencia::find($id);
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
            'codigo' => "required|unique:tipo_contigencias,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_contigencias,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de contingencia es requerido',
            'valor.unique' => 'Ya existe el tipo de contingencia',
            'valor.min'=> 'El tipo de contingencia debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $contingencia = TipoContigencia::find($this->selected_id);
        $contingencia->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tipo de contingencia Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoContigencia $contingencia /*$id*/)
    {
        $contingencia->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tipo de contingencia Eliminada');
    }
}
