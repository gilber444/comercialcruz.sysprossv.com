<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Compras;
use App\Models\ComprasDetalles;
use App\Models\Inventarios;
use App\Models\Kardex;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class ComprasController extends Component
{
    use WithPagination;

    public  $search, $selected_id, $pageTitle, $componentName, $detalleCompras = [];
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Compras';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();

        $query = Compras::join('proveedores as p', 'p.id', '=', 'compras.proveedor')
            ->join('tipo_compras as tc', 'tc.id', '=', 'compras.tipo')
            ->select('compras.*', 'p.nombre', 'tc.tipo as factura');

        // Filtrar por sucursal solo si no es Super o Administrador
        if (!in_array($user->profile, ['Super', 'Administrador'])) {
            $query->where('compras.sucursal', $user->sucursal);
        }

        // Aplicar filtros de búsqueda si hay texto en $this->search
        if (strlen($this->search) > 0) {
            $search = '%' . $this->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('p.nombre', 'like', $search)
                ->orWhere('tc.tipo', 'like', $search)
                ->orWhere('compras.fecha', 'like', $search)
                ->orWhere('compras.estado', 'like', $search);
            });
        }

        // Ordenar y paginar resultados
        $data = $query->orderBy('compras.fecha', 'desc')
                    ->paginate($this->pagination);

        return view('livewire.compras.compras', ['compras' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'anulaRow' => 'anular'
    ];

    public function Destroy(Compras $compra)
    {
        $compra->delete();
        $this->emit('item-deleted', 'Producto Eliminado');
    }

    public function cargarDetallesCompra($compraId)
    {
        $this->detalleCompras = ComprasDetalles::join('productos as p', 'p.id', 'compras_detalles.producto')
            ->join('medidas as m', 'm.id', 'compras_detalles.medida')
            ->join('compras', 'compras.id', 'compras_detalles.compra')
            ->select(
                'compras_detalles.id',
                'compras_detalles.cantidad',
                'compras_detalles.costo',
                'compras_detalles.total',
                'p.nombreProducto as producto',
                'm.unidad as medida',
                'compras.estado as estado_compras'
            )
            ->where('compras_detalles.compra', $compraId)
            ->get();

        $this->emit('detalle-modal', 'show modal');
    }

    public function anular($id){

        $compra = Compras::find($id);

        $detalle = ComprasDetalles::where('compra', $compra->id)->get();

        foreach ($detalle as $det) {
            $inventario = Inventarios::where('producto', $det->producto)->where('sucursal', $compra->sucursal)->first();

            $newExist = $inventario->existencia - $det->ingreso;

            $inventario->existencia = $newExist;
            $inventario->save();

            $ultiR = Kardex::where('producto', $det->producto)->where('inventario', $inventario->id)->latest()->first();

            $saldoCantidad = $newExist;
            $saldoValor = $ultiR->saldoValor + ($det->costo * $det->ingreso);
            Kardex::create([
                'producto' => $det->producto,
                'inventario' => $inventario->id,
                'descripcion' => 'Anulacion de la compra ' . $compra->correlativo,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'ingresoCantidad' => 0.00,
                'ingresoValor' => 0.00,
                'egresoCantidad' => $det->cantidad,
                'egresoValor' => $det->costo * $det->cantidad,
                'saldoCantidad' => $saldoCantidad,
                'saldoValor' => $saldoValor,
            ]);
        }

        $compra->estado = 'Anulado';
        $compra->save();

        $this->emit('item-updated', 'Compra Anulada');
    }
}
