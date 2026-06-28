<?php

namespace App\Http\Livewire;

use App\Models\Pais;
use Livewire\Component;
use Livewire\WithPagination;

class PaisController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Pais';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Pais::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = Pais::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.pais.pais', ['pais' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:pais|min:1',
            'valor' => 'required|unique:pais|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El Pais es requerido',
            'valor.unique' => 'Ya existe el Pais',
            'valor.min'=> 'El Pais debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $p = Pais::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Pais registrado');
    }

    public function Edit($id)
    {
        $record = Pais::find($id);
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
            'codigo' => "required|unique:pais,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:pais,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El Pais es requerido',
            'valor.unique' => 'Ya existe el Pais',
            'valor.min'=> 'El Pais debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $p = Pais::find($this->selected_id);
        $p->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Pais Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Pais $p /*$id*/)
    {
        $p->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Pais Eliminado');
    }
}
