<?php

namespace App\Http\Livewire;

use App\Models\HojaInventario;
use App\Models\HojaInventarioDetalles;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\tmpHojaInventario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
use Livewire\WithPagination;

class EditarHojaController extends Component
{
    use WithPagination;

    public $pageTitle, $componentName;
    private $pagination = 20;
    public $fecha, $hora, $responsable, $user, $empresa, $sucursal, $estado;
    public $hoja, $codebar, $producto, $nombre, $medida, $cantidadAnterior, $cantidadActual, $diferencia, $username, $password, $detalle;
    protected $inventarios;
    public $conteo = [], $uni = [];
    public $search = '', $hojaId, $products = [];
    public $productosFiltrados = [];
    public $productoName, $detallePrecios = [], $detalleEscalas = [];
    public $totalConteoProducto;
    public $hojaCorrelativo, $hojaFecha, $hojaEstado, $hojaSucursalNombre, $hojaResponsable;

    public function mount($hojaId)
    {
        $this->pageTitle = 'Editar';
        $this->componentName = 'Hoja de inventario';
        $usuario = Auth::user();
        $hoja = HojaInventario::with('Rsucursales')->find($hojaId);
        $data = tmpHojaInventario::where('user', $usuario->id)->where('hoja', $hojaId)->get();
        $this->sucursal = $hoja->sucursal;
        $this->hojaId = $hojaId;
        $this->hojaCorrelativo = $hoja->correlativo ?? '';
        $this->hojaFecha = $hoja->fecha ?? $hoja->fecha_inicio ?? '';
        $this->hojaEstado = $hoja->estado ?? '';
        $this->hojaSucursalNombre = optional($hoja->Rsucursales)->nombre ?? '';
        $this->hojaResponsable = optional(User::find($hoja->responsable))->name ?? '';
        $this->cargaData();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $sucursales = Sucursales::all();
        $user_id = Auth::id();
        $this->cargaData();
        $this->liveSearch();
        return view('livewire.hoja_inventarios.editar-hoja', [
            'sucursales' => $sucursales,
            'inventarios' => $this->inventarios,
        ])->extends('layouts.theme.app')->section('content');
    }

    public function liveSearch()
    {
        if (strlen($this->search) > 0) {
            $this->products = Precios::with(['Rproductos:id,nombreProducto'])
                ->where('cantidad', 1)
                ->whereNull('deleted_at')
                ->whereHas('Rproductos', function ($query) {
                    $query->where('activo', 1)
                        ->where(function ($q) {
                            $q->where('nombreProducto', 'like', '%' . $this->search . '%')
                                ->orWhere('codebar', 'like', '%' . $this->search . '%');
                        });
                })
                ->selectRaw('MIN(id) as id, producto, cantidad, costosiva, codebar3, presentacion')
                ->groupBy('producto', 'cantidad', 'costosiva', 'codebar', 'presentacion')
                ->take(20)
                ->get();
        } else {
            $this->products = [];
        }
    }

    public function Add($precioId)
    {
        $precio = Precios::find($precioId);
        if (!$precio) {
            $this->emit('item-error', 'Precio no encontrado');
            return;
        }

        $producto = Productos::find($precio->producto);
        if (!$producto) {
            $this->emit('item-error', 'Producto no encontrado');
            return;
        }

        $inventario = Inventarios::where('producto', $producto->id)
            ->where('sucursal', $this->sucursal)
            ->first();

        $existencia = $inventario?->existencia ?? 0;

        $tmp = tmpHojaInventario::create([
            'producto'     => $producto->id,
            'hoja'         => $this->hojaId,
            'sucursal'     => $this->sucursal,
            'name'         => $producto->nombreProducto,
            'codebar'      => $precio->codebar ?? $producto->codebar3,
            'medida'       => $precio->medida,
            'existencia'   => $existencia,
            'conteoFisico' => 0,
            'cantidad'     => 0,
            'limit'        => $precio->cantidad,
            'diferencia'   => 0,
            'costo'        => $precio->costosiva,
            'total'        => 0,
            'user'         => Auth::id(),
        ]);

        $this->search = '';
        $this->cargaData();
        $this->emit('focus-conteo', $tmp->id);
    }

