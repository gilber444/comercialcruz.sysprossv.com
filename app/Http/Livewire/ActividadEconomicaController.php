<?php

namespace App\Http\Livewire;

use App\Models\ActividadEconomica;
use Livewire\Component;
use Livewire\WithPagination;

class ActividadEconomicaController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Actividad Economica';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = ActividadEconomica::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = ActividadEconomica::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.actividad_economica.actividad-economica', ['actividad_economicas' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:actividad_economicas|min:1',
            'valor' => 'required|unique:actividad_economicas|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La actividad economica es requerido',
            'valor.unique' => 'Ya existe la actividad economica',
            'valor.min'=> 'La actividad economica debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $actividad = ActividadEconomica::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Actividad Economica registrada');
    }

    public function Edit($id)
    {
        $record = ActividadEconomica::find($id);
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
            'codigo' => "required|unique:actividad_economicas,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:actividad_economicas,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La actividad economica es requerido',
            'valor.unique' => 'Ya existe la actividad economica',
            'valor.min'=> 'La actividad economica debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $actividad = ActividadEconomica::find($this->selected_id);
        $actividad->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Actividad Economica Actualizada');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(ActividadEconomica $actividad /*$id*/)
    {
        $actividad->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Actividad Economica Eliminada');
    }
}
