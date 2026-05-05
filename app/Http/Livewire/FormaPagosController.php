<?php

namespace App\Http\Livewire;

use App\Models\FormaPagos;
use Livewire\Component;
use Livewire\WithPagination;

class FormaPagosController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Forma de Pago';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = FormaPagos::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = FormaPagos::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.forma_pago.forma-pagos', ['forma_pagos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:forma_pagos|min:1',
            'valor' => 'required|unique:forma_pagos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La forma de pago es requerido',
            'valor.unique' => 'Ya existe la forma de pago',
            'valor.min'=> 'La forma de pago debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $pago = FormaPagos::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Forma de Pago registrado');
    }

    public function Edit($id)
    {
        $record = FormaPagos::find($id);
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
            'codigo' => "required|unique:forma_pagos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:forma_pagos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La forma de pago es requerido',
            'valor.unique' => 'Ya existe la forma de pago',
            'valor.min'=> 'La forma de pago debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $pago = FormaPagos::find($this->selected_id);
        $pago->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Forma de Pago Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(FormaPagos $pago /*$id*/)
    {
        $pago->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'FormaPago Eliminada');
    }
}
