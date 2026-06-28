<?php

namespace App\Http\Livewire;


use Livewire\Component;
use App\Models\Bancos;
use Livewire\WithPagination;

class BancosController extends Component
{
    use WithPagination;

    public  $nombre, $search, $selected_id, $pageTitle, $componentName, $cuenta, $correlativo;

    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Bancos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Bancos::where('nombre', 'like', '%' . $this->search . '%')
            ->paginate($this->pagination);
        else
            $data = Bancos::orderBy('nombre', 'asc')->paginate($this->pagination);

        return view('livewire.bancos.bancos', ['bancos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'nombre' => 'required|unique:bancos|min:2',
        ];

        $messages = [
            'nombre.required' => 'El nombre del Banco es requerido',
            'nombre.unique' => 'Ya existe este Banco',
            'nombre.min'=> 'El nombre del Banco debe tener mas de 2 caracteres'
        ];

        $this->validate($rules, $messages);

        $agre = Bancos::create([
            'nombre' => $this->nombre,
            'cuenta'=> $this->cuenta,
            'correlativo' => $this->correlativo
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Banco registrado');
    }

    public function Edit($id)
    {
        $record = Bancos::find($id);
        $this->nombre = $record->nombre;
        $this->selected_id = $record->id;
        $this->cuenta = $record->cuenta;
        $this->correlativo = $record->correlativo;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->nombre = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->cuenta = '';
        $this->correlativo = '';
        $this->cuenta = '';
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'nombre' => "required|unique:bancos,nombre,{$this->selected_id}|min:2",
        ];

        $messages = [
            'nombre.required' => 'El nombre del Banco es requerido',
            'nombre.unique' => 'Ya existe este Banco',
            'nombre.min'=> 'El nombre del Banco debe tener mas de 2 caracteres'
        ];

        $this->validate($rules, $messages);

        $edit = Bancos::find($this->selected_id);
        $edit->update([
            'nombre' => $this->nombre,
            'cuenta' => $this->cuenta,
            'correlativo' => $this->correlativo
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Banco Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Bancos $banco /*$id*/)
    {
        $banco->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Banco Eliminado');
    }

}
