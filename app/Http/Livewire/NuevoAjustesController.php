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
class NuevoAjustesController extends Component
{
    use GenerarJsonAjuste;
    //variables para el diseño
    public  $pageTitle, $componentName;
    //para los datos de los productos
    public $total, $itemsQuantity, $can = [], $pri = [], $uni = [], $cart = [], $existenciaActual = [], $existenciaAjustada = [];
    public  $productos, $sucursal, $producto, $sucursalOrigen, $cantidad, $fecha, $detalle, $movimiento, $status, $tipo, $search, $selected_id, $select_id, $productoName, $detallePrecios = [], $detalleEscalas = [];
    public function mount()
    {
        $this->pageTitle = 'Nuevo';
        $this->componentName = 'Ajuste';
        $user_id = Auth::user()->id;
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
                /*$priceid = precios::where('producto', $c->producto)
                    ->where('medida', $c->unidad)
                    ->whereNull('deleted_at')
                    ->where('costosiva', $c->price)
                    ->first();
                $this->uni[$c->id] = $c->unidad . '|'.$priceid->id;
                $this->pri[$c->id] = $c->price;
                $this->can[$c->id] = $c->quantity;*/
                $priceid = precios::where('producto', $c->producto)
                    ->where('medida', $c->unidad)
                    ->whereNull('deleted_at')
                    ->where('costosiva', $c->price)
                    ->first();
                if ($priceid) {
                    $this->uni[$c->id] = $c->unidad . '|' . $priceid->id;
                    $this->pri[$c->id] = $c->price;
                    $this->can[$c->id] = $c->quantity;
                } else {
                    // Eliminar desde tmpAjuste
                    tmpAjuste::where('id', $c->id)->delete();
                    // Limpia los datos en memoria
                    unset($this->uni[$c->id], $this->pri[$c->id], $this->can[$c->id]);
                }
                if ($this->sucursal) {
                    $inventario = Inventarios::where('producto', $c->producto)
                        ->where('sucursal', $this->sucursal)
                        ->first();
                    $existencia = $inventario->existencia ?? 0;
                } else {
                    $existencia = 0;
                }
                // Guardar existencia actual y ajustada por ID de producto temporal
                $this->existenciaActual[$c->id] = $existencia;
                if ($this->tipo == 'Egreso') {
                    $this->existenciaAjustada[$c->id] = $existencia - $c->ingreso;
                } else {
                    $this->existenciaAjustada[$c->id] = $c->ingreso + $existencia;
                }
            }
        }
        $this->total = tmpAjuste::where('usuario', $user_id)->sum('total');
        $this->itemsQuantity = tmpAjuste::where('usuario', $user_id)->count();
    }
    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
    public function render()
    {
        $user_id = Auth::user()->id;
        if (in_array(auth()->user()->profile, ['Super', 'Administrador'])) {
            // Usuarios con perfil alto ven todas las sucursales
            $sucursal = Sucursales::all();
        } else {
            // Usuarios normales solo ven su propia sucursal
            $sucursal = Sucursales::where('id', auth()->user()->sucursal)->get();
        } 
        $this->cart = tmpAjuste::where('usuario', $user_id)->orderby('id', 'desc')->get();
        return view('livewire.ajustes.nuevo-ajuste', ['sucursales' => $sucursal])
            ->extends('layouts.theme.app')
            ->section('content');
    }
    protected $listeners = [
        'Add' => 'ScanCodeById',
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'scan-code-byid' => 'ScanCode2',
        'print-ticket' => 'printTicket'
    ];
    public function ScanCodeById($id)
    {
        $this->increaseQty($id);
    }
    public function increaseQty($productId)
    {
        //dd($productId);
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
        $this->emit('focus-input', ['id' => $tmp->id]);
    }
    public function updateUni($id)
    {
        $user_id = Auth::user()->id;
        $valor = $this->uni[$id]; // Ejemplo: "22|5.75"
        [$medidaId, $precio] = explode('|', $valor);
        $uniNew = $medidaId;
        $tmp = tmpAjuste::find($id);
        $PreCan = Precios::find($precio);
        $tmp->unidad = $uniNew;
        $tmp->limit = $PreCan->cantidad;
        $tmp->price = $PreCan->costosiva;
        $tmp->ingreso = $tmp->quantity * $PreCan->cantidad;
        $tmp->total = $PreCan->costosiva * $tmp->quantity;
        $tmp->save();
        $this->Carrito();
    }
    public function updateQty($id)
    {
        $cantidades = $this->can[$id];
        // Validar cantidad
        if ($cantidades <= 0) {
            $this->can[$id] = 1; // o el valor mínimo permitido
            $this->emit('scan-notfound', 'La cantidad debe ser mayor a 1');
            return;
        }
        $user_id = Auth::user()->id;
        $tmp = tmpAjuste::find($id);
        if (!$tmp) {
            return; // El registro ya no existe, no hacer nada
        }
        $pre = Precios::where('producto', $tmp->producto)->where('medida', $tmp->unidad)->first();
        $tmp->quantity = $cantidades;
        $tmp->ingreso = $cantidades * $tmp->limit;
        $tmp->total = $tmp->price * $cantidades;
        $tmp->save();
        $this->Carrito();
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
            ->leftJoin('inventarios as i', function ($join) {
                $join->on('i.producto', '=', 'productos.id')
                    ->where('i.sucursal', $this->sucursal);
            })
            ->where('p.id', $barcode)
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
    public function ScanCode($barcode, $cant = 1)
    {
        //dd($barcode);
        $user_id = Auth::user()->id;
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->leftJoin('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', 'like', '%' . $barcode . '%')
            ->select('productos.id')
            ->first();
        if ($product) {
            $this->ScanCode2($product->id);
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
    public function clearCart()
    {
        $user_id = Auth::user()->id;
        $tmp = tmpAjuste::where('usuario', $user_id)->delete();
        $this->Carrito();
    }
    public function Store()
    {
        $user = Auth::user();
        $user_id = Auth::user()->id;
        $rules = [
            'sucursal' => 'required',
            'fecha' => 'required',
            'detalle' => 'required',
            'tipo'     => 'required',
        ];
        $messages = [
            'sucursal.required' => 'La sucursal es requerida',
            'fecha.required' => 'La fecha del ingreso es requerida',
            'detalle.required' => 'El detalle del ingreso es requerido',
            'tipo.required'     => 'El tipo de ajuste es requerido',
        ];
        $this->validate($rules, $messages);
        $items = tmpAjuste::where('usuario', $user_id)->get();
        if ($items->isEmpty()) {
            $this->emit('error', 'No hay items para procesar.');
            return;
        }
        $tipo = trim($this->tipo); // guardas el tipo exacto (Ingreso por Traslado)
        $tiposIngreso = [
            'Ingreso Fac. Comercial',
            'Ingreso por Traslado',
            'Ingreso',
        ];
        $tiposEgreso = [
            'Gastos',
            'Producto Dañado',
            'Producto Vencido',
            'Egreso',
        ];
        // Por defecto, si no cae en ingreso, se considera egreso (más seguro)
        $tipo_logica = in_array($tipo, $tiposIngreso, true) ? 'Ingreso' : 'Egreso';

        $appModo = config('app.modo', 'local');
        $sucursalModo = Sucursales::find($this->sucursal)?->modo ?? 'local';
        $esLocalYSeAplica = $sucursalModo === $appModo;

        try {
            $ajuste = Ajustes::create([
                'sucursal' => $this->sucursal,
                'fecha' => $this->fecha,
                'detalle' => $this->detalle,
                'status' => $esLocalYSeAplica ? 'Finalizado' : 'Ingresado',
                'tipo' => $tipo,
                'user' => Auth::user()->id,
                'aplicado_local' => $esLocalYSeAplica ? now() : null
            ]);
            foreach ($items as $item) {
                $existencia = Inventarios::where('producto', $item->producto)
                    ->where('sucursal', $this->sucursal)
                    ->first();
                if (!$existencia) {
                    $sucur = Sucursales::find($this->sucursal);
                    $existencia = Inventarios::create([
                        'producto' => $item->producto,
                        'empresa' => $sucur->empresa,
                        'sucursal' => $this->sucursal,
                        'existencia' => 0.00,
                    ]);
                }
                AjustesDetalles::create([
                    'ajuste' => $ajuste->id,
                    'producto' => $item->producto,
                    'inventario' => $existencia->id,
                    'medida' => $item->unidad,
                    'cantidad' => $item->quantity,
                    'ingreso' => $item->ingreso,
                    'costo' => $item->price,
                    'total' => $item->price * $item->quantity,
                ]);
                if ($esLocalYSeAplica) {
                    if ($tipo_logica == 'Ingreso') {
                        $nuevaExistencia = $item->ingreso + $existencia->existencia;
                        $existencia->existencia = $nuevaExistencia;
                        $existencia->save();
                        $saldoCantidad = $nuevaExistencia;
                        $saldoValor = $saldoCantidad * ($item->price / $item->limit);
                        Kardex::create([
                            'producto' => $item->producto,
                            'inventario' => $existencia->id,
                            'descripcion' => 'Ingreso por Ajuste ' . $this->detalle . ', realizado por ' . $user->name,
                            'fecha' => date('Y-m-d'),
                            'hora' => date('H:i:s'),
                            'ingresoCantidad' => $item->ingreso,
                            'ingresoValor' => $item->total,
                            'egresoCantidad' => 0.00,
                            'egresoValor' => 0.00,
                            'saldoCantidad' => $saldoCantidad,
                            'saldoValor' => $saldoValor,
                            'user' => Auth::user()->id
                        ]);
                    } else {
                        $nuevaExistencia = $item->ingreso > $existencia->existencia ? 0.00 : $existencia->existencia - $item->ingreso;
                        $existencia->existencia = $nuevaExistencia;
                        $existencia->save();
                        $ultiR = Kardex::where('producto', $item->producto)
                            ->where('inventario', $existencia->id)
                            ->latest()
                            ->first();
                        $saldoCantidad = $nuevaExistencia;
                        $saldoValor = $ultiR ? $ultiR->saldoValor - $item->total : 0;
                        Kardex::create([
                            'producto' => $item->producto,
                            'inventario' => $existencia->id,
                            'descripcion' => 'Egreso por Ajuste ' . $this->detalle . ', realizado por ' . $user->name,
                            'fecha' => date('Y-m-d'),
                            'hora' => date('H:i:s'),
                            'ingresoCantidad' => 0.00,
                            'ingresoValor' => 0.00,
                            'egresoCantidad' => $item->ingreso,
                            'egresoValor' => $item->total,
                            'saldoCantidad' => $saldoCantidad,
                            'saldoValor' => $saldoValor,
                            'user' => Auth::user()->id
                        ]);
                    }
                }
            }
            $this->clearCart();
            //$this->emit('item-added', 'Ajuste registrado con éxito');
            $this->emit('print-ticket2', $this->GenerarJsonAjuste($ajuste->id));
            $this->ResetUI();
            //return Redirect::to('/ajustes');
        } catch (\Exception $e) {
            // Manejo de errores
            $this->emit('item-error', 'Hubo un error al registrar el ajuste: ' . $e->getMessage());
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
