<?php

namespace App\Http\Livewire;

use App\Helpers\Convertidor;
use App\Models\AmbienteDestino;
use App\Models\Caja;
use App\Models\Cortes;
use App\Models\DetalleNotaCredito;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Facturadores;
use App\Models\Inventarios;
use App\Models\ModeloFacturacion;
use App\Models\NotaCredito;
use App\Models\Parametros;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\resumenDte;
use App\Models\TempNotaCredito;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\TipoTransmision;
use App\Models\Tocken;
use App\Models\Tributo;
use App\Models\User;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use App\Traits\GenerarToken;
use App\Traits\NumeroControlTrait;
use App\Traits\RecepcionDTENota;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class NuevaNotaCreditoController extends Component
{
    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $detalle = '', $itemsQuantity,$creditosFiscales, $correlativo, $fiscal, $fecha, $total, $cart=[], $can = [], $subtotal, $iva, $totales, $percepcion, $cos = [], $products = [], $productoSeleccionado=null, $existencias = [], $origen, $name=[], $totalVenta;

    use GenerarToken;
    use WithPagination;
    use WithFileUploads;
    use NumeroControlTrait;
    use RecepcionDTENota;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Planillas';

        $ultimoCorrelativo = NotaCredito::withTrashed()->max('correlativo');

        if ($ultimoCorrelativo){
            $this->correlativo = $ultimoCorrelativo ? $ultimoCorrelativo + 1 : 1;;
        }
        else{
            // Si no se encontraron registros, puedes asignar un valor predeterminado
            $this->correlativo = 1;
        }
        $this->fecha = date('Y-m-d H:i:s');

        $this->creditosFiscales = Ventas::where('facturador', 3)
        ->orderBy('fecha', 'desc')
        ->orderBy('hora', 'desc')
        ->orderBy('correlativo', 'desc')
        ->get();


        $this->Carrito();

    }

    public function render()
    {
        $user = Auth::user();
        $this->liveSearch();
        return view('livewire.nueva_nota_credito.nueva_nota_credito')
        ->extends('layouts.theme.app')
        ->section('content');
        ;;
    }

    protected $listeners = [
        'GenerarDTE' => 'GenerarDTE',
    ];

    public function Carrito(){

        $usuario = Auth::user();
        $this->cart = TempNotaCredito::where('usuario', $usuario->id)->get();
        $this->itemsQuantity = TempNotaCredito::where('usuario', $usuario->id)->get()->sum('cantidad');
        $this->total = TempNotaCredito::where('usuario', $usuario->id)->get()->sum('total');

        $registro = TempNotaCredito::where('usuario', auth()->id())->with('Rventas')->first();

        $productos = TempNotaCredito::all();

        if ($registro) {
            $this->fiscal = $registro->Rventas->id;
            $this->totalVenta = $registro->Rventas->total;
        }

        if($this->fiscal){
            $cliente = Ventas::with('Rclientes:id,categoria')->find($this->fiscal);

            // 1. Subtotal = suma sin IVA
            $this->subtotal = $productos->sum('total');

            // 2. IVA = 13% del subtotal
            $this->iva = $this->subtotal * 0.13;

            // 3. Total con IVA incluido
            $this->totales = $this->subtotal + $this->iva;

            // 4. Percepción (1% del total con IVA)
            if($cliente->Rclientes->categoria == 'GRANDE' && $this->subtotal > 100)
            {
                $this->percepcion = 0;
            }
            else
            {
                $this->percepcion = 0;
            }
            //$this->percepcion = $this->subtotal * 0.01;
        }

        foreach($this->cart as $item)
        {
            $this->can[$item->id] = $item->cantidad;
            $this->cos[$item->id] = $item->precio;
            $this->name[$item->id] = $item->name;
        }


        $ultimoCorrelativo = NotaCredito::withTrashed()->max('correlativo');

        if ($ultimoCorrelativo){
            $this->correlativo = $ultimoCorrelativo ? $ultimoCorrelativo + 1 : 1;;
        }
        else{
            // Si no se encontraron registros, puedes asignar un valor predeterminado
            $this->correlativo = 1;
        }

    }

    public function CargarTempNotas(){
        $usuario = Auth::user();

        // ELIMINAR DATOS ANTERIORES
        TempNotaCredito::where('usuario', $usuario->id)->delete();

        $data = VentasDetalles::where('venta', $this->fiscal)->get();
        if($data->isNotEmpty()){
            foreach($data as $d){
                TempNotaCredito::create([
                    'venta' => $d->venta,
                    'producto' => $d->producto,
                    'medida'=> $d->medida,
                    'name'=> $d->name,
                    'unidad'=> $d->unidad,
                    'descargar'=> $d->descargar,
                    'cantidad'=> $d->cantidad,
                    'precio' => $d->precio / 1.13,
                    'descuento'=> $d->descuento,
                    'subtotal'=> $d->subtotal,
                    'iva'=> $d->iva,
                    'total'=> ($d->cantidad * $d->precio) / 1.13,
                    'costo'=> $d->costo,
                    'costo_total'=> $d->costo_total,
                    'utilidad_uni'=> $d->utilidad_uni,
                    'utilidad'=> $d->utilidad,
                    'usuario' => $usuario->id
                ]);
            }
            $this->emit('item-added', 'Se creo el preliminar');
            $this->search = '';
            $this->selected_id = 0;
            $this->resetValidation();
            $this->resetPage();
        }else{
            $this->emit('item-error', 'La tienda destino no tuvo movimientos para hacer un traslado');
            $this->search = '';
            $this->selected_id = 0;
            $this->resetValidation();
            $this->resetPage();
        }
        $this->Carrito();
    }

    public function updateCosto($id){
        $actualizarPrecio = $this->cos[$id];
        $consulta = TempNotaCredito::where('id', $id)->first();
        $consultaVenta = VentasDetalles::where('venta', $consulta->venta)
        ->where('producto', $consulta->producto)->first();

        if($consultaVenta){
            $precioVenta = $consultaVenta->precio / 1.13;
            if(round($actualizarPrecio, 4) <= round($precioVenta, 4)){
                TempNotaCredito::where('id',$id)
                ->update([
                    'precio' => $actualizarPrecio,
                ]);
                $this->updateQty($id);
            }
            else{
                $this->emit('item-error', 'El Precio es mayor al de la venta');
            }
        }
        elseif ($actualizarPrecio > 0){
            $dataPrecio = Precios::where('producto', $consulta->producto)
            ->where('presentacion', $consulta->unidad)
            ->first();
            if($actualizarPrecio <= $dataPrecio->pvventa ){
                TempNotaCredito::where('id', $id)->update([
                    'precio' => $actualizarPrecio,
                ]);
                $this->updateQty($id);
                $this->emit('item-added', 'Precio actualizado');
            }
            else{
                $this->emit('item-error', 'El Precio es mayor al registrado en precios');
            }
        }
        else {
            $this->emit('item-error', 'No se encontró detalle de venta para validar Precio');
        }



        $this->Carrito();
    }

    public function updateName($id){
        $nombre = $this->name[$id];
        $consulta = TempNotaCredito::where('id', $id)->first();
        if($consulta && $nombre){
            TempNotaCredito::where('id', $id)->update([
                'name' => $nombre,
            ]);
            $this->emit('item-added', 'Descripcion Actualizada');
        }
        else{
            $this->emit('item-error', 'No se pudo realizar la actualizacion');
        }

    }

    public function updateQty($id)
    {
        $cantidadProducto = $this->can[$id];
        $consulta = TempNotaCredito::where('id', $id)->first();
        $consultaVenta = VentasDetalles::where('venta', $consulta->venta)
            ->where('producto', $consulta->producto)
            ->first();

        if ($consultaVenta) {
            if ($cantidadProducto <= $consultaVenta->cantidad) {
                TempNotaCredito::where('id', $id)->update([
                    'cantidad' => $cantidadProducto,
                    'descargar' => $cantidadProducto,
                    'total' => $cantidadProducto * $consulta->precio
                ]);
                $this->emit('item-added', 'Cantidad actualizada');
            } else {
                $this->emit('item-error', 'La cantidad del producto es mayor al de la venta');
            }
        } elseif ($cantidadProducto > 0) {
            // Si no hay registro en ventas pero sí una cantidad válida
            $data = VentasDetalles::where('venta', $this->fiscal)->first();
            $dataVenta = Ventas::where('id',$data->venta)->first();
            $producto = TempNotaCredito::where('id', $id)->first();
            $validacion = Inventarios::where('sucursal', $dataVenta->sucursal)
            ->where('producto', $producto->producto)->first();

            if($cantidadProducto <= $validacion->existencia){
                TempNotaCredito::where('id', $id)->update([
                    'cantidad' => $cantidadProducto,
                    'descargar' => $cantidadProducto,
                    'total' => $cantidadProducto * $consulta->precio
                ]);
                $this->emit('item-added', 'Cantidad actualizada');
            }else{
                $this->emit('item-error', 'La cantidad del producto es mayor al del inventario');
            }

        } else {
            $this->emit('item-error', 'No se encontró detalle de venta para validar cantidad');
        }

        $this->Carrito();
    }


    public function removeItem($id)
    {
        $delete = TempNotaCredito::find($id)->delete();
        $this->Carrito();
    }

    public function Store(){
        // INSERTAR EN NOTA CREDITO
        $usuario = Auth::user();
        $dataVenta = Ventas::find($this->fiscal);
        $dataTempNotaCredito = TempNotaCredito::where('usuario', $usuario->id)->get();
        $para = Parametros::find($dataVenta->caja);

        if($para->dte == "Si")
        {
            $control = $this->obtenerCodigoDTENota(5, $dataVenta->caja);
            $this->GenerarToken();
            $codigoGeneracion = strtoupper(Str::uuid()->toString());
            $fechaHoy = Carbon::now()->toDateString();
            $tokenActivo = Tocken::where('estado', 'ok')->whereDate('fecha', $fechaHoy)->first();
            $tipo = 'DTE';
        }
        else
        {
            $control = NULL;
            $codigoGeneracion = NULL;
            $tipo = 'Fisico';

        }

        $notaCredito = NotaCredito::create([
            'cliente'         => $dataVenta->cliente,
            'tipoPago'        => $dataVenta->tipoPago,
            'facturador'      => $dataVenta->facturador,
            'correlativo'     => $this->correlativo,
            'fecha'           => now()->format('Y-m-d'),
            'hora'            => now()->format('H:i:s'),
            'tipo'            => $dataVenta->tipo,
            'codigo'          => $codigoGeneracion,
            'numero'          => $control,
            'sello'           => null,
            'vendedor'        => $usuario->id,
            'caja'            => $dataVenta->caja,
            'sucursal'        => $dataVenta->sucursal,
            'empresa'         => $dataVenta->empresa,
            'subtotal'        => $this->total,
            'descuento'       => $dataVenta->descuento,
            'iva'             => $this->iva,
            'percepcion'      => $this->percepcion,
            'total'           => $this->totales,
            'estado'          => $dataVenta->estado,
            'qr'              => null,
            'codigoVendedor'  => $dataVenta->codigoVendedor,
            'envio'           => 1,
            'venta'           => $dataVenta->id,
        ]);
        $idNotaCredito = $notaCredito->id;

        // INSERTAR EN DETALLE_NOTA_CREDITO
        foreach($dataTempNotaCredito as $d){
            DetalleNotaCredito::create([
                'venta'          => $d->venta,
                'producto'       => $d->producto,
                'medida'         => $d->medida,
                'name'           => $d->name,
                'unidad'         => $d->unidad,
                'descargar'      => $d->descargar,
                'cantidad'       => $d->cantidad,
                'precio'         => $d->precio,
                'descuento'      => $d->descuento,
                'subtotal'       => $d->subtotal,
                'iva'            => $d->iva,
                'total'          => $d->total,
                'costo'          => $d->costo,
                'costo_total'    => $d->costo_total,
                'utilidad_uni'   => $d->utilidad_uni,
                'utilidad'       => $d->utilidad,
                'usuario'        => $d->usuario,
                'nota_credito'   => $idNotaCredito,
            ]);
        }

        // INSERTAR EN VENTA (SALDO NEGATIVO)
        $searchNotaCredito = NotaCredito::find($idNotaCredito);
        $venta = Ventas::create([
            'cliente' => $searchNotaCredito->cliente,
            'tipoPago'=> $searchNotaCredito->tipoPago,
            'facturador' => 5,
            'correlativo'=> $para->crecorrelativo,
            'fecha' => now()->format('Y-m-d'),
            'hora' => now()->format('H:i:s'),
            'tipo'=> $searchNotaCredito->tipo,
            'codigo'=> $searchNotaCredito->codigo,
            'numero'=> $searchNotaCredito->numero,
            'sello'=> $searchNotaCredito->sello,
            'vendedor'=> $searchNotaCredito->vendedor,
            'caja'=> $searchNotaCredito->caja,
            'sucursal'=> $searchNotaCredito->sucursal,
            'empresa'=> $searchNotaCredito->empresa,
            'subtotal'=> -1 * $searchNotaCredito->subtotal, // SALDO NEGATIVO POR NOTA CREDITO
            'descuento'=> -1 * $searchNotaCredito->descuento,// SALDO NEGATIVO POR NOTA CREDITO
            'iva'=> -1 * $searchNotaCredito->iva, // SALDO NEGATIVO POR NOTA CREDITO
            'percepcion'=> -1 * $searchNotaCredito->percepcion, // SALDO NEGATIVO POR NOTA CREDITO
            'total'=> -1 * $searchNotaCredito->total, // SALDO NEGATIVO POR NOTA CREDITO
            'estado'=> $searchNotaCredito->estado,
            'qr'=> null,
            //'codigoVendedor'=> $searchNotaCredito->codigoVendedor,
            'envio'=> 1,
        ]);

        // INSERTAR EN DETALLE VENTA (SALDO NEGATIVO)
        foreach($dataTempNotaCredito as $d){
            VentasDetalles::create([
                'venta'          => $d->venta,
                'producto'       => $d->producto,
                'medida'         => $d->medida,
                'name'           => $d->name,
                'unidad'         => $d->unidad,
                'descargar'      => $d->descargar,
                'cantidad'       => $d->cantidad,
                'precio'         => $d->precio,
                'descuento'      => -1 * $d->descuento, // SALDO NEGATIVO POR NOTA CREDITO
                'subtotal'       => -1 * $d->subtotal, // SALDO NEGATIVO POR NOTA CREDITO
                'iva'            => -1 * $d->iva, // SALDO NEGATIVO POR NOTA CREDITO
                'total'          => -1 * $d->total, // SALDO NEGATIVO POR NOTA CREDITO
                'costo'          => $d->costo,
                'costo_total'    => $d->costo_total,
                'utilidad_uni'   => $d->utilidad_uni,
                'utilidad'       => $d->utilidad
            ]);
        }
        // INSERTAR EN CAJA (CORRESPONDIENTE AL DE LA VENTA)

        $corte = Cortes::where('caja', $searchNotaCredito->caja)
            ->where('sucursal', $searchNotaCredito->sucursal)
            ->where('empresa', $searchNotaCredito->empresa)
            //->where('fecha', date('Y-m-d'))
            ->select('id')
            ->first();

        //$corte = Cortes::where('caja', session('caja'))->where('sucursal', session('sucursal'))->where('empresa', session('empresa'))->where('fecha', date('Y-m-d'))->select('id')->first();

        $ca = Caja::create([
            'caja' => $searchNotaCredito->caja,
            'sucursal' => $searchNotaCredito->sucursal,
            'empresa' => $searchNotaCredito->empresa,
            'corte' => $corte->id,
            'venta' => $venta->id,
            'facturador' => 5,
            'tipoPago' => 1,
            'correlativo' => $venta->correlativo,
            'codigo' => $codigoGeneracion,
            'numero' => $control,
            'sello' => null,
            'fecha' => now()->format('Y-m-d'),
            'hora'  => now()->format('H:i:s'),
            'cajero' => Auth::user()->id,
            'comprobante' => null,
            'efectivo' => -1 * $searchNotaCredito->total,
            'cambio' => 0,
            'subtotal' => -1 * $searchNotaCredito->subtotal,
            'descuento' => -1 * $searchNotaCredito->descuentos,
            'iva' => -1 * $searchNotaCredito->iva,
            'percepcion' => -1 * $searchNotaCredito->percepcion,
            'total' => -1 * $searchNotaCredito->sub,
            'estado' => 'Cancelado',
            'arqueado' => false,
            'envio' => 1,
        ]);
        // SI EL VALOR DE LOS PRODUCTOS DE LA NOTA DE CREDITO ES MENOR AL DE LA VENTA , REGRESAR EL PRODUCTO AL INVENTARIO DE LA SUCURSAL

        // INSERT A LA TABA DETE
        $user = Auth::user();
        $emprea = Empresas::find($user->empresa);

        $ambiente = AmbienteDestino::find($emprea->ambiente);
        $tipoDte = TipoDocumento::where('status', 'Activo')->where('codigo', '05')->first();
        $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
        $tipoOpera = TipoTransmision::where('status', 'Activo')->first();
        $tipoContingencia = TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first();
        $tributo = Tributo::where('status', 'Activo')->whereNull('codigo')->first();
        $condipago =  1;

        if($para->dte == "Si")
        {
            $dte = dte::create([
                'motivoContin' => null,
                'version' => 3,
                'ambiente' => $ambiente->id,
                'tipoDte' => $tipoDte->id,
                'numeroControl' => $control,
                'codigoGeneracion' => $codigoGeneracion,
                'tipoModelo' => $tipoModelo->id,
                'tipoOperacion' => $tipoOpera->id,
                'tipoContingencia' => $tipoContingencia->id,
                'fecEmi' => date('Y-m-d'),
                'horEmi' => date('H:i:s'),
                'tipoMoneda' => 'USD',
                'documentoRelacionado' => $dataVenta->id,
                'emisor' => session('sucursal') ?? 1,
                'receptor' => $dataVenta->cliente,
                'otrosDocuentos' => null,
                'ventaTercero' => null,
                'venta' => $venta->id,
                'tocken' => $tokenActivo->id,
                'sello' => null,
                'estado' => 'Creado',
                'jsonDte' => null,
                'caja' => session('caja') ?? 1,
                'sucursal' => session('sucursal') ?? 1,
                'empresa' => session('empresa') ?? 1,
            ]); 

            $detalleDTE = resumenDte::create([
                'dte' => $dte->id,
                'totalNoSuj' => 0,
                'totalExenta' => 0,
                'totalGravada' => $searchNotaCredito->total,
                'totalIva' => $searchNotaCredito->iva,
                'subTotalVentas' => $searchNotaCredito->total,
                'descuNoSuj' => 0,
                'descuExenta' => 0,
                'descuGravada' => 0,
                'porcentajeDescuento' => 0,
                'totalDescu' => 0,
                'tributo' => $tributo->id,
                'codigo' => null,
                'descripcion' => null,
                'valor' => null,
                'subTotal' => $searchNotaCredito->subtotal,
                'ivaPerci1' => $searchNotaCredito->percepcion,
                'ivaRete1' => 0,
                'reteRenta' => 0,
                'montoTotalOperacion' => $searchNotaCredito->total,
                'totalNoGravado' => 0,
                'totalPagar' => $searchNotaCredito->total,
                'totalLetras' => strtoupper(Convertidor::montoALetras(round($searchNotaCredito->total, 2))),
                'saldoFavor' => 0,
                'condicionOperacion' => $condipago,
                'pagos' => 1,
                'montoPagado' => null,
                'refencia' => null,
                'palzo' => null,
                'periodo' => null,
                'numPagoElectronico' => null,
            ]);
        }

        $this->clearCart();
        $this->emit('item-added', 'Nota de credito creada');
        // COLOCARLO DENTRO DE UN TRY
        if ($para->dte == 'Si') {
            if ($para->dteAutomatico == 'Si') {
                $this->emit('startProcessing2', $dte->id);
            } else {
                $this->ImprimirTicket($ca->id);
            }
        } else {
            $this->ImprimirTicket($ca->id);
        }
        $this->resetUI();

       // return Redirect::to('/nueva_nota_credito');
    }

    public function ImprimirTicket($id)
    {
        $this->emit('print-ticket', $this->TraitTikets($id));
    }


    public function clearCart()
    {
        $user_id = Auth::user()->id;
        $delete = TempNotaCredito::where('usuario', $user_id)->delete();
        $this->Carrito();
    }

    public function resetUI()
    {
        $this->correlativo = 0;
        $this->fiscal = 0;
        $this->fecha = '';
        $this->total = 0;
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }


    public function liveSearch()
    {
        $user = Auth::user();
        $this->origen = $this->fiscal ? Ventas::find($this->fiscal)?->sucursal : null;
        if (strlen($this->search) > 0 ) {
            $this->reset('products'); // Limpia la variable antes de actualizar
            if($user->profile == 'Auditor' || $user->profile == 'Super' && $this->origen){
                $this->products = Productos::join('categorias as c', 'c.id', 'productos.categoria')
                ->join('medidas as m', 'm.id', 'productos.medida')
                ->join('inventarios as i', 'i.producto', 'productos.id')
                ->join('precios as p', 'p.producto', 'productos.id')
                ->select('productos.id', 'm.unidad as medida', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia', 'p.pvventa')
                ->where('i.sucursal', $this->origen)
                ->where(function ($query) {
                    $query
                        ->where('productos.nombreProducto', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar1', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar2', 'like', "%{$this->search}%")
                        ->orWhere('productos.codebar3', 'like', "%{$this->search}%")
                        ->orWhere('productos.codealternativo', 'like', "%{$this->search}%");
                })
                ->groupBy('productos.id', 'm.unidad', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia', 'p.pvventa')
                ->orderBy('productos.nombreProducto', 'asc')
                ->take(50)
                ->get();
            }else{
                $this->products = Productos::join('categorias as c', 'c.id', 'productos.categoria')
                    ->join('medidas as m', 'm.id', 'productos.medida')
                    ->join('inventarios as i', 'i.producto', 'productos.id')
                    ->join('precios as p', 'p.producto', 'productos.id')
                    ->select('productos.id', 'm.unidad as medida', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia', 'p.pvventa')
                    ->where('i.sucursal', $user->sucursal)
                    ->where(function ($query) {
                        $query
                            ->where('productos.nombreProducto', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar1', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar2', 'like', "%{$this->search}%")
                            ->orWhere('productos.codebar3', 'like', "%{$this->search}%")
                            ->orWhere('productos.codealternativo', 'like', "%{$this->search}%");
                    })
                    ->groupBy('productos.id', 'm.unidad', 'productos.nombreProducto', 'productos.codebar1', 'productos.codebar3', 'productos.codealternativo', 'i.existencia', 'p.pvventa')
                    ->orderBy('productos.nombreProducto', 'asc')
                    ->take(50)
                    ->get();
            }


        } else {
            $this->products = [];
        }
    }


    public function AddProductNota($id){
        $usuario = Auth::user();
        $data = VentasDetalles::where('venta', $this->fiscal)->first();
        $producto = Productos::find($id);
        $precio = Precios::where('producto', $producto->id)->first();
        if($data){
            TempNotaCredito::create([
                'venta' => $data->venta,
                'producto' => $producto->id,
                'medida'=> $producto->medida,
                'name'=> $producto->nombreProducto,
                'unidad'=> $producto->Rmedidas->unidad,
                'descargar'=> 0,
                'cantidad'=> 0,
                'precio' => $precio->pvventa,
                'descuento'=> 0,
                'subtotal'=> 0,
                'iva'=> 0,
                'total'=> 0,
                'costo'=> $precio->costosiva,
                'costo_total'=> 0,
                'utilidad_uni'=> 0,
                'utilidad'=> $precio->utilidad,
                'usuario' => $usuario->id
            ]);

            $this->emit('item-added', 'Se añadio el producto');
            $this->search = '';
            $this->selected_id = 0;
            $this->resetValidation();
            $this->resetPage();
        }else{
            $this->emit('item-error', 'Ocurrio un error');
            $this->search = '';
            $this->selected_id = 0;
            $this->resetValidation();
            $this->resetPage();
        }
        $this->Carrito();
    }

    //////Generar DTE de forma automatica
    public function confirmProcessing($id)
    {
        // Emitir el evento para mostrar la alerta de procesamiento
        $this->emit('processingDTE');

        // Llamar a la función GenerarDTE
        $this->GenerarDTE($id);
    }

    public function GenerarDTE($id)
    {
        try {
            $dte = dte::find($id);

            if (!$dte) {
                throw new \Exception("No se encontró el DTE con ID {$id}.");
            }

            $this->RecepcionDTENota($id);

            $dte2 = dte::find($id);

            if (!$dte2) {
                throw new \Exception("No se pudo recargar el DTE con ID {$id}.");
            }

            if ($dte2->estado == 'PROCESADO') {
                $this->emit('item-addedd', 'DTE Firmado y Procesado correctamente.', null);
            } elseif ($dte2->estado == 'RECHAZADO') {
                $this->emit('item-errorr', 'DTE Rechazado por Hacienda. Revise el DTE generado para más información.');
            } else {
                $this->emit('item-errorr', 'El DTE no pudo ser procesado. Estado actual: ' . $dte2->estado);
            }
        } catch (\Throwable $e) {
            \Log::error('Error en NuevaNotaCreditoController::GenerarDTE', [
                'dte_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->emit('item-errorr', 'Error al procesar el DTE: ' . $e->getMessage());
        }
    }
    ////////END GENERAR DTE?//////////
}
