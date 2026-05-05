<?php

namespace App\Http\Livewire;

use App\Helpers\Convertidor;
use App\Models\AmbienteDestino;
use App\Models\Clientes;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\ModeloFacturacion;
use App\Models\Parametros;
use App\Models\Precios;
use App\Models\resumenDte;
use App\Models\Sucursales;
use App\Models\SujetoExcluido;
use App\Models\SujetoExcluidosDetalles;
use App\Models\TempSujetoExcluidos;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\TipoTransmision;
use App\Models\Tocken;
use App\Models\Tributo;
use App\Traits\NumeroControlTrait;
use App\Traits\RecepcionDTESujeto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Livewire\Component;

class NuevoSujetoExcluidoController extends Component
{
    use NumeroControlTrait;
    use RecepcionDTESujeto;

    public $pageTitle, $componentName;
    public $total,
        $itemsQuantity,
        $can = [],
        $uni = [],
        $cart = [];
    public $cliente,
        $emisores = [];
    public $emisor;
    public $tipo; // 1: IVA, 2: Renta
    public $costo = [];
    public $observaciones = '';

    protected $listeners = [
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'scan-code-byid' => 'ScanCodeById',
        'print-ticket' => 'printTicket',
    ];

    public function mount()
    {
        $this->pageTitle = 'Nuevo';
        $this->componentName = 'Sujeto Excluido';
        $this->clientes = Clientes::all();
        $this->emisores = Empresas::all();
        //$this->emisores = Sucursales::all();
        $this->Carrito();
    }

