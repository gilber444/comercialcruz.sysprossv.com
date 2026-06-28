<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ActividadEconomica;
use App\Models\Actividades;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\CotizacionesDetalle;
use App\Models\Departamentos;
use App\Models\Descuentos;
use App\Models\Distritos;
use App\Models\Facturadores;
use App\Models\IdentificacionReceptor;
use App\Models\Medidas;
use App\Models\Municipios;
use App\Models\Parametros;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\TipoPersona;
use App\Models\tmpCompras;
use App\Models\tmpCotizaciones;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class NuevaCotizacionController extends Component
{
    public $pageTitle, $componentName, $cliente, $clientes;

    public $duiC, $nombreC, $idC, $direccionC, $telefonoC, $correoC, $detalles, $fechaC;

    //para los datos de los productos
    public $total, $itemsQuantity, $descu, $can = [], $pri = [], $uni = [], $unidades =[], $precios = [];

    //para los clientes
    public $departamentos, $municipios, $nombreCliente, $nit, $dui, $registro, $giro, $correo, $telefono, $celular, $tipoCliente, $direccion, $departamento, $municipio, $tipoPersona, $homologado, $distrito, $actividad, $clienteAddSelectId, $clienteAddSelectName, $idenreceptors, $idenReceptor, $distritos, $personas, $actividades, $facturadores, $facturador;

    ////////variables de la actividad de la sesion
    public $user, $caja, $empresa, $sucursal, $acti;

    public function mount()
    {
        $user_id = Auth::user()->id;

        $activ = Actividades::where('user', $user_id)
        ->where('status', 'Activo')
        ->whereDate('created_at', now()->toDateString())
        ->first();

        if ($activ) {
            $this->user = $user_id;
            $this->caja = $activ->caja;
            $this->sucursal = $activ->sucursal;
            $this->empresa = $activ->empresa;
        } else {
            $this->user = $user_id;
            $this->caja = 2;
            $this->sucursal = 1;
            $this->empresa = 1;
        }

        $this->pageTitle = 'Nueva';
        $this->componentName = 'Cotización';

        //$this->clearCart();
        $this->Carrito();

        $this->clientes = Clientes::all();
        $this->correo = 'clientesmultiagrogisselle@sysprossv.com';
        $this->idenreceptors = IdentificacionReceptor::all();
        $this->departamentos = Departamentos::orderBy('departamento', 'asc')->get();
        $this->municipios = Municipios::orderBy('municipio', 'asc')->get();
        $this->distritos = Distritos::orderBy('distrito', 'asc')->get();
        $this->personas = TipoPersona::orderBy('valor', 'asc')->get();
        $this->actividades = ActividadEconomica::orderBy('valor', 'asc')->get();
        $this->facturadores = Facturadores::all();
    }

    public function Carrito()
    {
        $user_id = Auth::user()->id;
        $ca = tmpCotizaciones::where('usuario', $user_id)->get();
        if($ca){
            foreach($ca as $c){
                $this->uni[$c->id] = $c->unidad;
                $this->pri[$c->id] = $c->price;
                $this->can[$c->id] = $c->quantity;
            }
        }

        $this->total = tmpCotizaciones::where('usuario', $user_id)->sum('total');
        $this->itemsQuantity = tmpCotizaciones::where('usuario', $user_id)->count();
    }

    public function render()
    {
        return view('livewire.cotizaciones.nueva-cotizacion', ['cart' => tmpCotizaciones::where('usuario', Auth::user()->id )->get()])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function SearchClientName()
    {
        $bus = Clientes::where('nombreCliente', 'like', '%' . $this->nombreC . '%')
        ->first();

        if($bus)
        {
            $this->duiC = ($bus->idenReceptor == 2) ? $bus->dui : $bus->nit;
            $this->idC = $bus->id;
            $this->nombreC = $bus->nombreCliente;
            $this->direccionC = $bus->direccion;
            $this->telefonoC = $bus->telefono;
            $this->correoC = $bus->email;
        }
        else
        {
            $this->emit('item-errorSearch', 'Cliente no Encontrado, Desea Registrarlo ?');
        }
    }

    public function SearchClientDui()
    {
        $bus = Clientes::where('dui', 'like', '%' . $this->duiC . '%')
        ->first();

        if($bus)
        {
            $this->duiC = ($bus->idenReceptor == 2) ? $bus->dui : $bus->nit;
            $this->idC = $bus->id;
            $this->nombreC = $bus->nombreCliente;
            $this->direccionC = $bus->direccion;
            $this->telefonoC = $bus->telefono;
            $this->correoC = $bus->email;
        }
        else
        {
            $this->emit('item-errorSearch', 'Cliente no Encontrado, Desea Registrarlo ?');
        }
    }

    protected $listeners = [
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'scan-code-byid' => 'ScanCodeById',
        'print-ticket' => 'printTicket',
        'save-clientes' => 'SaveClientes',
    ];

    public function ScanCode($barcode, $cant = 1)
    {
         $user_id = Auth::user()->id;

        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.producto', $barcode)
            //->where('i.sucursal', session('sucursal'))
            ->select('productos.id', 'productos.nombreProducto', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.pvventa', 'p.cantidad', 'i.sucursal', 'p.presentacion', 'p.cantidad as descargar', 'p.medida', 'p.id as pre')
            ->first();


        if ($product == null) {
            $this->emit('scan-notfound', 'El producto no encontrado');
        } else {

            $price = $product->pvventa;
            $des = Descuentos::where('producto', $product->id)
                ->where('inicio', '<=', Carbon::today()->endOfDay())
                ->where('fin', '>=', Carbon::today()->startOfDay())
                ->latest('created_at')
                ->first();

            if ($des) {
                $descuento = ($price * $des->descuento) / 100;
                $sub = $price - $descuento;
            } else {
                $descuento = 0;
                $sub = $price;
            }

            if ($cant * $product->descargar >= $product->existencia) {
                $this->emit('scan-notfound', 'Stock insuficiente');
            }
            else
            {
                $tmp = tmpCotizaciones::create([
                    'producto' =>$product->id,
                    'name' => $product->nombreProducto,
                    'price' => $sub,
                    'quantity' =>  $cant,
                    'sucursal' => $product->sucursal,
                    'codebar' => $product->codebar,
                    'descuento' => $descuento,
                    'total' => $sub,
                    'medida' => $product->presentacion,
                    'limit' => $product->descargar,
                    'descargar' =>  $cant  * $product->descargar,
                    'uni' => $product->medida,
                    'pre' => $product->pre,
                    'usuario' =>  $user_id,
                    'caja' => 2,
                    'esenario' =>1
                    ]);
                $this->updateCanti($tmp->id);
            }
            $this->Carrito();
        }
    }

    ////////Revisa la escala de productos////////////////
    public function updateCanti($id)
    {
        $user_id = Auth::user()->id;
        $cartItems = tmpCotizaciones::where('usuario', $user_id)->where('esenario', 1)->find($id);


        $bus = Precios::where('producto', $cartItems->producto)
            ->where('codebar', $cartItems->codebar)
            ->where('cantidad', '<=', $cartItems->quantity)
            ->orderBy('cantidad', 'desc')
            ->first();

        // Si no se encuentra en la escala más baja, hacer una segunda consulta
        if (!$bus) {
            $bus = Precios::where('producto', $cartItems->producto)
                ->where('codebar', $cartItems->codebar)
                ->where('cantidad', '>', $cartItems->quantity)
                ->orderBy('cantidad', 'asc')
                ->first();
        }

        $cartItems->price = $bus->pvventa;
        $cartItems->total = $bus->pvventa * $bus->cantidad;
        $cartItems->limit = $bus->cantidad;
        $cartItems->descargar = $bus->cantidad * $cartItems->quantity;
        $cartItems->save();
        $this->Carrito();
    }

    //////Actualiza la cantidadres/////////////////
    public function CantiUpdate($id, $codebar)
    {
        $user_id = Auth::user()->id;

        $tmp = tmpCotizaciones::find($id);

        $product = Productos::join('inventarios as i', 'i.producto', 'productos.id')->find($tmp->producto);

        $cantidades = (float) $this->can[$id];

        if ($cantidades >= $product->existencia) {
            $this->emit('scan-notfound', 'Stock insuficiente');
            return; // Añadido return para evitar continuar si hay un error
        }

        $bus = Precios::where('id', $tmp->pre)
            ->where('codebar', $tmp->codebar)
            ->where('cantidad', '<=', $cantidades)
            ->orderBy('cantidad', 'desc')
            ->first();

        if (!$bus) {
            $bus = Precios::where('id', $tmp->pre)
                ->where('codebar', $tmp->codebar)
                ->where('cantidad', '>', $cantidades)
                ->orderBy('cantidad', 'asc')
                ->first();
        }

        $des = Descuentos::where('producto', $tmp->producto)
            ->where('inicio', '<=', now())
            ->where('fin', '>=', now())
            ->latest('created_at')
            ->first();

        if ($des) {
            $descuento = ($bus->pvventa * $des->descuento) / 100;
            $sub = $bus->pvventa - $descuento;
        } else {
            $descuento = 0;
            $sub = $bus->pvventa;
        }

        $tmp->quantity = $cantidades;
        $tmp->total = $cantidades * $tmp->price;
        $tmp->descargar = $cantidades * $tmp->limit;
        $tmp->save();

        $this->Carrito();
    }

    //////Actualiza la unidad de medida////////////
    public function UpdateUni($id)
    {
        $this->uni[$id];
        $datos = tmpCotizaciones::find($id);

        $precios = Precios::where('producto', $datos->producto)->where('medida', $this->uni[$id])->first();

        $medida = Medidas::find($this->uni[$id]);

        //$producto = $datos->get($id);

        $datos->price = $precios->pvventa;
        $datos->total = $precios->pvventa * $datos->quantity;
        $datos->medida = $medida->unidad;
        $datos->uni = $this->uni[$id];
        $datos->save();
        $this->Carrito();
    }

    /////Actualiza el precio/////////////
    public function UpdatePrice($id)
    {
        $datos = tmpCotizaciones::find($id);

        $precios = Precios::find($this->pri[$id]);

        $datos->price = $precios->pvventa;
        $datos->total = $precios->pvventa * $datos->quantity;
        $datos->pre = $precios->id;
        $datos->save();
        $this->Carrito();
    }

    public function removeItem($id)
    {
        $user_id = Auth::user()->id;

        $tmpVentas = tmpCotizaciones::find($id);
        $tmpVentas->delete();
        $this->Carrito();
    }

    public function Store()
    {
        $rules = [
            'idC' => 'required',
            'fechaC' => 'required',
            'facturador' => 'required'
        ];

        $messages = [
            'idC.required' => 'Agregue un cliente para generar ests cotizacion',
            'fechaC.required' => 'La fecha de cotizacion es requerida',
            'facturador.required' => 'Selecciona el tipo de factura'
        ];

        $user_id = Auth::user()->id;

        $this->validate($rules, $messages);

        $totales = tmpCotizaciones::where('usuario', $user_id)->where('esenario', 1)->sum('total');
        $descuentos = tmpCotizaciones::where('usuario', $user_id)->where('esenario', 1)->sum('descuento');

        $this->itemsQuantity = tmpCotizaciones::where('usuario', $user_id)->where('esenario', 1)->count();
        $sub = $totales;
        $tiva = $sub - $sub / 1.13;
        $tt = $sub - $tiva;

        $verTipoC = Clientes::find($this->idC);

        if($verTipoC->tipoPersona == 3)
        {
            $percecion = $tt * 0.001;
        }else
        {
            $percecion = 0;
        }


        $estado = 'Cotizado';
        $ultimoCorrelativo = Cotizaciones::max('correlativo'); // Obtén el mayor correlativo de la tabla
        $correlativo = $ultimoCorrelativo ? $ultimoCorrelativo + 1 : 1; // Si no hay registros, asigna 1
        $control = NULL;
        $codigoGeneracion = NULL;
        $tipo = 'Fisico';

        try {
            DB::beginTransaction();
            $ingre = Cotizaciones::create([
                'cliente' => $this->idC,
                'tipoPago' => 1,
                'facturador' => $this->facturador,
                'tipoDocumento' => 1,
                'correlativo' => $correlativo,
                'fecha' => $this->fechaC,
                'hora' => date('H:i:s'),
                'fechaPago' => $this->fechaC,
                'tipo' => $tipo,
                'codigo' => $codigoGeneracion,
                'numero' => $control,
                'sello' => NULL,
                'vendedor' => Auth::user()->id,
                'caja' => 1,
                'sucursal' => 1,
                'empresa' => 1,
                'subtotal' => $sub,
                'descuento' => $descuentos,
                'iva' => $tiva,
                'percepcion' => $percecion,
                'total' => $sub,
                'estado' => $estado,
                'observaciones' => $this->detalles
            ]);

            if ($ingre) {
                $items = tmpCotizaciones::where('usuario', $user_id)->where('esenario', 1)->get();
                foreach ($items as $item) {
                    $subb = $item->price - $item->descuento;
                    $iva = $subb - $subb / 1.13;
                    $ttt = $subb - $iva;

                    CotizacionesDetalle::create([
                        'cotizacion' => $ingre->id,
                        'producto' => $item->producto,
                        'medida' => 1,
                        'unidad' => $item->medida,
                        'descargar' => $item->descargar,
                        'cantidad' => $item->quantity,
                        'precio' => $item->price,
                        'descuento' => $item->descuento,
                        'subtotal' => $subb * $item->quantity,
                        'iva' => $iva * $item->quantity,
                        'total' => ($ttt + $iva) * $item->quantity,
                    ]);
                }
                //////agregar los datos a caja///////////
                $this->resetPago();
            }

            ///actualizo el nuevo correlativo
            //$para->cocorrelativo = $correlativo + 1;
            //$para->save();

            DB::commit();
            $this->emit('cotizacionGuardada', $ingre->id);
            return redirect()->to('cotizaciones');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->emit('item-error', $e->getMessage());
        }
    }

    public function resetPago()
    {
        $user_id = Auth::user()->id;
        $this->fechaC = '';
        $this->idC = '';
        $this->detalles = 0;
        tmpCotizaciones::where('usuario', $user_id)->where('esenario', 1)->delete();
    }

    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->departamento)->get();
    }

    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->municipio)->get();
    }

    public function SaveClientes()
    {
        $rules = [
            'nombreCliente' => 'required|min:3',
            //'dui' => 'required|unique:clientes',
            //'correo' => 'required|email',
            //'celular' => 'required',
            'departamento' => 'required|not_in:Elegir',
            'municipio' => 'required|not_in:Elegir',
        ];

        $messages = [
            'nombreCliente.required' => 'Nombre del Cliente es requerido',
            'nombreCliente.min' => 'El Nombre del cliente debe tener más de 3 caracteres',
            'dui.required' => 'El número de DUI es requerido',
            'dui.unique' => 'El número de DUI ya está registrado',
            //'correo.required' => 'El correo electrónico del cliente es requerido',
            //'correo.email' => 'El formato del correo electrónico no es válido',
            'celular.required' => 'El número de celular del cliente es requerido',
            'departamento.required' => 'El departamento es requerido',
            'departamento.not_in' => 'Por favor, elige un valor diferente a "Elegir" en el departamento',
            'municipio.required' => 'El municipio es requerido',
            'municipio.not_in' => 'Por favor, elige un valor diferente a "Elegir" en el municipio',
        ];
        //dd('entra');
        $this->validate($rules, $messages);

        if ($this->clienteAddSelectId == null || empty($this->clienteAddSelectId)) {
            $acti = 775;
            $deta = 'Otros';
        } else {
            $acti = $this->clienteAddSelectId;
            $deta = $this->clienteAddSelectName;
        }

        $cli = Clientes::create([
            'nombreCliente' => strtoupper($this->nombreCliente),
            'tipoPersona' => $this->tipoPersona,
            'dui' => $this->dui,
            'nit' => $this->nit,
            'homologado' => $this->homologado,
            'registro' => $this->registro,
            'giro' => strtoupper($deta),
            'direccion' => strtoupper($this->direccion),
            'telefono' => $this->telefono,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $acti,
            'idenReceptor' => $this->idenReceptor,
            'celular' => $this->celular,
            'email' => $this->correo,
            'desActividad' => strtoupper($deta)
        ]);

        $this->resetUICliente();
        $this->emit('item-added', 'Cliente registrado');
    }

    public function resetUICliente()
    {
        $this->nombreCliente = '';
        $this->direccion = '';
        $this->departamento = 'Elegir';
        $this->municipio = 'Elegir';
        $this->dui = '';
        $this->nit = '';
        $this->registro = '';
        $this->giro = '';
        $this->correo = '';
        $this->telefono = '';
        $this->celular = '';
        $this->tipoCliente = 'Elegir';
    }
}
