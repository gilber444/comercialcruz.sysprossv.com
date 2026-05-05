<?php

namespace App\Http\Livewire;

use App\Models\ClaseDocumento;
use Livewire\Component;
use Livewire\WithPagination;

class ClaseDocumentoController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Clase de Documentos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = ClaseDocumento::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = ClaseDocumento::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.clase-documento.clase-documento', ['clase_documentos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:clase_documentos|min:1',
            'valor' => 'required|unique:clase_documentos|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'Codigo es requerido',
            'codigo.unique' => 'Ya existe el Codigo',
            'codigo.min'=> 'El codigo de la clase documento debe tener mas de 1 caracteres',
            'valor.required' => 'Clase documento es requerido',
            'valor.unique' => 'Ya existe la Clase documento',
            'valor.min'=> 'El Nombre de la clase documento debe tener mas de 3 caracteres',
            'status.required' => 'El estado de la clase documento es requerido',
        ];

        $this->validate($rules, $messages);

        $clase = ClaseDocumento::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Clase Documento registrado');
    }

    public function Edit($id)
    {
        $record = ClaseDocumento::find($id);
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
            'codigo' => "required|unique:clase_documentos,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:clase_documentos,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'Codigo es requerido',
            'codigo.unique' => 'Ya existe el Codigo',
            'codigo.min'=> 'El codigo del clase documentos debe tener mas de 1 caracteres',
            'valor.required' => 'Clase documentos es requerido',
            'valor.unique' => 'Ya existe la Clase de documentos',
            'valor.min'=> 'El Nombre del ambiente de destino debe tener mas de 3 caracteres',
            'status.required' => 'El estado del ambiente destino es requerido',
        ];

        $this->validate($rules, $messages);

        $clase = ClaseDocumento::find($this->selected_id);
        $clase->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Clase Documento Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(ClaseDocumento $clase /*$id*/)
    {
        $clase->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Clase Documento Eliminada');
    }
}
