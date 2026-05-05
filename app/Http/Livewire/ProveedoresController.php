<?php

namespace App\Http\Livewire;

use App\Models\ActividadEconomica;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Municipios;
use App\Models\Proveedores;
use App\Models\TipoPersona;
use Livewire\Component;
use Livewire\WithPagination;

class ProveedoresController extends Component
{
    use WithPagination;

    public  $nombre, $tipoPersona, $direccion, $telefono, $correo, $registro, $nit,
    $departamento, $municipio, $distrito, $actividad, $giro, $search, $selected_id, $pageTitle, $componentName, $departamentos, $municipios, $distritos, $proveedorAddSelectId, $proveedorAddSelectName, $categoria;

    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Proveedores';
        $this->departamentos = Departamentos::orderBy('departamento', 'asc')->get();
        $this->municipios = Municipios::orderBy('municipio', 'asc')->get();
        $this->distritos = Distritos::orderBy('distrito', 'asc')->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {

        if(strlen($this->search) > 0)
            $data = Proveedores::join('actividad_economicas as a', 'a.id', 'proveedores.actividad')
            ->join('departamentos as d', 'd.id', 'proveedores.departamento')
            ->join('municipios as m', 'm.id', 'proveedores.municipio')
            ->join('distritos as dis', 'dis.id', 'proveedores.distrito')
            ->join('tipo_personas as p', 'p.id', 'proveedores.tipoPersona')
            ->select('proveedores.*', 'a.valor as actividad', 'd.departamento', 'm.municipio', 'dis.distrito')
            ->where('nombre', 'like', '%' . $this->search . '%')->paginate($this->pagination);
        else
            $data = Proveedores::join('actividad_economicas as a', 'a.id', 'proveedores.actividad')
            ->join('departamentos as d', 'd.id', 'proveedores.departamento')
            ->join('municipios as m', 'm.id', 'proveedores.municipio')
            ->join('distritos as dis', 'dis.id', 'proveedores.distrito')
            ->join('tipo_personas as p', 'p.id', 'proveedores.tipoPersona')
            ->select('proveedores.*', 'a.valor as actividad', 'd.departamento', 'm.municipio', 'dis.distrito', 'p.valor as persona')
            ->orderBy('nombre', 'asc')->paginate($this->pagination);

        return view('livewire.proveedores.proveedores', ['proveedores' => $data, 'actividades' => ActividadEconomica::orderBy('valor', 'asc')->get(), 'personas' => TipoPersona::orderBy('valor', 'asc')->get()])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'nombre' => "required|unique:proveedores|min:3",
            'tipoPersona' => 'required|not_in:Elegir TipoPersona',
            'telefono' => "required|unique:proveedores|min:3",
            'nit' => "required|numeric|unique:proveedores|min:3",
            'registro' => 'numeric',
            'departamento' => 'required|not_in:Elegir Departamento',
            'municipio' => 'required|not_in:Elegir Municipio',
            'distrito' => 'required|not_in:Elegir Distrito',
            'giro' => "required|min:3",
            'proveedorAddSelectId' => 'required'
        ];

        $messages = [
            'proveedorAddSelectId.required' => 'La Actividad Econimica es Requerida',
            'nombre.required' => 'El Nombre del Proveedor es requerido',
            'nombre.unique' => 'El Nombre del Proveedor ya existe',
            'nombre.min'=> 'El Nombre del Proveedor debe tener mas de 3 caracteres',
            'tipoPersona.not_in' => 'El tipo de persona es Requerido',
            'telefono.required' => 'El Numero de telefono es requerido',
            'telefono.unique' => 'El numero de telefono ya existe',
            'telefono.min'=> 'El numero de telefono debe tener mas de 3 caracteres',
            'registro.numeric' => 'El Numero de registro es numerico',
            'nit.required' => 'El Numero de NIT es requerido',
            'nit.unique' => 'El Numero de NIT ya existe',
            'nit.min'=> 'El Numero de NIT debe tener mas de 3 caracteres',
            'departamento.not_in' => 'El departamento es Requerido',
            'municipio.not_in' => 'El municipio es Requerido',
            'distrito.not_in' => 'El distrito es Requerido',
            'giro.required' => 'El Giro del Proveedor es requerido',
            'giro.min'=> 'El Giro del Proveedor debe tener mas de 3 caracteres',
            'nit.numeric' =>'El numero de Registro tiene que ser numerico'
        ];

        $this->validate($rules, $messages);

