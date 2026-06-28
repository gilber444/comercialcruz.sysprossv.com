<?php

namespace App\Livewire;

use App\Http\Livewire\TipoPermisos;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Empleado;
use App\Models\TipoPermiso;
use App\Models\PermisosRRHH as PermisosRRHHmodel;
class PermisosRRHH extends Component
{
    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $detalle = '', $empleado, $fechainicio, $fechafin, $tipopermiso, $remuneracion;


    use WithPagination;
    use WithFileUploads;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Permisos';
    }

    public function render()
    {
        return view('livewire.permisosRRHH.permisos_rrhh', [
            'permisos' => $this->Allpermisos(),
            'empleados' => $this->Empleados(),
            'tipopermisos' => $this->TipoPermisos(),
        ]);
    }

    public function Allpermisos()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = PermisosRRHHmodel::where('fechainicio', 'like', "%{$this->search}%")
                ->where('fechafin', 'like', "%{$this->search}%")
                ->orderBy('created_at', 'asc');

        } else {
            $query = PermisosRRHHmodel::orderBy('created_at', 'desc');
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
                ->orderBy('id', 'asc');
        }

        $this->records = $query->count();

        return $query->get();
    }

    public function TipoPermisos()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = TipoPermisos::where('estado', '1')
                ->orderBy('id', 'asc');

        } else {
            $query = TipoPermisos::where('estado', '1')
                ->orderBy('id', 'asc');
        }

        $this->records = $query->count();

        return $query->get();
    }

    protected function rules()
    {
        $rules = [
            'empleado' => "required|min:1",
            'fechainicio' => "required|min:1",
            'fechafin' => "required|min:1",
            'tipopermiso' => "required|min:1",
            'remuneracion' => "required|min:1",
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'empleado.required' => 'El empleado es requeridO',
            'empleado.min'=> 'El empleado debe tener mas de 1 caracteres',
            'fechainicio.required' => 'la fecha de inicio es requerida',
            'fechainicio.min'=> 'la fecha inicio debe tener mas de 1 caracteres',
            'fechafin.required' => 'la fecha fin es requerida',
            'fechafin.min'=> 'la fecha fin debe tener mas de 1 caracteres',
            'tipopermiso.required' => 'el tipo permiso es requerida',
            'tipopermiso.min'=> 'el tipo permiso debe tener mas de 1 caracteres',
            'remuneracion.required' => 'la remuneracion es requerida',
            'remuneracion.min'=> 'la remuneracion debe tener mas de 1 caracteres',
        ];
    }

    public function Store()
    {
        $this->validate($this->rules(), $this->messages());

        $createStore = PermisosRRHHmodel::create([
            'empleado'=> $this->empleado,
            'fechainicio'=> $this->fechainicio,
            'fechafin'=> $this->fechafin,
            'tipopermiso'=> $this->tipopermiso,
            'remuneracion'=> $this->remuneracion,
            'detalle'=> $this->detalle
        ]);

        $createStore->save();

        $this->dispatch('noty', msg: 'Permiso registrado con exito');
        $this->ResetInt();
        $this->dispatch('close-modal');

        return redirect()->route(route: 'permisos');
    }

    public function Edit($id)
    {
        $selectPermiso = PermisosRRHHmodel::find($id);
        $this->empleado = $selectPermiso->empleado;
        $this->fechainicio = $selectPermiso->fechainicio;
        $this->fechafin = $selectPermiso->fechafin;
        $this->tipopermiso = $selectPermiso->tipopermiso;
        $this->remuneracion = $selectPermiso->remuneracion;
        $this->detalle = $selectPermiso->detalle;
        $this->selected_id = $selectPermiso->id;

        $this->dispatch('open-modal');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updatePermiso = PermisosRRHHmodel::find($this->selected_id);
        $updatePermiso->empleado = $this->empleado;
        $updatePermiso->fechainicio = $this->fechainicio;
        $updatePermiso->fechafin = $this->fechafin;
        $updatePermiso->tipopermiso = $this->tipopermiso;
        $updatePermiso->remuneracion = $this->remuneracion;
        $updatePermiso->detalle = $this->detalle;

        $updatePermiso->save();

        $this->dispatch('noty', msg: 'Permiso Actualizado con exito');
        $this->ResetInt();
        $this->dispatch('close-modal');

        return redirect()->route(route: 'permisos');
    }

    public function destroy($id)
    {
        $deletePermiso = PermisosRRHHmodel::findOrFail($id);
        $deletePermiso->delete();

        $this->resetPage();
        $this->ResetInt();
        $this->dispatch('noty', msg: 'PERMISO ELIMINADO CON ÉXITO');
    }

    public function ResetInt()
    {
        $this->empleado = '';
        $this->fechainicio = '';
        $this->fechafin = '';
        $this->tipopermiso = '';
        $this->remuneracion = '';
        $this->detalle = '';
        $this->selected_id = 0;
        $this->resetValidation();
    }
}
