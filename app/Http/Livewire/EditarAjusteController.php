<?php

namespace App\Http\Livewire;

use \Cart;
use App\Models\Actividades;
use App\Models\Ajustes;
use App\Models\AjustesDetalles;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Medidas;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\tmpAjuste;
use App\Traits\GenerarJsonAjuste;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class EditarAjusteController extends Component
{
    use GenerarJsonAjuste;
    //variables para el diseño
    public  $pageTitle, $componentName;

    //para los datos de los productos
    public $totales, $itemsQuantitys, $can = [], $pri = [], $uni = [], $cart = [], $carts = [];

    public  $productos, $sucursal, $producto, $sucursalOrigen, $cantidad, $fecha, $detalle, $movimiento, $status, $tipo, $search, $selected_id, $select_id;

    public function mount($id)
    {
        $this->selected_id = $id;
        $user_id = Auth::user()->id;
        $ajuste = Ajustes::find($this->selected_id);

        $this->sucursal = $ajuste->sucursal;
        $this->sucursalOrigen = $ajuste->sucursalOrigen;
        $this->fecha = $ajuste->fecha;
        $this->detalle = $ajuste->detalle;
        $this->tipo = $ajuste->tipo;


        $this->pageTitle = 'Editar';
        $this->componentName = 'Ajuste';

        //$this->clearCart();
        $this->fecha = date('Y-m-d');

        $acti = Actividades::where('user', $user_id)->whereDate('created_at', Carbon::today())->where('status', 'Activo')->first();

        if ($acti) {
            $this->sucursal = $acti->sucursal;
            $this->tipo = 'Ingreso';
        }

        $this->Carrito();
    }

    public function Carrito()
    {
        $user_id = Auth::user()->id;
        $ca = tmpAjuste::where('usuario', $user_id)->get();
        if ($ca) {
            foreach ($ca as $c) {
                $this->uni[$c->id] = $c->unidad;
                $this->pri[$c->id] = $c->price;
                $this->can[$c->id] = $c->quantity;
            }
        }
        $this->selected_id;
        $cantidadesA = AjustesDetalles::where('ajuste', $this->selected_id)->sum('cantidad');
        $cantidadesT = tmpAjuste::where('usuario', $user_id)->sum('quantity');
        $this->itemsQuantitys = $cantidadesA + $cantidadesT;
        $t = AjustesDetalles::where('ajuste', $this->selected_id)->whereNull('deleted_at')->sum('total');
        $tmp = tmpAjuste::where('usuario', $user_id)->sum('total');
        $this->totales = $t + $tmp;
        $this->carts = AjustesDetalles::where('ajuste', $this->selected_id)->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user_id = Auth::user()->id;
        $sucursal = Sucursales::all();
        $this->cart = tmpAjuste::where('usuario', $user_id)->orderby('id', 'desc')->get();

        return view('livewire.ajustes.editar-ajuste', ['sucursales' => $sucursal])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    protected $listeners = [
        'scan-code' => 'ScanCode',
        'deleteRoww' => 'DestroyProd',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Update' => 'Update',
        'scan-code-byid' => 'ScanCodeById',
        'print-ticket' => 'printTicket'
    ];

    public function ScanCodeById($id)
    {
        $this->increaseQty($id);
    }

    public function increaseQty($productId)
    {
        $cant = 1;
        $user_id = Auth::user()->id;
        $product = Precios::with('Rproductos:id,nombreProducto')->find($productId);

        if ($product) {
            $tmp = tmpAjuste::create([
                'producto' => $product->producto,
                'name' => $product->Rproductos->nombreProducto,
                'price' => $product->costosiva,
                'quantity' => $cant,
                'sucursal' => NULL,
                'codebar' => $product->codebar,
                'unidad' => $product->medida,
                'medida' => $product->presentacion,
                'total' => $cant * $product->costosiva,
                'limit' => $product->cantidad,
                'ingreso' => $cant * $product->cantidad,
                'inventario' => Null,
                'usuario' => $user_id
            ]);
        } else {
            $this->emit('item-error', 'Producto no encontrado');
        }

        $this->Carrito();
    }

    public function updateQty($id)
    {
        $cantidades = $this->can[$id];

        $user_id = Auth::user()->id;

        $tmp = tmpAjuste::find($id);
        $pre = Precios::where('producto', $tmp->producto)->where('medida', $tmp->unidad)->first();
        $tmp->quantity = $cantidades;
        $tmp->ingreso = $cantidades * $tmp->limit;
        $tmp->total = $tmp->price * $cantidades;
        $tmp->save();
        $this->Carrito();
    }

    public function ScanCode($barcode, $cant = 1)
    {
        //dd($barcode);
        $user_id = Auth::user()->id;
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', 'like', '%' . $barcode . '%')
            //->where('i.sucursal', session('sucursal'))
            //->where('i.sucursal', 3)
            ->select('productos.id', 'productos.nombreProducto', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.presentacion', 'p.cantidad as descargar', 'p.medida', 'p.id as precio')
            ->first();
        if ($product) {
            //if($this->InCart($product->precio))
            //{
            //$this->increaseQty($product->precio);
            //return;
            //}

            $user_id = Auth::user()->id;

            $tmp = tmpAjuste::create([
                'producto' => $product->id,
                'name' => $product->nombreProducto,
                'price' => $product->costosiva,
                'quantity' => $cant,
                'sucursal' => NULL,
                'codebar' => $product->codebar,
                'unidad' => $product->medida,
                'medida' => $product->presentacion,
                'total' => $cant * $product->costosiva,
                'limit' => $product->cantidad,
                'ingreso' => $cant * $product->cantidad,
                'inventario' => Null,
                'usuario' => $user_id
            ]);

            $this->Carrito();
        } else {
            $this->emit('item-error', 'El producto no esta registrado');
        }
    }

    public function InCart($productId)
    {
        $user_id = Auth::user()->id;

        $exist = tmpAjuste::where('usuario', $user_id)->get();
        if ($exist)
            return true;
        else
            return false;
    }

    public function removeItem($productId)
    {
        $user_id = Auth::user()->id;

        $tmp = tmpAjuste::find($productId);
        $tmp->delete();
        $this->Carrito();
    }

    public function DestroyProd(AjustesDetalles $detalle)
    {
        $up = Ajustes::find($detalle->ajuste);
        $exis = Inventarios::where('producto', $detalle->producto)->where('sucursal', $up->sucursal)->first();

        $nuevo = $exis->existencia - $detalle->ingreso;

        $exis->existencia = $nuevo;
        $exis->save();

        $kar = Kardex::where('producto', $detalle->producto)->where('inventario', $exis->id)->latest('id')->first();
        //dd($kar->descripcion);
        $kard = Kardex::create([
            'producto' => $detalle->producto,
            'inventario' => $exis->id,
            'descripcion' => 'Eliminacion del producto del ajuste ' . $kar->descripcion,
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:s'),
            'ingresoCantidad' => $detalle->quantity,
            'ingresoValor' => $detalle->total,
            'egresoCantidad' => 0.00,
            'egresoValor' => 0.00,
            'saldoCantidad' => $nuevo,
            'saldoValor' => $kar->saldoValor - $detalle->total
        ]);

        //dd($kar);
        $detalle->delete();
        $this->Carrito();
        $this->emit('item-deleted', 'Producto Eliminado del ajuste');
    }

    public function clearCart()
    {
        $user_id = Auth::user()->id;
        $tmp = tmpAjuste::where('usuario', $user_id)->delete();
        $this->Carrito();
    }

    public function Update()
    {
        $user_id = Auth::user()->id;

        $rules = [
            'sucursal' => 'required',
            'fecha' => 'required',
            'detalle' => 'required',
        ];

        $messages = [
            'sucursal.required' => 'La sucursal es requerida',
            'fecha.required' => 'La fecha del ingreso es requerida',
            'detalle.required' => 'El detalle del ingreso es requerido',
        ];
        $this->validate($rules, $messages);

        $items = tmpAjuste::where('usuario', $user_id)->get();

        if ($items->isEmpty()) {
            $this->emit('error', 'No hay items para procesar.');
            return;
        }

        $tipo = $this->tipo;

        try {

            // [2026-03-24] Editar solo guarda datos y detalles — sin tocar inventario ni kardex.
            // AprobacionAjuste es el único responsable de aplicar kardex/inventario al validar.
            $a = Ajustes::find($this->selected_id);
            $a->sucursal = $this->sucursal;
            $a->fecha    = $this->fecha;
            $a->detalle  = $this->detalle;
            $a->status   = 'Ingresado';
            $a->tipo     = $this->tipo;
            $a->user     = Auth::user()->id;
            $a->save();

            foreach ($items as $item) {
                $existencia = Inventarios::where('producto', $item->producto)
                    ->where('sucursal', $a->sucursal)
                    ->first();

                if (!$existencia) {
                    $this->emit('error', 'No se encontró el inventario para el producto ' . $item->producto);
                    continue;
                }

                AjustesDetalles::create([
                    'ajuste'     => $this->selected_id,
                    'producto'   => $item->producto,
                    'inventario' => $existencia->id,
                    'medida'     => $item->unidad,
                    'cantidad'   => $item->quantity,
                    'ingreso'    => $item->ingreso,
                    'costo'      => $item->price,
                    'total'      => $item->price * $item->quantity,
                ]);
            }

            $this->clearCart();
            //$this->emit('item-added', 'Ajuste registrado con éxito');
            //$this->emit('print-ticket2', $this->GenerarJsonAjuste($a->id));
            $this->ResetUI();
            return Redirect::to('/ajustes');
        } catch (\Exception $e) {
            // Manejo de errores
            $this->emit('error', 'Hubo un error al registrar el ajuste: ' . $e->getMessage());
        }
    }

    public function resetUI()
    {
        $this->sucursalOrigen = 'Elegir sucursal';
        $this->detalle = '';
    }

    public function cargaSucursal()
    {
        $this->emitTo('modal-ajuste', 'updateSucursal', $this->sucursal);
    }
}
