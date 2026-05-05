<?php

namespace App\Http\Livewire;

use App\Models\UnidadMedida;
use Livewire\Component;
use Livewire\WithPagination;

class UnidadMedidaController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Unidades Medidas';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = UnidadMedida::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = UnidadMedida::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.unidad_medida.unidad-medida', ['unidad_medidas' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:unidad_medidas|min:1',
            'valor' => 'required|unique:unidad_medidas|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El nombre de unidad de medida es requerido',
            'valor.unique' => 'Ya existe el nombre de unidad de medida',
            'valor.min'=> 'El nombre de unidad de medida debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $medida = UnidadMedida::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Unidad de Medida registrado');
    }

    public function Edit($id)
    {
        $record = UnidadMedida::find($id);
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
            'codigo' => "required|unique:unidad_medidas,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:unidad_medidas,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El nombre de unidad de medida es requerido',
            'valor.unique' => 'Ya existe el nombre de unidad de medida',
            'valor.min'=> 'El nombre de unidad de medida debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $medida = UnidadMedida::find($this->selected_id);
        $medida->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Unidad de Medida Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(UnidadMedida $medida /*$id*/)
    {
        $medida->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Unidad de Medida Eliminada');
    }
}
