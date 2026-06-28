<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TipoDocumento;
use Livewire\WithPagination;

class TipoDocumentoController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo Documento';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoDocumento::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = TipoDocumento::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.tipo_documentos.tipo-documento', ['tipo_documentos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:tipo_documentos|min:1',
            'valor' => 'required|unique:tipo_documentos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo documento es requerido',
            'valor.unique' => 'Ya existe el tipo de documento',
            'valor.min'=> 'El tipo de documento debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $documento = TipoDocumento::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Tipo de Documento registrado');
    }

    public function Edit($id)
    {
        $record = TipoDocumento::find($id);
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
            'codigo' => "required|unique:tipo_documentos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:tipo_documentos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El tipo documento es requerido',
            'valor.unique' => 'Ya existe el tipo de documento',
            'valor.min'=> 'El tipo de documento debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $documento = TipoDocumento::find($this->selected_id);
        $documento->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Tipo Documento Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoDocumento $documento /*$id*/)
    {
        $documento->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Tipo Documento Eliminada');
    }
}
