<?php

namespace App\Http\Livewire;

use App\Models\ActividadEconomica;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Inventarios;
use App\Models\Municipios;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Proveedores;
use App\Models\Sucursales;
use App\Models\tempTomaOrden;
use App\Models\tipoCompras;
use App\Models\TipoPersona;
use App\Models\TomaOrden;
use App\Models\TomaOrdenDetalle;
use Carbon\Carbon;
use Darryldecode\Cart\Cart;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class NuevaTomaOrdenController extends Component
{
    ///variables para el diseño
    public  $pageTitle, $componentName;

    //para agegar un proveedor
    public $nombre, $tipoPersona, $direccion, $telefono, $correo, $registro, $nit,
        $departamento, $municipio, $distrito, $actividad, $giro, $search, $selected_id, $departamentos, $municipios, $distritos, $proveedorAddSelectId, $proveedorAddSelectName, $select_id, $personas, $actividades, $sucursales, $sucursal, $categoria;

    //para los datos de los productos
    public $total, $itemsQuantity, $fechaV = [], $can = [], $pri = [], $uni = [], $medis = [], $percepcion = 0, $totales, $subtotal, $iva;

    // para guardar la toma de orden
    public $proveedorSelectId, $proveedorSelectName, $proveedorToma, $factura, $fecha, $condiPago, $cart = [], $NombreProductoSeleccionado;

    //cargar existencias
    public $productoSeleccionado, $existencias;
    public $selectedRow = null;

    public function mount()
    {
        $this->fecha = Carbon::now()->format('Y-m-d');

        $this->pageTitle = 'Nueva';
        $this->componentName = 'Toma de orden';

        $this->personas = TipoPersona::all();
        $this->actividades = ActividadEconomica::all();
        $this->departamentos = Departamentos::all();
        $this->municipios = Municipios::all();
        $this->distritos = Distritos::all();
        $this->sucursales = Sucursales::with('Rempresa:id,empresa')->get();

        $user_id = Auth::user()->id;

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

        return view('livewire.toma_ordens.nueva-toma', [
            'proveedores' => $proveedor,
            'tipos' => $tipos,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function obtenerProductos()
    {
        $user_id = Auth::user()->id;
        if ($this->proveedorSelectId) {
            DB::table('temp_toma_ordens')->where('usuario', $user_id)->delete();

            $compras = DB::table('compras')
                ->where('proveedor', $this->proveedorSelectId) // Usar la variable correcta
                ->pluck('id');

            if ($compras->isNotEmpty()) {
                $productos = DB::table('compras_detalles')
                    ->join('productos', 'compras_detalles.producto', '=', 'productos.id')
                    ->join('precios', 'precios.producto', '=', 'productos.id')
                    ->whereIn('compras_detalles.compra', $compras)
                    ->select(
                        'precios.producto',
                        'productos.nombreProducto',
                        'precios.codebar',
                        'precios.cantidad',
                        'precios.costosiva',
                        'precios.pvventa',
                        'precios.medida'
                    )
                    ->distinct()
                    ->get();

                foreach ($productos as $producto) {
                    $existe = DB::table('temp_toma_ordens')
                        ->where('producto', $producto->producto)
                        ->where('codebar', $producto->codebar)
                        ->exists();

                    if (!$existe) {
                        DB::table('temp_toma_ordens')->insert([
                            'producto' => $producto->producto,
                            'name' => $producto->nombreProducto,
                            'priceCompra' => $producto->costosiva,
                            'priceVenta' => $producto->pvventa,
                            'quantity' => 0,
                            'codebar' => $producto->codebar,
                            'sucursal' => Auth::user()->sucursal,
                            'vencimiento' => null,
                            'total' => 0.00,
                            'medida' => $producto->medida,
                            'ingreso' => 0,
                            'usuario' => $user_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        }
        $this->cargaData();
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
            'nombre.min' => 'El Nombre del Proveedor debe tener mas de 3 caracteres',
            'tipoPersona.not_in' => 'El tipo de persona es Requerido',
            'telefono.required' => 'El Numero de telefono es requerido',
            'telefono.unique' => 'El numero de telefono ya existe',
            'telefono.min' => 'El numero de telefono debe tener mas de 3 caracteres',
            'registro.numeric' => 'El Numero de registro es numerico',
            //'nit.required' => 'El Numero de NIT es requerido',
            'nit.unique' => 'El Numero de NIT ya existe',
            'nit.min' => 'El Numero de NIT debe tener mas de 3 caracteres',
            'departamento.not_in' => 'El departamento es Requerido',
            'municipio.not_in' => 'El municipio es Requerido',
            'distrito.not_in' => 'El distrito es Requerido',
            //'giro.required' => 'El Giro del Proveedor es requerido',
            //'giro.min'=> 'El Giro del Proveedor debe tener mas de 3 caracteres',
            'nit.numeric' => 'El numero de Registro tiene que ser numerico',
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
            'desActividad' => $this->proveedorAddSelectName,
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
        $this->distrito = '';
        $this->proveedorAddSelectId = '';
        $this->proveedorAddSelectName = '';
    }

    protected $listeners = [
        'loadExistencias' => 'loadExistencias',
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'scan-code-byid' => 'ScanCodeById',
        'print-ticket' => 'printTicket'
    ];

    public function ScanCodeById($product)
    {
        $this->increaseQty($product);
    }

    public function ScanCode($barcode, $cant = 1)
    {
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', 'like', '%' . $barcode . '%')
            ->where('i.sucursal', session('sucursal'))
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.cantidad', 'i.sucursal', 'p.id as precio')
            ->first();

        $this->increaseQty($product->precio);
    }

    public function InCart($id)
    {
        $user_id = Auth::user()->id;

        $exist = tempTomaOrden::find($id);
        if ($exist)
            return true;
        else
            return false;
    }

    public function increaseQty($id, $cant = 0)
    {
        $user_id = Auth::user()->id;

        // Obtener los detalles del producto
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.id', $id)
            //->where('i.sucursal', session('sucursal')) // Si necesitas filtrar por sucursal, descomenta esta línea
            ->select('productos.*', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.costosiva', 'p.pvventa', 'i.sucursal')
            ->first();

        // Verificar si ya existe el producto en tempTomaOrden con la misma unidad de medida
        $existingProduct = tempTomaOrden::where('producto', $id)
            ->where('medida', $product->medida)
            ->first();

        if ($existingProduct) {
            // Si el producto ya existe, actualizar la cantidad sumando 1
            $existingProduct->update([
                'quantity' => $existingProduct->quantity + $cant,
                'total' => $existingProduct->priceCompra * ($existingProduct->quantity + $cant)
            ]);
        } else {
            // Si no existe, crear un nuevo registro
            tempTomaOrden::create([
                'producto' => $id,
                'name' => $product->nombreProducto,
                'priceCompra' => $product->costosiva,
                'priceVenta' => $product->pvventa,
                'quantity' => $cant,
                'codebar' => $product->codebar,
                'sucursal' => Auth::user()->sucursal,
                'vencimiento' => null,
                'total' => 0.00,
                'medida' => $product->medida,
                'ingreso' => $cant,
                'usuario' => $user_id
            ]);
        }

        // Recargar la data y emitir la notificación de éxito
        $this->cargaData();
        $this->emit('scan-ok');
    }


    public function updateQty($id)
{
    $cantidades = $this->can[$id] ?? null;

    if ($cantidades === null) {
        return;
    }

    $user_id = Auth::id();

    $exist = tempTomaOrden::where('usuario', $user_id)->where('id', $id)->first();

    if (!$exist) {
        return;
    }

    $pre = Precios::where('producto', $exist->producto)
        ->where('medida', $exist->medida)
        ->first();

    if (!$pre) {
        return;
    }

    if ($cantidades > 0) {
        $exist->quantity = $cantidades;
        $exist->total = $cantidades * $exist->priceCompra;
        $exist->ingreso = $pre->cantidad * $cantidades;
    } else {
        $exist->quantity = 0.00;
        $exist->total = 0.00;
        $exist->ingreso = 0;
    }

    $exist->save();
    // Después de regenerar, enviamos un evento para volver a enfocar
    $this->dispatchBrowserEvent('focus-after-update', ['id' => $id]);

    $this->cargaData(); // Aquí Livewire regenera el HTML

    
}



    public function removeItem($id)
    {
        $user_id = Auth::user()->id;

        // Buscar el registro
        $item = tempTomaOrden::where('usuario', $user_id)->where('id', $id)->first();

        if ($item) {
            $item->delete();
            $this->cargaData(); // Recargar la lista después de eliminar
        } else {
            session()->flash('error', 'El producto no existe o ya fue eliminado.');
        }
    }


    public function clearCart()
    {
        $user_id = Auth::user()->id;
        tempTomaOrden::where('usuario', $user_id)->delete();
        $this->cargaData();
    }


    public function Store()
    {
        $user_id = Auth::user()->id;

        $horaActual = date('H:i:s');

        $rules = [
            'proveedorSelectId' => 'required',
            'factura' => 'required|not_in:Elegir',
            'fecha' => 'required',
            'condiPago' => 'required|not_in:Elegir',
            'sucursal' => 'required|not_in:Elegir',

        ];

        $messages = [
            'proveedorSelectId.required' => 'El nombre del proveedor es requerido',
            'factura.required' => 'El campo factura es requerido',
            'factura.not_in' => 'Elige un tipo de factura diferente de Elegir',
            'fecha.required' => 'El campo fecha es requerido',
            'condiPago.required' => 'El campo condicion de pago es requerido',
            'condiPago.not_in' => 'Elige una condicion de pago diferente de Elegir',
            'sucursal.required' => 'La sucursal es requerido',
            'sucursal.not_in' => 'Elige una sucursal diferente de Elegir',
        ];
        $this->validate($rules, $messages);

        $ultimoNumeroToma = TomaOrden::where('tipo', $this->factura)
            ->orderBy('numero', 'desc')
            ->select('numero')
            ->first();

        $nuevoNumeroToma = $ultimoNumeroToma ? ($ultimoNumeroToma->numero + 1) : 1;


        $estadoPago = ($this->condiPago === 'credito') ? 'Pendiente' : 'Procesado';

        DB::beginTransaction();


        try {
            $toma = TomaOrden::create([
                'numero' => $nuevoNumeroToma,
                'tipo' => $this->factura,
                'fecha' => $this->fecha,
                'condi_pago' => $this->condiPago,
                'estado' => $estadoPago,
                'proveedor' => $this->proveedorSelectId,
                'user' => $user_id,
                'sucursal' => $this->sucursal
            ]);

            if ($toma) {
                $items = tempTomaOrden::where('usuario', $user_id)->where('quantity', '>', 0)->get();
                foreach ($items as $item) {
                    //dd($item->attributes['vencimiento']);
                    if (empty($item->vencimiento) || $item->vencimiento == '0000-00-00') {
                        $venci = null;
                    } else {
                        $venci = $item->vencimiento;
                    }

                    TomaOrdenDetalle::create([
                        'tomaOrden' => $toma->id,
                        'producto' => $item->producto,
                        'medida' => $item->medida,
                        'cantidad' => $item->quantity,
                        'ingreso' => $item->ingreso,
                        'costo' => $item->priceCompra,
                        'total' => $item->total,
                        'fechaVencimiento' => $venci,
                    ]);
                }
            }

            DB::commit();

            $this->clearCart();
            $this->emit('item-added', 'Toma de orden registrada con exito');
            $this->ResetUI();
            return Redirect::to('/toma_ordens');
        } catch (Exception $e) {
            DB::rollback();
            $this->emit('scan-notfound', $e->getMessage());
        }
    }

    public function selectRow($id)
    {
        $this->selectedRow = $id;
        $this->dispatchBrowserEvent('highlightRow', ['id' => $id]);
        $this->loadExistencias($id); // O lo que desees ejecutar
    }

    public function loadExistencias($productId = null)
    {
        // Asignar el producto seleccionado
        $this->productoSeleccionado = $productId;

        if ($productId) {
            // Obtener existencias en todas las sucursales para ese producto
            $this->existencias = Inventarios::where('producto', $productId)->get();
        } else {
            // Si no hay un producto, asignar 0 a las existencias
            $this->existencias = collect([['cantidad' => 0]]);
        }

        // Emitir evento para actualizar la fila seleccionada
        $this->emit('updateSelectedRow', $productId);
    }


    public function ResetUI()
    {
        $this->proveedorSelectId = '';
        $this->proveedorSelectName = '';
        $this->proveedorToma = '';
        $this->factura = '';
        //$this->correlativo = '';
        //$this->serie = '';
        $this->fecha = '';
        $this->condiPago = '';
        //$this->vendedor = '';
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

        $data = tempTomaOrden::where('usuario', $user_id)->get();
        $this->cart = tempTomaOrden::where('usuario', $user_id)->get();


        $this->total = tempTomaOrden::where('usuario', $user_id)->sum('total');
        $this->totales = tempTomaOrden::where('usuario', $user_id)->sum('total');
        $this->subtotal = tempTomaOrden::where('usuario', $user_id)->sum('total');
        $this->itemsQuantity = tempTomaOrden::where('usuario', $user_id)->count();

        // if ($data) {
        //     foreach ($data as $r) {
        //         $this->can[$r->id] = $r->quantity;
        //         $this->pri[$r->id] = $r->price;
        //         $this->uni[$r->id] = $r->medida;
        //     }
        // }
        $this->CalcularPercicionProveedor();
    }

    public function CalcularPercicionProveedor()
    {
        $prove = Proveedores::find($this->proveedorSelectId);
        //dd($prove);
        if ($prove && $prove->categoria === 'GRANDE') {
            $this->percepcion = number_format(($this->total * 0.01), 2);
        } else {
            $this->percepcion = 0;
        }
    }

    public function calcularPercepcion()
    {
        $user_id = Auth::user()->id;

        $this->totales =  ($this->total + ($this->total * 0.13)) + $this->percepcion;

        $this->mount();
    }

    //ABRIR MODAL DEL DETALLE DE PRODUCTO
    public function mostrarInfoProducto($id)
    {
        $this->productoSeleccionado = tempTomaOrden::find($id);
        $producto = Productos::where('nombreProducto', $this->productoSeleccionado->name)->first();

        $this->NombreProductoSeleccionado = $producto->nombreProducto;
        $this->loadExistencias($producto->id);
        $this->dispatchBrowserEvent('mostrar-modal-producto');
    }
}
