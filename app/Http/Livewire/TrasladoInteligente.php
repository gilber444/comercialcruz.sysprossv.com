<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Solicitudes;
use App\Models\SolicitudesDetalles;
use App\Models\Sucursales;
use App\Models\tmpSolicitudes;
use App\Models\User;
use App\Models\TrasladoInteligente as TrasladoInteligenteModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Exception;


class TrasladoInteligente extends Component
{

    use WithPagination;

    public  $origen, $origen1, $destino, $destino1, $search, $selected_id, $correlativo, $fecha, $detalle, $itemsQuantity, $total, $can = [], $empre, $sucu, $ca, $rol, $cart=[], $nombreTienda1, $nombreTienda2, $modalItemId, $modalCantidad, $productoSeleccionado=null, $precioMostrarInfo=null, $sucursales, $existencias = [], $products = [], $nombreProductoSeleccionado;
    private $pagination = 7;

    public function mount()
    {
        $user = Auth::user();
        $this->rol = $user->profile;
        $bus = Solicitudes::orderBy('id', 'desc')->first();

        $this->fecha = date('Y-m-d H:i:s');

        if ($bus)
        {
            $this->correlativo = $bus->numero + 1;
        }
        else
         {
            // Si no se encontraron registros, puedes asignar un valor predeterminado
            $this->correlativo = 1;
        }
        $items = Productos::orderBy('id'); // Reemplaza esto con tu lógica para obtener los ítems

        foreach ($items as $item) {
            $this->can[$item->id] = null;
        }
        $user = Auth::user();

        if ($user->profile === 'Super' || $user->profile === 'Administrador' || $user->profile === 'Auditor')
        {
            $this->origen1 = Sucursales::with('Rempresa')->orderBy('nombre')->get();
            $this->destino1 = Sucursales::with('Rempresa')->OrderBy('nombre')->get();
        }
        else
        {
            $this->origen1 = Sucursales::with('Rempresa')->find($user->sucursal);
            $this->destino1 = Sucursales::with('Rempresa')->OrderBy('nombre')->get();
            //$this->destino = $this->destino1->id;
            $this->origen = $this->origen1->id;

        }

        $this->setDefaultFecha();

        $this->empre = $user->empresa;
        $this->sucu = $user->sucursal;
        $this->sucursales = Sucursales::all();

        $this->Carrito();

    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.traslado_inteligente.traslado_inteligente')            
        ->extends('layouts.theme.app')
        ->section('content');;
    }

    public function Carrito()
    {
        $usuario = Auth::user();
        $this->cart = TrasladoInteligenteModel::where('usuario', $usuario->id)
        ->orderBy('producto_destino', 'asc')->get();


        $data_inputs = TrasladoInteligenteModel::where('usuario', $usuario->id)
        ->orderBy('producto_destino', 'asc')->first();
        if($data_inputs){

            $sucursalOrigen = Sucursales::where('nombre', $data_inputs->sucursal_origen)->first();
            $this->origen = $sucursalOrigen->id;
            $this->nombreTienda1 = $sucursalOrigen->nombre;
    
            $sucursalDestino = Sucursales::where('nombre', $data_inputs->sucursal_destino)->first();
            $this->destino = $sucursalDestino->id;
            $this->nombreTienda2 = $sucursalDestino->nombre;
        }

    }


