<?php

namespace App\Http\Livewire;

use App\Models\Empresas;
use App\Models\TomaOrden;
use App\Models\TomaOrdenDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TomaOrdenController extends Component
{
    use WithPagination;

    public  $search, $selected_id, $pageTitle, $componentName, $detalleToma = [];
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Toma de Orden';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TomaOrden::join('proveedores as p', 'p.id', 'toma_ordens.proveedor')
            ->join('tipo_compras as tc', 'tc.id', 'toma_ordens.tipo')
            ->select('toma_ordens.*', 'p.nombre', 'tc.tipo as factura')
            ->where('p.nombre', 'like', '%' . $this->search . '%')
            ->orWhere('tc.tipo', 'like', '%' . $this->search . '%')
            ->orWhere('toma_ordens.fecha', 'like', '%' . $this->search . '%')
            ->orWhere('toma_ordens.estado', 'like', '%' . $this->search . '%')
            ->orderBy('toma_ordens.id', 'desc')
            ->paginate($this->pagination);

        else
            $data = TomaOrden::join('proveedores as p', 'p.id', 'toma_ordens.proveedor')->join('tipo_compras as tc', 'tc.id', 'toma_ordens.tipo')->select('toma_ordens.*', 'p.nombre', 'tc.tipo as factura')->orderBy('toma_ordens.id', 'desc')->paginate($this->pagination);

        return view('livewire.toma_ordens.tomas', ['tomas' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'anulaRow' => 'anular'
    ];

    public function Destroy(TomaOrden $toma)
    {
        $toma->delete();
        $this->emit('item-deleted', 'Producto Eliminado');
    }

    public function cargarDetallesToma($tomaId)
    {
        $this->detalleToma = TomaOrdenDetalle::join('productos as p', 'p.id', 'toma_orden_detalles.producto')
            ->join('medidas as m', 'm.id', 'toma_orden_detalles.medida')
            ->join('toma_ordens', 'toma_ordens.id', 'toma_orden_detalles.tomaOrden')
            ->select(
                'toma_orden_detalles.id',
                'toma_orden_detalles.cantidad',
                'toma_orden_detalles.costo',
                'toma_orden_detalles.total',
                'p.nombreProducto as producto',
                'm.unidad as medida',
                'toma_ordens.estado as estado_toma'
            )
            ->where('toma_orden_detalles.tomaOrden', $tomaId)
            ->where('toma_orden_detalles.cantidad', '>', 0) 
            ->get();

        $this->emit('toma-modal', 'show modal');
    }

    public function anular($id){

        $toma = TomaOrden::find($id);

        $toma->estado = 'Anulado';
        $toma->save();

        $this->emit('item-updated', 'Toma de orden Anulada');
    }

    public function generarPdf($id)
{
    $user = Auth::user();
    $empresa = Empresas::find($user->empresa);
    $imagenUrl = asset('logo/' . $empresa->image);
    
    $tomas = TomaOrden::findOrFail($id);
    // Filtrar solo los registros con cantidad mayor a 0
    $tomasDetalle = TomaOrdenDetalle::where('tomaOrden', $id)
                                    ->where('cantidad', '>', 0)
                                    ->get();

    $pdf = Pdf::loadView('pdf.pdfTomas', compact('tomas', 'tomasDetalle', 'imagenUrl', 'empresa'));

    return $pdf->stream();
}

}
