<?php

namespace App\Http\Livewire;

use App\Models\ActividadEconomica;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Empresas;
use App\Models\Municipios;
use App\Models\AmbienteDestino;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;


class EmpresaController extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search, $selected_id, $pageTitle, $modalAction, $componentName, $empresa, $direccion, $telefono, $responsable, $registro, $giro, $nit, $tipoContribuyente, $image, $razon, $actividades, $actividadSelectId, $actividadSelectName, $actividad, $departamentos, $municipios, $distritos, $depto, $muni, $distrito, $correo, $apiPassword, $plan, $isSuper, $ambiente, $ambientes, $certificado;

    private $pagination = 10;

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Empresas Activas';

        $this->actividades = ActividadEconomica::orderBy('valor', 'asc')->get();
        $this->departamentos = Departamentos::all();
        $this->municipios = Municipios::all();
        $this->distritos = Distritos::all();
        $this->ambientes = AmbienteDestino::all();

        $user = Auth::user();
        $this->isSuper = $user->profile == 'Super';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
        {
            $empresas = Empresas::where('empresa', 'like', '%' . $this->search . '%')->paginate($this->pagination);
        }
        else
        {
            $empresas = Empresas::orderBy('empresa', 'asc')->paginate($this->pagination);
        }

        return view('livewire.empresa.empresa', ['empresas' => $empresas])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'empresa' => 'required|min:2|unique:empresas,empresa',
            'registro' => 'required|numeric',
            'nit' => 'required|numeric',
        ];

        $messages = [
            'empresa.required' => 'El nombre de la empresa es requerido',
            'empresa.unique' => 'La empresa ya existe',
            'empresa.min' => 'La empresa debe tener al menos dos caracteres',
            'registro.required' => 'El numero de registro es Requerido',
            'nit.required' => 'El numero de NIT es requerido',
            'registro.numeric' => 'El número de registro debe ser numérico',
            'nit.numeric' => 'El número de NIT debe ser numérico',
        ];
        $this->validate($rules, $messages);

        $empe = Empresas::create([
            'empresa' => $this->empresa,
            'razon' => $this->razon,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'responsable' => $this->responsable,
            'registro' => $this->registro,
            'giro' => $this->giro,
            'nit' => $this->nit,
            'tipoContribuyente' => $this->tipoContribuyente,
            'actividad' => $this->actividadSelectId,
            'desActividad' => $this->actividadSelectName,
            'correo' => $this->correo,
            'apiPassword' => $this->apiPassword,
            'departamento' => $this->depto,
            'municipio' => $this->muni,
            'distrito' => $this->distrito,
            'plan' => $this->plan,
            'ambiente' => $this->ambiente
        ]);

        if($this->image)
        {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/empresas', $customFileName);
            $empe->image = $customFileName;
            $empe->save();
        }

        if($this->certificado)
        {
            $customFileName =  $this->certificado->getClientOriginalName();
            $this->certificado->storeAs('public/empresas/certificados', $customFileName);
            $empe->certificado = $customFileName;
            $empe->save();
        }

        $this->emit('item-added', 'Empresa registrada con exito');
        $this->resetUI();
    }

    public function Edit(Empresas $empresa)
    {
        $this->selected_id = $empresa->id;
        $this->empresa = $empresa->empresa;
        $this->direccion = $empresa->direccion;
        $this->telefono = $empresa->telefono;
        $this->responsable = $empresa->responsable;
        $this->registro = $empresa->registro;
        $this->giro = $empresa->giro;
        $this->nit = $empresa->nit;
        $this->tipoContribuyente = $empresa->tipoContribuyente;
        $this->razon = $empresa->razon;
        $this->actividadSelectId = $empresa->actividad;
        $this->actividadSelectName = $empresa->desActividad;
        $this->correo = $empresa->correo;
        // No cargar la contraseña de API al frontend por seguridad
        $this->apiPassword = '';
        $this->depto = $empresa->departamento;
        $this->muni = $empresa->municipio;
        $this->distrito = $empresa->distrito;
        $this->plan = $empresa->plan;
        //$this->certificado = $empresa->certificado;
        $this->ambiente = $empresa->ambiente;

        $this->emit('show-modal','Editar Rol');
    }

    public function Update()
    {
        $rules = [
            'empresa' => 'required|min:2',
            'registro' => 'required|numeric',
            'nit' => 'required|numeric',
        ];

        $messages = [
            'empresa.required' => 'El nombre de la empresa es requerido',
            'empresa.min' => 'La empresa debe tener al menos dos caracteres',
            'registro.required' => 'El numero de registro es Requerido',
            'nit.required' => 'El numero de NIT es requerido',
            'registro.numeric' => 'El número de registro debe ser numérico',
            'nit.numeric' => 'El número de NIT debe ser numérico',
        ];
        $this->validate($rules, $messages);

        $empresas = Empresas::find($this->selected_id);
        $empresas->empresa = $this->empresa;
        $empresas->razon = $this->razon;
        $empresas->direccion = $this->direccion;
        $empresas->telefono = $this->telefono;
        $empresas->responsable = $this->responsable;
        $empresas->registro = $this->registro;
        $empresas->giro = $this->giro;
        $empresas->nit = $this->nit;
        $empresas->tipoContribuyente = $this->tipoContribuyente;
        $empresas->correo = $this->correo;
        if (!empty($this->apiPassword)) {
            $empresas->apiPassword = $this->apiPassword;
        }
        $empresas->departamento = $this->depto;
        $empresas->municipio = $this->muni;
        $empresas->distrito = $this->distrito;
        $empresas->actividad =$this->actividadSelectId;
        $empresas->desActividad = $this->actividadSelectName;
        $empresas->plan =$this->plan;
        $empresas->ambiente = $this->ambiente;
        $empresas->save();

        //dd($this->image);
        if($this->image)
        {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAS('public/empresas', $customFileName);
            $imagetemp = $empresas->image;
            $empresas->image = $customFileName;
            $empresas->save();

            if($imagetemp !=null)
            {
                if(file_exists('storage/empresas/' . $imagetemp)){
                    unlink('storage/empresas/' . $imagetemp);
                }
            }
        }

        if($this->certificado)
        {
            $customFileName = $this->certificado->getClientOriginalName();
            $this->certificado->storeAS('public/empresas/certificados', $customFileName);
            $certificadotemp = $empresas->certificado;
            $empresas->certificado = $customFileName;
            $empresas->save();

            if($certificadotemp !=null)
            {
                if(file_exists('storage/empresas/certificados/' . $certificadotemp)){
                    unlink('storage/empresas/certificados/' . $certificadotemp);
                }
            }
        }

        $this->emit('item-updated', 'Se actualizo los datos de la Empresa con exito');
        $this->resetUI();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Empresas $empresa)
    {

        $sucursalesCount = Empresas::find($empresa->id)->sucursales->count();

        if($sucursalesCount > 0)
        {
            $this->emit('item-error', 'No se puede eliminar la empresa porque tiene sucursales asociadas');
            return;
        }

        $empresa->delete();
        $this->emit('item-deleted', 'Se elimino la empresa con exito');
        $this->resetUI();
    }

    public function resetUI()
    {
        $this->empresa = '';
        $this->razon = '';
        $this->direccion = '';
        $this->telefono = '';
        $this->responsable = '';
        $this->registro = '';
        $this->giro = '';
        $this->nit = '';
        $this->tipoContribuyente = '';
        $this->image = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->depto = '';
        $this->muni = '';
        $this->distrito = '';
        $this->correo = '';
        $this->apiPassword = '';
        $this->actividadSelectId='';
        $this->actividadSelectName = '';
        $this->plan = '';
        $this->ambiente= '';
        $this->resetValidation();
    }

    public function getListeners()
    {
        return [
            'keydown.alt.s' => 'Store',
            'keydown.alt.s' => 'Update'
        ];
    }

    ///simulador del symlik o enlace simbolico para el storage/////
    public function renderImagen($filename)
    {
        $path = 'public/empresas/' . $filename;
        if (!Storage::exists($path)) {
            abort(404);
        }
        return response()->file(storage_path("app/{$path}"));
    }

    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->depto)->get();
    }

    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->muni)->get();
    }

    public function renderCertificado($filename)
    {
        // Ruta al archivo en el almacenamiento
        $path = 'public/empresas/certificados/' . $filename;

        // Verificar si el archivo existe
        if (!Storage::exists($path)) {
            abort(404, 'Certificado no encontrado.');
        }

        // Opcional: Verificar si el usuario tiene permiso para acceder al certificado
        //$user = Auth::user();
        //$empresa = $user->empresa;

        //if (!$empresa || $empresa->certificado !== $filename) {
            //abort(403, 'No tienes acceso a este certificado.');
        //}

        // Devolver el archivo de forma segura
        return response()->file(storage_path("app/{$path}"));
    }
    ////probando
}
