<?php

namespace App\Http\Livewire;

use App\Models\DocumentosAsociados;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentosAsociadosController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Documentos Asociados';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = DocumentosAsociados::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = DocumentosAsociados::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.documentos_asociados.documentos-asociados', ['documentos_asociados' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:documentos_asociados|min:1',
            'valor' => 'required|unique:documentos_asociados|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El documento asociado es requerido',
            'valor.unique' => 'Ya existe el documento asociado',
            'valor.min'=> 'El documento asociado debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $asociados = DocumentosAsociados::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Documentos Asociados registrado');
    }

    public function Edit($id)
    {
        $record = DocumentosAsociados::find($id);
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
            'codigo' => "required|unique:documentos_asociados,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:documentos_asociados,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El documento asociado es requerido',
            'valor.unique' => 'Ya existe el documento asociado',
            'valor.min'=> 'El documento asociado debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $asociados = DocumentosAsociados::find($this->selected_id);
        $asociados->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Documentos Asociados Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(DocumentosAsociados $asociados /*$id*/)
    {
        $asociados->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Documentos Asociados Eliminada');
    }
}
