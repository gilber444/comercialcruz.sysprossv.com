<?php

namespace App\Http\Livewire;

use App\Models\Cotizaciones;
use App\Models\CotizacionesDetalle;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CotizacionesController extends Component
{
    use WithPagination;

    public $detalleCotizacion = [], $productos, $sucursales, $producto, $cantidad, $detalle, $estado, $tipo, $search, $selected_id, $pageTitle, $componentName, $detalleCotizaciones = [];

    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Cotizaciones';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = Cotizaciones::join('sucursales as s', 's.id', 'ajustes.sucursal')
            //->select('ajustes.*', 's.nombre as sucursal')
            ///->orwhere('s.sucursal', 'like', '%'.$this->search.'%')
            //->orwhere('tipo', 'like', '%'.$this->search.'%')
            ->paginate($this->pagination);
        else
        $data = Cotizaciones::with('Rcliente:id,nombreCliente')
        ->select(
            'cotizaciones.id',
            'cotizaciones.correlativo',
            'cotizaciones.fecha',
            'cotizaciones.estado',
            'cotizaciones.cliente',
            'cotizaciones.estado',
            DB::raw('(SELECT SUM(cantidad) FROM cotizaciones_detalles WHERE cotizacion = cotizaciones.id) as total_cantidad'),
            'total'
        )
        ->paginate($this->pagination);

        return view('livewire.cotizaciones.cotizaciones', ['cotizaciones' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'UpdateRow' => 'Update'
    ];

    public  function cargarDetalles($id)
    {
        $this->detalleCotizaciones = CotizacionesDetalle::join('productos as p', 'p.id', 'cotizaciones_detalles.producto')
            ->join('medidas as m', 'm.id', 'cotizaciones_detalles.medida')
            ->join('cotizaciones', 'cotizaciones.id', 'cotizaciones_detalles.cotizacion')
            ->select(
                'cotizaciones_detalles.id',
                'cotizaciones_detalles.cantidad',
                'p.nombreProducto as producto',
                'cotizaciones_detalles.precio',
                'cotizaciones_detalles.total',
                'm.unidad as medida')
            ->where('cotizaciones_detalles.cotizacion', $id)
            ->get();

        $this->emit('ajuste-modal', 'show modal');
    }
}
