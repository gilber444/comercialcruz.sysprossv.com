<?php

namespace App\Http\Livewire;

use App\Models\Apis;
use Livewire\Component;
use Livewire\WithPagination;

class ApisController extends Component
{
    use WithPagination;

    public  $nombre, $url, $metodo, $tipo, $estado, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Apis';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Apis::where('nombre', 'like', '%' . $this->search . '%')
            ->orwhere('estado', 'like', ''.$this->search.'')
            ->paginate($this->pagination);
        else
            $data = Apis::orderBy('nombre', 'asc')->paginate($this->pagination);

        return view('livewire.apis.apis', ['apis' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'nombre' => 'required|unique:apis|min:1',
            'url' => 'required',
            'metodo' => 'required',
            'tipo' => 'required',
            'estado' => 'required'
        ];

        $messages = [
            'nombre.required' => 'El nombre de la api es requerido',
            'nombre.unique' => 'Ya existe el nombre de la api',
            'nombre.min'=> 'El nombre de la api debe tener mas de 1 caracteres',
            'url.required' => 'La Url es requerida',
            'metodo.required' => 'El metodo es requerida',
            'tipo.required' => 'El tipo es requerida',
            'estado.required' => 'El estado es requerido',
        ];

        $this->validate($rules, $messages);

        $t = Apis::create([
            'nombre' => $this->nombre,
            'url' => $this->url,
            'metodo' => $this->metodo,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Apis registrado');
    }

    public function Edit($id)
    {
        $record = Apis::find($id);
        $this->nombre = $record->nombre;
        $this->url = $record->url;
        $this->metodo = $record->metodo;
        $this->tipo = $record->tipo;
        $this->estado = $record->estado;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->nombre = '';
        $this->url = '';
        $this->estado = '';
        $this->metodo = '';
        $this->tipo = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'nombre' => "required|unique:apis,nombre,{$this->selected_id}|min:1",
            'url' => "required",
            'metodo' => "required",
            'tipo' => "required",
            'estado' => 'required'
        ];

        $messages = [
            'nombre.required' => 'El nombre de la api es requerido',
            'nombre.unique' => 'Ya existe el nombred e la api',
            'nombre.min'=> 'El nombre debe tener mas de 1 caracteres',
            'url.required' => 'La Url es requerida',
            'metodo.required' => 'El metodo es requerida',
            'tipo.required' => 'El tipo es requerida',
            'estado.required' => 'El estado es requerido'
        ];

        $this->validate($rules, $messages);

        $a = Apis::find($this->selected_id);
        $a->update([
            'nombre' => $this->nombre,
            'url' => $this->url,
            'metodo' => $this->metodo,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'APIS Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Apis $a /*$id*/)
    {
        $a->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Apis Eliminado');
    }
}
