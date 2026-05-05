<?php

namespace App\Http\Livewire;

use App\Models\Cargos as ModelsCargos;
use Livewire\Component;
use Livewire\WithPagination;

class Cargos extends Component
{
    use WithPagination;

    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $nombre, $estado;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Cargos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.cargos.cargos', [
            'cargos' => $this->Allcargos()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Allcargos()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = ModelsCargos::where('nombre', 'like', "%{$this->search}%")
                ->orderBy('created_at', 'asc');

        } else {
            $query = ModelsCargos::orderBy('created_at', 'desc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }

    protected function rules()
    {
        $rules = [
            'nombre' => "required|min:1"
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'nombre.required' => 'El nombre es requerida',
            'nombre.min'=> 'El nombre debe tener mas de 1 caracteres'
        ];
    }

    public function Store()
    {
        $this->validate($this->rules(), $this->messages());

        $createStore = ModelsCargos::create([
            'nombre' => $this->nombre,
            'estado' => $this->estado
        ]);

        $this->emit('item-added', 'Cargo registrado con exito');
        $this->ResetInt();
    }

    public function Edit($id)
    {
        $selectCargo = ModelsCargos::find($id);
        $this->nombre = $selectCargo->nombre;
        $this->estado = $selectCargo->estado;
        $this->selected_id = $selectCargo->id;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updateCargo = ModelsCargos::find($this->selected_id);
        $updateCargo->nombre = $this->nombre;
        $updateCargo->estado = $this->estado;

        $updateCargo->save();

        $this->emit('item-updated', 'Cargo Actualizado con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy($id)
    {
        $deleteCargo = ModelsCargos::findOrFail($id);
        $deleteCargo->delete();

        $this->ResetInt();
        $this->dispatch('item-updated', 'CARGO ELIMINADO CON ÉXITO');
    }

    public function ResetInt()
    {
        $this->nombre = '';
        $this->estado = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
