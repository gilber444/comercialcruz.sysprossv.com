<?php

namespace App\Http\Livewire;

use App\Models\TipoInvalidacion;
use Livewire\Component;
use Livewire\WithPagination;

class TipoInvalidacionController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo Invalidacion';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoInvalidacion::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoInvalidacion::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_invalidacion.tipo-invalidacion', ['tipo_invalidacions' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_invalidacions|min:1',
            'valor' => 'required|unique:tipo_invalidacions|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo invalidacion es requerido',
            'valor.unique' => 'Ya existe el tipo invalidacion',
            'valor.min'=> 'El tipo invalidacion debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $invalidacion = TipoInvalidacion::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tipo Invalidacion registrado');
    }

    public function Edit($id)
    {
        $record = TipoInvalidacion::find($id);
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
            'codigo' => "required|unique:tipo_invalidacions,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_invalidacions,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo invalidacion es requerido',
            'valor.unique' => 'Ya existe el tipo invalidacion',
            'valor.min'=> 'El tipo invaliddacion debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $invalidacion = TipoInvalidacion::find($this->selected_id);
        $invalidacion->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tipo Invalidacion Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoInvalidacion $invalidacion /*$id*/)
    {
        $invalidacion->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tipo Invalidacion Eliminado');
    }
}
