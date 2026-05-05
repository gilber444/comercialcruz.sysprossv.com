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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class EditarCompraController extends Component
{
    public $total, $itemsQuantity, $fechaV = [], $can = [], $pri = [], $uni = [], $sucursales = [];

    // para guardar la compra
    public $proveedorSelectId, $proveedorSelectName, $proveedorCompras, $factura, $correlativo, $serie, $fecha, $condiPago, $vendedor, $proveedores, $tipos, $select_id, $car, $personas, $actividades, $departamentos, $municipios, $distritos, $sucursal;

    public function mount($id)
    {
        $compras = Compras::find($id);
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
        $this->car = ComprasDetalles::where('compra', $id)->join('productos as p', 'p.id', 'compras_detalles.producto')->join('medidas as m', 'm.id', 'compras_detalles.medida')->select('compras_detalles.id', 'p.nombreProducto', 'p.id as producto', 'compras_detalles.cantidad', 'compras_detalles.costo', 'compras_detalles.total', 'compras_detalles.fechaVencimiento', 'm.unidad')->get();

        $this->total = ComprasDetalles::where('compra', $id)->sum('total');
        $this->itemsQuantity = ComprasDetalles::where('compra', $id)->sum('cantidad');

        $this->fecha = $compras->fecha;
        $this->correlativo = $compras->correlativo;
        $this->serie = $compras->serie;
        $this->condiPago = $compras->condi_pago;
        $this->vendedor = $compras->vendedor;
        $this->factura = $compras->tipo;
        $this->select_id =$id;
        $this->proveedorCompras = $compras->proveedor;
        $this->sucursal = $compras->sucursal;
    }

    public function render()
    {
        return view('livewire.compras.editar-compra')
        ->extends('layouts.theme.app')
        ->section('content');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'deleteRoww' => 'DestroyProd'
    ];

    public function DestroyProd(ComprasDetalles $detalle)
    {
        $exis = Inventarios::where('producto', $detalle->producto)->where('sucursal', 2)->first();

        $nuevo = $exis->existencia - $detalle->ingreso;

        $exis->existencia = $nuevo;
        $exis->save();

        $kar = Kardex::where('producto', $detalle->producto)->where('inventario', $exis->id)->latest('id')->first();
        //dd($kar->descripcion);
        $kard = Kardex::create([
            'producto' => $detalle->producto,
            'inventario' => $exis->id,
            'descripcion' => 'Eliminacion de la ' . $kar->descripcion,
            'fecha' => date('Y-m-d') ,
            'hora' => date('H:i:s'),
            'ingresoCantidad' => 0.00,
            'ingresoValor' => 0.00,
            'egresoCantidad' => $detalle->ingreso,
            'egresoValor' => $detalle->total,
            'saldoCantidad' => $nuevo,
            'saldoValor' => $kar->saldoValor-$detalle->total
        ]);

        //dd($kar);
        $detalle->delete();
        $this->emit('item-deleted', 'Producto Eliminado');
    }

    public function resetUI()
    {
        $this->factura = '';
        $this->correlativo = '';
        $this->serie = '';
        $this->fecha = '';
        $this->condiPago = '';
        $this->vendedor = '';
        $this->proveedorCompras = '';
        $this->select_id = 0;

    }
    public function Update($id)
    {
        $up = Compras::find($id);

        $up->tipo = $this->factura;
        $up->correlativo = $this->correlativo;
        $up->serie = $this->serie;
        $up->fecha = $this->fecha;
        $up->condi_pago = $this->condiPago;
        $up->vendedor = $this->vendedor;
        $up->proveedor = $this->proveedorCompras;
        $up->save();
        $this->emit('item-updated', 'Datos de la Compra Actualizada');
        $this->ResetUI();
        return Redirect::to('/compras');
    }
}