    protected $listeners = [
        'Add'            => 'Add',
        'scan-code'      => 'ScanCode',
        'Add2'           => 'ScanCode2',
        'removeItem'     => 'removeItem',
        'clearCart'      => 'clearCart',
        'Store'          => 'Store',
        'scan-code-byid' => 'ScanCodeById',
    ];

    public function ScanCode2($barcode, $cant = 1)
    {
        $user = Auth::user();
        $product = Productos::query()
            ->select([
                'productos.id',
                'productos.nombreProducto',
                'i.existencia',
                'p.codebar',
                'i.id as inventario',
                'p.pvventa',
                'p.cantidad',
                'i.sucursal',
                'p.presentacion',
                'p.cantidad as descargar',
                'p.medida',
                'p.id as pprecio',
                'productos.familia',
            ])
            ->join('precios as p', 'p.producto', '=', 'productos.id')
            ->join('inventarios as i', function ($join) {
                $join->on('i.producto', '=', 'productos.id')
                    ->where('i.sucursal', $this->sucursal);
            })
            ->where('p.producto', $barcode)
            ->where('p.escala', 'No')
            ->whereNull('p.deleted_at')
            ->first();

        if ($product) {
            $this->productoName = $product->nombreProducto;
            $preciosQuery = Precios::where('producto', $barcode)->orderBy('cantidad', 'asc');
            $this->detallePrecios = (clone $preciosQuery)->where('escala', 'No')->get();
            $this->detalleEscalas = (clone $preciosQuery)->where('escala', 'Si')->get();
            $this->emit('abrirModal', 'detalleprecios');
        } else {
            $this->emit('item-error', 'Producto no encontrado', 'error');
        }
    }

    public function ScanCode($barcode)
    {
        $this->ScanCode3($barcode);
    }

    public function ScanCode3($barcode, $cant = 1)
    {
        $user = Auth::user();
        $product = Productos::query()
            ->select([
                'productos.id',
                'productos.nombreProducto',
                'i.existencia',
                'p.codebar',
                'i.id as inventario',
                'p.pvventa',
                'p.cantidad',
                'i.sucursal',
                'p.presentacion',
                'p.cantidad as descargar',
                'p.medida',
                'p.id as pprecio',
                'productos.familia',
            ])
            ->join('precios as p', 'p.producto', '=', 'productos.id')
            ->join('inventarios as i', function ($join) {
                $join->on('i.producto', '=', 'productos.id')
                    ->where('i.sucursal', $this->sucursal);
            })
            ->where('p.codebar', $barcode)
            ->where('p.escala', 'No')
            ->whereNull('p.deleted_at')
            ->first();

        if ($product) {
            $this->productoName = $product->nombreProducto;
            $preciosQuery = Precios::where('codebar', $barcode)->orderBy('cantidad', 'asc');
            $this->detallePrecios = (clone $preciosQuery)->where('escala', 'No')->get();
            $this->detalleEscalas = (clone $preciosQuery)->where('escala', 'Si')->get();
            $this->emit('abrirModal', 'detalleprecios');
        } else {
            $this->emit('item-error', 'Producto no encontrado', 'error');
        }
    }

    public function ScanCodeById($id)
    {
        $this->ScanCode2($id);
    }

