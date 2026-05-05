<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\AmbienteDestino;
use Livewire\WithPagination;

class AmbienteDestinoController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Ambiente de Destino';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = AmbienteDestino::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = AmbienteDestino::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.ambiente_destino.ambiente-destino', ['ambiente_destinos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:ambiente_destinos|min:1',
            'valor' => 'required|unique:ambiente_destinos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'Codigo es requerido',
            'codigo.unique' => 'Ya existe el Codigo',
            'codigo.min'=> 'El codigo del ambiente de destino debe tener mas de 1 caracteres',
            'valor.required' => 'Ambiente destino es requerido',
            'valor.unique' => 'Ya existe el ambiente de destino',
            'valor.min'=> 'El Nombre del ambiente de destino debe tener mas de 3 caracteres',
            'status.required' => 'El estado del ambiente destino es requerido',
        ];

        $this->validate($rules, $messages);

        $ambiente = AmbienteDestino::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Ambiente de Destino registrado');
    }

    public function Edit($id)
    {
        $record = AmbienteDestino::find($id);
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
            'codigo' => "required|unique:ambiente_destinos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:ambiente_destinos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'Codigo es requerido',
            'codigo.unique' => 'Ya existe el Codigo',
            'codigo.min'=> 'El codigo del ambiente de destino debe tener mas de 1 caracteres',
            'valor.required' => 'Ambiente destino es requerido',
            'valor.unique' => 'Ya existe el ambiente de destino',
            'valor.min'=> 'El Nombre del ambiente de destino debe tener mas de 3 caracteres',
            'status.required' => 'El estado del ambiente destino es requerido',
        ];

        $this->validate($rules, $messages);

        $ambiente = AmbienteDestino::find($this->selected_id);
        $ambiente->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Ambiente de Destino Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(AmbienteDestino $ambiente /*$id*/)
    {
        $ambiente->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Ambiente de Destino Eliminada');
    }
}
