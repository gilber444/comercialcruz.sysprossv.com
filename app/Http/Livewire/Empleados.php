<?php

namespace App\Http\Livewire;

use App\Models\Cargos;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Empleado;
use App\Models\Municipios;
use App\Models\Pais;
use App\Models\User;
use App\Models\Sucursales;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Empleados extends Component
{
    use WithPagination;

    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $nombre, $nacimiento, $dui, $nit, $seguro, $afp, $ingreso, $salario, $cargo, $updateEmpleado,$estado, $tipo_pago, $eventual, $domicilio, $telefono, $sexo, $pais, $departamento, $municipio, $distrito, $expedicion_dui, $salida, $estadoEmpleado, $comentarios , $sucursales, $sucursal_destino;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Empleados';

        $this->sucursales = Sucursales::all();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.empleados.empleados', [
            'empleados' => $this->Allempleados(),
            'cargos' => $this->Cargos(),
            'departamentos' => $this->Departamentos(),
            'municipios' => $this->Municipios(),
            'distritos' => $this->Distritos(),
            'paises' => $this->Paises(),
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Allempleados()
    {
        $query = Empleado::query();
    
        // Filtro por búsqueda si hay algo en $this->search
        if (!empty($this->search)) {
            $query = $query->where('nombre', 'like', "%{$this->search}%")
                           ->orWhere('dui', 'like', "%{$this->search}%")
                           ->orWhere('tipo_pago', 'like', "%{$this->search}%")
                           ->orderBy('nombre', 'asc');
        } else {
            // Si no hay búsqueda, ordenar por fecha de creación
            $query = $query->orderBy('created_at', 'desc');
        }
    
        // Filtro por estado si $this->estadoEmpleado tiene un valor
        if ($this->estadoEmpleado !== null) {
            $query = $query->where('estado', $this->estadoEmpleado);
        }
    
        // Contar el total de registros filtrados
        $this->records = $query->count();
    
        // Devolver los resultados paginados
        return $query->paginate($this->pagination);
    }
    
    

    public function Cargos()
    {

        $query = Cargos::where('estado', '1')
        ->orderBy('id', 'asc');


        $this->records = $query->count();

        return $query->get();
    }

    public function Departamentos()
    {

        $query = Departamentos::orderBy('id', 'asc');

        $this->records = $query->count();

        return $query->get();
    }

    public function Distritos()
    {

        $query = Distritos::orderBy('id', 'asc');

        $this->records = $query->count();

        return $query->get();
    }

    public function Municipios()
    {

        $query = Municipios::orderBy('id', 'asc');

        $this->records = $query->count();

        return $query->get();
    }

    public function Paises()
    {

        $query = Pais::orderBy('id', 'asc');

        $this->records = $query->count();

        return $query->get();
    }

    protected function rules()
    {
        $rules = [
            'nombre' => "required|min:1",
            'nacimiento' => "required|min:1",
            'ingreso' => "required|min:1",
            'dui' => "required|min:1",
            'nit' => "required|min:1",
            'seguro' => "required|min:1",
            'afp' => "required|min:1",
            'salario' => "required|min:1",
            'cargo' => "required|min:1",
            'estado' => "required|min:1",
            'tipo_pago' => "required|min:1",
            'eventual' => "required|min:1",
            'sucursal_destino'=> "required|min:1",
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'nombre.required' => 'El nombre es requerida',
            'nombre.min'=> 'El nombre debe tener mas de 1 caracteres',
            'nacimiento.required' => 'la fecha de nacimiento es requerida',
            'nacimiento.min'=> 'la fecha nacimiento debe tener mas de 1 caracteres',
            'ingreso.required' => 'la fecha de ingreso es requerida',
            'ingreso.min'=> 'la fecha de ingreso debe tener mas de 1 caracteres',
            'dui.required' => 'El dui es requerido',
            'dui.min'=> 'El dui debe tener mas de 1 caracteres',
            'nit.required' => 'El nit es requerido',
            'nit.min'=> 'El nit debe tener mas de 1 caracteres',
            'seguro.required' => 'El seguro es requerido',
            'seguro.min'=> 'El seguro debe tener mas de 1 caracteres',
            'afp.required' => 'El afp es requerido',
            'afp.min'=> 'El afp debe tener mas de 1 caracteres',
            'salario.required' => 'El salario es requerido',
            'salario.min'=> 'El salario debe tener mas de 1 caracteres',
            'cargo.required' => 'El cargo es requerido',
            'cargo.min'=> 'El cargo debe tener mas de 1 caracteres',
            'estado.required' => 'El estado es requerido',
            'estado.min'=> 'El estado debe tener mas de 1 caracteres',
            'tipo_pago.required' => 'El tipo de pago es requerido',
            'tipo_pago.min'=> 'El tipo de pago debe tener mas de 1 caracteres',
            'eventual.required' => 'El eventual es requerido',
            'eventual.min'=> 'El eventual debe tener mas de 1 caracteres',
            'sucursal_destino.required' => 'La sucursal es requerido',
            'sucursal_destino.min'=> 'La sucursal debe tener mas de 1 caracteres',
        ];
    }

    public function Store()
    {
        $this->validate($this->rules(), $this->messages());

        DB::beginTransaction();
        try {

            $createEmpleado = Empleado::create([
                'nombre' => $this->nombre,
                'nacimiento' => $this->nacimiento,
                'dui' => $this->dui,
                'nit' => $this->nit,
                'seguro' => $this->seguro,
                'afp' => $this->afp,
                'ingreso' => $this->ingreso,
                'salario' => $this->salario,
                'cargo' => $this->cargo,
                'estado' => $this->estado,
                'tipo_pago' => $this->tipo_pago,
                'eventual' => $this->eventual,
                'domicilio' => $this->domicilio,
                'telefono' => $this->telefono,
                'sexo' => $this->sexo,
                'pais' => $this->pais,
                'departamento' => $this->departamento,
                'municipio' => $this->municipio,
                'distrito' => $this->distrito,
                'expedicion_dui' => $this->expedicion_dui ?: null,
                'salida' => $this->salida ?: null,
                'comentarios'=> $this->comentarios ?: null,
                'sucursal' => $this->sucursal_destino ?: null
            ]);

            // 3) Generamos username y password basados en el nombre
            $parts        = preg_split('/\s+/', trim($this->nombre));
            $firstInitial = strtolower(substr($parts[0], 0, 1));
            $firstSurname = count($parts) >= 3
                ? strtolower($parts[2])
                : (count($parts) >= 2
                    ? strtolower($parts[1])
                    : '');

            $username    = $firstInitial . $firstSurname;
            $passwordRaw = $username;

            // 4) Creamos el User
            $user = User::create([
                'name'      => $this->nombre,
                'user'      => $username,
                'email'     => $username.'@sysprossv.com',
                'phone'     => $this->telefono,
                'profile'   => 'Vendedor',
                'status'    => 'ACTIVE',
                'password'  => bcrypt($passwordRaw),
                'password2' => $passwordRaw,
                'empresa'   => 1,
                'sucursal'  => 1,
                'codigo' => $createEmpleado->id
            ]);

            // 5) Asignamos rol según cargo
            $user->syncRoles(4);

            DB::commit();

            $this->emit('item-created', 'Empleado y Usuario registrados con éxito');
            $this->ResetInt();
        } catch (\Exception $e) {
            DB::rollBack();
            // opcional: manejar/loguear el error
            throw $e;
        }
    }

    public function Edit($id)
    {
        $selectEmpleado = Empleado::find($id);
        $this->nombre = $selectEmpleado->nombre;
        $this->nacimiento = $selectEmpleado->nacimiento;
        $this->dui = $selectEmpleado->dui;
        $this->nit = $selectEmpleado->nit;
        $this->seguro = $selectEmpleado->seguro;
        $this->afp = $selectEmpleado->afp;
        $this->ingreso = $selectEmpleado->ingreso;
        $this->salario = $selectEmpleado->salario;
        $this->cargo = $selectEmpleado->cargo;
        $this->estado = $selectEmpleado->estado;
        $this->tipo_pago = $selectEmpleado->tipo_pago;
        $this->eventual = $selectEmpleado->eventual;

        //Nuevos Campos - Cambio DICIEMBRE 22/12/2024
        $this->domicilio = $selectEmpleado->domicilio;
        $this->telefono = $selectEmpleado->telefono;
        $this->sexo = $selectEmpleado->sexo;
        $this->pais = $selectEmpleado->pais;
        $this->departamento = $selectEmpleado->departamento;
        $this->municipio = $selectEmpleado->municipio;
        $this->distrito = $selectEmpleado->distrito;
        $this->expedicion_dui = $selectEmpleado->expedicion_dui;
        $this->salida = $selectEmpleado->salida;
        $this->comentarios = $selectEmpleado->comentarios;
        $this->sucursal_destino = $selectEmpleado->sucursal;
        $this->selected_id = $selectEmpleado->id;

        $this->emit('show-modal');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updateEmpleado = Empleado::find($this->selected_id);
        $updateEmpleado->nombre = $this->nombre;
        $updateEmpleado->nacimiento = $this->nacimiento;
        $updateEmpleado->dui = $this->dui;
        $updateEmpleado->nit = $this->nit;
        $updateEmpleado->seguro = $this->seguro;
        $updateEmpleado->afp = $this->afp;
        $updateEmpleado->ingreso = $this->ingreso;
        $updateEmpleado->salario = $this->salario;
        $updateEmpleado->cargo = $this->cargo;
        $updateEmpleado->estado = $this->estado;
        $updateEmpleado->tipo_pago = $this->tipo_pago;
        $updateEmpleado->eventual = $this->eventual;
        $updateEmpleado->save();

        //Nuevos Campos - Cambio DICIEMBRE 22/12/2024
        $updateEmpleado->domicilio = $this->domicilio;
        $updateEmpleado->telefono = $this->telefono;
        $updateEmpleado->sexo = $this->sexo;
        $updateEmpleado->pais = $this->pais;
        $updateEmpleado->departamento = $this->departamento;
        $updateEmpleado->municipio = $this->municipio;
        $updateEmpleado->distrito = $this->distrito;
        $updateEmpleado->expedicion_dui = $this->expedicion_dui ?: null;
        $updateEmpleado->salida = $this->salida ?: null;
        $updateEmpleado->comentarios = $this->comentarios ?: null;
        $updateEmpleado->sucursal = $this->sucursal_destino ?: null;
        $updateEmpleado->save();

        $this->emit('item-updated', 'Empleado Actualizado con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'destroy'
    ];

    public function destroy($id)
    {
        $deleteUnidad = Empleado::findOrFail($id);
        $deleteUnidad->delete();

        $this->ResetInt();
        $this->emit('item-updated', 'EMPELADO ELIMINADO CON ÉXITO');
    }


    public function ResetInt()
    {
        $this->nombre = '';
        $this->nacimiento = '';
        $this->dui = '';
        $this->nit = '';
        $this->seguro = '';
        $this->afp = '';
        $this->ingreso = '';
        $this->salario = '';
        $this->cargo = '';
        $this->estado = '';
        $this->tipo_pago = '';
        $this->eventual = '';
        $this->selected_id = 0;
        $this->domicilio= '';
        $this->telefono= '';
        $this->sexo= '';
        $this->pais= '';
        $this->departamento= '';
        $this->municipio= '';
        $this->distrito= '';
        $this->expedicion_dui= '';
        $this->salida= '';
        $this->comentarios= '';
        $this->sucursal_destino= '';
        $this->resetValidation();
        $this->resetPage();
    }

    public function resetUI()
    {
        $this->sucursal_destino= '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

}
