<?php

namespace App\Http\Livewire;

use App\Models\Tocken;
use Livewire\Component;
use Livewire\WithPagination;

class TockenController extends Component
{
    use WithPagination;

    public  $tocken, $fecha, $estado, $json, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tockens';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Tocken::where('tocken', 'like', '%' . $this->search . '%')
            ->orwhere('estado', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = Tocken::orderBy('tocken', 'asc')->paginate($this->pagination);

        return view('livewire.tocken.tocken', ['tockens' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'tocken' => 'required|unique:tockens|min:1',
            'fecha' => 'required',
            'estado' => 'required'
        ];

        $messages = [
            'tocken.required' => 'El tocken es requerido',
            'tocken.unique' => 'Ya existe el tocken',
            'tocken.min'=> 'El tocken debe tener mas de 1 caracteres',
            'fecha.required' => 'La fecha es requerida',
            'estado.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $t = Tocken::create([
            'tocken' => $this->tocken,
            'fecha' => $this->fecha,
            'estado' => $this->estado,
            'json' => $this->json
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tocken registrado');
    }

    public function Edit($id)
    {
        $record = Tocken::find($id);
        $this->tocken = $record->tocken;
        $this->fecha = $record->fecha;
        $this->estado = $record->estado;
        $this->json = $record->json;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->tocken = '';
        $this->fecha = '';
        $this->estado = '';
        $this->json = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'tocken' => "required|unique:tockens,tocken,{$this->selected_id}|min:1",
            'fecha' => "required",
            'estado' => 'required'
        ];

        $messages = [
            'tocken.required' => 'El tocken es requerido',
            'tocken.unique' => 'Ya existe el tocken',
            'tocken.min'=> 'El tocken debe tener mas de 1 caracteres',
            'fecha.required' => 'La fecha es requerido',
            'estado.required' => 'El estado es requerido'
        ];

        $this->validate($rules, $messages);

        $t = Tocken::find($this->selected_id);
        $t->update([
            'tocken' => $this->tocken,
            'fecha' => $this->fecha,
            'estado' => $this->estado,
            'json' => json_decode($this->json)
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tocken Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Tocken $t /*$id*/)
    {
        $t->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tocken Eliminado');
    }
}
