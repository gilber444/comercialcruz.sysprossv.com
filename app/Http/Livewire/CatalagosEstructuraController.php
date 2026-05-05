<?php

namespace App\Http\Livewire;

use App\Models\catalagos;
use App\Models\catalagosEstructura;
use Livewire\Component;
use Livewire\WithPagination;

class CatalagosEstructuraController extends Component
{
    use WithPagination;

    public  $codigo, $catalago, $descripcion, $valores, $estado, $dependencia, $search, $selected_id, $pageTitle, $componentName, $catalagos, $referencias;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Catalagos de Estructuras DTE';

        $this->catalagos = catalagos::all();
        $this->referencias = catalagosEstructura::all();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = catalagosEstructura::join('catalagos as c', 'c.id', 'catalagos_estructuras.catalago')
            ->select('catalagos_estructuras.*', 'c.codigo as cat', 'c.catalago as cata')
            ->where('catalagos_estructuras.codigo', 'like', '%' . $this->search . '%')
            ->orWhere('catalagos_estructuras.valores', 'like', '%' . $this->search . '%')
            ->orWhere('c.catalago', 'like', '%' . $this->search . '%')
            ->paginate($this->pagination);
        else
            $data = catalagosEstructura::join('catalagos as c', 'c.id', 'catalagos_estructuras.catalago')
            ->select('catalagos_estructuras.*', 'c.codigo as cat', 'c.catalago as cata')
            ->orderBy('id', 'asc')->paginate($this->pagination);

        return view('livewire.catalagos.catalagos-estructura', ['estructuras' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required',
            'catalago' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo del Catalago de Estructuras es requerido',
            'catalago.require'=> 'El Catalago es requerido'
        ];

        $this->validate($rules, $messages);

        $estruc = catalagosEstructura::create([
            'catalago' => $this->catalago,
            'codigo' => $this->codigo,
            'valores' => $this->valores,
            'dependencia' => $this->dependencia,
            'estado' => $this->estado
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Catalago registrado');
    }

    public function resetUI()
    {
        $this->codigo = '';
        $this->catalago = '';
        $this->descripcion = '';
        $this->valores = '';
        $this->estado = '';
        $this->dependencia = '';
        $this->selected_id = '';
        //$this->catalagos = 0;
        //$this->referencias = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Edit($id)
    {
        $record = catalagosEstructura::find($id);
        $this->codigo = $record->codigo;
        $this->catalago = $record->catalago;
        $this->valores = $record->valores;
        $this->estado = $record->estado;
        $this->dependencia = $record->dependencia;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $rules = [
            'codigo' => 'required',
            'catalago' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo del Catalago de Estructuras es requerido',
            'catalago.require'=> 'El Catalago es requerido'
        ];

        $this->validate($rules, $messages);

        $cata = catalagosEstructura::find($this->selected_id);
        $cata->update([
            'catalago' => $this->catalago,
            'codigo' => $this->codigo,
            'valores' => $this->valores,
            'dependencia' => $this->dependencia,
            'estado' => $this->estado
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Catalago Actualizado');
    }

}
