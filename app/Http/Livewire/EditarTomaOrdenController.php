<?php

namespace App\Http\Livewire;

use App\Models\ActividadEconomica;
use App\Models\Compras;
use App\Models\ComprasDetalles;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Municipios;
use App\Models\Proveedores;
use App\Models\Sucursales;
use App\Models\tipoCompras;
use App\Models\TipoPersona;
use App\Models\TomaOrden;
use App\Models\TomaOrdenDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class EditarTomaOrdenController extends Component
{
    public $total, $itemsQuantity, $fechaV = [], $can = [], $pri = [], $uni = [], $sucursales = [];

    // para guardar la compra
    public $proveedorSelectId, $proveedorSelectName, $proveedorToma, $factura, $fecha, $condiPago, $proveedores, $tipos, $select_id, $car, $personas, $actividades, $departamentos, $municipios, $distritos, $sucursal;

    public function mount($id)
    {
        $toma = TomaOrden::find($id);
        $this->proveedores = Proveedores::all();
        $this->proveedorSelectId = $this->proveedores->first()->id;
        $this->proveedorSelectName = $this->proveedores->first()->name;
        $this->sucursales = Sucursales::all();
        $this->tipos =tipoCompras::all();
        $this->personas = TipoPersona::all();
        $this->actividades = ActividadEconomica::all();
        $this->departamentos = Departamentos::all();
        $this->municipios = Municipios::all();
        $this->distritos = Distritos::all();
        $this->car = TomaOrdenDetalle::where('tomaOrden', $id)->join('productos as p', 'p.id', 'toma_orden_detalles.producto')->join('medidas as m', 'm.id', 'toma_orden_detalles.medida')->select('toma_orden_detalles.id', 'p.nombreProducto', 'p.id as producto', 'toma_orden_detalles.cantidad', 'toma_orden_detalles.costo', 'toma_orden_detalles.total', 'toma_orden_detalles.fechaVencimiento', 'm.unidad')->get();

        $this->total = TomaOrdenDetalle::where('tomaOrden', $id)->sum('total');
        $this->itemsQuantity = TomaOrdenDetalle::where('tomaOrden', $id)->sum('cantidad');

        $this->fecha = $toma->fecha;
        $this->condiPago = $toma->condi_pago;
        $this->factura = $toma->tipo;
        $this->select_id =$id;
        $this->proveedorToma = $toma->proveedor;
        $this->sucursal = $toma->sucursal;
    }

    public function render()
    {
        return view('livewire.toma_ordens.editar-toma')
        ->extends('layouts.theme.app')
        ->section('content');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'deleteRoww' => 'DestroyProd'
    ];

    public function DestroyProd(TomaOrdenDetalle $detalle)
    {
        $detalle->delete();
        $this->emit('item-deleted', 'Producto Eliminado');
    }

    public function resetUI()
    {
        $this->factura = '';
        $this->fecha = '';
        $this->condiPago = '';
        $this->proveedorToma = '';
        $this->select_id = 0;

    }
    public function Update($id)
    {
        $up = TomaOrden::find($id);

        $up->tipo = $this->factura;
        $up->fecha = $this->fecha;
        $up->condi_pago = $this->condiPago;
        $up->proveedor = $this->proveedorToma;
        $up->save();
        $this->emit('item-updated', 'Datos de la toma Actualizada');
        $this->ResetUI();
        return Redirect::to('/toma_ordens');
    }
}
