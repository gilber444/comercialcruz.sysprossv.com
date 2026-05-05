<?php

namespace App\Http\Livewire;

use App\Models\ActividadEconomica;
use App\Models\Clientes;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\IdentificacionReceptor;
use App\Models\Municipio;
use App\Models\Municipios;
use App\Models\TipoPersona;
use Livewire\Component;
use Livewire\WithPagination;

class ClientesController extends Component
{
    use WithPagination;

    public  $nombreCliente, $tipoPersona, $dui, $nit, $homologado, $registro, $giro, $direccion, $telefono,
    $departamento, $municipio, $distrito, $actividad, $tipo = 'CLIENTE', $search, $selected_id, $pageTitle, $componentName, $clienteAddSelectId, $clienteAddSelectName, $idenreceptors, $idenReceptor, $celular, $correo, $departamentos, $municipios, $distritos;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Clientes';
        $this->idenreceptors = IdentificacionReceptor::all();
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
            $data = Clientes::join('actividad_economicas as a', 'a.id', 'clientes.actividad')
            ->join('departamentos as d', 'd.id', 'clientes.departamento')
            ->join('municipios as m', 'm.id', 'clientes.municipio')
            ->join('distritos as dis', 'dis.id', 'clientes.distrito')
            ->join('tipo_personas as p', 'p.id', 'clientes.tipoPersona')
            ->select('clientes.*', 'a.valor as actividad', 'd.departamento', 'm.municipio', 'dis.distrito', 'p.valor as persona')
            ->where('nombreCliente', 'like', '%' . $this->search . '%')->paginate($this->pagination);
        else
            $data = Clientes::join('actividad_economicas as a', 'a.id', 'clientes.actividad')
            ->join('departamentos as d', 'd.id', 'clientes.departamento')
            ->join('municipios as m', 'm.id', 'clientes.municipio')
            ->join('distritos as dis', 'dis.id', 'clientes.distrito')
            ->join('tipo_personas as p', 'p.id', 'clientes.tipoPersona')
            ->select('clientes.*', 'a.valor as actividad', 'd.departamento', 'm.municipio', 'dis.distrito', 'p.valor as persona')
            ->orderBy('nombreCliente', 'asc')->paginate($this->pagination);
        return view('livewire.clientes.clientes', ['clientes' => $data, 'actividades' => ActividadEconomica::orderBy('valor', 'asc')->get(), 'personas' => TipoPersona::orderBy('valor', 'asc')->get()])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'nombreCliente' => 'required|unique:clientes|min:3'
        ];

        $messages = [
            'nombreCliente.required' => 'Nombre del Cliente es requerido',
            'nombreCliente.unique' => 'Ya existe el nombre del Cliente',
            'nombreCliente.min'=> 'El Nombre del Cliente debe tener mas de 3 caracteres'
        ];

        $this->validate($rules, $messages);

        $cliente = Clientes::create([
            'nombreCliente' => strtoupper($this->nombreCliente),
            'tipoPersona' => $this->tipoPersona,
            'dui' => $this->dui,
            'nit' => $this->nit,
            'homologado' => $this->homologado,
            'registro' => $this->registro,
            'giro' => strtoupper($this->clienteAddSelectName),
            'direccion' => strtoupper($this->direccion),
            'telefono' => $this->telefono,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->clienteAddSelectId,
            'idenReceptor' => $this->idenReceptor,
            'celular' => $this->celular,
            'email' => $this->correo,
            'desActividad' => strtoupper($this->clienteAddSelectName)
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Cliente registrado');
    }

    public function Edit($id)
    {
        $record = Clientes::find($id);
        $this->nombreCliente = $record->nombreCliente;
        $this->tipoPersona = $record->tipoPersona;
        $this->dui = $record->dui;
        $this->nit = $record->nit;
        $this->homologado = $record->homologado;
        $this->registro = $record->registro;
        $this->giro = $record->giro;
        $this->direccion = $record->direccion;
        $this->telefono = $record->telefono;
        $this->departamento = $record->departamento;
        $this->municipio = $record->municipio;
        $this->distrito = $record->distrito;
        $this->clienteAddSelectId = $record->actividad;
        $this->clienteAddSelectName = $record->desActividad;
        $this->idenReceptor = $record->idenReceptor;
        $this->celular = $record->celular;
        $this->correo = $record->email;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->nombreCliente = '';
        $this->tipoPersona = 'Elegir Tipo de PErsona';
        $this->dui = '';
        $this->nit = '';
        $this->homologado = 'SI';
        $this->registro = '';
        $this->giro = '';
        $this->telefono = '';
        $this->direccion = '';
        $this->departamento = 'Elegir Departamento';
        $this->municipio = 'Elegir Municipio';
        $this->distrito = 'Elegir distrito';
        $this->clienteAddSelectId = '';
        $this->search = '';
        $this->celular = '';
        $this->idenReceptor = '';
        $this->correo = '';
        $this->clienteAddSelectName= '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'nombreCliente' => 'required|min:3'
        ];

        $messages = [
            'nombreCliente.required' => 'Nombre del Cliente es requerido',
            'nombreCliente.min'=> 'El Nombre del Cliente debe tener mas de 3 caracteres'
        ];

        $this->validate($rules, $messages);

        $cliente = Clientes::find($this->selected_id);
        $cliente->update([
            'nombreCliente' => strtoupper($this->nombreCliente),
            'tipoPersona' => $this->tipoPersona,
            'dui' => $this->dui,
            'nit' => $this->nit,
            'homologado' => $this->homologado,
            'registro' => $this->registro,
            'giro' => strtoupper($this->giro),
            'direccion' => strtoupper($this->direccion),
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->clienteAddSelectId,
            'telefono' => $this->telefono,
            'celular' => $this->celular,
            'idenReceptor' => $this->idenReceptor,
            'email' => $this->correo,
            'desActividad' => strtoupper($this->clienteAddSelectName)
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Cliente Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Clientes $cliente /*$id*/)
    {
        $cliente->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Cliente Eliminada');
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
