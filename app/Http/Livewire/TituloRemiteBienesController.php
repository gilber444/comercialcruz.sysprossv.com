<?php

namespace App\Http\Livewire;

use App\Models\TituloRemiteBienes;
use Livewire\Component;
use Livewire\WithPagination;

class TituloRemiteBienesController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Titulo Remiten Bienes';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TituloRemiteBienes::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TituloRemiteBienes::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.titulo_remiten_bienes.titulo-remite-bienes', ['titulo_remiten_bienes' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:titulo_remiten_bienes|min:1',
            'valor' => 'required|unique:titulo_remiten_bienes|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El remision de bienes es requerido',
            'valor.unique' => 'Ya existe el remision de bienes',
            'valor.min'=> 'El remision de bienes debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $remiten = TituloRemiteBienes::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Titulo Remiten Bienes registrado');
    }

    public function Edit($id)
    {
        $record = TituloRemiteBienes::find($id);
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
            'codigo' => "required|unique:titulo_remiten_bienes,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:titulo_remiten_bienes,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El remision de bienes es requerido',
            'valor.unique' => 'Ya existe el remision de bienes',
            'valor.min'=> 'El remision de bienes debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $remiten = TituloRemiteBienes::find($this->selected_id);
        $remiten->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Titulo Remiten Bienes Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TituloRemiteBienes $remiten /*$id*/)
    {
        $remiten->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Titulo Remiten Bienes Eliminado');
    }
}
