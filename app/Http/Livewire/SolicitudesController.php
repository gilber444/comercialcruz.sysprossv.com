<?php

namespace App\Http\Livewire;

use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Solicitudes;
use App\Models\SolicitudesDetalles;
use App\Models\Sucursales;
use App\Models\tmpSolicitud;
use App\Models\tmpSolicitudes;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitudesController extends Component
{
    use WithPagination;

    public  $origen, $origen1, $destino, $destino1, $search, $selected_id, $correlativo, $fecha, $detalle, $itemsQuantity, $total, $can = [], $empre, $sucu, $ca, $rol, $cart = [], $uni = [], $modalItemId, $modalCantidad, $productoSeleccionado = null, $precioMostrarInfo = null, $sucursales, $existencias = [], $products = [], $nombreProductoSeleccionado;
    private $pagination = 7;

    public $productoName, $detallePrecios = [], $detalleEscalas = [];

    public function mount()
    {
        $user = Auth::user();
        $this->rol = $user->profile;
        $bus = Solicitudes::orderBy('id', 'desc')->first();

        $this->fecha = date('Y-m-d H:i:s');

        if ($bus) {
            $this->correlativo = $bus->numero + 1;
        } else {
            // Si no se encontraron registros, puedes asignar un valor predeterminado
            $this->correlativo = 1;
        }
        $items = Productos::orderBy('id'); // Reemplaza esto con tu lógica para obtener los ítems

        foreach ($items as $item) {
            $this->can[$item->id] = null;
        }
        $user = Auth::user();

        if ($user->profile === 'Super' || $user->profile === 'Administrador' || $user->profile === 'BODEGA') {
            $this->origen1 = Sucursales::with('Rempresa')->orderBy('nombre')->get();
            $this->destino1 = Sucursales::with('Rempresa')->OrderBy('nombre')->get();
        } else {
            $this->origen1 = Sucursales::with('Rempresa')->find(1);
            $this->destino1 = Sucursales::with('Rempresa')->where('id', $user->sucursal)->OrderBy('nombre')->get();
            //$this->destino = $this->destino1->id;
            $this->origen = $this->origen1->id;
        }

        $this->empre = $user->empresa;
        $this->sucu = $user->sucursal;
        $this->sucursales = Sucursales::all();
        $this->Carrito();
    }

    public function Carrito()
    {
        $user = Auth::user();
        $this->cart =  tmpSolicitudes::where('user', $user->id)->orderBy('id', 'desc')->get();
        $this->total = tmpSolicitudes::where('user', $user->id)->get()->sum('total');
        $this->itemsQuantity = tmpSolicitudes::where('user', $user->id)->get()->sum('cantidad');

        foreach ($this->cart as $item) {
            $this->can[$item->id] = $item->cantidad;
        }
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();
        $this->liveSearch();
        return view('livewire.existencias.solicitudes')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    protected $listeners = [
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'Add' => 'ScanCodeById',
        'scan-code-byid' => 'ScanCode2',
        'print-ticket' => 'printTicket',
        'resetSearch' => 'resetSearch',
    ];

    public function ScanCodeById($id)
    {
        $precio = Precios::find($id);
        if ($precio) {
            $this->increaseQty($precio->id);
        } else {
            $this->emit('item-error', 'Precio no encontrado', 'error');
        }
    }

    public function ScanCode($barcode, $cant = 1)
    {
        $inputId = null;
        $user = Auth::user();

        // Buscar producto
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', $barcode)
            ->where('p.escala', 'No')
            ->where('i.sucursal', $user->sucursal)
            ->select(
                'productos.*',
                'i.existencia',
                'p.codebar',
                'i.id as inventario',
                'p.costosiva',
                'p.cantidad',
                'i.sucursal',
                'p.presentacion'
            )
            ->first();

        // Validar si existe el producto
        if ($product == null) {
            $this->emit('scan-notfound', 'El producto no está registrado');
            return;
        }

        // Verificar si el producto ya está en el carrito
        $exist = tmpSolicitudes::where('producto', $product->id)
            ->where('user', $user->id)
            ->first();

        // Validar que se haya seleccionado la sucursal de entrega
        if (!$this->origen) {
            $this->emit('item-error', 'Seleccione la Sucursal de Entrega');
            return;
        }

        // Validar existencia en la sucursal origen
        $validacionCantidad = Inventarios::where('producto', $product->id)
            ->where('sucursal', $this->origen)
            ->first();

        if (!$validacionCantidad) {
            $this->emit('item-error', 'No existe inventario en la sucursal seleccionada');
            return;
        }

        if (($cant * $product->cantidad) > $validacionCantidad->existencia) {
            $this->emit('item-error', 'Producto insuficiente para trasladar');
            return;
        }
        if ($exist) {
            $exist->cantidad += $cant;
            $exist->descargar = $exist->cantidad * $product->cantidad;
            $exist->total = $exist->cantidad * $exist->costo;
            $exist->save();

            $inputId = $exist->id;
        } else {
            $tmp = tmpSolicitudes::create([
                'codebar' => $product->codebar,
                'producto' => $product->id,
                'name' => $product->nombreProducto,
                'cantidad' => $cant,
                'costo' => $product->costosiva,
                'total' => $product->costosiva * $cant,
                'unidad' => $product->medida,
                'medida' => $product->presentacion,
                'limit' => $product->cantidad,
                'descargar' => $product->cantidad,
                'user' => $user->id
            ]);

            $inputId = $tmp->id;
        }

        // Cargar carrito
        $this->Carrito();

        // Si algo falló, no continuar
        if (!$inputId) return;

        // Enfocar input correcto
        $this->emit('focus-input', ['id' => $inputId]);
    }

    public function InCart($productId)
    {
        $user_id = Auth::user()->id;

        $exist = tmpSolicitudes::where('producto', $productId)->where('user', $user_id)->first();
        if ($exist)
            return true;
        else
            return false;
    }

    public function ScanCode2($barcode, $cant = 1)
    {
        //dd($barcode);
        $user = Auth::user();
        /*$product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
        ->join('inventarios as i', 'i.producto', 'productos.id')
        ->where('p.id',  $barcode )
        ->where('p.escala', 'No')
        ->whereNull('p.deleted_at')
        ->where('i.sucursal', session('sucursal'))
        ->select('productos.id', 'productos.nombreProducto', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.pvventa', 'p.cantidad', 'i.sucursal', 'p.presentacion', 'p.cantidad as descargar', 'p.medida', 'p.id as pprecio', 'productos.familia')
        ->first();*/

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
                    ->where('i.sucursal', Auth::user()->sucursal);
            })
            ->where('p.producto', $barcode)
            ->where('p.escala', 'No')
            ->whereNull('p.deleted_at')
            ->first();

        if ($product) {
            /*$this->productoName = $product->nombreProducto;
            $this->detallePrecios = Precios::where('producto', $product->id)->where('escala', 'No')
            ->orderBy('cantidad', 'asc')
            ->get();
            $this->detalleEscalas = Precios::where('producto', $product->id)->where('escala', 'Si')
            ->orderBy('cantidad', 'asc')
            ->get();
            //dd($this->detallePrecios);*/
            $this->productoName = $product->nombreProducto;

            // Usar una sola consulta base para evitar código duplicado
            $preciosQuery = Precios::where('producto', $product->id)
                ->orderBy('cantidad', 'asc');

            $this->detallePrecios = (clone $preciosQuery)->where('escala', 'No')->get();
            $this->detalleEscalas = (clone $preciosQuery)->where('escala', 'Si')->get();
            $this->emit('abrirModal', 'detalleprecios');
        } else {
            $this->emit('item-error', 'Producto no encontrado', 'error'); // Notificación con SweetAlert
        }
    }

    public function increaseQty($productId, $cant = 1)
    {
        $inputId = null;
        $user = Auth::user();

        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.id', $productId)
            //->where('p.cantidad', 1)
            ->whereNull('p.deleted_at')
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.presentacion')
            ->first();

        $exist = tmpSolicitudes::where('producto', $product->id)->where('user', $user->id)->first();

        if ($this->origen) {
            $validacionCantidad = Inventarios::where('producto', $product->id)
                ->where('sucursal', $this->origen)->first();

            if (($cant * $product->cantidad) <= $validacionCantidad->existencia) {
                if ($exist) {
                    $exist->cantidad =  $exist->cantidad + $cant;
                    $exist->descargar = $exist->cantidad * $product->cantidad;
                    $exist->total = $exist->cantidad * $exist->costo;
                    $exist->save();

                    $inputId = $exist->id;
                } else {
                    $tmp = tmpSolicitudes::create([
                        'codebar' => $product->codebar,
                        'producto' => $product->id,
                        'name' => $product->nombreProducto,
                        'cantidad' => $cant,
                        'costo' => $product->costosiva,
                        'total' => $product->costosiva * $cant,
                        'unidad' => $product->medida,
                        'medida' => $product->presentacion,
                        'limit' => $product->cantidad,
                        'descargar' => $cant * $product->cantidad,
                        'user' => $user->id
                    ]);

                    $inputId = $tmp->id;
                }
            } else {
                $this->emit('item-error', 'Producto insuficiente para trasladar');
            }
        } else {
            $this->emit('item-error', 'Seleccione la Sucursal de Entrega');
        }

        $this->Carrito();
        if (!$inputId) {
            return; // O manejar error si lo deseas
        }
        $this->emit('focus-input', ['id' => $inputId]);
    }

    public function updateQty($id)
    {
        $user = Auth::user();

        $cantidades = $this->can[$id];
        $exist = tmpSolicitudes::find($id);
        $descargar = $exist->descargar / $exist->cantidad;

        if ($this->origen) {
            $validacionCantidad = Inventarios::where('producto', $exist->producto)
                ->where('sucursal', $this->origen)->first();

            if (($cantidades * $descargar) <= $validacionCantidad->existencia) {
                $exist->cantidad =  $cantidades;
                $exist->descargar = $descargar * $cantidades;
                $exist->total =  $cantidades * $exist->costo;
                $exist->save();
            } else {
                $this->emit('item-error', 'Producto insuficiente para trasladar');
            }
        } else {
            $this->emit('item-error', 'Seleccione la Sucursal de Entrega');
        }

        $this->Carrito();
    }

    public function removeItem($id)
    {
        $delete = tmpSolicitudes::find($id)->delete();
        $this->Carrito();
    }

    public function updateUni($id)
    {
        if ($this->origen) {
            $user_id = Auth::user()->id;
            $uniNew = $this->uni[$id];
            $exist = tmpSolicitudes::find($id);
            $PreCan = Precios::where('medida', $uniNew)->where('producto', $exist->producto)->first();
            $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
                ->join('inventarios as i', 'i.producto', 'productos.id')
                ->where('p.producto', $exist->producto)
                ->where('p.medida', $uniNew)
                ->where('i.sucursal', $this->origen)
                ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.id as idpre')
                ->first();
            $quantity = $exist->cantidad;
            $price = $PreCan->costosiva;


            $ingresoCantidad = $PreCan->cantidad * $exist->cantidad;
            $exist->cantidad = $ingresoCantidad;
            $exist->costo = $product->costosiva / $product->cantidad;
            $exist->limit = $PreCan->cantidad;
            //$exist->newcosto = $product->costosiva;
            $exist->unidad = $uniNew;
            $exist->descargar = $ingresoCantidad;
            $exist->total = $exist->costo * $exist->cantidad;
            $exist->save();
        } else {
            $this->emit('item-error', 'Seleccione la Sucursal de Entrega');
        }
        $this->Carrito();
    }

    public function clearCart()
    {
        $user_id = Auth::user()->id;
        $delete = tmpSolicitudes::where('user', $user_id)->delete();
        $this->Carrito();
    }

    public function Store()
    {
        $rules = [
            'destino' => 'required',
            'fecha' => 'required'
        ];

        $messages = [
            'destino.required' => 'Seleccione el destino de solicutd',
            'fecha.required' => 'la fecha es requerido'
        ];

        $this->validate($rules, $messages);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            $soli = Solicitudes::create([
                'origen' => $this->origen,
                'destino' => $this->destino,
                'numero' => $this->correlativo,
                'fecha' => $this->fecha,
                'detalle' => $this->detalle,
                'solicitante' => $user->id,
                'estado' => 'Solicitado',
                //'sucursal' => $user->sucursal,
                //'empresa' => $user->empresa,

            ]);

            if ($soli) {
                $items = tmpSolicitudes::where('user', $user->id)->get();
                foreach ($items as $item) {
                    SolicitudesDetalles::create([
                        'solicitud' => $soli->id,
                        'producto' => $item->producto,
                        'medida' => $item->unidad,
                        'unidad' => $item->medida,
                        'origen' => $this->origen,
                        'destino' => $this->destino,
                        'cantidad' => $item->cantidad,
                        'descargar' => $item->descargar,
                        'costo' => $item->costo,
                        'total' => $item->cantidad * $item->costo,
                        'realizado' => $user->id,
                        'autorizado' => $user->id,
                        'despachado' => $user->id,
                        'ingresado' => $user->id,
                        'estado' => 'Solicitado',
                        //'sucursal' => $user->sucursal,
                        //'empresa' => $user->empresa,
                    ]);

                    // $inven = Inventarios::where('sucursal', $this->origen)->where('producto', $item->producto)->first();

                    // $newExis = $inven->existencia - $item->descargar;

                    // $inven->existencia = $newExis;
                    // $inven->save();

                    // $kardex = Kardex::where('producto', $item->producto)->where('inventario', $inven->id)
                    //     ->orderBy('id', 'desc')
                    //     ->first();

                    // //$saldoCantidad = $kardex->saldoCantidad - $detalle->cantidad;
                    // //$saldoValor = $saldoCantidad * $detalle->costo;
                    // $sucur = Sucursales::find($this->origen);
                    // $des = Sucursales::find($this->destino);
                    // $kar = Kardex::create([
                    //     'producto' => $item->producto,
                    //     'inventario' => $inven->id,
                    //     'descripcion' => 'Despacho de producto de la sucursal ' . $sucur->nombre . ' para la sucursal ' . $des->nombre,
                    //     'fecha' => date('Y-m-d'),
                    //     'hora' => date('H:s:i'),
                    //     'ingresoCantidad' => 0,
                    //     'ingresoValor' => 0,
                    //     'egresoCantidad' => $item->descargar,
                    //     'egresoValor' => $item->cantidad * $item->costo,
                    //     'saldoCantidad' => $newExis,
                    //     'saldoValor' => ($newExis * $item->costo),
                    // ]);
                }
            }

            DB::commit();
            return redirect()->route('solicitudesVer');
            $this->clearCart();
            $this->emit('item-added', 'Solicitud Registrada, pendiente de Autizacion');
            $this->resetUI();
        } catch (Exception $e) {
            DB::rollback();
            $this->emit('scan-notfound', $e->getMessage());
        }
    }

    public function resetUI()
    {
        $this->origen = '';
        //$this->origen1 = '';
        $this->destino = '';
        $this->destino1 = '';
        $this->search = '';
        $this->correlativo = '';
        $this->fecha = '';
        $this->detalle = '';
        $this->itemsQuantity = '';
        $this->total = '';
        $this->can = [];
        $this->empre = '';
        $this->sucu = '';
        $this->ca = '';
        //$this->rol = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    // MODAL PRODUCTOS

    public function mostrarInfoProducto($id)
    {
        /*dd('dsf');
        $this->productoSeleccionado = tmpSolicitudes::find($id);
        $producto = Productos::where('nombreProducto', $this->productoSeleccionado->name)->first();

        $this->nombreProductoSeleccionado = $producto->nombreProducto;
        //$this->loadExistencias($producto->id);
        $this->dispatchBrowserEvent('mostrar-modal-producto');*/
    }

    public function addAll()
    {
        if (count($this->products) > 0) {
            foreach ($this->products as $product) {
                $this->emit('scan-code-byid', $product->id);
            }
        }
    }

    public function loadExistencias($productId = null)
    {
        // Asign ar el producto seleccionado
        /*$this->productoSeleccionado = $productId;

        if ($productId) {
            // Obtener existencias en todas las sucursales para ese producto
            $this->existencias = Inventarios::where('producto', $productId)->get();
        } else {
            // Si no hay un producto, asignar 0 a las existencias
            $this->existencias = collect([['cantidad' => 0]]);
        }

        // Emitir evento para actualizar la fila seleccionada
        $this->emit('updateSelectedRow', $productId);*/
    }

    public function liveSearch()
    {
        $user = Auth::user();

        if (strlen($this->search) > 0) {
            $this->reset('products'); // Limpia la variable antes de actualizar
            if ($user->profile == 'Auditor' || $user->profile == 'Super' && $this->origen) {
                $this->products = Productos::join('categorias as c', 'c.id', 'productos.categoria')
                    ->join('medidas as m', 'm.id', 'productos.medida')
                    ->join('inventarios as i', 'i.producto', 'productos.id')
                    ->select('productos.id', 'm.unidad as medida', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                    ->where('i.sucursal', $this->origen)
                    ->where(function ($query) {
                        $query
                            ->where('productos.nombreProducto', 'like', $this->search . "%")
                            ->orWhere('productos.codebar1', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar2', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar3', 'like', "%{$this->search}%")
                            ->orWhere('productos.codealternativo', 'like', "%{$this->search}%");
                    })
                    ->groupBy('productos.id', 'm.unidad', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                    ->orderBy('productos.nombreProducto', 'asc')
                    ->take(50)
                    ->get();
            } else {
                $this->products = Productos::join('categorias as c', 'c.id', 'productos.categoria')
                    ->join('medidas as m', 'm.id', 'productos.medida')
                    ->join('inventarios as i', 'i.producto', 'productos.id')
                    ->select('productos.id', 'm.unidad as medida', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                    ->where('i.sucursal', $user->sucursal)
                    ->where(function ($query) {
                        $query
                            ->where('productos.nombreProducto', 'like', $this->search . "%")
                            ->orWhere('productos.codebar1', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar2', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar3', 'like', "%{$this->search}%")
                            ->orWhere('productos.codealternativo', 'like', "%{$this->search}%");
                    })
                    ->groupBy('productos.id', 'm.unidad', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                    ->orderBy('productos.nombreProducto', 'asc')
                    ->take(50)
                    ->get();
            }
        } else {
            $this->products = [];
        }
    }

    public function Add($id)
    {

        $this->emit('scan-code-byid', $id);
        //$this->search = '';
    }

    public function resetSearch()
    {
        $this->search = ''; // Limpiar el campo de búsqueda en Livewire
    }
}
