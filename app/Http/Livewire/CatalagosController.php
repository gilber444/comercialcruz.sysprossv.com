<?php

namespace App\Http\Livewire;

use App\Models\catalagos;
use Livewire\Component;
use Livewire\WithPagination;

class CatalagosController extends Component
{
    use WithPagination;

    public  $codigo, $catalago, $descripcion, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Catalagos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = catalagos::where('codigo', 'like', '%' . $this->search . '%')
            ->orWhere('catalago', 'like', '%' . $this->search . '%')
            ->orWhere('descripcion', 'like', '%' . $this->search . '%')
            ->paginate($this->pagination);
        else
            $data = catalagos::orderBy('id', 'asc')->paginate($this->pagination);

        return view('livewire.catalagos.catalagos', ['catalagos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'codigo' => 'required|unique:catalagos|min:3'
        ];

        $messages = [
            'codigo.required' => 'El codigo del Catalago es requerido',
            'codigo.unique' => 'Ya existe este codigo de Catalago',
            'codigo.min'=> 'El codogio del catalago debe tener mas de 3 caracteres'
        ];

        $this->validate($rules, $messages);

        $category = catalagos::create([
            'codigo' => $this->codigo,
            'catalago' => $this->catalago,
            'descripcion' => $this->descripcion
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Catalago registrado');
    }

    public function resetUI()
    {
        $this->codigo = '';
        $this->catalago = '';
        $this->descripcion ='';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Edit($id)
    {
        $record = catalagos::find($id);
        $this->codigo = $record->codigo;
        $this->selected_id = $record->id;
        $this->catalago = $record->catalago;
        $this->descripcion = $record->descripcion;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $rules = [
            'codigo' => 'required|unique:catalagos,codigo, '.$this->selected_id .'|min:3'
        ];

        $messages = [
            'codigo.required' => 'El codigo del Catalago es requerido',
            'codigo.unique' => 'Ya existe este codigo de Catalago',
            'codigo.min'=> 'El codogio del catalago debe tener mas de 3 caracteres'
        ];

        $this->validate($rules, $messages);
        $cata = catalagos::find($this->selected_id);
        $cata->update([
            'codigo' => $this->codigo,
            'catalago' => $this->catalago,
            'descripcion' => $this->descripcion
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Catalago Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(catalagos $catalago /*$id*/)
    {
        $catalago->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Catalago Eliminado');
    }
}
