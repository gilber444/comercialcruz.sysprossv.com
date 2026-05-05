<?php

namespace App\Http\Livewire;

use App\Models\DomicilioFiscal;
use Livewire\Component;
use Livewire\WithPagination;

class DomicilioFiscalController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Domicilio Fiscal';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = DomicilioFiscal::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = DomicilioFiscal::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.domicilio_fiscal.domicilio-fiscal', ['domicilio_fiscals' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:domicilio_fiscals|min:1',
            'valor' => 'required|unique:domicilio_fiscals|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El domicilio fiscal es requerido',
            'valor.unique' => 'Ya existe el domicilio fiscal',
            'valor.min'=> 'El domicilio fiscal debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $domicilio = DomicilioFiscal::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Domicilio Fiscal registrado');
    }

    public function Edit($id)
    {
        $record = DomicilioFiscal::find($id);
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
            'codigo' => "required|unique:domicilio_fiscals,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:domicilio_fiscals,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El domicilio fiscal es requerido',
            'valor.unique' => 'Ya existe el domicilio fiscal',
            'valor.min'=> 'El domicilio fiscal debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $domicilio = DomicilioFiscal::find($this->selected_id);
        $domicilio->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Domicilio Fiscal Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(DomicilioFiscal $domicilio /*$id*/)
    {
        $domicilio->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Domicilio Fiscal Eliminado');
    }
}
