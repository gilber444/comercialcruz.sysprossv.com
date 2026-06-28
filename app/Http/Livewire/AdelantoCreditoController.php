<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Empleado;
use App\Models\AdelantoCredito;
class AdelantoCreditoController extends Component
{
    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $empleado, $credito, $tipo_adelanto, $estado, $detalle;

    use WithPagination;
    use WithFileUploads;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Adelantos Credito';
    }

    public function render()
    {
        return view('livewire.adelanto_credito.adelanto_credito', [
            'empleados' => $this->Empleados(),
            'adelantos' => $this->Alladelanto(),
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Alladelanto()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = AdelantoCredito::whereHas('Rempleado', function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%"); 
            })->orderBy('created_at', 'asc');
            

        } else {
            $query = AdelantoCredito::orderBy('created_at', 'desc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }

    public function Empleados()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = Empleado::where('estado', '1')
                ->orderBy('id', 'asc');

        } else {
            $query = Empleado::where('estado', '1')
                ->orderBy('id', 'desc');
        }

        $this->records = $query->count();

        return $query->get();
    }


    protected function rules()
    {
        $rules = [
            'empleado' => "required|min:1",
            'credito' => "required|min:1",
            // 'tipo_adelanto' => "required|min:1",
            'estado' => "required|min:1",
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'empleado.required' => 'El empleado es requeridO',
            'empleado.min'=> 'El empleado debe tener mas de 1 caracteres',
            'credito.required' => 'El credito es requerido',
            'credito.min'=> 'El credito tener mas de 1 caracteres',
            // 'tipo_adelanto.required' => 'El tipo adelanto es requerida',
            // 'tipo_adelanto.min'=> 'El tipo adelanto debe tener mas de 1 caracteres',
            'estado.required' => 'el estado es requerida',
            'estado.min'=> 'el estado debe tener mas de 1 caracteres',
        ];
    }


    public function Store()
    {
        $this->validate($this->rules(), $this->messages());

        $ultimoCorrelativo = AdelantoCredito::withTrashed()->max('correlativo');
        $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo + 1 : 1;

        $createStore = AdelantoCredito::create([
            'correlativo'=> $nuevoCorrelativo,
            'empleado'=> $this->empleado,
            'credito'=> $this->credito,
            'tipo_adelanto'=> 'contado',
            'estado'=> $this->estado,
            'detalle'=> $this->detalle
        ]);

        $createStore->save();

        $this->emit('item-added', 'Adelanto Guardado con exito');
        $this->ResetInt();
    }

    public function Edit($id)
    {
        $selectPermiso = AdelantoCredito::find($id);
        $this->empleado = $selectPermiso->empleado;
        $this->credito = $selectPermiso->credito;
        $this->tipo_adelanto = $selectPermiso->tipo_adelanto;
        $this->estado = $selectPermiso->estado;
        $this->detalle = $selectPermiso->detalle;
        $this->selected_id = $selectPermiso->id;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updatePermiso = AdelantoCredito::find($this->selected_id);
        $updatePermiso->empleado = $this->empleado;
        $updatePermiso->credito = $this->credito;
        $updatePermiso->tipo_adelanto = $this->tipo_adelanto;
        $updatePermiso->estado = $this->estado;
        $updatePermiso->detalle = $this->detalle;

        $updatePermiso->save();

        $this->emit('item-updated', 'Adelanto Modificado con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'destroy'
    ];

    public function destroy($id)
    {
        $deleteAdelanto = AdelantoCredito::findOrFail($id);
        $deleteAdelanto->delete();

        $this->emit('item-confirmar', 'Adelanto Eliminado con exito');
        $this->ResetInt();
    }


    public function ResetInt()
    {
        $this->empleado = '';
        $this->credito = '';
        $this->tipo_adelanto = '';
        $this->estado = '';
        $this->detalle = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
        $this->resetUI();
    }


    public function resetUI()
    {
        $this->empleado = '';
        $this->credito = '';
        $this->tipo_adelanto = '';
        $this->estado = '';
        $this->detalle = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
