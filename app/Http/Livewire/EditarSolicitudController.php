<?php

namespace App\Http\Livewire;

use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Solicitudes;
use App\Models\SolicitudesDetalles;
use App\Models\Sucursales;
use App\Models\tmpSolicitudes;
use Exception;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
use Livewire\WithPagination;

class EditarSolicitudController extends Component
{
    use WithPagination;

    public  $origen, $products = [], $origen1, $destino, $destino1, $search, $selected_id, $correlativo, $fecha, $detalle, $itemsQuantity, $total, $can = [], $empre, $sucu, $ca, $rol, $cart = [], $solicitudes;
    private $pagination = 7;

    public $productoName, $detallePrecios = [], $detalleEscalas = [];

    public function mount($id)
    {
        $this->selected_id = $id;
        $user = Auth::user();
        $this->rol = $user->profile;
        $bus = Solicitudes::find($this->selected_id);
        $this->origen = $bus->origen;
        $this->destino = $bus->destino;
        $this->correlativo = $bus->numero;
        $this->fecha = $bus->fecha;
        $this->detalle = $bus->detalle;

        $this->fecha = date('Y-m-d H:i:s');

        $items = Productos::orderBy('id'); // Reemplaza esto con tu lógica para obtener los ítems

        foreach ($items as $item) {
            $this->can[$item->id] = null;
        }
        $user = Auth::user();

        if ($user->profile === 'Super' || $user->profile === 'Administrador' || $user->profile === 'Auditor') {
            $this->origen1 = Sucursales::with('Rempresa')->orderBy('nombre')->get();
            $this->destino1 = Sucursales::with('Rempresa')->OrderBy('nombre')->get();
        } else {
            $this->origen1 = Sucursales::with('Rempresa')->find($user->sucursal);
            $this->destino1 = Sucursales::with('Rempresa')->OrderBy('nombre')->get();
            //$this->destino = $this->destino1->id;
            $this->origen = $this->origen1->id;
        }

        $this->empre = $user->empresa;
        $this->sucu = $user->sucursal;

        $this->Carrito();
    }