    public function Carrito()
    {
        $user = Auth::user();
        $ca = TempSujetoExcluidos::where('user_id', $user->id)->get();
        if ($ca) {
            foreach ($ca as $c) {
                $this->uni[$c->id] = $c->unidad;
                $this->can[$c->id] = $c->cantidad;
                //$this->costo[$c->id] = $c->costo;
            }
        }

        $this->total = TempSujetoExcluidos::where('user_id', $user->id)->sum('toatal');
        $this->itemsQuantity = TempSujetoExcluidos::where('user_id', $user->id)->count();
        $this->cart = TempSujetoExcluidos::where('user_id', $user->id)->orderBy('id', 'desc')->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();
        $cart = TempSujetoExcluidos::where('user_id', $user->id)->orderBy('id', 'desc')->get();

        return view('livewire.sujeto.nuevo-sujetoexcluido', [
            'cart' => $cart,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

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
            TempSujetoExcluidos::create([
                'user_id' => $user_id,
                'producto' => $product->producto,
                'unidad' => $product->medida,
                'name' => $product->Rproductos->nombreProducto,
                'cantidad' => $cant,
                'costo' => $product->costosiva,
                'toatal' => $cant * $product->costosiva,
            ]);
        } else {
            $this->emit('item-error', 'Producto no encontrado');
        }

        $this->Carrito();
    }

    public function updateUni($id)
    {
        $user_id = Auth::user()->id;
        $uniNew = $this->uni[$id];

        $tmp = TempSujetoExcluidos::find($id);
        if ($tmp) {
            $tmp->unidad = $uniNew;
            $tmp->save();
        }
        $this->Carrito();
    }

    public function updateQty($id)
    {
        $cantidades = $this->can[$id];
        $user_id = Auth::user()->id;

        $tmp = TempSujetoExcluidos::find($id);
        if ($tmp) {
            $tmp->cantidad = $cantidades;
            $tmp->toatal = $tmp->costo * $cantidades;
            $tmp->save();
        }
        $this->Carrito();
    }

    public function updateCosto($id)
    {
        $costo = $this->costo[$id];
        $user_id = Auth::user()->id;

        $tmp = TempSujetoExcluidos::find($id);
        if ($tmp) {
            $tmp->costo = $costo;
            $tmp->toatal = $costo * $tmp->cantidad;

            $tmp->save();
        }
        $this->Carrito();
    }

    public function removeItem($id)
    {
        $user_id = Auth::user()->id;
        $tmp = TempSujetoExcluidos::find($id);
        if ($tmp) {
            $tmp->delete();
        }
        $this->Carrito();
    }

    public function clearCart()
    {
        $user_id = Auth::user()->id;
        TempSujetoExcluidos::where('user_id', $user_id)->delete();
        $this->Carrito();
    }

    public function Store()
    {
        //dd($this->cliente);
        $user = Auth::user();
        $rules = [
            'cliente' => 'required',
            'emisor' => 'required',
        ];

        $messages = [
            'cliente.required' => 'El cliente es requerido',
            'emisor.required' => 'El emisor es requerido',
        ];

        $this->validate($rules, $messages);

        $items = TempSujetoExcluidos::where('user_id', $user->id)->get();
        if ($items->isEmpty()) {
            $this->emit('error', 'No hay detalles para procesar.');
            return;
        }

        $codigoGeneracion = strtoupper(Str::uuid()->toString());
        $sucursal = Sucursales::find($user->sucursal);

        $numeroControl = $this->obtenerCodigoDTESujeto(6);

        $fechaHoy = Carbon::now()->toDateString();
        $tokenActivo = Tocken::where('estado', 'ok')->whereDate('fecha', $fechaHoy)->first();

        //   dd('prueba de dd');
        try {
            DB::beginTransaction();

            $sujeto = SujetoExcluido::create([
                'empresa' => $user->empresa,
                'sucursal' => 1,
                'user' => $user->id,
                'codigo_generacion' => $codigoGeneracion,
                'numero_control' => $numeroControl,
                'sello_recepcion' => null,
                'cliente' => $this->cliente,
                'emisor' => $this->emisor,
                'total_pagar' => $items->sum('toatal'),
                'observaciones' => $this->observaciones,
                'tipo' => $this->tipo,
                'estado' => 'Creado',
            ]);
            if ($sujeto) {
                foreach ($items as $item) {
                    SujetoExcluidosDetalles::create([
                        'sujeto_excluido_id' => $sujeto->id,
                        'producto' => $item->producto,
                        'cantidad' => $item->cantidad,
                        'unidad' => $item->unidad,
                        'descripcion' => $item->name,
                        'precio_unitario' => $item->costo,
                        'ventas' => $item->toatal,
                    ]);
                }

                $emprea = Empresas::find($user->empresa);

                $ambiente = AmbienteDestino::find($emprea->ambiente);
                $tipoDte = TipoDocumento::find(10);
                $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
                $tipoOpera = TipoTransmision::where('status', 'Activo')->first();
                $tipoContingencia = TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first();
                $tributo = Tributo::where('status', 'Activo')->whereNull('codigo')->first();

                $parametro = Parametros::where('empresa', $user->empresa)->where('sucursal', 1)->first();

                if ($parametro->dte == 'Si') {
                    $dte = dte::create([
                        'motivoContin' => null,
                        'version' => 1,
                        'ambiente' => $ambiente->id,
                        'tipoDte' => $tipoDte->id,
                        'numeroControl' => $numeroControl,
                        'codigoGeneracion' => $sujeto->codigo_generacion,
                        'tipoModelo' => $tipoModelo->id,
                        'tipoOperacion' => $tipoOpera->id,
                        'tipoContingencia' => $tipoContingencia->id,
                        'fecEmi' => date('Y-m-d'),
                        'horEmi' => date('H:i:s'),
                        'tipoMoneda' => 'USD',
                        'documentoRelacionado' => null,
                        'emisor' => 1,
                        'receptor' => $this->cliente,
                        'otrosDocuentos' => null,
                        'ventaTercero' => null,
                        'venta' => $sujeto->id,
                        //'tocken' => $tokenActivo->id,
                        'tocken' => 1,
                        'sello' => null,
                        'estado' => 'Creado',
                        'jsonDte' => null,
                        'caja' => $parametro->id,
                        'sucursal' => 1,
                        'empresa' => $user->empresa,
                    ]);

                    $total = $items->sum('toatal'); // Asumiendo que 'toatal' es un typo de 'total'
                    $ivaRete1 = 0.0;
                    $reteRenta = 0.0;
                    $base = $total; // base sin IVA
                    $subTotal = $total; // para guardar en columnas 'subTotal' y 'subTotalVentas'
                    $totalPagar = $total; // lo que realmente paga el cliente

                    if ($this->tipo == 1) {
                        // IVA implícito dentro del precio (cliente paga el total completo)
                        $ivaRete1 = round($total * (13 / 113), 2);
                        $base = round($total - $ivaRete1, 2); // base sin IVA
                        $subTotal = $base;
                        $totalPagar = $total; // no se resta el IVA implícito
                    } else {
                        // Otros casos: deja todo como viene
                        $reteRenta = round($total * 0.1, 2);
                        $base = $total - $reteRenta; // no hay desglose de IVA
                        $subTotal = $base;
                        $totalPagar = round($total - $reteRenta, 2);
                    }

                    // Texto en letras del total a pagar
                    $totalLetras = Convertidor::montoALetras($base);

                    $detalleDTE = resumenDte::create([
                        'dte' => $dte->id,

                        // Totales por condición
                        'totalNoSuj' => 0,
                        'totalExenta' => 0,
                        // Para DTE-14 es común reportar la base en "gravada" y el IVA implícito aparte
                        'totalGravada' => $base,

                        // IVA explícito (no aplica aquí); el implícito va en ivaRete1
                        'totalIva' => 0,

                        // Subtotales (usa la base sin IVA cuando hay IVA implícito)
                        'subTotalVentas' => $subTotal,
                        'subTotal' => $subTotal,

                        // Descuentos
                        'descuNoSuj' => 0,
                        'descuExenta' => 0,
                        'descuGravada' => 0,
                        'porcentajeDescuento' => 0,
                        'totalDescu' => 0,

                        // Tributos adicionales (si corresponde)
                        'tributo' => $tributo->id ?? null,
                        'codigo' => null,
                        'descripcion' => null,
                        'valor' => null,

                        // Percepciones y retenciones
                        'ivaPerci1' => 0,
                        'ivaRete1' => $ivaRete1, // IVA implícito (13/113 del total) cuando tipo=1
                        'reteRenta' => $reteRenta, // 10% cuando tipo=2

                        // Monto total de la operación (total con todo incluido, antes de retenciones)
                        'montoTotalOperacion' => $total,

                        'totalNoGravado' => 0,

                        // Lo que paga efectivamente el cliente
                        'totalPagar' => $totalPagar,
                        'totalLetras' => $totalLetras,

                        'saldoFavor' => 0,

                        // Condición de la operación y pago
                        'condicionOperacion' => 1, // 1 = contado (ajusta si es crédito)
                        'pagos' => 1, // si manejas detalle de pagos aparte, deja 1 o tu convención
                        'montoPagado' => $totalPagar,
                        'refencia' => null, // (si tu columna se llama así; si es 'referencia', corrige)
                        'palzo' => null, // (si tu columna es 'plazo', corrige en migración/modelo)
                        'periodo' => null,
                        'numPagoElectronico' => null,
                    ]);
                }
            }

            TempSujetoExcluidos::where('user_id', $user->id)->delete();

            DB::commit();
            $this->clearCart();
            $this->emit('item-added', 'Sujeto Excluido registrado con éxito');
            $this->RecepcionDTESujeto($sujeto->id);
            $this->resetUI();
            // $this->emit('print-ticket2', $sujeto->id);
            return Redirect::to('/sujeto-excluidos');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->emit('error', 'Hubo un error al registrar: ' . $e->getMessage());
        }
    }

    public function resetUI()
    {
        $this->cliente = '';
        $this->observaciones = '';
        $this->total = 0;
        $this->itemsQuantity = 0;
        $this->can = [];
        $this->costo = [];
        $this->cart = [];
    }
}
