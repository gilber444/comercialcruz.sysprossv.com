<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ModeloFacturacion;
use Livewire\WithPagination;

class ModeloFacturacionController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Modelo Facturacion';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = ModeloFacturacion::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = ModeloFacturacion::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.modelo_facturacion.modelo-facturacion', ['modelo_facturacion' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:modelo_facturacions|min:1',
            'valor' => 'required|unique:modelo_facturacions|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El modelo de facturacion es requerido',
            'valor.unique' => 'Ya existe el modelo de facturacion',
            'valor.min'=> 'El modelo de facturacion debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $facturacion = ModeloFacturacion::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Modelo de Facturacion registrado');
    }

    public function Edit($id)
    {
        $record = ModeloFacturacion::find($id);
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
            'codigo' => "required|unique:modelo_facturacions,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:modelo_facturacions,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El modelo de facturacion es requerido',
            'valor.unique' => 'Ya existe el modelo de facturacion',
            'valor.min'=> 'El modelo de facturacion debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $facturacion = ModeloFacturacion::find($this->selected_id);
        $facturacion->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Modelo de Facturacion Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(ModeloFacturacion $facturacion /*$id*/)
    {
        $facturacion->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Modelo de Facturacion Eliminada');
    }
}