    public function Preliminar(){

        $rules = [
            'destino' => 'required',
            'origen' => 'required',
            'fecha' => 'required'
        ];

        $messages = [
            'destino.required' => 'Seleccione el destino de traslado',
            'origen.required' => 'Seleccione el origen de traslado',
            'fecha.required' => 'la fecha es requerido'
        ];

        $this->validate($rules, $messages);


        $usuario = Auth::user();

        // ELIMINAR DATOS ANTERIORES
        TrasladoInteligenteModel::where('usuario', $usuario->id)->delete();

        // CONSULTA DE FECHAS
        $fecha_actual = Carbon::now()->format('Y-m-d'); 
        $fecha_hace_30_dias = Carbon::now()->subDays(30)->format('Y-m-d'); 

        // CONSULTA DE DATOS PARA SACAR EL PRELIMINAR
        $dataPreliminar = DB::table('ventas as v')
        ->join('ventas_detalles as vd', 'v.id', '=', 'vd.id')  
        ->join('productos as p', 'vd.producto', '=', 'p.id') 
        ->join('sucursales as s', 'v.sucursal', '=', 's.id')  
        ->leftJoin('inventarios as i', function ($join) {
            $join->on('p.id', '=', 'i.producto')
                 ->on('i.sucursal', '=', 'v.sucursal');
        })  // Relación con inventarios en la sucursal 2
        ->leftJoin('sucursales as s12', function ($join) {
            $join->on('s12.id', '=', DB::raw($this->origen));  
        })
        ->leftJoin('inventarios as i12', function ($join) {
            $join->on('p.id', '=', 'i12.producto')
                 ->on('i12.sucursal', '=', DB::raw($this->origen));  
        })
        ->select(
            's.nombre as sucursal',
            'p.nombreProducto as producto',
            DB::raw('COUNT(vd.id) as veces_vendido'),
            DB::raw('COALESCE(i.existencia, 0) as cantidad_en_inventario'),
            DB::raw('(COALESCE(i.existencia, 0) - COUNT(vd.id)) as diferencia_inventario'),
            's12.nombre as sucursal_12',
            DB::raw('COALESCE(i12.existencia, 0) as cantidad_sucursal_12')
        )
        ->where('v.sucursal', $this->destino)
        ->whereBetween('v.fecha', [$fecha_hace_30_dias, $fecha_actual])
        ->groupBy('s.nombre', 'p.nombreProducto', 'i.existencia', 's12.nombre', 'i12.existencia')
        ->orderByDesc(DB::raw('veces_vendido'))
        ->get();


        if($dataPreliminar->isNotEmpty()){
            // INSERTANDO LA DATA DEL PRELIMINAR EN LA TABLA TEMPORAL
            foreach ($dataPreliminar as $data) {
                $existencia_origen = $data->cantidad_sucursal_12 ?? 0; // Si no existe, se asume 0

                $producto = Productos::where('nombreProducto', $data->producto)->first();
                $existenciaPrecio = Precios::where('producto', $producto->id)
                ->where('cantidad', 1)->first();
                $cantidad_traslado = $data->veces_vendido;
                if($existencia_origen > 0  && $existenciaPrecio && $cantidad_traslado <= $existencia_origen){
                    TrasladoInteligenteModel::create([
                        'sucursal_destino'      => $data->sucursal,
                        'producto_destino'      => $data->producto,
                        'num_ventas'            => $data->veces_vendido,
                        'existencia_destino'    => $data->cantidad_en_inventario,
                        'diferencia_inventario' => $data->diferencia_inventario,
                        'sucursal_origen'       => $data->sucursal_12,
                        'existencia_origen'     => $existencia_origen,
                        'cantidad_traslado'     => $cantidad_traslado,
                        'usuario'     => $usuario->id,
                    ]);
                }


            }

            $this->emit('item-added', 'Se creo el preliminar');
        }
        else{
            $this->emit('item-error', 'La tienda destino no tuvo movimientos para hacer un traslado');
        }


        $this->Carrito();


    }

    public function EditarPreliminar($id, $valor){
        $consulta = TrasladoInteligenteModel::where('id', $id)->first();
        if($valor <= $consulta->existencia_origen){
            TrasladoInteligenteModel::where('id', $id)
            ->update(['cantidad_traslado' => $valor]);

            $this->emit('item-confirmar', 'Se actualizo el valor');
        }
        else{
            $this->emit('item-error', 'Inventario insuficiente');
        }

    }

    public function EliminarFilaPreliminar($id){
        TrasladoInteligenteModel::where('id', $id)->delete();
        $this->resetUIEdit();
    }


    public function EliminarPreliminar(){
        $usuario = Auth::user();
        // ELIMINAR DATOS ANTERIORES
        TrasladoInteligenteModel::where('usuario', $usuario->id)->delete();

        $this->emit('item-confirmar', 'Se elimino el preliminar');
        $this->resetUI();
        
        $this->destino = '';
        $this->origen = '';
        $this->fecha = '';

        $this->setDefaultFecha();
    }

