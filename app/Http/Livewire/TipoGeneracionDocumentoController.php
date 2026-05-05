<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TipoGeneracionDocumento;
use Livewire\WithPagination;

class TipoGeneracionDocumentoController extends Component
{

    use WithPagination;

    public $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Generacion Documento';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoGeneracionDocumento::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoGeneracionDocumento::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_generacion_documento.tipo-generacion-documento', ['tipo_generacion_documentos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_generacion_documentos|min:1',
            'valor' => 'required|unique:tipo_generacion_documentos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'La generacion de documento es requerido',
            'valor.unique' => 'Ya existe la generacion de documento',
            'valor.min'=> 'La generacion de documento debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $generacion = TipoGeneracionDocumento::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Generacion de documento registrado');
    }

    public function Edit($id)
    {
        $record = TipoGeneracionDocumento::find($id);
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
            'codigo' => "required|unique:tipo_generacion_documentos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_generacion_documentos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'la generaciond e documento es requerido',
            'valor.unique' => 'Ya existe la generacion de documento',
            'valor.min'=> 'La generacion de documento debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $generacion = TipoGeneracionDocumento::find($this->selected_id);
        $generacion->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Generacion de documento Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoGeneracionDocumento $generacion /*$id*/)
    {
        $generacion->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Generacion de documento Eliminada');
    }
}
