<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Familias;
use Livewire\WithPagination;

class FamiliasController extends Component
{
    use WithPagination;

    public  $familia, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Familia de Productos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Familias::where('familia', 'like', '%' . $this->search . '%')->paginate($this->pagination);
        else
            $data = Familias::orderBy('familia', 'asc')->paginate($this->pagination);

        return view('livewire.familias.familias', ['familias' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'familia' => 'required|unique:familias,familia|min:3'
        ];

        $messages = [
            'familia.required' => 'Nombre de la Familia es requerido',
            'familia.unique' => 'Ya existe el nombre de la Familia',
            'familia.min'=> 'El Nombre de la Familia debe tener mas de 3 caracteres'
        ];

        $this->validate($rules, $messages);

        $family = Familias::create(['familia' => $this->familia]);

        $this->resetUI();
        $this->emit('item-added', 'Familia registrada');
    }

    public function Edit($id)
    {
        $record = Familias::find($id);
        $this->familia = $record->familia;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->familia = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'familia' => 'required|min:3'
        ];

        $messages = [
            'familia.required' => 'Nombre de la Familia es requerido',
            'familia.min'=> 'El Nombre de la Familia debe tener mas de 3 caracteres'
        ];

        $this->validate($rules, $messages);

        $family = Familias::find($this->selected_id);
        $family->update([
            'familia' => $this->familia
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Familia Actualizada');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Familias $family /*$id*/)
    {
        $family->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Familia Eliminada');
    }
}
