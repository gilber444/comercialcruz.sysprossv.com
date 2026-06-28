<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TipoTransmision;
use Livewire\WithPagination;

class TipoTransmisionController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo Transmision';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoTransmision::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoTransmision::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_transmision.tipo-transmision', ['tipo_transmisions' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_transmisions|min:1',
            'valor' => 'required|unique:tipo_transmisions|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de transmision es requerido',
            'valor.unique' => 'Ya existe el tipo de transmision',
            'valor.min'=> 'El tipo de transmision debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $transmision = TipoTransmision::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tipo de Transmision registrado');
    }

    public function Edit($id)
    {
        $record = TipoTransmision::find($id);
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
            'codigo' => "required|unique:tipo_transmisions,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_transmisions,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de transmision es requerido',
            'valor.unique' => 'Ya existe el tipo de transmision',
            'valor.min'=> 'El tipo de transmision debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $transmision = TipoTransmision::find($this->selected_id);
        $transmision->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tipo de Transmision Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoTransmision $transmision /*$id*/)
    {
        $transmision->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tipo de Transmision Eliminada');
    }
}