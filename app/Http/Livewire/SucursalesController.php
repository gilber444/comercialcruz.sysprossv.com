<?php

namespace App\Http\Livewire;

use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Empresas;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Municipios;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\TipoEstablecimiento;
use DB;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SucursalesController extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search, $selected_id, $pageTitle, $modalAction, $componentName, $empresa, $numero, $nombre, $direccion, $telefono, $cajas, $departamentos, $depto, $municipios, $muni, $distritos, $distrito, $establecimientos, $tipo, $empresas, $modo;

    private $pagination = 10;

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Sucursales Activas';
        $this->empresa ='Elegir...';
        $this->dispatchBrowserEvent('keydown', [
            'keys' => ['alt', 's'],
            'action' => 'store',
        ]);

        $this->dispatchBrowserEvent('keydown', [
            'keys' => ['alt', 's'],
            'action' => 'edit',
        ]);
        $this->departamentos = Departamentos::all();
        $this->municipios = Municipios::all();
        $this->distritos = Distritos::all();
        $this->establecimientos = TipoEstablecimiento::all();

        $user = Auth::user();

        if ($user->profile == 'Super') {
            // Si el usuario es super, obtiene todas las empresas
            $this->empresas = Empresas::all();
        } else {
            // Si no es super, solo obtiene su empresa
            $this->empresas = Empresas::where('id', $user->empresa)->get();
            $this->empresa = $user->empresa;
        }
    }

    public function render()
    {
        if(strlen($this->search) > 0)
        {
            $sucursales = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->where('nombre', 'like', '%' . $this->search . '%')->select('sucursales.*', 'e.empresa')->paginate($this->pagination);
        }
        else
        {
            $sucursales = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->select('sucursales.*', 'e.empresa')->orderBy('nombre', 'asc')->paginate($this->pagination);
        }

        return view('livewire.sucursales.sucursales', ['sucursales' => $sucursales])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'numero' => 'required|min:1|unique:sucursales,numero',
            'nombre' => 'required|min:3|unique:sucursales,nombre',
            'cajas' => 'required',
            'empresa' => 'required|not_in:Elegir'
        ];

        $messages = [
            'numero.required' => 'El numero se la sucursal es requerido',
            'numero.unique' => 'El numero de la sucursal ya existe',
            'numero.min' => 'El numero de la sucursal tiene que tener mas de un caracter',
            'nombre.requited' => 'El nombre de la sucursal es requerido',
            'nombre.unique'=> 'El nombre de la sucursal ya existe',
            'nombre.min' => 'El nombre de la sucirsal tiene que tener al menos 3 caracteres',
            'cajas.required' => 'El numero de cajas es requerido',
            'empresa.not_in' => 'Elige un nombre de empresa diferente de elegir'
        ];
        $this->validate($rules, $messages);

        $sucursal = Sucursales::create([
            'numero' => $this->numero,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'cajas' => $this->cajas,
            'empresa' => $this->empresa,
            'tipo' => $this->tipo,
            'departamento' => $this->depto,
            'municipio' => $this->muni,
            'distrito' => $this->distrito,
            'modo' => $this->modo
        ]);

        // Obtener todos los productos
        $productos = Productos::all();
        // Crear inventario para cada producto con existencia inicial 0
        foreach ($productos as $producto) {
            $inventario = Inventarios::create([
                'producto' => $producto->id,
                'empresa' => $this->empresa,
                'sucursal' => $sucursal->id,
                'existencia' => 0
            ]);

            // Opcional: Registrar movimiento en el Kardex
            Kardex::create([
                'producto' => $producto->id,
                'inventario' => $inventario->id,
                'descripcion' => 'Creación de inventario inicial',
                'fecha' => now()->format('Y-m-d'),
                'hora' => now()->format('H:i:s'),
                'ingresoCantidad' => 0,
                'ingresoValor' => 0,
                'egresoCantidad' => 0,
                'egresoValor' => 0,
                'saldoCantidad' => 0,
                'saldoValor' => 0
            ]);
        }

        $this->emit('item-added', 'Sucursal registrada con exito');
        $this->resetUI();
    }

    public function Edit(Sucursales $sucursal)
    {
        $this->selected_id = $sucursal->id;
        $this->empresa = $sucursal->empresa;
        $this->direccion = $sucursal->direccion;
        $this->telefono = $sucursal->telefono;
        $this->cajas = $sucursal->cajas;
        $this->nombre = $sucursal->nombre;
        $this->numero = $sucursal->numero;
        $this->tipo = $sucursal->tipo;
        $this->depto = $sucursal->departamento;
        $this->muni = $sucursal->municipio;
        $this->distrito = $sucursal->distrito;
        $this->modo = $sucursal->modo;

        $this->emit('show-modal','Editar Sucursal');
    }

    public function Update()
    {
        $rules = [
            'numero' => 'required|min:1',
            'nombre' => 'required|min:3',
            'cajas' => 'required',
            'empresa' => 'required|not_in:Elegir'
        ];

        $messages = [
            'numero.required' => 'El numero se la sucursal es requerido',
            'numero.min' => 'El numero de la sucursal tiene que tener mas de un caracter',
            'nombre.requited' => 'El nombre de la sucursal es requerido',
            'nombre.min' => 'El nombre de la sucirsal tiene que tener al menos 3 caracteres',
            'cajas.required' => 'El numero de cajas es requerido',
            'empresa.not_in' => 'Elige un nombre de empresa diferente de elegir'
        ];
        $this->validate($rules, $messages);

        $sucursales = Sucursales::find($this->selected_id);
        $sucursales->numero = $this->numero;
        $sucursales->nombre = $this->nombre;
        $sucursales->direccion = $this->direccion;
        $sucursales->telefono = $this->telefono;
        $sucursales->cajas = $this->cajas;
        $sucursales->empresa = $this->empresa;
        $sucursales->tipo = $this->tipo;
        $sucursales->departamento =$this->depto;
        $sucursales->municipio = $this->muni;
        $sucursales->distrito = $this->distrito;
        $sucursales->modo = $this->modo;
        $sucursales->save();

        $this->emit('item-updated', 'Se actualizo los datos de la Sucursal con exito');
        $this->resetUI();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'store' => 'Store',
        'edit' => 'Edit'
    ];

    public function Destroy($id)
    {
        $cajasCount = Sucursales::find($id)->cajas->count();

        if($cajasCount > 0)
        {
            $this->emit('item-error', 'No se puede eliminar la sucursal porque tiene cajas asociadas');
            return;
        }

        Sucursales::find($id)->delete();
        $this->emit('item-deleted', 'Se elimino la sucursal con exito');
        $this->resetUI();
    }

    public function resetUI()
    {
        $this->empresa = '';
        $this->direccion = '';
        $this->telefono = '';
        $this->nombre = '';
        $this->numero = '';
        $this->cajas = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->tipo = '';
        $this->depto = '';
        $this->muni = '';
        $this->distrito = '';
        $this->modo = '';
        $this->resetValidation();
    }

    public function getListeners()
    {
        return [
            'keydown.alt.s' => 'Store',
            'keydown.alt.s' => 'Update'
        ];
    }
    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->depto)->get();
    }

    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->muni)->get();
    }
}