    public function Carrito()
    {
        $user = Auth::user();
        $this->solicitudes = SolicitudesDetalles::with('Rproductos')->where('solicitud', $this->selected_id)->orderBy('id', 'desc')->get();
        $this->cart =  SolicitudesDetalles::where('solicitud', $this->selected_id)->orderBy('id', 'desc')->get();
        $this->total = SolicitudesDetalles::where('solicitud', $this->selected_id)->get()->sum('total');
        $t = tmpSolicitudes::where('user', $user->id)->get()->sum('cantidad');
        $s = SolicitudesDetalles::where('solicitud', $this->selected_id)->get()->sum('cantidad');
        $this->itemsQuantity = $s;
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
        return view('livewire.existencias.editar-solicitud')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    protected $listeners = [
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'deleteRoww' => 'DestroyProd',
        'Store' => 'Store',
        'Add' => 'ScanCodeById',
        'scan-code-byid' => 'ScanCode2',
        //'scan-code-byid' => 'ScanCodeById',
        'print-ticket' => 'printTicket',
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
        $user = Auth::user();
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', $barcode)
            ->where('i.sucursal', $user->sucursal)
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.presentacion')
            ->first();

        if ($product == null) {
            $this->emit('scan-notfound', 'El producto no esta registrado');
        } else {
            if ($this->InCart($product->id)) {
                $this->increaseQty($product->id);
                return;
            }

            // $tmp = tmpSolicitudes::create([
            //     'codebar' => $product->codebar,
            //     'producto' => $product->id,
            //     'name' => $product->nombreProducto,
            //     'cantidad' => $cant,
            //     'costo' => $product->costosiva,
            //     'unidad' => $product->medida,
            //     'medida' => $product->presentacion,
            //     'limit' => $product->cantidad,
            //     'descargar' => $cant * $product->cantidad,
            //     'user' => $user->id
            // ]);

            SolicitudesDetalles::create([
                'solicitud' => $this->selected_id,
                'producto' => $product->id,
                'medida' => $product->medida,
                'unidad' => $product->presentacion,
                'origen' => $this->origen,
                'destino' => $this->destino,
                'cantidad' => $cant,
                'descargar' => $cant * $product->cantidad,
                'costo' => $product->costosiva,
                'total' => $cant * $product->costosiva,
                'realizado' => $user->id,
                'autorizado' => $user->id,
                'despachado' => $user->id,
                'ingresado' => $user->id,
                'estado' => 'Solicitado',
                //'sucursal' => $user->sucursal,
                //'empresa' => $user->empresa,
            ]);

            $inven = Inventarios::where('sucursal', $this->origen)->where('producto', $product->id)->first();

            // $newExis = $inven->existencia - $cant * $product->cantidad;

            // $inven->existencia = $newExis;
            // $inven->save();

            // $kardex = Kardex::where('producto', $product->id)->where('inventario', $inven->id)
            //     ->orderBy('id', 'desc')
            //     ->first();

            // $saldoCantidad = $kardex->saldoCantidad - $detalle->cantidad;
            // $saldoValor = $saldoCantidad * $detalle->costo;
            // $sucur = Sucursales::find($this->origen);
            // $des = Sucursales::find($this->destino);
            // $kar = Kardex::create([
            //     'producto' => $product->id,
            //     'inventario' => $inven->id,
            //     'descripcion' => 'Despacho de producto de la sucursal ' . $sucur->nombre . ' para la sucursal ' . $des->nombre,
            //     'fecha' => date('Y-m-d'),
            //     'hora' => date('H:s:i'),
            //     'ingresoCantidad' => 0,
            //     'ingresoValor' => 0,
            //     'egresoCantidad' => $cant * $product->cantidad,
            //     'egresoValor' => $product->cantidad * $product->costosiva,
            //     'saldoCantidad' => $newExis,
            //     'saldoValor' => ($newExis * $product->costosiva),
            // ]);

            $this->ScanCode2($product->id);
            $this->Carrito();
        }
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

    public function InCart($productId)
    {
        $user_id = Auth::user()->id;

        $exist = tmpSolicitudes::where('producto', $productId)->where('user', $user_id)->first();
        if ($exist)
            return true;
        else
            return false;
    }

    public function increaseQty($productId, $cant = 1)
    {
        $user = Auth::user();

        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.id', $productId)
            //->where('p.cantidad', 1)
            ->whereNull('p.deleted_at')
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.presentacion')
            ->first();

        $exist = tmpSolicitudes::where('producto', $productId)->where('user', $user->id)->first();

        // Buscar si ya existe el producto en esta solicitud con la misma unidad de medida
        $exist = SolicitudesDetalles::where('solicitud', $this->selected_id)
            ->where('producto', $product->id)
            ->where('medida', $product->medida)
            ->first();

        if ($exist) {

            // Incrementa cantidad
            $exist->cantidad = $exist->cantidad + $cant;

            // Recalcula descargar y total
            $exist->descargar = $exist->cantidad * $product->cantidad;
            $exist->total = $exist->cantidad * $exist->costosiva;

            $exist->save();

            $inputId = $exist->id;
        } else {
            $new = SolicitudesDetalles::create([
                'solicitud' => $this->selected_id,
                'producto' => $product->id,
                'medida' => $product->medida,
                'unidad' => $product->presentacion,
                'origen' => $this->origen,
                'destino' => $this->destino,
                'cantidad' => $cant,
                'descargar' => $cant * $product->cantidad,
                'costo' => $product->costosiva,
                'total' => $cant * $product->costosiva,
                'realizado' => $user->id,
                'autorizado' => $user->id,
                'despachado' => $user->id,
                'ingresado' => $user->id,
                'estado' => 'Solicitado',
            ]);

            $inven = Inventarios::where('sucursal', $this->origen)->where('producto', $product->id)->first();

            // $newExis = $inven->existencia - $cant * $product->cantidad;

            // $inven->existencia = $newExis;
            // $inven->save();

            // $kardex = Kardex::where('producto', $product->id)->where('inventario', $inven->id)
            //     ->orderBy('id', 'desc')
            //     ->first();

            // $saldoCantidad = $kardex->saldoCantidad - $detalle->cantidad;
            // $saldoValor = $saldoCantidad * $detalle->costo;
            // $sucur = Sucursales::find($this->origen);
            // $des = Sucursales::find($this->destino);
            // $kar = Kardex::create([
            //     'producto' => $product->id,
            //     'inventario' => $inven->id,
            //     'descripcion' => 'Despacho de producto de la sucursal ' . $sucur->nombre . ' para la sucursal ' . $des->nombre,
            //     'fecha' => date('Y-m-d'),
            //     'hora' => date('H:s:i'),
            //     'ingresoCantidad' => 0,
            //     'ingresoValor' => 0,
            //     'egresoCantidad' => $cant * $product->cantidad,
            //     'egresoValor' => $product->cantidad * $product->costosiva,
            //     'saldoCantidad' => $newExis,
            //     'saldoValor' => ($newExis * $product->costosiva),
            // ]);


            $inputId = $new->id;
            // $this->ScanCode2($product->id);
        }
        $this->Carrito();
        $this->emit('focus-input', ['id' => $inputId]);
    }


    public function updateQty($id)
    {
        $user = Auth::user();

        $cantidades = $this->can[$id];
        $exist = SolicitudesDetalles::find($id);
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
        $delete = SolicitudesDetalles::find($id)->delete();
        $this->Carrito();
    }

    public function DestroyProd(SolicitudesDetalles $detalle)
    {
        $detalle = SolicitudesDetalles::find($detalle->id);
        $up = Solicitudes::find($detalle->solicitud);
        // $exis = Inventarios::where('producto', $detalle->producto)->where('sucursal', $up->origen)->first();

        // $nuevo = $exis->existencia + $detalle->ingreso;

        // $exis->existencia = $nuevo;
        // $exis->save();

        // // $kar = Kardex::where('producto', $detalle->producto)->where('inventario', $exis->id)->latest('id')->first();
        // $kard = Kardex::create([
        //     'producto' => $detalle->producto,
        //     'inventario' => $exis->id,
        //     'descripcion' => 'Eliminacion del producto de la solicitud ' . $kar->descripcion,
        //     'fecha' => date('Y-m-d'),
        //     'hora' => date('H:i:s'),
        //     'ingresoCantidad' => $detalle->cantidad,
        //     'ingresoValor' => $detalle->total,
        //     'egresoCantidad' => 0.00,
        //     'egresoValor' => 0.00,
        //     'saldoCantidad' => $nuevo,
        //     'saldoValor' => $kar->saldoValor - $detalle->total
        // ]);

        //dd($kar);
        $detalle->delete();
        $this->Carrito();
        $this->emit('item-deleted', 'Producto Eliminado de la solicitud');
    }

    public function clearCart()
    {
        $user_id = Auth::user()->id;
        $delete = tmpSolicitudes::where('user', $user_id)->delete();
        $this->Carrito();
    }

    // public function Update()
    // {
    //     $rules = [
    //         'destino' => 'required',
    //         'fecha' => 'required'
    //     ];

    //     $messages = [
    //         'destino.required' => 'Seleccione el destino de solicutd',
    //         'fecha.required' => 'la fecha es requerido'
    //     ];

    //     $this->validate($rules, $messages);

    //     $user = Auth::user();

    //     DB::beginTransaction();

    //     try
    //     {
    //         $soli = Solicitudes::find($this->selected_id);
    //         $soli->detalle = $this->detalle;
    //         $soli->origen = $this->origen;
    //         $soli->destino = $this->destino;
    //         $soli->fecha = $this->fecha;
    //         $soli->estado = 'Despachado';
    //         $soli->save();

    //         if($soli)
    //         {
    //             $items = tmpSolicitudes::where('user', $user->id)->get();
    //             foreach($items as $item)
    //             {
    //                 SolicitudesDetalles::create([
    //                     'solicitud' => $this->selected_id,
    //                     'producto' => $item->producto,
    //                     'medida' => $item->unidad,
    //                     'origen' => $this->origen,
    //                     'destino' => $this->destino,
    //                     'cantidad' => $item->cantidad,
    //                     'costo' => $item->costo,
    //                     'total' => $item->cantidad * $item->costo,
    //                     'realizado' => $user->id,
    //                     'autorizado' => $user->id,
    //                     'despachado' => $user->id,
    //                     'ingresado' => $user->id,
    //                     'estado' => 'Despachado',
    //                     //'sucursal' => $user->sucursal,
    //                     //'empresa' => $user->empresa,
    //                 ]);

    //                 $inven = Inventarios::where('sucursal', $this->origen)->where('producto', $item->producto)->first();

    //                 $newExis = $inven->existencia - $item->descargar;

    //                 $inven->existencia = $newExis;
    //                 $inven->save();

    //                 $kardex = Kardex::where('producto', $item->producto)->where('inventario', $inven->id)
    //                     ->orderBy('id', 'desc')
    //                     ->first();

    //                 //$saldoCantidad = $kardex->saldoCantidad - $detalle->cantidad;
    //                 //$saldoValor = $saldoCantidad * $detalle->costo;
    //                 $sucur = Sucursales::find($this->origen);
    //                 $des = Sucursales::find($this->destino);
    //                 $kar = Kardex::create([
    //                     'producto' => $item->producto,
    //                     'inventario' => $inven->id,
    //                     'descripcion' => 'Despacho de producto de la sucursal ' . $sucur->nombre . ' para la sucursal ' . $des->nombre,
    //                     'fecha' => date('Y-m-d'),
    //                     'hora' => date('H:s:i'),
    //                     'ingresoCantidad' => 0,
    //                     'ingresoValor' => 0,
    //                     'egresoCantidad' => $item->descargar,
    //                     'egresoValor' => $item->cantidad * $item->costo,
    //                     'saldoCantidad' => $newExis,
    //                     'saldoValor' => ($newExis * $item->costo),
    //                 ]);

    //             }
    //         }

    //         DB::commit();

    //         $this->clearCart();
    //         $this->emit('item-added', 'Solicitud Registrada, pendiente de Autizacion');
    //         $this->resetUI();
    //         return Redirect::to('/solicitudesVer');
    //     }
    //     catch(Exception $e)
    //     {
    //         DB::rollback();
    //         $this->emit('scan-notfound', $e->getMessage());
    //     }
    // }

    public function Despachar()
    {
        $soli = Solicitudes::find($this->selected_id);

        if (!$soli) {
            $this->emit('item-error', 'No se encontro la solicitud');
            return;
        }

        $detalles = SolicitudesDetalles::where('solicitud', $soli->id)
            ->where('estado', '<>', 'Despachado')
            ->get();

        if ($detalles->isEmpty()) {
            $this->emit('item-error', 'La solicitud no tiene productos pendientes');
            return;
        }

        // ── PASO 1: Validar todos los productos antes de despachar ninguno ──
        $errores = [];

        foreach ($detalles as $detalle) {
            $producto = Productos::find($detalle->producto);
            $nombre   = $producto->nombreProducto ?? '(ID ' . $detalle->producto . ')';

            $inven = Inventarios::where('sucursal', $detalle->origen)
                ->where('producto', $detalle->producto)
                ->first();

            if (!$inven) {
                $errores[] = $nombre . ' — sin registro de inventario en sucursal origen';
                continue;
            }

            if ((float) $inven->existencia < (float) $detalle->descargar) {
                $errores[] = $nombre
                    . ' — existencia: ' . number_format($inven->existencia, 2)
                    . ', necesita: ' . number_format($detalle->descargar, 2);
            }
        }

        if (!empty($errores)) {
            $mensaje = 'No se puede despachar. Agregue existencia a los siguientes productos:' . PHP_EOL
                . implode(PHP_EOL, $errores);
            $this->emit('item-error', $mensaje);
            return;
        }

        // ── PASO 2: Todo ok, despachar ──
        $user_id = Auth::user()->id;
        $empresa = Auth::user()->empresa;
        $fecha   = date('Y-m-d');
        $hora    = date('H:i:s');

        try {
            DB::transaction(function () use ($soli, $detalles, $user_id, $empresa, $fecha, $hora) {

                foreach ($detalles as $detalle) {

                    $inven = Inventarios::where('sucursal', $detalle->origen)
                        ->where('producto', $detalle->producto)
                        ->lockForUpdate()
                        ->first();

                    $newExis = (float) $inven->existencia - (float) $detalle->descargar;
                    $inven->existencia = $newExis;
                    $inven->save();

                    Kardex::create([
                        'producto'        => $detalle->producto,
                        'inventario'      => $inven->id,
                        'descripcion'     => 'Despacho solicitud #' . $soli->id,
                        'fecha'           => $fecha,
                        'hora'            => $hora,
                        'ingresoCantidad' => 0,
                        'ingresoValor'    => 0,
                        'egresoCantidad'  => (float) $detalle->descargar,
                        'egresoValor'     => (float) $detalle->descargar * (float) $detalle->costo,
                        'saldoCantidad'   => $newExis,
                        'saldoValor'      => $newExis * (float) $detalle->costo,
                        'user'            => $user_id,
                        'empresa'         => $empresa,
                        'sucursal'        => $detalle->origen,
                    ]);

                    $detalle->despachado = $user_id;
                    $detalle->estado     = 'Despachado';
                    $detalle->save();
                }

                $soli->estado = 'Despachado';
                $soli->save();
            });

            $this->emit('item-confirmar', 'Solicitud despachada correctamente');
            return redirect()->route('solicitudesVer');

        } catch (\Exception $e) {
            $this->emit('item-error', 'Error al despachar: ' . $e->getMessage());
            return;
        }
    }

        public function resetUI()
    {
        $this->origen = '';
        $this->selected_id = '';
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

    public function addAll()
    {
        if (count($this->products) > 0) {
            foreach ($this->products as $product) {
                $this->emit('scan-code-byid', $product->id);
            }
        }
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