        $pro = Proveedores::create([
            'nombre' => $this->nombre,
            'tipoPersona' => $this->tipoPersona,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'registro' => $this->registro,
            'nit' => $this->nit,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->proveedorAddSelectId,
            'giro' => $this->giro,
            'desActividad'=>$this->proveedorAddSelectName,
            'categoria' => $this->categoria
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Proveedores registrado');
    }

    public function Edit(Proveedores $pro)
    {
        $this->selected_id = $pro->id;
        $this->nombre = $pro->nombre;
        $this->tipoPersona = $pro->tipoPersona;
        $this->direccion = $pro->direccion;
        $this->telefono = $pro->telefono;
        $this->correo = $pro->correo;
        $this->registro = $pro->registro;
        $this->nit = $pro->nit;
        $this->departamento = $pro->departamento;
        $this->municipio = $pro->municipio;
        $this->distrito = $pro->distrito;
        $this->actividad = $pro->actividad;
        $this->giro = $pro->giro;
        $this->proveedorAddSelectId = $pro->actividad;
        $this->proveedorAddSelectName = $pro->desActividad;
        $this->categoria = $pro->categoria;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->nombre = '';
        $this->direccion = '';
        $this->telefono = '';
        $this->correo = '';
        $this->registro = '';
        $this->nit = '';
        $this->departamento = 'Elegir Departamento';
        $this->tipoPersona = 'Elegir Tipo de Persona';
        $this->municipio = 'Elegir Municipio';
        $this->distrito = 'Elegir distrito';
        $this->actividad = ' Elegir Actividad Economica';
        $this->categoria = '';
        $this->giro = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->departamento = '';
        $this->municipio = '';
        $this->distrito= '';
        $this->proveedorAddSelectId = '';
        $this->proveedorAddSelectName = '';
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update(Proveedores $pro)
    {
        $rules = [
            'nombre' => "required|unique:proveedores,nombre,{$this->selected_id}|min:3",
            'tipoPersona' => 'required|not_in:Elegir TipoPersona',
            'telefono' => "required|unique:proveedores,telefono,{$this->selected_id}|min:3",
            'registro' => "required|numeric|unique:proveedores,registro,{$this->selected_id}|min:3",
            'nit' => "required|numeric|unique:proveedores,nit,{$this->selected_id}|min:3",
            'departamento' => 'required|not_in:Elegir Departamento',
            'municipio' => 'required|not_in:Elegir Municipio',
            'distrito' => 'required|not_in:Elegir Distrito',
            'actividad' => 'required|not_in:Elegir Actividad Economica',
            'giro' => "required|min:3"
        ];

        $messages = [
            'proveedorSelectId.required' => 'La Actividad Econimica es Requerida',
            'nombre.required' => 'El Nombre del Proveedor es requerido',
            'nombre.min'=> 'El Nombre del Proveedor debe tener mas de 3 caracteres',
            'tipoPersona.not_in' => 'El tipo de persona es Requerido',
            'telefono.required' => 'El Numero de telefono es requerido',
            'telefono.min'=> 'El numero de telefono debe tener mas de 3 caracteres',
            'registro.required' => 'El Numero de registro es requerido',
            'registro.min'=> 'El numero de registro debe tener mas de 3 caracteres',
            'nit.required' => 'El Numero de NIT es requerido',
            'nit.min'=> 'El Numero de NIT debe tener mas de 3 caracteres',
            'departamento.not_in' => 'El departamento es Requerido',
            'municipio.not_in' => 'El municipio es Requerido',
            'distrito.not_in' => 'El distrito es Requerido',
            'actividad.not_in' => 'La Actividad economica es requerida',
            'giro.required' => 'El Giro del Proveedor es requerido',
            'giro.min'=> 'El Giro del Proveedor debe tener mas de 3 caracteres',
            'proveedorSelectId' => 'required',
            'registro.numeric' => 'El Numero de registro es numerico',
            'nit.required' => 'El Numero de NIT es requerido',
            'nit.numeric' =>'El numero de Registro tiene que ser numerico'
        ];
        $this->validate($rules, $messages);

        $pro = Proveedores::find($this->selected_id);
        $pro->update([
            'nombre' => $this->nombre,
            'tipoPersona' => $this->tipoPersona,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'registro' => $this->registro,
            'nit' => $this->nit,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->actividad,
            'giro' => $this->giro,
            'categoria' => $this->categoria
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Proveedores Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Proveedores $pro)
    {
        $pro->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Proveedores Eliminada');
    }

    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->departamento)->get();
    }

    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->municipio)->get();
    }
}