    public function TrasladoInteligente(){
        $usuario = Auth::user();

        try {
            $data_inputs = TrasladoInteligenteModel::where('usuario', $usuario->id)
            ->orderBy('producto_destino', 'asc')->first();

            $sucursalOrigen = Sucursales::where('nombre', $data_inputs->sucursal_origen)->first();
            $origen= $sucursalOrigen->id;

            $sucursalDestino = Sucursales::where('nombre', $data_inputs->sucursal_destino)->first();
            $destino = $sucursalDestino->id;

            

            // INSERTAR EL ENCABEZADO EN SOLICITUDES 
            $solicitud = Solicitudes::create([
                'origen'      => $origen,
                'destino'     => $destino,
                'numero'      => $this->correlativo,
                'fecha'       => $this->fecha,
                'detalle'     => "Traslado de {$data_inputs->sucursal_origen} a {$data_inputs->sucursal_destino}",
                'solicitante' => $usuario->id,
                'estado'      => 'Despachado'
            ]);

            $idSolicitud = $solicitud->id;

            // INSERTAR LOS PRODUCTOS EN SOLICITUDES 
            $productos = TrasladoInteligenteModel::where('usuario', $usuario->id)
            ->orderBy('producto_destino', 'asc')->get();
            foreach ($productos as $p){
                # busqueda del id del producto
                $producto = Productos::where('nombreProducto', $p->producto_destino)->first();

                # busqueda del costo en el inventario
                $precio = Precios::where('producto', $producto->id)
                ->where('cantidad', 1)->first();
            

                SolicitudesDetalles::create([
                    'solicitud'  => $idSolicitud, 
                    'producto'   => $producto->id,
                    'origen'     => $origen,
                    'destino'    => $destino,
                    'cantidad'   => $p->cantidad_traslado,
                    'costo'      => $precio->costosiva,
                    'total'      => $p->cantidad_traslado * $precio->costosiva,
                    'realizado'  => $usuario->id,
                    'autorizado' => $usuario->id,
                    'despachado' => $usuario->id,
                    'ingresado'  => $usuario->id,
                    'estado'     => 'Despachado'
                ]);

                // DESCARGO AL INVENTARIO ORIGEN
                $descargoOrigen = Inventarios::where('sucursal', $origen)
                ->where('producto', $producto->id)->first();

                $restaInventarioOrigen = $descargoOrigen->existencia - $p->cantidad_traslado;

                Inventarios::where('id', $descargoOrigen->id)
                ->update(['existencia' => $restaInventarioOrigen]);


                // ABONO AL INVENTARIO DESTINO
                $CargoDestino = Inventarios::where('sucursal', $destino)
                ->where('producto', $producto->id)->first();

                $sumaInventarioDestino = $CargoDestino->existencia + $p->cantidad_traslado;

                Inventarios::where('id', $CargoDestino->id)
                ->update(['existencia' => $sumaInventarioDestino]);


                //REGISTRO EN KARDEX
                $kar = Kardex::create([
                    'producto' => $producto->id,
                    'inventario' => $origen,
                    'descripcion' => 'Despacho de producto de la sucursal ' . $data_inputs->sucursal_origen . ' para la sucursal ' . $data_inputs->sucursal_destino,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:s:i'),
                    'ingresoCantidad' => 0,
                    'ingresoValor' => 0,
                    'egresoCantidad' => $p->cantidad_traslado,
                    'egresoValor' => $p->cantidad_traslado * $precio->costosiva,
                    'saldoCantidad' => $restaInventarioOrigen,
                    'saldoValor' => ($restaInventarioOrigen * $precio->costosiva)
                ]);
            }



            // ELIMINACION DE LA TABLA TEMPORAL
            TrasladoInteligenteModel::where('usuario', $usuario->id)->delete();

            $this->emit('item-confirmar', 'Traslado Procesado con exito');
            $this->resetUI();
                    
            $this->destino = '';
            $this->origen = '';
            $this->fecha = '';
            return Redirect::to('/solicitudesVer');

        } catch (Exception $e) {
            DB::rollback();
            $this->emit('scan-notfound', $e->getMessage());
        }
    }


    public function resetUI()
    {
        $this->selected_id = 0;
        $this->Carrito();
        $this->resetValidation();
        $this->resetPage();
    }

    public function resetUIEdit()
    {
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
        $this->Carrito();
    }

    public function mostrarInfoProducto($id)
    {
        $this->productoSeleccionado = TrasladoInteligenteModel::find($id);
        $producto = Productos::where('nombreProducto', $this->productoSeleccionado->producto_destino)->first();

        $this->nombreProductoSeleccionado = $producto->nombreProducto;
        $this->loadExistencias($producto->id);
        $this->dispatchBrowserEvent('mostrar-modal-producto');
    }

    protected $listeners = [
        'loadExistencias' => 'loadExistencias',
        'resetSearch' => 'resetSearch',
    ];

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

    

    public function liveSearch()
    {
        $user = Auth::user();

        if (strlen($this->search) > 0) {
            $this->reset('products'); // Limpia la variable antes de actualizar
            $this->products = Productos::join('categorias as c', 'c.id', 'productos.categoria')
                ->join('medidas as m', 'm.id', 'productos.medida')
                ->join('inventarios as i', 'i.producto', 'productos.id')
                ->select('productos.id', 'm.unidad as medida', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia')
                ->where('i.sucursal', $user->sucursal)
                ->where(function ($query) {
                    $query
                        ->where('productos.nombreProducto', 'like', "%{$this->search}%")
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
            $this->products = [];
        }
    }

    public function setDefaultFecha()
    {
        $this->fecha = now()->format('Y-m-d\TH:i');
    }

    
}
