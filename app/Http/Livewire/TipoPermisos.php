<?php

namespace App\Http\Livewire;

use App\Models\TipoPermisos as ModelsTipoPermisos;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TipoPermisos extends Component
{
    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $nombre, $estado;

    use WithPagination;
    use WithFileUploads;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Tipo de Permisos';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.tipo_permisos.tipo_permisos', [
            'tipopermisos' => $this->Alltipopermiso()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Alltipopermiso()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = ModelsTipoPermisos::where('nombre', 'like', "%{$this->search}%")
                ->orderBy('created_at', 'asc');

        } else {
            $query = ModelsTipoPermisos::orderBy('created_at', 'desc');
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

        $createStore = ModelsTipoPermisos::create([
            'nombre' => $this->nombre,
            'estado' => $this->estado
        ]);

        $createStore->save();

        $this->emit('item-added', 'Tipo de Permiso registrado con exito');
        $this->ResetInt();
    }

    public function Edit($id)
    {
        $selectTipoPermiso = ModelsTipoPermisos::find($id);
        $this->nombre = $selectTipoPermiso->nombre;
        $this->estado = $selectTipoPermiso->estado;
        $this->selected_id = $selectTipoPermiso->id;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updateTipoPermiso = ModelsTipoPermisos::find($this->selected_id);
        $updateTipoPermiso->nombre = $this->nombre;
        $updateTipoPermiso->estado = $this->estado;

        $updateTipoPermiso->save();

        $this->emit('item-updated', 'Tipo de Permiso Actualizado con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'destroy'
    ];

    public function destroy($id)
    {
        $deleteTipoPermiso = ModelsTipoPermisos::findOrFail($id);
        $deleteTipoPermiso->delete();

        $this->resetPage();
        $this->ResetInt();
        $this->emit('item-updated', 'PERMISO ELIMINADO CON ÉXITO');
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
