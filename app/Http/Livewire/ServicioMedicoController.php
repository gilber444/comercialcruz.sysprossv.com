<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ServicioMedico;
use Livewire\WithPagination;

class ServicioMedicoController extends Component
{

    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Servicio Medico';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = ServicioMedico::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = ServicioMedico::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.servicio_medico.servicio-medico', ['servicio_medicos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:servicio_medicos|min:1',
            'valor' => 'required|unique:servicio_medicos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de servicio medico es requerido',
            'valor.unique' => 'Ya existe el tipo de servicio medico',
            'valor.min'=> 'El tipo de servicio medico debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $servicio = ServicioMedico::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Servicio Medico registrado');
    }

    public function Edit($id)
    {
        $record = ServicioMedico::find($id);
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
            'codigo' => "required|unique:servicio_medicos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:servicio_medicos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de servicio medico es requerido',
            'valor.unique' => 'Ya existe el tipo de servicio medico',
            'valor.min'=> 'El tipo de servicio medico debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $servicio = ServicioMedico::find($this->selected_id);
        $servicio->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Servicio Medico Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(ServicioMedico $servicio /*$id*/)
    {
        $servicio->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Servicio Medico Eliminada');
    }
}
