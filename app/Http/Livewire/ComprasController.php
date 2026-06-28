<?php
namespace App\Http\Livewire;
use App\Helpers\SistemaHelper;
use App\Models\Compras;
use App\Models\ComprasDetalles;
use App\Models\CuentasPagar;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Pagos;
use App\Models\Sucursales;
use App\Models\tipoCompras;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
class ComprasController extends Component
{
    use WithPagination;
    public $search = '', $selected_id, $pageTitle, $componentName, $detalleCompras = [];
    public $filterEstado = '', $filterCondiPago = '', $filterTipo = '', $fechaDesde, $fechaHasta;
    public $filterSucursal = 0, $perPage = 20;
    protected $listeners = [
        'deleteRow' => 'Destroy',
        'anulaRow' => 'anular',
    ];
    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Compras';
        $this->fechaDesde = now()->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->filterSucursal = $this->puedeVerTodasSucursales() ? 0 : (Auth::user()->sucursal ?? 0);
    }
    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterEstado() { $this->resetPage(); }
    public function updatingFilterCondiPago() { $this->resetPage(); }
    public function updatingFilterTipo() { $this->resetPage(); }
    public function updatingFechaDesde() { $this->resetPage(); }
    public function updatingFechaHasta() { $this->resetPage(); }
    public function updatingFilterSucursal() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function limpiarFiltros()
    {
        $this->search = '';
        $this->filterEstado = '';
        $this->filterCondiPago = '';
        $this->filterTipo = '';
        $this->filterSucursal = $this->puedeVerTodasSucursales() ? 0 : (Auth::user()->sucursal ?? 0);
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->endOfMonth()->toDateString();
        $this->perPage = 20;
        $this->resetPage();
    }
    public function setRango(string $rango)
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
    public function render()
    {
        $user = Auth::user();
        $puedeVerTodasSucursales = $this->puedeVerTodasSucursales();
        $tieneGeneracion = $this->tieneColumna('compras', 'generacion');
        $query = Compras::query()
            ->with([
                'Rproveedores:id,nombre',
                'RtipoCompra:id,tipo',
                'Rsucursal:id,nombre',
                'Rusers:id,name',
            ])
            ->select('compras.*')
            ->selectSub(function ($q) {
                $q->from('compras_detalles')
                    ->selectRaw('COALESCE(SUM(cantidad), 0)')
                    ->whereColumn('compras_detalles.compra', 'compras.id');
            }, 'total_productos')
            ->selectSub(function ($q) {
                $q->from('compras_detalles')
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn('compras_detalles.compra', 'compras.id');
            }, 'total_detalle');
        if ($this->fechaDesde && $this->fechaHasta) {
            $query->whereDate('compras.fecha', '>=', $this->fechaDesde)
                ->whereDate('compras.fecha', '<=', $this->fechaHasta);
        } elseif ($this->fechaDesde) {
            $query->whereDate('compras.fecha', '>=', $this->fechaDesde);
        } elseif ($this->fechaHasta) {
            $query->whereDate('compras.fecha', '<=', $this->fechaHasta);
        }
        if ($this->filterEstado) {
            $query->where('compras.estado', $this->filterEstado);
        }
        if ($this->filterCondiPago) {
            $query->where('compras.condi_pago', $this->filterCondiPago);
        }
        if ($this->filterTipo) {
            $query->where('compras.tipo', $this->filterTipo);
        }
        if (!empty($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search, $tieneGeneracion) {
                $q->whereHas('Rproveedores', fn ($proveedor) => $proveedor->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('RtipoCompra', fn ($tipo) => $tipo->where('tipo', 'like', "%{$search}%"))
                    ->orWhereHas('Rsucursal', fn ($sucursal) => $sucursal->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('Rusers', fn ($usuario) => $usuario->where('name', 'like', "%{$search}%"))
                    ->orWhere('compras.fecha', 'like', "%{$search}%")
                    ->orWhere('compras.estado', 'like', "%{$search}%")
                    ->orWhere('compras.condi_pago', 'like', "%{$search}%")
                    ->orWhere('compras.correlativo', 'like', "%{$search}%");
                if ($tieneGeneracion) {
                    $q->orWhere('compras.generacion', 'like', "%{$search}%");
                }
            });
        }
        if ((int) $this->filterSucursal > 0) {
            $query->where('compras.sucursal', (int) $this->filterSucursal);
        } elseif (!$puedeVerTodasSucursales && !empty($user->sucursal)) {
            $query->where('compras.sucursal', $user->sucursal);
        }
        $estados = Compras::query()
            ->select('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado')
            ->filter();
        $condiciones = Compras::query()
            ->select('condi_pago')
            ->distinct()
            ->orderBy('condi_pago')
            ->pluck('condi_pago')
            ->filter()
            ->merge(['Contado', 'Credito'])
            ->unique()
            ->values();
        $tiposCompra = tipoCompras::orderBy('tipo')->get(['id', 'tipo']);
        $sucursales = Sucursales::query()
            ->when(!$puedeVerTodasSucursales && !empty($user->sucursal), fn ($q) => $q->where('id', $user->sucursal))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
        $totalRegistros = (clone $query)->count();
        $compras = $query->orderByDesc('compras.fecha')->paginate((int) $this->perPage);
        return view('livewire.compras.compras', compact(
            'compras',
            'estados',
            'condiciones',
            'tiposCompra',
            'sucursales',
            'totalRegistros',
            'puedeVerTodasSucursales',
            'tieneGeneracion'
        ))
            ->extends('layouts.theme.app')
            ->section('content');
    }
    public function Destroy(Compras $compra)
    {
        $compra->delete();
        $this->emit('item-deleted', 'Compra eliminada');
    }
    public function cargarDetallesCompra($compraId)
    {
        $compra = Compras::findOrFail($compraId);
        $this->detalleCompras = ComprasDetalles::from('compras_detalles as cd')
            ->join('productos as p', 'p.id', 'cd.producto')
            ->join('medidas as m', 'm.id', 'cd.medida')
            ->select(
                'cd.id',
                'cd.cantidad',
                'cd.costo',
                'cd.total',
                'p.nombreProducto as producto',
                'm.unidad as medida'
            )
            ->where('cd.compra', $compra->id)
            ->get()
            ->map(function ($row) use ($compra) {
                $row->estado_compras = $compra->estado;
                return $row;
            });
        $this->emit('detalle-modal', 'show modal');
    }
    public function anular($id)
    {
        $compra = Compras::find($id);
        if (!$compra) {
            $this->emit('noti', 'Error', 'Compra no encontrada.', 'error');
            return;
        }
        if ($compra->estado === 'Anulado') {
            $this->emit('noti', 'Aviso', 'La compra ya esta anulada.', 'warning');
            return;
        }
        if (SistemaHelper::operacionBloqueada((int) $compra->sucursal)) {
            $this->emit('noti', 'Bloqueado', 'Esta sucursal opera en modo local. Anula la compra desde la PC local.', 'error');
            return;
        }
        $detalles = ComprasDetalles::where('compra', $compra->id)->get();
        if ($detalles->isEmpty()) {
            $this->emit('noti', 'Error', 'La compra no tiene detalle para anular.', 'error');
            return;
        }
        DB::beginTransaction();
        try {
            \Log::info('INICIO anular compra', [
                'compra_id' => $compra->id,
                'correlativo' => $compra->correlativo,
                'sucursal' => $compra->sucursal,
            ]);
            foreach ($detalles as $det) {
                $inventario = Inventarios::where('producto', $det->producto)
                    ->where('sucursal', $compra->sucursal)
                    ->lockForUpdate()
                    ->first();
                if (!$inventario) {
                    throw new \RuntimeException('No existe inventario para el producto ' . $det->producto . ' en la sucursal ' . $compra->sucursal);
                }
                $egresoCantidad = (float) $det->ingreso;
                $existenciaActual = (float) $inventario->existencia;
                $newExist = $existenciaActual - $egresoCantidad;
                $inventario->existencia = $newExist;
                $inventario->save();
                \Log::info('Inventario descontado en anular compra', [
                    'producto' => $det->producto,
                    'inventario_id' => $inventario->id,
                    'existencia_anterior' => $existenciaActual,
                    'egreso' => $egresoCantidad,
                    'nueva_existencia' => $newExist,
                ]);
                $ultimoKardex = Kardex::where('producto', $det->producto)
                    ->where('inventario', $inventario->id)
                    ->latest()
                    ->first();
                $egresoValor = (float) $det->costo * $egresoCantidad;
                $saldoValor = $ultimoKardex
                    ? max(0, (float) $ultimoKardex->saldoValor - $egresoValor)
                    : max(0, (float) $det->costo * $newExist);
                Kardex::create([
                    'producto' => $det->producto,
                    'inventario' => $inventario->id,
                    'sucursal' => $compra->sucursal,
                    'descripcion' => 'Anulacion de la compra ' . $compra->correlativo,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'ingresoCantidad' => 0.00,
                    'ingresoValor' => 0.00,
                    'egresoCantidad' => $egresoCantidad,
                    'egresoValor' => $egresoValor,
                    'saldoCantidad' => $newExist,
                    'saldoValor' => $saldoValor,
                ]);
            }
            // Anular cuentas por pagar y pagos asociados
            $cuentas = CuentasPagar::where('compra', $compra->id)->get();
            foreach ($cuentas as $cuenta) {
                // Si tiene pagos registrados, se anulan (soft delete)
                Pagos::where('cuenta_pagar', $cuenta->id)->delete();
                // Se anula la cuenta por pagar (estado pendiente con saldo 0 porque el enum no incluye 'anulado')
                $cuenta->estado = 'pendiente';
                $cuenta->saldo = 0.00;
                $cuenta->save();
            }
            $compra->estado = 'Anulado';
            $compra->save();
            DB::commit();
            \Log::info('Compra anulada correctamente', ['compra_id' => $compra->id]);
            $this->emit('item-updated', 'Compra anulada');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('ERROR anular compra', [
                'compra_id' => $compra->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->emit('noti', 'Error', $e->getMessage(), 'error');
        }
    }
    private function puedeVerTodasSucursales(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->can('Compras_ViewAll') || in_array($user->profile, ['Super', 'Administrador'], true);
    }
    private function tieneColumna(string $tabla, string $columna): bool
    {
        try {
            return Schema::hasColumn($tabla, $columna);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