    public function increaseQty($productId)
    {
        $cant = 1;
        $user_id = Auth::user()->id;
        $inventario = Inventarios::with('Rproductos')
            ->where('producto', $productId)
            ->where('sucursal', $this->sucursal)
            ->first();
        $producto = $inventario?->producto;
        $prod = Productos::find($productId);
        $existencia = ($inventario && $inventario->existencia != null)
            ? ($inventario->existencia == 0 ? number_format(0, 2, '.', '') : $inventario->existencia)
            : number_format(0, 2, '.', '');
        if ($producto) {
            $tmp = tmpHojaInventario::create([
                'producto'     => $productId,
                'hoja'         => $this->hojaId,
                'sucursal'     => $this->sucursal,
                'name'         => $prod->nombreProducto,
                'codebar'      => $prod->codebar3,
                'medida'       => $prod->medida,
                'existencia'   => $existencia,
                'conteoFisico' => 0,
                'cantidad'     => 0,
                'limit'        => 0,
                'diferencia'   => 0,
                'costo'        => 0,
                'total'        => 0,
                'user'         => Auth::id(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            $this->emit('item-error', 'Producto no encontrado');
        }
        $this->cargaData();
        $this->dispatchBrowserEvent('focus-after-update', [
            'id' => $tmp->id
        ]);
    }

    public function cargaData()
    {
        $user_id = Auth::id();
        $inventarios = tmpHojaInventario::where('hoja', $this->hojaId)
            ->where('sucursal', $this->sucursal)
            ->orderBy('id', 'desc')
            ->get();
        $this->inventarios = $inventarios;
        $this->totalConteoProducto = $inventarios->sum('total');
    }

    public function updateUni($id)
    {
        $user_id = Auth::user()->id;
        $uniNew = $this->uni[$id];
        $exist = tmpHojaInventario::find($id);
        $PreCan = Precios::find($this->uni[$id]);
        $existencia = floatval($exist->existencia);
        $diferencia = $exist->diferencia != 0 ? $exist->diferencia - $existencia : 0.00;
        $exist->cantidad = $PreCan->cantidad * $exist->conteoFisico;
        $exist->medida = $PreCan->medida;
        $exist->diferencia = $diferencia;
        $exist->costo = $PreCan->costosiva / $PreCan->cantidad;
        $exist->total = number_format($PreCan->costosiva * abs($diferencia), 4, '.', '');
        $exist->save();
        $this->cargaData();
    }

    public function updatedConteo($cantidad, $id)
    {
        $user_id = Auth::user()->id;
        $cantidad = floatval($cantidad);
        $registro = tmpHojaInventario::where('sucursal', $this->sucursal)
            ->where('hoja', $this->hojaId)
            ->where('id', $id)
            ->first();

        if (!$registro) {
            $this->cargaData();
            return;
        }

        $precio = Precios::where('producto', $registro->producto)
            ->where('medida', $registro->medida)
            ->whereNull('deleted_at')
            ->orderBy('cantidad', 'asc')
            ->first();

        if (!$precio) {
            $this->cargaData();
            return;
        }

        $registro->conteoFisico = $cantidad * $registro->limit;
        $registro->cantidad = ($cantidad > 0) ? ($cantidad * (float)$precio->cantidad) : 0;
        $registro->costo = (float) $precio->costosiva / $precio->cantidad;
        $registro->save();

        $totalConteoProducto = (float) tmpHojaInventario::where('sucursal', $this->sucursal)
            ->where('hoja', $this->hojaId)
            ->where('producto', $registro->producto)
            ->sum('conteoFisico');

        $existencia = (float) $registro->existencia;
        $diferenciaReal = $totalConteoProducto != 0
            ? ($totalConteoProducto - $existencia)
            : 0.00;

        $registro->diferencia = $diferenciaReal;
        $registro->total = number_format(((float)$precio->costosiva) * abs($cantidad), 4, '.', '');
        $registro->save();

        $this->cargaData();
        unset($this->conteo[$id]);
    }

    public function saveConteo($id)
    {
        $this->emit('focus-barcode');
    }

    public function focusCodeInput()
    {
        $this->dispatchBrowserEvent('focus-code-input');
    }

    public function removeItem($id)
    {
        $user_id = Auth::user()->id;
        $exist = tmpHojaInventario::find($id);
        $exist->delete();
        $this->cargaData();
    }

    public function Store()
    {
        DB::beginTransaction();
        try {
            $hoja = HojaInventario::findOrFail($this->hojaId);
            $aperturaId = $hoja->apertura_id;

            $acumuladoPrevio = DB::table('hoja_inventario_detalles as d')
                ->join('hoja_inventarios as h', 'h.id', '=', 'd.hoja')
                ->where('h.apertura_id', $aperturaId)
                ->where('h.estado', 'Cerrada')
                ->where('h.id', '<>', $this->hojaId)
                ->groupBy('d.producto')
                ->selectRaw('d.producto, SUM(d.cantidadActual) as total')
                ->pluck('total', 'producto');

            $items = tmpHojaInventario::where('hoja', $this->hojaId)
                ->where('sucursal', $this->sucursal)
                ->get()
                ->groupBy('producto')
                ->map(function ($group) {
                    $costo    = $group->avg('costo');
                    $cantidad = $group->sum('cantidad');
                    return [
                        'producto'     => $group->first()->producto,
                        'codebar'      => $group->first()->codebar,
                        'medida'       => $group->first()->medida,
                        'existencia'   => $group->sum('existencia'),
                        'conteoFisico' => $group->sum('conteoFisico'),
                        'cantidad'     => $cantidad,
                        'diferencia'   => $group->sum('diferencia'),
                        'costo'        => $costo,
                        'total'        => $cantidad * $costo,
                    ];
                }); 

            foreach ($items as $item) {
                $inventario = Inventarios::where('producto', $item['producto'])
                    ->where('sucursal', $this->sucursal)
                    ->first();
                if (!$inventario) continue;

                $producto         = Productos::find($item['producto']);
                $existenciaSistema = (float) $inventario->existencia;
                $prev             = (float) ($acumuladoPrevio[$item['producto']] ?? 0);
                $final            = $prev + (float) $item['cantidad'];
                $ajuste           = $final - $existenciaSistema;
                $ingresoCantidad  = $ajuste > 0 ? $ajuste : 0.00;
                $egresoCantidad   = $ajuste < 0 ? abs($ajuste) : 0.00;
                $costoUnit        = (float) ($item['costo'] ?? 0);
                $ingresoValor     = $ingresoCantidad * $costoUnit;
                $egresoValor      = $egresoCantidad  * $costoUnit;

                $inventario->update(['existencia' => $final]);

                HojaInventarioDetalles::create([
                    'hoja'             => $this->hojaId,
                    'codebar'          => $item['codebar'],
                    'producto'         => $item['producto'],
                    'nombre'           => $producto->nombreProducto ?? '',
                    'medida'           => $item['medida'],
                    'cantidadAnterior' => $existenciaSistema,
                    'cantidadActual'   => $item['cantidad'],
                    'diferencia'       => abs($ajuste),
                    'costo'            => $costoUnit,
                    'total'            => $item['total'],
                ]);

                // Registro 1: egreso de la existencia actual → deja saldo en 0
                Kardex::create([
                    'producto'        => $item['producto'],
                    'inventario'      => $inventario->id,
                    'descripcion'     => 'Ajuste Inventario - Baja saldo (Hoja #' . $this->hojaId . ')',
                    'fecha'           => date('Y-m-d'),
                    'hora'            => date('H:i:s'),
                    'ingresoCantidad' => 0,
                    'ingresoValor'    => 0,
                    'egresoCantidad'  => $existenciaSistema,
                    'egresoValor'     => $existenciaSistema * $costoUnit,
                    'saldoCantidad'   => 0,
                    'saldoValor'      => 0,
                    'user'            => Auth::user()->id,
                ]);

                // Registro 2: ingreso del conteo físico → nuevo saldo
                Kardex::create([
                    'producto'        => $item['producto'],
                    'inventario'      => $inventario->id,
                    'descripcion'     => 'Ajuste Inventario - Conteo físico (Hoja #' . $this->hojaId . ')',
                    'fecha'           => date('Y-m-d'),
                    'hora'            => date('H:i:s'),
                    'ingresoCantidad' => $final,
                    'ingresoValor'    => $final * $costoUnit,
                    'egresoCantidad'  => 0,
                    'egresoValor'     => 0,
                    'saldoCantidad'   => $final,
                    'saldoValor'      => $final * $costoUnit,
                    'user'            => Auth::user()->id,
                ]);
            }

            $hoja->update([
                'estado'    => 'Cerrada',
                'fecha_fin' => now()->toDateString(),
                'hora_fin'  => now()->toTimeString(),
            ]);

            tmpHojaInventario::where('hoja', $this->hojaId)->delete();

            DB::commit();

            $this->emit('item-added', 'Hoja de inventario registrada con éxito');
            return Redirect::to('/hoja_inventarios');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->emit('error', 'Error al guardar la hoja: ' . $e->getMessage());
            return;
        }
    }
}
