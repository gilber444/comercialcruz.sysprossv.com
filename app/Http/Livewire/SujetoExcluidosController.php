<?php


namespace App\Http\Livewire;

use App\Models\Clientes;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\SujetoExcluido;
use App\Models\SujetoExcluidosDetalle;
use App\Traits\GeneraJsonS;
use App\Traits\RecepcionDTESujeto;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SujetoExcluidosController extends Component
{ 
    use WithPagination;
    use GeneraJsonS;
    use RecepcionDTESujeto;

    public $detalleExcluidos = [], $empresas, $sucursales, $clientes, $empresa, $sucursal, $cliente,
        $search, $selected_id, $pageTitle, $componentName, $dateFrom, $dateTo, $form;

    private $pagination = 12;

    protected $listeners = [
        'deleteRow' => 'destroy',
        'deleteDetalle' => 'destroyDetalle',
        'anularRow' => 'anular'
    ];

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Sujetos Excluidos';
        $this->empresas = Empresas::all();
        $this->sucursales = Sucursales::all();
        $this->clientes = Clientes::all();
        $this->empresa = 0;
        $this->sucursal = 0;
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-d');
        $this->resetForm();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $query = SujetoExcluido::query()
            ->join('empresas as e', 'e.id', 'sujeto_excluidos.empresa')
            ->join('sucursales as s', 's.id', 'sujeto_excluidos.sucursal')
            ->join('clientes as c', 'c.id', 'sujeto_excluidos.cliente')
            ->select('sujeto_excluidos.*', 'e.empresa as empresa_nombre', 's.nombre as sucursal_nombre', 'c.nombreCliente as cliente_nombre');

       /* if ($this->empresa && $this->empresa != 0) {
            $query->where('sujeto_excluidos.empresa', $this->empresa);
        }
        if ($this->sucursal && $this->sucursal != 0) {
            $query->where('sujeto_excluidos.sucursal', $this->sucursal);
        }   
        if ($this->dateFrom) {
            $query->whereDate('sujeto_excluidos.fecha_hora_generacion', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('sujeto_excluidos.fecha_hora_generacion', '<=', $this->dateTo);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('emisor_nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('receptor_nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_control', 'like', '%' . $this->search . '%');
            });
        }*/

        $data = $query->orderBy('sujeto_excluidos.id', 'desc')->paginate($this->pagination);
        //dd($data);
        return view('livewire.sujeto.sujeto-excluidos', [
            'sujetos' => $data,
            'empresas' => $this->empresas,
            'sucursales' => $this->sucursales,
            'clientes' => $this->clientes
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    // Eliminar registro principal
    public function destroy($id)
    {
        $sujeto = SujetoExcluido::find($id);
        if ($sujeto) {
            $sujeto->update(['status' => 'Eliminado']);
            $sujeto->delete();
            $this->emit('item-deleted', 'Sujeto Excluido Eliminado');
        }
    }

    // Eliminar detalle
    public function destroyDetalle($detalleId)
    {
        $detalle = SujetoExcluidosDetalle::find($detalleId);
        if ($detalle) {
            $detalle->delete();
            $this->emit('item-deleted', 'Detalle eliminado');
        }
    }

    // Anular registro (cambiar estado a "Anulado" y eliminar detalles)
    public function anular($id)
    {
        $sujeto = SujetoExcluido::find($id);
        if ($sujeto) {
            $sujeto->update(['status' => 'Anulado']);
            $detalles = SujetoExcluidosDetalle::where('sujeto_excluido_id', $sujeto->id)->get();
            foreach ($detalles as $det) {
                $det->delete();
            }
            $this->emit('item-updated', 'Sujeto Excluido anulado y detalles eliminados');
        }
    }

    // Cargar detalles con joins para mostrar información legible
    public function cargarDetallesExcluidos($sujetoId)
    {
        $this->detalleExcluidos = SujetoExcluidosDetalle::join('sujeto_excluidos as s', 's.id', 'sujeto_excluidos_detalles.sujeto_excluido_id')
            ->select(
                'sujeto_excluidos_detalles.*',
                's.emisor_nombre as emisor',
                's.receptor_nombre as receptor'
            )
            ->where('sujeto_excluidos_detalles.sujeto_excluido_id', $sujetoId)
            ->get();

        $this->emit('excluido-modal', 'show modal');
    }

    // Limpiar formulario
    public function resetForm()
    {
        $this->form = [
            'empresa' => null,
            'sucursal' => null,
            'user' => Auth::id(),
            'codigo_generacion' => null,
            'producto' => null,
            'numero_control' => null,
            'sello_recepcion' => null,
            'modelo_facturacion' => null,
            'tipo_transmision' => null,
            'fecha_hora_generacion' => null,
            'cliente' => null,
            'emisor_nombre' => null,
            'emisor_nit' => null,
            'emisor_actividad_economica' => null,
            'emisor_direccion' => null,
            'emisor_telefono' => null,
            'emisor_correo' => null,
            'emisor_nombre_comercial' => null,
            'emisor_tipo_establecimiento' => null,
            'receptor_nombre' => null,
            'receptor_tipo_doc' => null,
            'receptor_num_doc' => null,
            'receptor_telefono' => null,
            'receptor_direccion' => null,
            'sumatoria_ventas' => null,
            'sub_total' => null,
            'total_pagar' => null,
            'valor_letras' => null,
            'condicion_operacion' => null,
            'observaciones' => null,
            'status' => 'Pendiente',
        ];
        $this->selected_id = null;
    }

    public function Generar($id)
    {
        $sujeto = SujetoExcluido::find($id);
        if ($sujeto) {
            
            
            $json = $this->GeneraJsonS($sujeto->id);

            $dte = dte::where('venta', $sujeto->id)
                ->where('tipoDte', 10) // Asegura que es tipo 14
                ->first();
                
            $dte->jsonDte = $json;
            $dte->save();

            $this->RecepcionDTESujeto($sujeto->id);



            $this->emit('item-added', 'Proceso de generación iniciado para el Sujeto Excluido');
        } else {
            $this->emit('item-error', 'Sujeto Excluido no encontrado');
        }
    }
}