<?php
namespace App\Http\Livewire;
use App\Models\ActividadEconomica;
use App\Models\Compras;
use App\Models\ComprasDetalles;
use App\Models\CuentasPagar;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Facturadores;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Municipios;
use App\Models\Notificacion;
use App\Models\Pagos;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Proveedores;
use App\Models\Sucursales;
use App\Models\tipoCompras;
use App\Models\TipoPersona;
use App\Models\tmpCompras;
use App\Models\User;
use Darryldecode\Cart\Cart;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
class NuevaCompraController extends Component
{
    ///variables para el diseño
    public  $pageTitle, $componentName;
    //para agegar un proveedor
    public $nombre, $tipoPersona, $direccion, $telefono, $correo, $registro, $nit,
    $departamento, $municipio, $distrito, $actividad, $giro, $search, $selected_id, $departamentos, $municipios, $distritos, $proveedorAddSelectId, $proveedorAddSelectName, $select_id, $personas, $actividades, $sucursales, $sucursal, $categoria, $proveedorCompras;
    //para los datos de los productos
    public $total, $itemsQuantity, $fechaV = [], $can = [], $pri = [], $totals = [], $uni = [], $medis = [], $percepcion = 0, $subtotal, $iva, $totales, $existenciaActual = [], $nuevaExistencia = [];
    // para guardar la compra
    public $proveedorSelectId, $proveedorSelectName, $proveedorCompra, $factura, $correlativo, $serie, $fecha, $condiPago, $vendedor;
    public $productoName, $detallePrecios = [], $detalleEscalas = [];
    public function mount()
    {
        $user = Auth::user();
        $this->pageTitle = 'Nueva';
        $this->componentName = 'Compras';
        $this->personas = TipoPersona::all();
        $this->actividades = ActividadEconomica::all();
        $this->departamentos = Departamentos::all();
        $this->municipios = Municipios::all();
        $this->distritos = Distritos::all();
        if($user->profile == 'Super' || $user->profile == 'Administrador')
        {
            $this->sucursales = Sucursales::all();
        }
        else
        {
            $this->sucursales = Sucursales::where('id', $user->sucursal)->get();
            foreach($this->sucursales as $s)
            {
                $this->sucursal = $s->id;
            }
        }
        $this->cargaData();
    }
    public function updatedFactura()
    {
        $this->generarCostos(); // Ejecutar cuando se cambia la factura
        $this->cargaData();
    }
    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
    public function render()
    {
        $user_id = Auth::user()->id;
        $proveedor = Proveedores::all();
        $tipos = tipoCompras::all();
        $cart = tmpCompras::where('usuario', $user_id)->orderBy('id', 'desc')->get() ;
        return view('livewire.compras.nueva-compra', ['proveedores' => $proveedor, 'tipos' => $tipos, 'cart' =>  $cart])
        ->extends('layouts.theme.app')
        ->section('content');
    }
    public function SaveProveedor()
    {
        $rules = [
            'nombre' => "required|unique:proveedores|min:3",
            'tipoPersona' => 'required|not_in:Elegir TipoPersona',
            'telefono' => "required|unique:proveedores|min:3",
            'nit' => "numeric|unique:proveedores|min:3",
            'registro' => 'numeric',
            'departamento' => 'required|not_in:Elegir Departamento',
            'municipio' => 'required|not_in:Elegir Municipio',
            'distrito' => 'required|not_in:Elegir Distrito',
            //'giro' => "required|min:3",
            'proveedorAddSelectId' => 'required'
        ];
        $messages = [
            'proveedorAddSelectId.required' => 'La Actividad Econimica es Requerida',
            'nombre.required' => 'El Nombre del Proveedor es requerido',
            'nombre.unique' => 'El Nombre del Proveedor ya existe',
            'nombre.min'=> 'El Nombre del Proveedor debe tener mas de 3 caracteres',
            'tipoPersona.not_in' => 'El tipo de persona es Requerido',
            'telefono.required' => 'El Numero de telefono es requerido',
            'telefono.unique' => 'El numero de telefono ya existe',
            'telefono.min'=> 'El numero de telefono debe tener mas de 3 caracteres',
            'registro.numeric' => 'El Numero de registro es numerico',
            //'nit.required' => 'El Numero de NIT es requerido',
            'nit.unique' => 'El Numero de NIT ya existe',
            'nit.min'=> 'El Numero de NIT debe tener mas de 3 caracteres',
            'departamento.not_in' => 'El departamento es Requerido',
            'municipio.not_in' => 'El municipio es Requerido',
            'distrito.not_in' => 'El distrito es Requerido',
            //'giro.required' => 'El Giro del Proveedor es requerido',
            //'giro.min'=> 'El Giro del Proveedor debe tener mas de 3 caracteres',
            'nit.numeric' =>'El numero de Registro tiene que ser numerico',
        ];
        $this->validate($rules, $messages);
        $pro = Proveedores::create([
            'nombre' => $this->nombre,
            'tipoPersona' => $this->tipoPersona,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'registro' => $this->registro,
            'nit' => $this->nit,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->proveedorAddSelectId,
            'giro' => $this->giro,
            'desActividad'=>$this->proveedorAddSelectName,
            'categoria' => $this->categoria
        ]);
        $this->ResetPro();
        $this->emit('item-added', 'Proveedor Registrado');
    }
    public function ResetPro()
    {
        $this->nombre = '';
        $this->direccion = '';
        $this->telefono = '';
        $this->correo = '';
        $this->registro = '';
        $this->nit = '';
        $this->departamento = 'Elegir Departamento';
        $this->tipoPersona = 'Elegir Tipo de Persona';
        $this->municipio = 'Elegir Municipio';
        $this->distrito = 'Elegir distrito';
        $this->actividad = ' Elegir Actividad Economica';
        $this->giro = '';
        $this->departamento = '';
        $this->municipio = '';
        $this->distrito= '';
        $this->proveedorAddSelectId = '';
        $this->proveedorAddSelectName = '';
    }
    protected $listeners = [
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'Add' => 'ScanCodeById',
        'scan-code-byid' => 'ScanCode2',
        'print-ticket'=> 'printTicket'
    ];
    public function ScanCodeById($product)
    {
        $this->increaseQty($product);
    }
    public function ScanCode($barcode, $cant = 1)
    {
        $user = Auth::user();
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
        ->join('inventarios as i', 'i.producto', 'productos.id')
        ->where('p.codebar', 'like', '%' . $barcode . '%')
        ->where('i.sucursal', $user->sucursal)
        ->whereNull('p.deleted_at')
        ->whereNull('productos.deleted_at')
        ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.id as precio')
        ->first();
        if($product){
            $this->increaseQty($product->precio);
        }else{
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', 'like', '%' . $barcode . '%')
            //->where('i.sucursal', $user->sucursal)
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'i.sucursal', 'p.id as idpre', 'p.costociva')
            ->first();
            //$p = Precios::find($id);
            $insert = Inventarios::create([
                'producto' => $product->id,
                'empresa' => $user->empresa,
                'sucursal' => $user->sucursal,
                'existencia' => 0
            ]);
            $kardex = Kardex::create([
                'producto' => $product->id,
                'inventario' => $insert->id,
                'sucursal' => $user->sucursal,
                'descripcion' => 'Nuevo producto',
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'ingresoCantidad' => 0.00,
                'ingresoValor' => 0.00,
                'egresoCantidad' => 0.00,
                'egresoValor' => 0.00,
                'saldoCantidad' => 0.00,
                'saldoValor' => 0.00
             ]);
             $this->increaseQty($product->precio);
        }  //$this->emit('scan-notfound', 'Producto no encontrado');
    }
    public function InCart($id)
    {
        $user_id = Auth::user()->id;
         $exist = tmpCompras::find($id);
        if($exist)
            return true;
        else
            return false;
    }
    public function ScanCode2($barcode, $cant = 1)
    {
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
            $this->emit('item-error', 'Producto no encontrado, seleccione la sucursal', 'error'); // Notificación con SweetAlert
        }
    }
    public function increaseQty($id, $cant = 1)
    {
        //dd($id);
        $user = Auth::user();
        $p = Precios::find($id);
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
        ->join('inventarios as i', 'i.producto', 'productos.id')
        ->where('p.id', $id)
        ->whereNull('p.deleted_at')
        ->whereNull('productos.deleted_at')
        ->where('i.sucursal', $user->sucursal)
        ->select('productos.*', 'i.existencia', 'p.codebar', 'p.cantidad', 'i.id as inventario', 'p.costosiva', 'i.sucursal', 'p.id as idpre', 'p.costociva', 'p.medida as uni')
        ->first();
        if($product)
        {
            if($this->factura == 1)
            {
                $costo = $product->costosiva;
            }
            else
            {
                $costo = $product->costociva;
            }
            $tmp = tmpCompras::create([
                'producto' => $product->id,
                'name' => $product->nombreProducto,
                'price' => $costo,
                'newcosto' => 0.00,
                'quantity' => $cant,
                'sucursal' => $product->sucursal,
                'codebar' => $product->codebar,
                'vencimiento' => null,
                'total' => $costo * $cant,
                'medida' => $product->uni,
                'ingreso' => $product->cantidad * $cant,
                'idpre' => $product->idpre,
                'usuario' => $user->id
            ]);
        }
        else
        {
            $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.id', $id)
            ->where('i.sucursal', $user->sucursal)
            ->whereNull('p.deleted_at')
            ->whereNull('productos.deleted_at')
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'i.sucursal', 'p.id as idpre', 'p.costociva')
            ->first();
            //$p = Precios::find($id);
            if(!$product){
                $prod = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
                    ->join('inventarios as i', 'i.producto', 'productos.id')
                    ->where('p.id', $id)
                    //->where('i.sucursal', $user->sucursal)
                    ->whereNull('p.deleted_at')
                    ->whereNull('productos.deleted_at')
                    ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'i.sucursal', 'p.id as idpre', 'p.costociva')
                    ->first();
                $insert = Inventarios::create([
                    'producto' => $prod->id,
                    'empresa' => $user->empresa,
                    'sucursal' => $user->sucursal,
                    'existencia' => 0
                ]);
                $kardex = Kardex::create([
                    'producto' => $prod->id,
                    'inventario' => $insert->id,
                    'sucursal' => $user->sucursal,
                    'descripcion' => 'Nuevo producto',
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'ingresoCantidad' => 0.00,
                    'ingresoValor' => 0.00,
                    'egresoCantidad' => 0.00,
                    'egresoValor' => 0.00,
                    'saldoCantidad' => 0.00,
                    'saldoValor' => 0.00
                ]);
            }
            $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.id', $id)
            ->where('i.sucursal', $user->sucursal)
            ->whereNull('p.deleted_at')
            ->whereNull('productos.deleted_at')
            ->select('productos.*', 'i.existencia', 'p.codebar', 'p.cantidad', 'i.id as inventario', 'p.costosiva', 'i.sucursal', 'p.id as idpre', 'p.costociva')
            ->first();
             if($this->factura == 1)
            {
                $costo = $product->costosiva;
            }
            else
            {
                $costo = $product->costociva;
            }
            $tmp = tmpCompras::create([
                'producto' => $product->id,
                'name' => $product->nombreProducto,
                'price' => $costo,
                'newcosto' => 0.00,
                'quantity' => $cant,
                'sucursal' => $product->sucursal,
                'codebar' => $product->codebar,
                'vencimiento' => null,
                'total' => $costo * $cant,
                'medida' => $product->medida,
                'ingreso' => $product->cantidad * $cant,
                'idpre' => $product->idpre,
                'usuario' => $user->id
            ]);
        }
        $this->cargaData();
        $this->emit('focus-input', ['id' => $tmp->id]);
    }
    public function updateQty($id)
    {
        $cantidades = $this->can[$id];
        $user_id = Auth::user()->id;
        $exist = tmpCompras::where('usuario', $user_id)->find($id);
        $pre = Precios::where('producto', $exist->producto)->where('medida', $exist->medida)->orderBy('cantidad', 'asc')->first();
        $exist->quantity = $cantidades;
        $exist->total = $cantidades * $exist->price;
        $exist->ingreso = $pre->cantidad * $cantidades;
        $exist->save();
        $this->cargaData();
    }
    public function removeItem($id)
    {
        $user_id = Auth::user()->id;
        $exist = tmpCompras::find($id);
        $exist->delete();
        $this->cargaData();
    }
    public function clearCart()
    {
        $user_id = Auth::user()->id;
        tmpCompras::where('usuario', $user_id)->delete();
        $this->cargaData();
    }
    public function updateFV($id)
    {
        $user_id = Auth::user()->id;
        $fehcaVencimiento = $this->fechaV[$id];
        $exist = tmpCompras::find($id);
        $exist->vencimiento = $fehcaVencimiento;
        $exist->save();
        $this->cargaData();
    }
    public function updateTotal($id)
    {
        $user_id = Auth::user()->id;
        $tot = $this->totals[$id];
        $exist = tmpCompras::find($id);
        $quantity = $exist->quantity;
        $vencimiento = $exist->vencimiento;
        $ingreso = $exist->ingreso;
        $exist->total = $tot;
        $exist->price = $tot / $exist->quantity;
        $exist->newcosto = $tot / $exist->quantity;
        $exist->save();
        $this->cargaData();
    }
    public function updatePre($id)
    {
        $user_id = Auth::user()->id;
        $priceNew = $this->pri[$id];
        $exist = tmpCompras::find($id);
        $quantity = $exist->quantity;
        $vencimiento = $exist->vencimiento;
        $ingreso = $exist->ingreso;
        $exist->newcosto = $priceNew;
        $exist->total = $priceNew * $exist->quantity;
        $exist->save();
        $this->cargaData();
    }
    public function updateUni($id)
    {
        $user_id = Auth::user()->id;
        $uniNew = $this->uni[$id];
        $exist = tmpCompras::find($id);
        $PreCan = Precios::find($this->uni[$id]);
        //dd($PreCan = Precios::where('medida',$uniNew)->where('producto', $exist->producto)->first());
        /*$product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
        ->join('inventarios as i', 'i.producto', 'productos.id')
        ->where('p.producto', $exist->producto)
        ->where('p.medida', $uniNew)
        //->where('i.sucursal', session('sucursal'))
        ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.id as idpre')
        ->first();*/
        //$quantity = $exist->quantity;
        //$price = $PreCan->costosiva;
        //$vencimiento = $exist->vencimiento;
        //$ingresoCantidad = $PreCan->cantidad * $exist->quantity;
        $exist->quantity = $PreCan->cantidad;
        $exist->price = $PreCan->costosiva ;
        $exist->newcosto = $PreCan->costosiva ;
        $exist->medida = $PreCan->medida;
        $exist->ingreso = $PreCan->cantidad;
        $exist->total = $exist->price * $exist->quantity;
        $exist->idpre = $uniNew;
        $exist->save();
        $this->cargaData();
    }
    public function Store()
    {
        $user_id = Auth::user()->id;
        $horaActual = date('H:i:s');
        $rules = [
            'proveedorSelectId' => 'required',
            'factura' => 'required|not_in:Elegir',
            'correlativo' => 'required',
            'fecha' => 'required',
            'condiPago' => 'required|not_in:Elegir',
            'sucursal' => 'required|not_in:Elegir',
        ];
        $messages = [
            'proveedorSelectId.required' => 'El nombre del proveedor es requerido',
            'factura.required' => 'El campo factura es requerido',
            'factura.not_in' => 'Elige un tipo de factura diferente de Elegir',
            'correlativo.required' => 'Digite el correlativo de la factura',
            'fecha.required' => 'El campo fecha es requerido',
            'condiPago.required' => 'El campo condicion de pago es requerido',
            'condiPago.not_in' => 'Elige una condicion de pago diferente de Elegir',
            'sucursal.required' => 'La sucursal es requerido',
            'sucursal.not_in' => 'Elige una sucursal diferente de Elegir',
        ];
        $this->validate($rules, $messages);
        $ultimoNumeroCompra = Compras::orderBy('numero', 'desc')->select('numero')->first();
        $nuevoNumeroCompra = $ultimoNumeroCompra ? ($ultimoNumeroCompra->numero + 1) : 1;
        $estadoPago = ($this->condiPago === 'Credito') ? 'Pendiente' : 'Pagado';
        $subtotal = tmpCompras::where('usuario', $user_id)->sum('total');
        if($this->factura == 1)
        {
            $iva = $subtotal * 0.13;
        }
        else
        {
            $iva = 0.00;
        }
        $total = $subtotal + $iva + $this->percepcion;
        DB::beginTransaction();
        try
        {
            \Log::info('INICIO Store Compra', [
                'user_id' => $user_id,
                'sucursal' => $this->sucursal,
                'condiPago' => $this->condiPago,
                'estadoPago' => $estadoPago,
                'items_count' => tmpCompras::where('usuario', $user_id)->count(),
            ]);
            $compra = Compras::create([
                'numero' => $nuevoNumeroCompra,
                'tipo' => $this->factura,
                'correlativo' => $this->correlativo,
                'serie' => $this->serie,
                'fecha' => $this->fecha,
                'condi_pago' => $this->condiPago,
                'vendedor' => $this->vendedor,
                'estado' => $estadoPago,
                'fechaPago' => null,
                'proveedor' => $this->proveedorSelectId,
                'user' => $user_id,
                'sucursal' => $this->sucursal,
                'total' => $total,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'percepcion' => $this->percepcion
            ]);
            if($compra)
            {
                $items = tmpCompras::where('usuario', $user_id)->get();
                foreach($items as $item)
                {
                    //dd($item->attributes['vencimiento']);
                    if(empty($item->vencimiento) || $item->vencimiento == '0000-00-00')
                    {
                        $venci= null;
                    }
                    else
                    {
                        $venci = $item->vencimiento;
                    }
                    $costo = ($item->newcosto == 0.00) ? $item->price : $item->newcosto;
                    ComprasDetalles::create([
                        'compra' => $compra->id,
                        'producto' => $item->producto,
                        'medida' => $item->medida,
                        'cantidad' => $item->quantity,
                        'ingreso' => $item->ingreso,
                        'costo' => $costo,
                        'total' => $item->total,
                        'fechaVencimiento' => $venci,
                    ]);
                    $existencia = Inventarios::where('producto', $item->producto)->where('sucursal', $this->sucursal)->first();
                    if(!$existencia)
                    {
                        $sucursal = Sucursales::find($this->sucursal);
                        if(!$sucursal)
                        {
                            throw new \RuntimeException('No se encontró la sucursal seleccionada.');
                        }
                        $existencia = Inventarios::create([
                            'producto' => $item->producto,
                            'empresa' => $sucursal->empresa,
                            'sucursal' => $sucursal->id,
                            'existencia' => 0
                        ]);
                        \Log::info('Inventario creado en Store Compra', [
                            'inventario_id' => $existencia->id,
                            'producto' => $item->producto,
                            'sucursal' => $this->sucursal,
                        ]);
                    }
                    $nuevaExistencia = $item->ingreso + $existencia->existencia;
                    $existencia->existencia = $nuevaExistencia;
                    $existencia->save();
                    \Log::info('Inventario actualizado en Store Compra', [
                        'producto' => $item->producto,
                        'existencia_id' => $existencia->id,
                        'nueva_existencia' => $nuevaExistencia,
                    ]);
                    $saldoCantidad = $nuevaExistencia;
                    $saldoValor = $costo * $saldoCantidad;
                    \Log::info('Intentando crear kardex en Store Compra', [
                        'producto' => $item->producto,
                        'inventario' => $existencia->id,
                        'sucursal' => $this->sucursal,
                        'ingresoCantidad' => $item->ingreso,
                        'ingresoValor' => $costo * $item->ingreso,
                        'saldoCantidad' => $saldoCantidad,
                        'saldoValor' => $saldoValor,
                    ]);
                    try {
                        $kardex = Kardex::create([
                            'producto' => $item->producto,
                            'inventario' => $existencia->id,
                            'sucursal' => $this->sucursal,
                            'descripcion' => 'Compra de productos a ' . $this->proveedorSelectName . ' Factura ' .$this->correlativo,
                            'fecha' => $this->fecha,
                            'hora' => $horaActual,
                            'ingresoCantidad' => $item->ingreso,
                            'ingresoValor' => $costo * $item->ingreso,
                            'egresoCantidad' => 0.00,
                            'egresoValor' => 0.00,
                            'saldoCantidad' => $saldoCantidad,
                            'saldoValor' => $saldoValor
                        ]);
                        \Log::info('Kardex creado en Store Compra', [
                            'kardex_id' => $kardex->id,
                            'producto' => $item->producto,
                        ]);
                    } catch (\Throwable $kardexError) {
                        \Log::error('ERROR creando kardex en Store Compra', [
                            'producto' => $item->producto,
                            'message' => $kardexError->getMessage(),
                            'file' => $kardexError->getFile(),
                            'line' => $kardexError->getLine(),
                        ]);
                        throw $kardexError;
                    }
                    // Actualizar precio si se modificó el costo
                    if($item->newcosto <> 0.00)
                    {
                        $precios = Precios::find($item->idpre);
                        $ppventa = ((($item->newcosto * 1.13) * $precios->cantidad ) * $precios->utilidad / 100) + (($item->newcosto * 1.13) * $precios->cantidad);
                        if (auth()->user()->can('Actualiza_Costo'))
                        {
                            $precios->costosiva = $item->newcosto;
                            $precios->costociva = $item->newcosto * 1.13;
                        }
                        $precios->save();
                        // Generar notificación
                        $usuarios = User::where('status', 'ACTIVE')->get();
                        foreach($usuarios as $u)
                        {
                            Notificacion::create([
                                'titulo' => 'Actualizacion de Precios',
                                'descripcion' => 'Se ha actualizado el precio de venta del producto ' . $item->name . ' a $ ' . number_format($ppventa, 2),
                                'user' => $u->id,
                                'leido' => 0
                            ]);
                        }
                    }
                    \Log::info('Item procesado en Store Compra', [
                        'producto' => $item->producto,
                        'ingreso' => $item->ingreso,
                        'existencia_id' => $existencia->id,
                        'nueva_existencia' => $nuevaExistencia,
                    ]);
                }
            }
            /**Manejar Cuentas por Pagar o Pagos**/
            if ($this->condiPago === 'Credito')
            {
                // Registrar deuda en `cuentas_pagar`
                CuentasPagar::create([
                    'compra' => $compra->id,
                    'proveedor' => $this->proveedorSelectId,
                    'monto_total' => $total,
                    'saldo' => $total,
                    'estado' => 'pendiente',
                    'fecha_vencimiento' => now()->addDays(30),
                ]);
            }
            else
            {
                // Registrar deuda en `cuentas_pagar`
                $cuenta = CuentasPagar::create([
                    'compra' => $compra->id,
                    'proveedor' => $this->proveedorSelectId,
                    'monto_total' => $total,
                    'saldo' => 0.00,
                    'estado' => 'pagada',
                    'fecha_vencimiento' => now(),
                ]);
                // Registrar pago en `pagos`
                Pagos::create([
                    'correlativo' => Pagos::max('correlativo') + 1,
                    'fecha' => $this->fecha,
                    'hora' => $horaActual,
                    'user' => $user_id,
                    'concepto' => 'Compra al contado - Factura ' . $this->correlativo,
                    'total' => $total,
                    'cuenta_pagar' => $cuenta->id,
                    'tipo_pago' => 1
                ]);
            }
            DB::commit();
            $this->clearCart();
            $this->emit('item-added', 'Compra registrada con exito');
            $this->ResetUI();
            return Redirect::to('/compras');
        }
        catch(\Throwable $e)
        {
            DB::rollback();
            \Log::error('ERROR Store Compra', [
                'message' => $e->getMessage(),
            ]);
            $this->emit('scan-notfound', $e->getMessage());
        }
    }
    public function ResetUI()
    {
        $this->proveedorSelectId = '';
        $this->proveedorSelectName = '';
        $this->proveedorCompra = '';
        $this->factura = '';
        $this->correlativo = '';
        $this->serie = '';
        $this->fecha = '';
        $this->condiPago = '';
        $this->vendedor = '';
    }
    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->departamento)->get();
    }
    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->municipio)->get();
    }
    public function cargaData()
    {
        $user_id = Auth::user()->id;
        $data = tmpCompras::where('usuario', $user_id)->get();
        $this->itemsQuantity = tmpCompras::where('usuario', $user_id)->count();
        //$this->subtotal =  tmpCompras::where('usuario', $user_id)->sum('total');
        if($this->factura == 1)
        {
            $s = tmpCompras::where('usuario', $user_id)->sum('total');
            $this->total = $s * 1.13;
            $this->subtotal =  $s;
            $this->iva = $this->total - $s;
            $this->totales = $s + $this->iva;
        }
        else
        {
            $this->subtotal =  $this->total;
            $this->iva = 0.00;
            $this->totales = tmpCompras::where('usuario', $user_id)->sum('total');
            $this->total = tmpCompras::where('usuario', $user_id)->sum('total');
        }
        if($data){
            foreach($data as $r)
            {
                $this->fechaV[$r->id] = $r->vencimiento;
                $this->can[$r->id] = $r->quantity;
                $this->totals[$r->id] = $r->total;
                $this->pri[$r->id] = ($r->newcosto === null || $r->newcosto == 0.00) ? $r->price : $r->newcosto;
                $this->uni[$r->id] = $r->medida;
                if ($this->sucursal) {
                    $inventario = Inventarios::where('producto', $r->producto)
                        ->where('sucursal', $this->sucursal)
                        ->first();
                    $existencia = $inventario->existencia ?? 0;
                } else {
                    $existencia = 0;
                }
                // Guardar existencia actual y ajustada por ID de producto temporal
                $this->existenciaActual[$r->id] = $existencia;
                $this->nuevaExistencia[$r->id] = $r->ingreso + $existencia;
            }
        }
        $this->totales =  $this->total + $this->percepcion;
        $this->calcularPercepcion();
    }
    public function CalcularPercicionProveedor()
    {
        //$prove = Proveedores::find($this->proveedorSelectId);
        //dd($prove);
        //if($prove && $prove->categoria === 'GRANDE')
        //{
            //$this->percepcion = number_format(($this->total * 0.01), 2);
        //}
        //else
        //{
            //$this->percepcion = 0;
        //}
    }
    public function calcularPercepcion()
    {
        $tipoFactura = tipoCompras::find($this->factura);
        $proveedor = Proveedores::find($this->proveedorSelectId);
        if($tipoFactura){
            if ($proveedor && $proveedor->categoria == 'GRANDE' && $this->total >= 113) {
                if($tipoFactura->id == 1){
                    $this->percepcion = round($this->subtotal * 0.01, 2);
                    $this->totales = $this->subtotal + $this->percepcion + $this->iva;
                }elseif($tipoFactura->id == 2){
                    $operacion = $this->subtotal / 1.13;
                    $this->percepcion = round($operacion * 0.01, 2);
                    $this->totales = $this->subtotal + $this->percepcion;
                }
            } else {
                $this->percepcion = 0.00;
            }
        }
    }
    public function generarCostos()
    {
        $user = Auth::user();
        $tmp = tmpCompras::where('usuario', $user->id)->get(); // Carga todos los datos de tmpCompras
        $esFactura = $this->factura == 1;
        foreach ($tmp as $t) {
            // 🔹 Obtener el precio del producto correspondiente en la tabla Precios
            $pre = Precios::where('id', $t->idpre)->first(); // Asegúrate de que la relación sea correcta
            if (!$pre) {
                continue; // 🔹 Si no encuentra el precio, omite esta iteración
            }
            $costo = $esFactura ? $pre->costosiva : $pre->costociva;
            // 🔹 Seleccionar el costo correcto según el tipo de factura
            $t->price = $costo;
            // 🔹 Calcular el total si newcosto es NULL
            if ($t->newcosto == 0.00) {
                $t->total =  $costo * $t->quantity;
            }
            $t->save();
        }
        $this->cargaData(); // Recargar datos después de la actualización
    }
    public function obtenerProductos(){
        return;
    }
}
