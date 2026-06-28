<?php

namespace App\Http\Livewire;

use App\Helpers\Convertidor;
use App\Models\Abonos;
use App\Models\Actividades;
use App\Models\AmbienteDestino;
use App\Models\Caja;
use App\Models\Cortes;
use App\Models\Creditos;
use App\Models\dte;
use App\Models\ModeloFacturacion;
use App\Models\Parametros;
use App\Models\Productos;
use App\Models\resumenDte;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\TipoPagos;
use App\Models\TipoTransmision;
use App\Models\Tocken;
use App\Models\Tributo;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use App\Traits\GenerarTicketgetJsonBase64;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class CuentasCobrarController extends Component
{
    use WithPagination;
    use GenerarTicketgetJsonBase64;

    public  $search, $selected_id, $pageTitle, $componentName,
    $correlativo, $monto, $saldo, $metodo, $cliente, $tipos = [], $factura, $detalle_productos = [], $sucursal;

    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Cuentas por Cobrar a Clientes';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $query = Creditos::with([
            'Rventas' => function ($q) {
                $q->withCount('RdetalleVentas');
            },
            'Rclientes'
        ]);

        if (strlen($this->search) > 0) {
            $query->where(function ($q) {
                $q->where('correlativo', 'like', '%' . $this->search . '%')
                  ->orWhereHas('Rclientes', function ($q2) {
                      $q2->where('nombreCliente', 'like', '%' . $this->search . '%');
                  })
                  ->orWhere('fechaCredito', 'like', '%' . $this->search . '%')
                  ->orWhere('saldo', 'like', '%' . $this->search . '%')
                  ->orWhere('total', 'like', '%' . $this->search . '%');
            });
        }

        $data = $query->orderBy('id', 'desc')->paginate(10);
        //dd($data);
        return view('livewire.cuentas_cobrar.cuentas-cobrar', [
            'data' => $data
        ])->extends('layouts.theme.app')
        ->section('content');
    }

    public function CargaProductos($id)
    {
        $data = Creditos::find($id);

        $this->detalle_productos = VentasDetalles::with('Rproductos')->where('venta', $data->venta)->get();

        $this->emit('ajuste-modal', 'show modal');
    }

    public function CargaDatos($id)
    {
        $data = Creditos::with('Rclientes')->find($id);

        $this->selected_id = $data->id;
        $this->correlativo = $data->correlativo;
        $this->saldo = number_format($data->saldo, 2);
        $this->metodo = $data->metodo;
        $this->cliente = $data->Rclientes->nombreCliente;
        $this->sucursal = $data->sucursal;
        $this->tipos = TipoPagos::all();

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->selected_id = 0;
        $this->correlativo = 0;
        $this->sucursal =   '';
        $this->saldo = number_format(0, 2);
        $this->metodo = '';
        $this->cliente = '';
    }

    public function Procesar()
    {
        $rules = [
            'correlativo' => 'required',
            'saldo' => 'required',
            'metodo' => 'required',
        ];

        $messages = [
            'correlativo.required' => 'El correlativo es requerido',
            'saldo.required' => 'El saldo es requerido',
            'metodo.required' => 'El metodo de pago es requerido',
        ];

        $this->validate($rules, $messages);

        $user = Auth::user();

        $acti = Actividades::where('user', $user->id)-> whereDate('created_at', date('Y-m-d'))->where('status', 'Activo')->first();

        if($acti)
        {
            $empresa = $acti->empresa;
            $sucursal = $acti->sucursal;
            $caja = $acti->caja;
        }
        else
        {
            $actis = Actividades:: whereDate('created_at', date('Y-m-d'))->where('status', 'Activo')->first();
            $empresa = $actis->empresa;
            $sucursal = $actis->sucursal;
            $caja = $actis->caja;
        }

        $ultimoCorrelativo = Abonos::orderBy('correlativo', 'desc')->first();
        $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo->correlativo + 1 : 1;

        $para = Parametros::find($caja);
        $correlativo = $para->tcorrelativo;
        if($para->dte == "Si" && $para->tiquedte == '1')
        {
            $control = $this->obtenerCodigoDTE(1);
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

        DB::beginTransaction();

        try {
            // Actualizar el crédito
            $credito = Creditos::find($this->selected_id);

            $nuewSaldo = $credito->saldo - $this->monto;

            $credito->saldo = $nuewSaldo;
            //$credito->metodo = $this->metodo;
            if($nuewSaldo == 0)
            {
                $credito->estado = 'Pagado';
            }
            $credito->save();

            // Insertar en la tabla cobros
            Abonos::create([
                'empresa' => $empresa,
                'sucursal' => $sucursal,
                'credito' => $credito->id,
                'correlativo' => $nuevoCorrelativo,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'monto' => $this->monto,
                'user' => $user->id,
                'estado' => 'pagado'
            ]);

            $ingre = Ventas::create([
                'cliente' => 1,
                'tipoPago' => $this->metodo,
                'facturador' => 1,
                'correlativo' => $correlativo,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'tipo' => $tipo,
                'codigo' => $codigoGeneracion,
                'numero' => $control,
                'sello' => '',
                'vendedor' => Auth::user()->id,
                'caja' => $caja,
                'sucursal' => $sucursal,
                'empresa' => $empresa,
                'subtotal' => $this->monto,
                'descuento' => 0.00,
                'iva' => $this->monto / 1.13,
                'percepcion' => 0,
                'total' => $this->monto,
                'estado' => 'Cancelado',
                'qr' => '',
            ]);

            if ($ingre) {
                if($nuewSaldo ==  0)
                {
                    $producto = Productos::find(2);
                }
                else
                {
                    $producto = Productos::find(1);
                }

                VentasDetalles::create([
                    'venta' => $ingre->id,
                    'producto' => $producto->id,
                    'medida' => $producto->medida,
                    'unidad' => 'Unidad',
                    'descargar' => 1,
                    'cantidad' => 1,
                    'precio' => $this->monto,
                    'descuento' => 0,
                    'subtotal' => $this->monto,
                    'iva' => $this->monto/1.13,
                    'total' => $this->monto,
                ]);
            }
            //////agregar los datos a caja///////////
            $corte = Cortes::where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->where('fecha', date('Y-m-d'))
            ->select('id')
            ->first();

            $ca = Caja::create([
                'caja' => $caja,
                'sucursal' => $sucursal,
                'empresa' => $empresa,
                'corte' => $corte->id,
                'venta' => $ingre->id,
                'facturador' => 1,
                'tipoPago' => $this->metodo,
                'correlativo' => $correlativo,
                'codigo' => $codigoGeneracion,
                'numero' => $control,
                'sello' => null,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'cajero' => Auth::user()->id,
                'comprobante' => null,
                'efectivo' => $this->monto,
                'cambio' => 0,
                'subtotal' => $this->monto,
                'descuento' => 0,
                'iva' => $this->monto/1.13,
                'percepcion' => 0,
                'total' => $this->monto,
                'estado' => 'Cancelado',
                'arqueado' => false
            ]);

            $ambiente = AmbienteDestino::where('status', 'Activo')->first();
            $tipoDte = TipoDocumento::where('status', 'Activo')->where('valor', 'FACTURA')->first();
            $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
            $tipoOpera = TipoTransmision::where('status', 'Activo')->first();
            $tipoContingencia = TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first();
            $tributo = Tributo::where('status', 'Activo')->whereNull('codigo')->first();
            $condipago = $this->metodo == 4 ? 2 : 1;

            if($para->dte == 'Si' && $para->tiquedte == '1')
            {
                $dte = dte::create([
                    'motivoContin' => null,
                    'version' => 1,
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
                    'documentoRelacionado' => null,
                    'emisor' => $sucursal,
                    'receptor' => 1,
                    'otrosDocuentos' => null,
                    'ventaTercero' => null,
                    'venta' => $ingre->id,
                    'tocken' => $tokenActivo->id,
                    'sello' => null,
                    'estado' => 'Creado',
                    'jsonDte' => null,
                    'caja' => $caja,
                    'sucursal' => $sucursal,
                    'empresa' => $empresa,
                ]);

                $detalleDTE = resumenDte::create([
                    'dte' => $dte->id,
                    'totalNoSuj' => 0,
                    'totalExenta' => 0,
                    'totalGravada' => $this->monto,
                    'totalIva' => $this->monto/1.13,
                    'subTotalVentas' => $this->monto,
                    'descuNoSuj' => 0,
                    'descuExenta' => 0,
                    'descuGravada' => 0,
                    'porcentajeDescuento' => 0,
                    'totalDescu' => 0,
                    'tributo' => $tributo->id,
                    'codigo' => null,
                    'descripcion' => null,
                    'valor' => null,
                    'subTotal' => $this->monto,
                    'ivaPerci1' => 0,
                    'ivaRete1' => 0,
                    'reteRenta' => 0,
                    'montoTotalOperacion' => $this->monto,
                    'totalNoGravado' => 0,
                    'totalPagar' => $this->monto,
                    'totalLetras' => strtoupper(Convertidor::montoALetras($this->monto)),
                    'saldoFavor' => 0,
                    'condicionOperacion' => $condipago,
                    'pagos' => $this->metodo,
                    'montoPagado' => null,
                    'refencia' => null,
                    'palzo' => null,
                    'periodo' => null,
                    'numPagoElectronico' => null,
                ]);

                if($para->dteAutomatico == 'Si')
                {
                    $json = $this->GeneraJsonF($dte->id);
                    $this->RecepcionDTEF($dte->id);
                }
            }
            ///actualizo el nuevo correlativo
            $para->tcorrelativo = $correlativo + 1;
            $para->save();

            DB::commit();

            $this->resetUI();
            $this->emit('item-update', 'Crédito actualizado correctamente');
            $this->ImprimirTicket($ca->id);
        } catch (\Exception $e) {
            DB::rollback();
            $this->emit('item-error', 'Error al actualizar el crédito: ' . $e->getMessage());
        }
    }
    public function ImprimirTicket($id)
    {
        $this->emit('print-credito', $this->GenerarTicketgetJsonBase64($id));
    }
}
