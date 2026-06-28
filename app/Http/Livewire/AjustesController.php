<?php

namespace App\Http\Livewire;

use App\Models\Ajustes;
use App\Models\AjustesDetalles;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\User;
use App\Traits\GenerarJsonAjuste;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AjustesController extends Component
{
    use WithPagination;
    use GenerarJsonAjuste;

    public $detalleAjustes = [], $productos, $sucursales, $producto, $sucursalOrigen,
    $cantidad, $detalle, $estado, $tipo, $search, $selected_id, $pageTitle, $componentName, $UsuarioReversor;

    public $filterStatus = '';
    public $filterTipo = '';
    public $filterSucursal = '';
    public $fechaDesde;
    public $fechaHasta;
    public $perPage = 20;

    private $pagination = 20;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Ajustes';
        $this->UsuarioReversor = User::find(Auth::user()->id);
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->endOfMonth()->toDateString();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();

        $query = Ajustes::join('sucursales as s', 's.id', 'ajustes.sucursal')
            ->select('ajustes.*', 's.nombre as sucursal_nombre')
            ->orderBy('ajustes.id', 'desc');

        if ($user->profile === 'Cajeros') {
            $query->where('ajustes.sucursal', $user->sucursal);
        }

        if ($this->fechaDesde && $this->fechaHasta) {
            $query->whereBetween('ajustes.fecha', [$this->fechaDesde, $this->fechaHasta]);
        } elseif ($this->fechaDesde) {
            $query->where('ajustes.fecha', '>=', $this->fechaDesde);
        } elseif ($this->fechaHasta) {
            $query->where('ajustes.fecha', '<=', $this->fechaHasta);
        }

        if ($this->filterStatus) {
            $query->where('ajustes.status', $this->filterStatus);
        }

        if ($this->filterTipo) {
            $query->where('ajustes.tipo', $this->filterTipo);
        }

        if ($this->filterSucursal) {
            $query->where('ajustes.sucursal', $this->filterSucursal);
        }

        if ($this->search) {
            $search = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('s.nombre', 'like', $search)
                    ->orWhere('ajustes.detalle', 'like', $search)
                    ->orWhere('ajustes.id', 'like', $search);
            });
        }

        $totalRegistros = $query->count();
        $data = $query->paginate($this->perPage);
        $sucursalesList = $user->profile === 'Cajeros'
            ? Sucursales::where('id', $user->sucursal)->get()
            : Sucursales::orderBy('nombre')->get();

        return view('livewire.ajustes.ajustes', [
            'ajustes' => $data,
            'totalRegistros' => $totalRegistros,
            'sucursalesList' => $sucursalesList,
        ])->extends('layouts.theme.app')->section('content');
    }

    public function setRango(string $rango): void
    {
        switch ($rango) {
            case 'hoy':
                $this->fechaDesde = now()->toDateString();
                $this->fechaHasta = now()->toDateString();
                break;
            case 'semana':
                $this->fechaDesde = now()->startOfWeek()->toDateString();
                $this->fechaHasta = now()->endOfWeek()->toDateString();
                break;
            case 'mes':
                $this->fechaDesde = now()->startOfMonth()->toDateString();
                $this->fechaHasta = now()->endOfMonth()->toDateString();
                break;
            case 'anio':
                $this->fechaDesde = now()->startOfYear()->toDateString();
                $this->fechaHasta = now()->endOfYear()->toDateString();
                break;
            case 'todo':
                $this->fechaDesde = '';
                $this->fechaHasta = '';
                break;
        }

        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterTipo = '';
        $this->filterSucursal = '';
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->endOfMonth()->toDateString();
        $this->perPage = 20;
        $this->resetPage();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'UpdateRow' => 'Update',
        'aplicarConCredenciales' => 'aplicarConCredenciales'
    ];

    public function aplicarConCredenciales($id, $usuario, $password)
    {
        if (!Auth::attempt(['user' => $usuario, 'password' => $password])) {
            $this->dispatchBrowserEvent('swal-error', [
                'message' => 'Credenciales incorrectas'
            ]);
            return;
        }

        $this->Update($id);
    }

    public function Update($id){

        $ajuste = Ajustes::where('id', $id)->latest()->first();

        $ajuste->update([
            'status' => 'Realizado'
        ]);

        $this->emit('item-updated', 'Ajuste Actualizado');
    }

    public function Destroy($id)
    {
        $ajuste = Ajustes::where('id', $id)->latest()->first();

        $ajuste->update([
            'status' => 'Eliminado'
        ]);

        $ajuste->delete();

        $this->emit('item-deleted', 'Ajuste Eliminado');
    }

    public function DestroyP($detalleId)
    {
        $detalle = AjustesDetalles::where('id', $detalleId)->get();

        foreach ($detalle as $det) {
            $ajuste = Ajustes::find($det->ajuste);

            if($ajuste->tipo == 'Ingreso'){

                $inventario = Inventarios::find($det->inventario);

                $newExist = $inventario->stock - $det->ingreso;

                $inventario->update([
                    'stock' => $newExist
                ]);

                $ultiR = Kardex::where('producto', $det->producto)->where('inventario', $det->inventario)->latest()->first();

                $saldoCantidad = $newExist;
                $saldoValor = $ultiR->saldoValor + ($det->costo * $det->cantidad);
                Kardex::create([
                    'producto' => $det->producto,
                    'inventario' => $det->inventario,
                    'descripcion' => 'Eliminacion del producto por ingreso ' . $det->id,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'ingresoCantidad' => 0.00,
                    'ingresoValor' => 0.00,
                    'egresoCantidad' => $det->cantidad,
                    'egresoValor' => $det->costo * $det->cantidad,
                    'saldoCantidad' => $saldoCantidad,
                    'saldoValor' => $saldoValor,
                    'user' => Auth::user()->id
                ]);
            } else {
                $inventario = Inventarios::find($det->inventario);

                $newExist = $inventario->stock + $det->ingreso;

                $inventario->update([
                    'stock' => $newExist
                ]);

                $ultiR = Kardex::where('producto', $det->producto)->where('inventario', $det->inventario)->latest()->first();

                $saldoCantidad = $newExist;
                $saldoValor = $ultiR->saldoValor + ($det->costo * $det->cantidad);
                Kardex::create([
                    'producto' => $det->producto,
                    'inventario' => $det->inventario,
                    'descripcion' => 'Eliminacion del producto por Egreso ' . $det->id,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'ingresoCantidad' => $det->cantidad,
                    'ingresoValor' => $det->costo * $det->cantidad,
                    'egresoCantidad' => 0.00,
                    'egresoValor' => 0.00,
                    'saldoCantidad' => $saldoCantidad,
                    'saldoValor' => $saldoValor,
                    'user' => Auth::user()->id
                ]);
            }
            $det->delete();
        }
        $this->emit('item-deleted', 'Productos eliminados');
    }

    public function anular(Ajustes $ajuste){

        $ajuste->update([
            'status' => 'Anulado'
        ]);

        $detalle = AjustesDetalles::where('ajuste', $ajuste->id)->get();

        foreach ($detalle as $det) {

            if($ajuste->tipo == 'Ingreso'){

                $inventario = Inventarios::find($det->inventario);

                $newExist = $inventario->stock - $det->ingreso;

                $inventario->update([
                    'stock' => $newExist
                ]);

                $ultiR = Kardex::where('producto', $det->producto)->where('inventario', $det->inventario)->latest()->first();

                $saldoCantidad = $newExist;
                $saldoValor = $ultiR->saldoValor + ($det->costo * $det->ingreso);
                Kardex::create([
                    'producto' => $det->producto,
                    'inventario' => $det->inventario,
                    'descripcion' => 'Anulacion del Ingreso por Ajuste ' . $ajuste->id,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'ingresoCantidad' => 0.00,
                    'ingresoValor' => 0.00,
                    'egresoCantidad' => $det->cantidad,
                    'egresoValor' => $det->costo * $det->cantidad,
                    'saldoCantidad' => $saldoCantidad,
                    'saldoValor' => $saldoValor,
                    'user' => Auth::user()->id
                ]);
            } else {
                $inventario = Inventarios::find($det->inventario);

                $newExist = $inventario->stock + $det->ingreso;

                $inventario->update([
                    'stock' => $newExist
                ]);

                $ultiR = Kardex::where('producto', $det->producto)->where('inventario', $det->inventario)->latest()->first();

                $saldoCantidad = $newExist;
                $saldoValor = $ultiR->saldoValor + ($det->costo * $det->cantidad);
                Kardex::create([
                    'producto' => $det->producto,
                    'inventario' => $det->inventario,
                    'descripcion' => 'Anulacion del Egreso por Ajuste ' . $ajuste->id,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'ingresoCantidad' => $det->cantidad,
                    'ingresoValor' => $det->costo * $det->cantidad,
                    'egresoCantidad' => 0.00,
                    'egresoValor' => 0.00,
                    'saldoCantidad' => $saldoCantidad,
                    'saldoValor' => $saldoValor,
                    'user' => Auth::user()->id
                ]);
            }
        }

        $this->emit('item-updated', 'Ajuste Anulado');
    }

    public function cargarDetallesAjustes($ajusteId)
    {
        $this->detalleAjustes = AjustesDetalles::join('productos as p', 'p.id', 'ajustes_detalles.producto')
            ->join('medidas as m', 'm.id', 'ajustes_detalles.medida')
            ->join('ajustes', 'ajustes.id', 'ajustes_detalles.ajuste')
            ->select(
                'ajustes_detalles.id',
                'ajustes_detalles.cantidad',
                'ajustes_detalles.total',
                'p.nombreProducto as producto',
                'm.unidad as medida',
                'ajustes.status as status_ajustes')
            ->where('ajustes_detalles.ajuste', $ajusteId)
            ->get();

        $this->emit('ajuste-modal', 'show modal');
    }

    public function Print($id)
    {
        $this->emit('print-ticket2', $this->GenerarJsonAjuste($id));
    }
}
