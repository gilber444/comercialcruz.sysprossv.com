<?php

namespace App\Http\Livewire;

use App\Models\TipoEstablecimiento;
use Livewire\Component;
use Livewire\WithPagination;

class TipoEstablecimientoController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo Establecimiento';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoEstablecimiento::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoEstablecimiento::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_establecimiento.tipo-establecimiento', ['tipo_establecimientos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_establecimientos|min:1',
            'valor' => 'required|unique:tipo_establecimientos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de establecimiento es requerido',
            'valor.unique' => 'Ya existe el tipo de establecimiento',
            'valor.min'=> 'El tipo de establecimiento debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $establecimiento = TipoEstablecimiento::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tipo Establecimiento registrado');
    }

    public function Edit($id)
    {
        $record = TipoEstablecimiento::find($id);
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
            'codigo' => "required|unique:tipo_establecimientos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_establecimientos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo de establecimiento es requerido',
            'valor.unique' => 'Ya existe el tipo de establecimiento',
            'valor.min'=> 'El tipo de establecimiento debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $establecimiento = TipoEstablecimiento::find($this->selected_id);
        $establecimiento->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tipo Establecimiento Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoEstablecimiento $establecimiento /*$id*/)
    {
        $establecimiento->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tipo Establecimiento Eliminada');
    }
}
