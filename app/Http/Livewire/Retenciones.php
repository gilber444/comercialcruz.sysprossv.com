<?php

namespace App\Http\Livewire;

use App\Models\Retencion;
use App\Models\Retenciones as ModelsRetenciones;
use Livewire\Component;
use Livewire\WithPagination;

class Retenciones extends Component
{
    use WithPagination;

    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $nombre, $porcentaje;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Retenciones';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.retenciones.retenciones',[
            'retenciones' => $this->Allretenciones()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Allretenciones()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = ModelsRetenciones::where('nombre', 'like', "%{$this->search}%")
                ->orderBy('created_at', 'asc');

        } else {
            $query = ModelsRetenciones::orderBy('created_at', 'desc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }


    protected function rules()
    {
        $rules = [
            'nombre' => "required|min:1",
            'porcentaje' => "required|min:1|numeric"
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'nombre.required' => 'El nombre es requerida',
            'nombre.min'=> 'El nombre debe tener mas de 1 caracteres',
            'porcentaje.required' => 'El porcentaje es requerida',
            'porcentaje.min'=> 'El porcenataje debe tener mas de 1 caracteres',
            'porcentaje.numeric' => 'El porcentaje debe ser un número válido'
        ];
    }


    public function Store()
    {
        $this->validate($this->rules(), $this->messages());

        $createRetencion = ModelsRetenciones::create([
            'nombre' => $this->nombre,
            'porcentaje' => $this->porcentaje
        ]);

        $this->emit('item-added', 'Retencion registrado con exito');
        $this->ResetInt();
    }

    public function Edit($id)
    {
        $selectRetencion = ModelsRetenciones::find($id);
        $this->nombre = $selectRetencion->nombre;
        $this->porcentaje = $selectRetencion->porcentaje;
        $this->selected_id = $selectRetencion->id;

        $this->emit('show-modal','Editar Retencion');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updateRetencion = ModelsRetenciones::find($this->selected_id);
        $updateRetencion->nombre = $this->nombre;
        $updateRetencion->porcentaje = $this->porcentaje;

        $updateRetencion->save();

        $this->emit('item-updated', 'Retencion Actualizado con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy($id)
    {
        $deleteRetencion = ModelsRetenciones::findOrFail($id);
        $deleteRetencion->delete();

        $this->resetPage();
        $this->ResetInt();
        $this->emit('item-updated', 'RETENCION ELIMINADO CON ÉXITO');
    }

    public function ResetInt()
    {
        $this->nombre = '';
        $this->porcentaje = '';
        $this->selected_id = 0;
        $this->resetValidation();
    }
}
