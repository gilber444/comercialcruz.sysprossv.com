<?php

namespace App\Http\Livewire;

use App\Models\DetallePlanilla;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Sucursales;
use App\Models\Planilla;
use App\Models\Empleado;
use App\Models\AdelantoCredito;
use Illuminate\Support\Facades\Auth;

class PlanillaController extends Component
{
    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $sucursales, $sucursal, $fechaplanilla_inicio, $fechaplanilla_fin, $tipo_pago, $detalle, $sucursalesList;

    use WithPagination;
    use WithFileUploads;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Planillas';

        $this->sucursalesList = Sucursales::all();
    }

    public function render()
    {
        return view('livewire.planilla.planilla',[
            'planillas' => $this->Allplanillas(),
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }
    public function Allplanillas()
    {
        if (!empty($this->search)) {
            $this->resetPage();
    
            // Realizamos el join con la tabla 'sucursales' para incluir la búsqueda en el nombre de la sucursal
            $query = Planilla::join('sucursales', 'planillas.sucursal', '=', 'sucursales.id')
                ->select('planillas.*', 'sucursales.nombre as sucursal_nombre') // Seleccionamos las columnas necesarias
                ->where(function($query) {
                    $query->where('planillas.correlativo', 'like', "%{$this->search}%")
                          ->orWhere('planillas.fechaplanilla_inicio', 'like', "%{$this->search}%")
                          ->orWhere('planillas.id', 'like', "%{$this->search}%")
                          ->orWhere('sucursales.nombre', 'like', "%{$this->search}%"); // Buscamos en el nombre de sucursal
                })
                ->orderBy('planillas.created_at', 'asc');
        } else {
            $query = Planilla::orderBy('created_at', 'desc');
        }
    
        $this->records = $query->count();
    
        return $query->paginate($this->pagination);
    }
    

    protected function rules()
    {
        $rules = [
            'detalle' => "required|min:1",
            'fechaplanilla_inicio' => "required|min:1",
            'fechaplanilla_fin' => "required|min:1",
            'tipo_pago' => "required|min:1",
            'sucursal' => "required|min:1"
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'detalle.required' => 'El detalle es requeridO',
            'detalle.min'=> 'El detalle debe tener mas de 1 caracteres',
            'fechaplanilla_inicio.required' => 'la fecha de planilla es requerida',
            'fechaplanilla_inicio.min'=> 'la fecha de planilla debe tener mas de 1 caracteres',
            'fechaplanilla_fin.required' => 'la fecha de planilla es requerida',
            'fechaplanilla_fin.min'=> 'la fecha de planilla debe tener mas de 1 caracteres',
            'tipo_pago.required' => 'El tipo de pago es requerido',
            'tipo_pago.min'=> 'El tipo de pago debe tener mas de 1 caracteres',
            'sucursal.required' => 'la sucursal es requerida',
            'sucursal.min'=> 'la sucursal debe tener mas de 1 caracteres',
        ];
    }

    // En el modelo Planilla

    public function Store()
    {
        $user = Auth::user();
        $this->validate($this->rules(), $this->messages());

        $ultimoCorrelativo = Planilla::withTrashed()->max('correlativo');
        $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo + 1 : 1;


        $empleados = Empleado::where('tipo_pago', $this->tipo_pago)
        ->Where('eventual', operator: 'no')
        ->Where('estado', operator: '1')
        ->where('sucursal', $this->sucursal)
        ->orderBy('nombre', 'asc')->get();

        $existeRegistro = Planilla::where('fechaplanilla_inicio', '=', $this->fechaplanilla_inicio)
        ->where('fechaplanilla_fin', '=', $this->fechaplanilla_fin)
        ->where('tipo_pago', '=', $this->tipo_pago)
        ->where('sucursal', '=', $this->sucursal) // Si el nombre de la columna es 'sucursal_id'
        ->exists(); // Si existe, retorna true

        if($empleados->isNotEmpty() && !$existeRegistro){

            $createStore = Planilla::create([
                'correlativo'=> $nuevoCorrelativo,
                'sucursal' => $this->sucursal,
                'fechaplanilla_inicio'=> $this->fechaplanilla_inicio,
                'fechaplanilla_fin' => $this->fechaplanilla_fin,
                'tipo_pago' => $this->tipo_pago,
                'detalle'=> $this->detalle,
                'usuario' => $user->id
            ]);

            $createStore->save();
            $planillaId = $createStore->id;

            foreach($empleados as $e){
                $sueldoBase = $e->salario;
                if ($this->tipo_pago === 'quincenal') {
                    $sueldoBase /= 2;
                }
                $totalCredito = AdelantoCredito::where('empleado', $e->id)
                ->where('estado', 'pendiente')
                ->sum('credito');

                $inventario = DetallePlanilla::create([
                    'planilla_encabezado'=> $planillaId,
                    'empleado'=>$e->id,
                    'dui_empleado'=>$e->dui,
                    'dias_trabajados'=> 0,
                    'sueldo_base'=>$sueldoBase,
                    'sueldo_liquido'=>0,
                    'afp'=> 0,
                    'seguro'=> 0,
                    'renta'=> 0,
                    'total_descuento'=> 0,
                    'total_pagar'=> 0,
                    'estado'=>'Pendiente',

                    // Ingresos
                    'horas_extras_d'=> 0,
                    'horas_extras_n'=> 0,
                    'porcentaje_nocturnidad'=> 0,
                    'bonificaciones'=> 0,
                    'vacaciones'=> 0,
                    'reintegro_salarial'=> 0,
                    'asueto'=> 0,
                    'otros_ingresos'=> 0,
                    'total_ingresos_generales'=> 0,

                    // Descuentos
                    'anticipo_sueldo'=> $totalCredito > 0 ? $totalCredito : 0,
                    'prestamos_empresarial'=> 0,
                    'otros_descuentos'=> 0,
                    'total_descuentos_generales'=> 0,
                ]);

                $inventario->save();
            }

            $this->emit('item-added', 'Planilla Guardada con exito');
            $this->ResetInt();

            // return redirect()->route(route: 'detalle_planilla');
            return redirect()->route('detalle_planilla', ['idPlanilla' => $planillaId]);

        }else{
            if($existeRegistro){
                $this->emit('item-error', 'El registro ya existe, verifique fechas, sucursal y tipo de pago');
                $this->ResetInt();
            }
            else{
                $this->emit('item-error', 'No se creo la planilla porque no hay empleados con lo parametros requeridos');
                $this->ResetInt();
            }

        }

    }

    public function Edit($id)
    {
        $selectPlanilla = Planilla::find($id);
        $this->fechaplanilla_inicio = $selectPlanilla->fechaplanilla_inicio;
        $this->tipo_pago = $selectPlanilla->tipo_pago;
        $this->detalle = $selectPlanilla->detalle;
        $this->fechaplanilla_fin = $selectPlanilla->fechaplanilla_fin;
        $this->selected_id = $selectPlanilla->id;

        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updatePlanilla = Planilla::find($this->selected_id);
        $updatePlanilla->fechaplanilla_inicio = $this->fechaplanilla_inicio;
        $updatePlanilla->fechaplanilla_fin = $this->fechaplanilla_fin;
        $updatePlanilla->tipo_pago = $this->tipo_pago;
        $updatePlanilla->detalle = $this->detalle;

        $updatePlanilla->save();

        $this->emit('item-updated', 'Planilla Modificada con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'destroy'
    ];

    public function destroy($id)
    {
        $deletePlanilla = Planilla::find($id);

        if ($deletePlanilla) {
    
            DetallePlanilla::where('planilla_encabezado', $deletePlanilla->id)->delete();

            $deletePlanilla->delete();
        } else {
            $this->emit('item-error', 'No se encontro la planilla a eliminar');
        }

        $this->emit('item-confirmar', 'Planilla Eliminada con exito');
        $this->ResetInt();
    }


    public function ResetInt()
    {
        $this->sucursales = '';
        $this->sucursal = '';
        $this->fechaplanilla_inicio = '';
        $this->fechaplanilla_fin = '';
        $this->tipo_pago = '';
        $this->detalle = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
        $this->resetUI();
    }

    public function resetUI()
    {
        $this->sucursales = '';
        $this->sucursal = '';
        $this->fechaplanilla_inicio = '';
        $this->fechaplanilla_fin = '';
        $this->tipo_pago = '';
        $this->detalle = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
