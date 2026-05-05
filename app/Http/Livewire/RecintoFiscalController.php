<?php

namespace App\Http\Livewire;

use App\Models\RecintoFiscal;
use Livewire\Component;
use Livewire\WithPagination;

class RecintoFiscalController extends Component
{
    use WithPagination;

    public  $codigo, $valor, $status, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Recinto Fiscal';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = RecintoFiscal::where('valor', 'like', '%' . $this->search . '%')
            ->orwhere('codigo', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = RecintoFiscal::orderBy('valor', 'asc')->paginate($this->pagination);

        return view('livewire.recinto_fiscal.recinto-fiscal', ['recinto_fiscals' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:recinto_fiscals|min:1',
            'valor' => 'required|unique:recinto_fiscals|min:3',
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El nombre del recinto fiscal es requerido',
            'valor.unique' => 'Ya existe el nombre del recinto fiscal',
            'valor.min'=> 'El nombre del recinto fiscal debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $recinto = RecintoFiscal::create([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Recinto Fiscal registrado');
    }

    public function Edit($id)
    {
        $record = RecintoFiscal::find($id);
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
            'codigo' => "required|unique:recinto_fiscals,codigo,{$this->selected_id}|min:1",
            'valor' => "required|unique:recinto_fiscals,valor,{$this->selected_id}|min:3",
            'status' => 'required'
        ];

        $messages = [
            'codigo.required' => 'El codigo es requerido',
            'codigo.unique' => 'Ya existe el codigo',
            'codigo.min'=> 'El codigo debe tener mas de 1 caracteres',
            'valor.required' => 'El nombre del recinto fiscal es requerido',
            'valor.unique' => 'Ya existe el nombre del recinto fiscal',
            'valor.min'=> 'El nombre del recinto fiscal debe tener mas de 3 caracteres',
            'status.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $recinto = RecintoFiscal::find($this->selected_id);
        $recinto->update([
            'codigo' => $this->codigo,
            'valor' => $this->valor,
            'status' => $this->status
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Recinto Fiscal Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(RecintoFiscal $recinto /*$id*/)
    {
        $recinto->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Recinto Fiscal Eliminado');
    }
}
