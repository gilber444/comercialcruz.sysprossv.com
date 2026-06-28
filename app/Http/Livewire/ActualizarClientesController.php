<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ActividadEconomica;
use App\Models\Clientes;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\IdentificacionReceptor;
use App\Models\Municipios;
use App\Models\TipoPersona;

class ActualizarClientesController extends Component
{
    public $nombreCliente, $tipoPersona, $dui, $nit, $homologado, $registro, $giro, $direccion, $telefono,
           $departamento, $municipio, $distrito, $actividadCliente, $tipo = 'CLIENTE', $selected_id,
           $pageTitle, $componentName, $clienteAddSelectId, $clienteAddSelectName, $idenreceptors,
           $idenReceptor, $celular, $correo, $departamentos, $municipios, $distritos;

    public function mount()
    {
        $this->pageTitle = 'Actualizacion de Datos de ';
        $this->componentName = 'Clientes';
        $this->idenreceptors = IdentificacionReceptor::all();
        $this->departamentos = Departamentos::orderBy('departamento', 'asc')->get();
        $this->municipios = Municipios::orderBy('municipio', 'asc')->get();
        $this->distritos = Distritos::orderBy('distrito', 'asc')->get();

        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.clientes.actualizar-clientes', [
            'actividades' => ActividadEconomica::orderBy('valor', 'asc')->get(),
            'personas' => TipoPersona::orderBy('valor', 'asc')->get()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function buscarDui()
    {
        $record = Clientes::where('dui', $this->dui)->first();
        if($record)
        {
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
            $this->actividadCliente = $record->actividad;
            $this->clienteAddSelectName = $record->desActividad;
            $this->idenReceptor = $record->idenReceptor;
            $this->celular = $record->celular;
            $this->correo = $record->email;
            $this->selected_id = $record->id;
        }
        else
        {
            $this->emit('item-error', 'No se encontro este numero de Dui');
        }
    }

    public function buscarNombre()
    {
        $record = Clientes::where('nombreCliente', $this->nombreCliente)->first();
        if($record)
        {
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
            $this->actividadCliente = $record->actividad;
            $this->clienteAddSelectName = $record->desActividad;
            $this->idenReceptor = $record->idenReceptor;
            $this->celular = $record->celular;
            $this->correo = $record->email;
            $this->selected_id = $record->id;
        }
        else
        {
            $this->emit('item-error', 'No se encontraron datos del cliente');
        }
    }

    public function resetForm()
    {
        $this->nombreCliente = '';
        $this->tipoPersona = 'Elegir Tipo de Persona';
        $this->dui = '';
        $this->nit = '';
        $this->homologado = 'SI';
        $this->registro = '';
        $this->giro = '';
        $this->telefono = '';
        $this->direccion = '';
        $this->actividadCliente = '';
        $this->celular = '';
        $this->idenReceptor = '';
        $this->correo = '';
        $this->clienteAddSelectName = '';
        $this->selected_id = 0;
    }

    public function Update()
    {
        // Reglas de validación
        $rules = [
            'nombreCliente' => 'required|min:3',
            'telefono' => 'numeric', // Requerido y solo números
            'correo' => 'required|email', // Requerido y formato de correo válido
            'idenReceptor' => 'required' // Requerido
        ];

        // Mensajes de error personalizados
        $messages = [
            'nombreCliente.required' => 'Nombre del Cliente es requerido',
            'nombreCliente.min'=> 'El Nombre del Cliente debe tener más de 3 caracteres',
            'telefono.required' => 'Teléfono es requerido',
            'telefono.numeric' => 'Teléfono solo debe contener números',
            'correo.required' => 'Correo es requerido',
            'correo.email' => 'Correo debe tener un formato válido',
            'idenReceptor.required' => 'Identificación del Receptor es requerida'
        ];

        //validar di DUI solo tiene valor
        if ($this->dui !== null) {
            $rules['dui'] = 'regex:/^\d{8}-\d{1}$/'; // Solo números si hay valor
            $messages['nit.regex'] = 'DUI debe seguir el formato 99999999-9';
        }

        // Validar NIT solo si tiene valor
        if ($this->nit !== null) {
            $rules['nit'] = 'numeric'; // Solo números si hay valor
            $messages['nit.numeric'] = 'NIT solo debe contener números';
        }

        // Validar Registro solo si tiene valor
        if ($this->registro !== null) {
            $rules['registro'] = 'numeric'; // Solo números si hay valor
            $messages['registro.numeric'] = 'Registro solo debe contener números';
        }

        // Validación
        $this->validate($rules, $messages);

        // Actualización del cliente
        $cliente = Clientes::find($this->selected_id);
        $cliente->update([
            'nombreCliente' => strtoupper($this->nombreCliente),
            'tipoPersona' => $this->tipoPersona,
            'dui' => $this->dui,
            'nit' => $this->nit,
            'homologado' => $this->homologado,
            'registro' => $this->registro,
            'giro' => strtoupper($this->clienteAddSelectName),
            'direccion' => strtoupper($this->direccion),
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->actividadCliente,
            'telefono' => $this->telefono,
            'celular' => $this->celular,
            'idenReceptor' => $this->idenReceptor,
            'email' => $this->correo,
            'desActividad' => strtoupper($this->clienteAddSelectName)
        ]);

        // Resetear UI y emitir evento
        $this->resetForm();
        $this->emit('item-updated', 'Cliente Actualizado');
    }

    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->departamento)->get();
    }

    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->municipio)->get();
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

        //validar di DUI solo tiene valor
        if ($this->dui !== null) {
            $rules['dui'] = 'regex:/^\d{8}-\d{1}$/'; // Solo números si hay valor
            $messages['nit.regex'] = 'DUI debe seguir el formato 99999999-9';
        }

        // Validar NIT solo si tiene valor
        if ($this->nit !== null) {
            $rules['nit'] = 'numeric'; // Solo números si hay valor
            $messages['nit.numeric'] = 'NIT solo debe contener números';
        }

        // Validar Registro solo si tiene valor
        if ($this->registro !== null) {
            $rules['registro'] = 'numeric'; // Solo números si hay valor
            $messages['registro.numeric'] = 'Registro solo debe contener números';
        }


        $this->validate($rules, $messages);
        $tipo = (trim($this->tipoPersona) === '' || is_null($this->tipoPersona)) ? 1 : $this->tipoPersona;

        if(!$this->clienteAddSelectId)
        {
            $actividad = 775;
            $des = 'OTROS';
        }
        else
        {
            $actividad = $this->clienteAddSelectId;
            $des = strtoupper($this->clienteAddSelectName);
        }

       // dd($this->idenReceptor);

        $cliente = Clientes::create([
            'nombreCliente' => strtoupper($this->nombreCliente),
            'tipoPersona' => $tipo,
            'dui' => $this->dui,
            'nit' => $this->nit,
            'homologado' => $this->homologado,
            'registro' => $this->registro,
            'giro' => $des,
            'direccion' => strtoupper($this->direccion),
            'telefono' => $this->telefono,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $actividad,
            'idenReceptor' => empty($this->idenReceptor) ? 2 : $this->idenReceptor,
            'celular' => $this->celular,
            'email' => $this->correo,
            'desActividad' => $des
        ]);

        $this->resetForm();
        $this->emit('item-added', 'Cliente registrado');
    }


}
