<?php

namespace App\Http\Livewire;

use App\Models\Firmador;
use Livewire\Component;
use Livewire\WithPagination;

class FirmadorController extends Component
{
    use WithPagination;

    public  $firmador, $fecha, $estado, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Firmador';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Firmador::where('firmador', 'like', '%' . $this->search . '%')
            ->orwhere('estado', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = Firmador::orderBy('firmador', 'asc')->paginate($this->pagination);

        return view('livewire.firmador.firmador', ['firmadors' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'firmador' => 'required|unique:firmadors|min:1',
            'fecha' => 'required',
            'estado' => 'required'
        ];

        $messages = [
            'firmador.required' => 'El firmador es requerido',
            'firmador.unique' => 'Ya existe el firmador',
            'firmador.min'=> 'El firmador debe tener mas de 1 caracteres',
            'fecha.required' => 'La fecha es requerida',
            'estado.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $t = Firmador::create([
            'firmador' => $this->firmador,
            'fecha' => $this->fecha,
            'estado' => $this->estado
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Firmador registrado');
    }

    public function Edit($id)
    {
        $record = Firmador::find($id);
        $this->firmador = $record->firmador;
        $this->fecha = $record->fecha;
        $this->estado = $record->estado;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->firmador = '';
        $this->fecha = '';
        $this->estado = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'firmador' => "required|unique:firmadors,firmador,{$this->selected_id}|min:1",
            'fecha' => "required",
            'estado' => 'required'
        ];

        $messages = [
            'firmador.required' => 'El firmador es requerido',
            'firmador.unique' => 'Ya existe el firmador',
            'firmador.min'=> 'El firmador debe tener mas de 1 caracteres',
            'fecha.required' => 'La fecha es requerido',
            'estado.required' => 'El estado es requerido'
        ];

        $this->validate($rules, $messages);

        $f = Firmador::find($this->selected_id);
        $f->update([
            'firmador' => $this->firmador,
            'fecha' => $this->fecha,
            'estado' => $this->estado,
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Firmador Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Firmador $f /*$id*/)
    {
        $f->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Firmador Eliminado');
    }
}
