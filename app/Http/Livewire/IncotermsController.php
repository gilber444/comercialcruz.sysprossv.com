<?php

namespace App\Http\Livewire;

use App\Models\Incoterms;
use Livewire\Component;
use Livewire\WithPagination;

class IncotermsController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'INCOTERMS';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Incoterms::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = Incoterms::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.incoterms.incoterms', ['incoterms' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:incoterms|min:1',
            'valor' => 'required|unique:incoterms|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El INCOTERMS es requerido',
            'valor.unique' => 'Ya existe el INCOTERMS',
            'valor.min'=> 'El INCOTERMS debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $in = Incoterms::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'INCOTERMS registrado');
    }

    public function Edit($id)
    {
        $record = Incoterms::find($id);
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
            'codigo' => "required|unique:incoterms,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:incoterms,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El INCOTERMS es requerido',
            'valor.unique' => 'Ya existe el INCOTERMS',
            'valor.min'=> 'El INCOTERMS debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $in = Incoterms::find($this->selected_id);
        $in->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'INCOTERMS Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Incoterms $in /*$id*/)
    {
        $in->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'INCOTERMS Eliminado');
    }
}
