<?php

namespace App\Http\Livewire;

use App\Helpers\Convertidor;
use App\Http\Controllers\ExportController;
use App\Jobs\ProcesarDTEJob;
use App\Models\ActividadEconomica;
use App\Models\Actividades;
use App\Models\AmbienteDestino;
use App\Models\Anulaciones;
use App\Models\AnulacionesDetalle;
use App\Models\Aperturas;
use App\Models\Apis;
use App\Models\Arqueos;
use App\Models\Caja;
use App\Models\Clientes;
use App\Models\Cortes;
use App\Models\Credito;
use App\Models\Creditos;
use App\Models\Departamentos;
use App\Models\Descuentos;
use App\Models\Devoluciones;
use App\Models\DevolucionesDetalles;
use App\Models\Distritos;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\envioTmp;
use App\Models\Facturadores;
use App\Models\Firmador;
use App\Models\IdentificacionReceptor;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Medidas;
use App\Models\ModeloFacturacion;
use App\Models\Municipios;
use App\Models\Parametros;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\RecepcionDte;
use App\Models\Remesas;
use App\Models\resumenDte;
use App\Models\Sucursales;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\TipoItem;
use App\Models\TipoPagos;
use App\Models\TipoPersona;
use App\Models\TipoTransmision;
use App\Models\tmpVentas;
use App\Models\Tocken;
use App\Models\Tributo;
use App\Models\User;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use App\Traits\AnulacionGetJsonBase64;
use App\Traits\DevolucionGetJsonBase64;
use App\Traits\FirmadorDTE;
use App\Traits\FirmadorLocal;
use App\Traits\GeneraJsonC;
use App\Traits\GeneraJsonF;
use App\Traits\GenerarJsonCorteX;
use App\Traits\GenerarJsonCorteZ;
use App\Traits\GenerarTicketgetJsonBase64;
use App\Traits\GenerarToken;
use App\Traits\NumeroControlTrait;
use App\Traits\RecepcionDTEC;
use App\Traits\RecepcionDTEF;
use App\Traits\RemesagetJsonBase64;
use App\Traits\TraitTikets;
use App\Traits\TraitTiketsRe;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use PHPMailer\PHPMailer\PHPMailer;

class PosController extends Component
{
    use NumeroControlTrait;
    use GenerarToken;
    use FirmadorDTE;
    use RecepcionDTEF;
    use RecepcionDTEC;
    use GeneraJsonF;
    use GenerarJsonCorteX;
    use WithPagination;
    use GenerarTicketgetJsonBase64;
    use GenerarJsonCorteZ;
    use FirmadorLocal;
    use GeneraJsonC;
    use TraitTikets;
    use RemesagetJsonBase64;
    use DevolucionGetJsonBase64;
    use AnulacionGetJsonBase64;
    use TraitTiketsRe;

    ////para mostrar en la pantalla y crear la apertura de caja
    public $pageTitle, $estadoCaja, $fechaApertura, $horaApertura, $montoApertura, $aperturas, $aperturas2, $valid, $corteActivo, $act = 0, $parametros;

    //para los datos de los productos
    public $total, $itemsQuantity, $descu, $fechaV = [], $can = [], $pri = [], $uni = [];

    //para los clientes
    public $departamentos, $municipios, $nombreCliente, $nit, $dui, $registro, $giro, $correo, $telefono, $celular, $tipoCliente, $direccion, $departamento, $municipio, $tipoPersona, $homologado, $distrito, $actividad, $clienteAddSelectId, $clienteAddSelectName, $idenreceptors, $idenReceptor, $distritos, $personas, $actividades;

    ///para generar las ventas
    public $formas, $efectivo, $cambio, $metodo = 1, $comprobante, $duiC, $idC, $nombreC, $direccionC, $telefonoC, $correoC, $nrcC, $tipoC = 0, $actividadC, $fecha, $correlativo;

    ///para la parte de remesas
    public $remesas, $montoCaja, $disponible, $montoEnvio2, $montoEnvio, $remesas2, $montoCaja2, $disponible2, $concepto;

    //para los cierres de caja
    public $showModalCorteZ = false, $showModalCorteZ2 = false,  $showModalCorteX = false,  $showModalAutenticate = false, $showModalAutenticate2 = false,  $showModalAutenticateX = false,
        $username, $password, $username2, $password2, $modalUpdated, $b100, $b100R, $b50, $b50R, $b20, $b20R, $b10, $b10R, $b5, $b5R, $b1, $b1R, $bd1, $bd1R, $b025, $b025R, $b010, $b010R, $b005, $b005R, $b001, $b001R, $totalEfectivo, $totalDiferencia, $totalTarjetas, $totalCheque, $totalCreditos, $totalVentas, $totalRemesas, $totalDevoluciones, $totalAnulaciones, $totalSumas, $totalSumaResta, $totalEfectivo2, $totalDiferencia2, $totalTarjetas2, $totalCheque2, $totalCreditos2, $totalVentas2, $totalRemesas2, $totalDevoluciones2, $totalAnulaciones2, $totalSumas2, $totalSumaResta2, $cortes, $cortes2, $showModalReimpresion = false, $showModalAutenticateR = false, $usernameR, $passwordR;

    //para los cortes X
    public $usernamex, $passwordx, $totalEfectivox, $totalDiferenciax, $totalTarjetasx, $totalChequex, $totalCreditosx, $totalVentasx, $totalRemesasx, $totalDevolucionesx, $totalAnulacionesx, $totalSumasx, $totalSumaRestax, $cortesx;

    ///para las devoculiciones y cancelaciones
    public $numeroDoc, $tipoTran, $passwordd, $error, $formasPagos = [], $fo;

    ////para las reimpresiones
    public $search, $detallePrecios = [], $detalleEscalas = [], $productoName = '', $detalleAnulaciones = [], $search2, $tmpventasId;

    /////impresion de la ultima venta
    public $ultimoTotal, $ultimoEfectivo, $ultimoCambio;

    ////impresion de tiquets
    public $metodot, $comprobantet, $efectivot, $totalt = 0;

    public $focused;

    // variables de los escenarios
    public $escenarios = [], $escResumen = [], $carritoLleno, $name = [], $ventasModal = [], $empresa, $sucursal, $caja, $hoy, $userId, $itemsProd;

    public $pagination = 10;

    public $procesando = false;

    /*public function mount()
    {
        $this->Carrito();
        $this->cargaDatos();
        $this->loadEscenarios();

        $empresa = session('empresa');
        $sucursal = session('sucursal');
        $caja = session('caja');
        $hoy = date('Y-m-d');

        // Validaciones de apertura
        $this->valid = Aperturas::where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('estado', 'Aperturado')
            ->where('fechaApertura', '<>', $hoy)
            ->count();

        $this->estadoCaja = Aperturas::where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('fechaApertura', $hoy)
            ->where('estado', 'Aperturado')
            ->count();

        $this->aperturas = Aperturas::where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('fechaApertura', $hoy)
            ->where('estado', 'Aperturado')
            ->first();

        $this->aperturas2 = Aperturas::where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('estado', 'Aperturado')
            ->first();

        $this->corteActivo = Cortes::where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('fecha', date('Y-m-d'))
            ->where('estado', 'Activo')
            ->first();

        $this->departamentos = Departamentos::all();

        $this->municipios = Municipios::all();

        $this->fechaApertura = date('Y-m-d');
        $this->horaApertura = date('h:i:s');

        $this->formas = TipoPagos::all();

        $this->formasPagos = Facturadores::all();

        if ($this->aperturas) {
            $this->calcularMontos($this->aperturas, false);
        }

        if ($this->aperturas2) {
            $this->calcularMontos($this->aperturas2, true);
        }
    }*/

    public function mount()
    {
        $user = Auth::user();
        $this->empresa = (int) session('empresa');
        $this->sucursal = (int) session('sucursal');
        $this->caja = (int) session('caja');
        $this->hoy = date('Y-m-d');
        $this->userId = Auth::id();

        //$t = $svc->totalesDia($this->empresa, $this->sucursal, $this->caja, $this->hoy);



        // 🔹 Validar si el usuario tiene una actividad activa hoy
        $actividadActiva = Actividades::where('user', $user->id)
            ->where('empresa', $user->empresa)
            ->where('sucursal', $user->sucursal)
            ->whereDate('created_at', Carbon::today()) // Actividad del día de hoy
            ->where('status', 'Activo')
            ->exists();

        if (!$actividadActiva) {
            return redirect()->route('actividad'); // Si no hay actividad activa, redirigir a actividad
        }

        $this->Carrito();
        $this->cargaDatos();
        $this->loadEscenarios();
        //$this->CargaImpresiones();

        $empresa  = (int) session('empresa');
        $sucursal = (int) session('sucursal');
        $caja     = (int) session('caja');
        $hoy      = date('Y-m-d');

        $this->aperturas = \App\Models\Aperturas::query()
            ->where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->whereDate('fechaApertura', $hoy)
            ->where('estado', 'Aperturado')
            ->select('*')
            ->selectSub(function ($q) use ($empresa, $sucursal, $caja, $hoy) {
                $q->from('aperturas')
                    ->where('empresa', $empresa)
                    ->where('sucursal', $sucursal)
                    ->where('caja', $caja)
                    ->whereDate('fechaApertura', $hoy)
                    ->where('estado', 'Aperturado')
                    ->selectRaw('COUNT(*)');
            }, 'estadoCaja')
            ->latest('id')
            ->first();

        // Asigna el count sin otra consulta
        $this->estadoCaja = $this->aperturas ? (int) $this->aperturas->estadoCaja : 0;

        $this->aperturas2 = Aperturas::where('empresa', session('empresa'))->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Aperturado')->first();

        $this->corteActivo = Cortes::where('empresa', session('empresa'))->where('fecha', date('Y-m-d'))->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Activo')->first();

        $this->departamentos = Departamentos::all();

        $this->municipios = Municipios::all();

        $this->fechaApertura = date('Y-m-d');
        $this->horaApertura = date('h:i:s');

        $this->formas = TipoPagos::all();

        $this->formasPagos = Facturadores::all();

        if ($this->aperturas) {
            $this->remesas = Remesas::where('fecha', $this->aperturas->fechaApertura)
                ->where('sucursal', session('sucursal'))
                ->where('caja', session('caja'))
                ->where('estado', 'Remesado')
                ->where('arqueado', 0)
                ->sum('monto');

            $this->cortes = Arqueos::where('fecha', $this->aperturas->fechaApertura)
                ->where('sucursal', session('sucursal'))
                ->where('caja', session('caja'))
                ->sum('totalGlobal');

            $this->montoCaja = Caja::where('fecha', $this->aperturas->fechaApertura)
                ->where('sucursal', session('sucursal'))
                ->where('caja', session('caja'))
                ->where('estado', 'Cancelado')
                ->where('arqueado', 0)
                ->sum('total');

            //$this->montoEnvio = number_format($this->montoCaja - $this->remesas, 2);

            $this->totalVentas = Caja::where('fecha', $this->aperturas->fechaApertura)
                ->where('tipoPago', 1)
                ->where('sucursal', session('sucursal'))
                ->where('caja', session('caja'))
                ->where('estado', 'Cancelado')
                ->sum('total');

            $this->totalTarjetas = Caja::where('fecha', $this->aperturas->fechaApertura)->where('tipoPago', 2)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->sum('total');

            $this->totalCheque = Caja::where('fecha', $this->aperturas->fechaApertura)->where('tipoPago', 3)->where('estado', 'Cancelado')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

            $this->totalCreditos = Caja::where('fecha', $this->aperturas->fechaApertura)->where('tipoPago', 4)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

            $this->totalRemesas = Remesas::where('fecha', $this->aperturas->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('monto');

            $this->totalAnulaciones = Caja::where('fecha', $this->aperturas->fechaApertura)->where('estado', 'Anulado')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

            $this->totalDevoluciones = Caja::where('fecha', $this->aperturas->fechaApertura)->where('estado', 'Devolucion')->where('caja', session('caja'))->where('sucursal', session('sucursal'))->sum('total');

            $this->totalSumas = $this->totalVentas + $this->totalTarjetas + $this->totalCheque;
            $this->totalSumaResta = $this->totalSumas - $this->cortes - $this->remesas;

            $this->disponible = $this->montoCaja - $this->remesas;
        }

        if ($this->aperturas2) {
            $this->remesas2 = Remesas::where('fecha', $this->aperturas2->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Cancelado')->sum('monto');

            $this->montoCaja2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Cancelado')->sum('total');

            $this->montoEnvio2 = number_format($this->montoCaja2 - $this->remesas2, 2);

            $this->totalVentas2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 1)->where('sucursal', session('sucursal'))
                ->where('caja', session('caja'))->where('estado', 'Cancelado')->sum('total');

            $this->totalTarjetas2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 2)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->sum('total');

            $this->totalCheque2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 3)->where('estado', 'Cancelado')->where('sucursal', session('sucursal'))->sum('total');

            $this->totalCreditos2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 4)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

            $this->totalRemesas2 = Remesas::where('fecha', $this->aperturas2->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('monto');

            $this->cortes2 = Arqueos::where('fecha', $this->aperturas2->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('totalGeneral');

            $this->totalAnulaciones2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('estado', 'Anulado')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

            $this->totalDevoluciones2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('estado', 'Devolucion')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

            $this->totalSumas2 = $this->totalVentas2 + $this->totalTarjetas2 + $this->totalCheque2;
            $this->totalSumaResta2 = $this->totalSumas2 -  $this->totalRemesas2 - $this->cortes2;

            $this->disponible2 = $this->totalSumaResta2;
        }
    }

    private function calcularMontos($apertura, $esSegunda = false)
    {
        $fecha = $apertura->fechaApertura;
        $sucursal = session('sucursal');
        $caja = session('caja');

        $remesas = Remesas::where('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->when(!$esSegunda, fn($q) => $q->where('estado', 'Remesado')->where('arqueado', 0))
            ->when($esSegunda, fn($q) => $q->where('estado', 'Cancelado'))
            ->sum('monto');

        $cortes = Arqueos::where('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->sum($esSegunda ? 'totalGeneral' : 'totalGlobal');

        $montoCaja = Caja::where('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('estado', 'Cancelado')
            ->when(!$esSegunda, fn($q) => $q->where('arqueado', 0))
            ->sum('total');

        $ventas = [
            'efectivo' => Caja::where('fecha', $fecha)->where('tipoPago', 1)->where('estado', 'Cancelado')->where('sucursal', $sucursal)->where('caja', $caja)->sum('total'),
            'tarjeta'  => Caja::where('fecha', $fecha)->where('tipoPago', 2)->where('estado', 'Cancelado')->where('sucursal', $sucursal)->where('caja', $caja)->sum('total'),
            'cheque'   => Caja::where('fecha', $fecha)->where('tipoPago', 3)->where('estado', 'Cancelado')->where('sucursal', $sucursal)->where('caja', $caja)->sum('total'),
            'credito'  => Caja::where('fecha', $fecha)->where('tipoPago', 4)->where('sucursal', $sucursal)->where('caja', $caja)->sum('total'),
            'anulaciones' => Caja::where('fecha', $fecha)->where('estado', 'Anulado')->where('sucursal', $sucursal)->where('caja', $caja)->sum('total'),
            'devoluciones' => Caja::where('fecha', $fecha)->where('estado', 'Devolucion')->where('sucursal', $sucursal)->where('caja', $caja)->sum('total')
        ];

        $totalSumas = $ventas['efectivo'] + $ventas['tarjeta'] + $ventas['cheque'];
        $totalSumaResta = $totalSumas - $cortes - $remesas;
        $disponible = $montoCaja - $remesas;

        if ($esSegunda) {
            $this->remesas2 = $remesas;
            $this->montoCaja2 = $montoCaja;
            $this->montoEnvio2 = number_format($disponible, 2);
            $this->totalVentas2 = $ventas['efectivo'];
            $this->totalTarjetas2 = $ventas['tarjeta'];
            $this->totalCheque2 = $ventas['cheque'];
            $this->totalCreditos2 = $ventas['credito'];
            $this->totalRemesas2 = $remesas;
            $this->cortes2 = $cortes;
            $this->totalAnulaciones2 = $ventas['anulaciones'];
            $this->totalDevoluciones2 = $ventas['devoluciones'];
            $this->totalSumas2 = $totalSumas;
            $this->totalSumaResta2 = $totalSumaResta;
            $this->disponible2 = $totalSumaResta;
        } else {
            $this->remesas = $remesas;
            $this->montoCaja = $montoCaja;
            $this->totalVentas = $ventas['efectivo'];
            $this->totalTarjetas = $ventas['tarjeta'];
            $this->totalCheque = $ventas['cheque'];
            $this->totalCreditos = $ventas['credito'];
            $this->totalRemesas = $remesas;
            $this->cortes = $cortes;
            $this->totalAnulaciones = $ventas['anulaciones'];
            $this->totalDevoluciones = $ventas['devoluciones'];
            $this->totalSumas = $totalSumas;
            $this->totalSumaResta = $totalSumaResta;
            $this->disponible = $disponible;
        }
    }


    public function CargaDatos()
    {
        $this->idenreceptors = IdentificacionReceptor::all();
        $this->departamentos = Departamentos::orderBy('departamento', 'asc')->get();
        $this->municipios = Municipios::orderBy('municipio', 'asc')->get();
        $this->distritos = Distritos::orderBy('distrito', 'asc')->get();
        $this->personas = TipoPersona::orderBy('valor', 'asc')->get();
        $this->actividades = ActividadEconomica::orderBy('valor', 'asc')->get();

        $empresas = Empresas::find(session('empresa'));
        $sucursales = Sucursales::find(session('sucursal'));
        $this->parametros = Parametros::find(session('caja'));
        $parametros = Parametros::find(session('caja'));

        $this->pageTitle = $sucursales->nombre . ' CAJA No. ' . $parametros->caja;
    }

    /*public function Carrito()
    {
        $userId = Auth::id();
        $sucursal = session('sucursal');

        $this->itemsQuantity = tmpVentas::where('user', $userId)
            ->where('esenario', 1)
            ->where('sucursal', $sucursal)
            ->count();

        $this->total = tmpVentas::where('user', $userId)
            ->where('esenario', 1)
            ->where('sucursal', $sucursal)
            ->sum('total');

        $this->descu = tmpVentas::where('user', $userId)
            ->where('esenario', 1)
            ->where('sucursal', $sucursal)
            ->sum('descuento');

        $cartItems = tmpVentas::where('user', $userId)
            ->where('esenario', 1)
            ->where('sucursal', $sucursal)
            ->get();

        foreach ($cartItems as $item) {
            $this->can[$item->id] = $item->quantity;
            $this->uni[$item->id] = $item->pid;
        }

        $ultimotiquet = Caja::where('cajero', $userId)
            ->where('sucursal', $sucursal)
            ->where('caja', session('caja'))
            ->where('empresa', session('empresa'))
            ->latest()
            ->first();

        $this->setUltimoTicket($ultimotiquet);
    }*/

    public function Carrito()
    {
        $empresa  = $this->empresa ?? (int) session('empresa');
        $sucursal = $this->sucursal ?? (int) session('sucursal');
        $caja     = $this->caja ?? (int) session('caja');
        $userId   = $this->userId ?? Auth::id();
        $hoy      = $this->hoy ?? date('Y-m-d');

        // 1) Totales rápidos del carrito (una sola query con SUM)
        $row = DB::table('tmp_ventas')
            ->where('user', $userId)
            ->where('esenario', 1)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->selectRaw('
                SUM(quantity)   as qty,
                SUM(total)      as tot,
                SUM(descuento)  as descu,
                COUNT(*)        as lineas
            ')
            ->first();

        $this->itemsQuantity = (int) ($row->qty ?? 0);
        $this->itemsProd     =  (int) ($row->lineas ?? 0); // cantidad de líneas
        $this->total         = (float) ($row->tot ?? 0);
        $this->descu         = (float) ($row->descu ?? 0);

        // 2) Detalle del carrito
        $cartItems = tmpVentas::where('user', $userId)
            ->where('esenario', 1)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->get();

        $this->carritoLleno = $cartItems->count();

        // 🔹 Resetear arrays para no arrastrar basura
        $this->can  = [];
        $this->uni  = [];
        $this->name = [];

        if ($cartItems->isNotEmpty()) {

            // 2.a) Armar listas de productos y medidas del carrito
            $productos = [];
            $medidas   = [];

            foreach ($cartItems as $item) {
                $productos[] = $item->producto;
                $medidas[]   = $item->uni;
            }

            $productos = array_unique($productos);
            $medidas   = array_unique($medidas);

            // 2.b) Traer TODOS los precios necesarios en UNA sola consulta
            $precios = Precios::whereIn('producto', $productos)
                ->whereIn('medida', $medidas)
                ->withoutTrashed()
                ->get();

            // 2.c) Indexar por "producto-medida" para lookup rápido
            $mapPrecios = [];
            foreach ($precios as $p) {
                $key = $p->producto . '-' . $p->medida;
                $mapPrecios[$key] = $p;
            }

            // 2.d) Llenar arrays usando el mapa de precios
            foreach ($cartItems as $item) {
                $key = $item->producto . '-' . $item->uni;
                $pp  = $mapPrecios[$key] ?? null;

                $this->can[$item->id]  = $item->quantity;
                $this->name[$item->id] = $item->name;

                // Solo si existe el precio evita errores de null
                $this->uni[$item->id]  = $pp ? $pp->id : null;
            }
        }

        // 3) Último ticket del cajero (1 query)
        $ultimotiquet = Caja::where('cajero', $userId)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('empresa', $empresa)
            ->latest('id')
            ->first();

        if ($ultimotiquet && $this->itemsQuantity == 0 && $ultimotiquet->fecha == $hoy) {
            $this->ultimoTotal    = $ultimotiquet->total;
            $this->ultimoEfectivo = $ultimotiquet->efectivo;
            $this->ultimoCambio   = $ultimotiquet->cambio;
        } else {
            $this->ultimoTotal    = 0;
            $this->ultimoEfectivo = 0;
            $this->ultimoCambio   = 0;
        }

        // 4) Validar límite de productos
        /*if ($cartItems->count() >= 20) {
            $this->emit('scan-notfound', 'Se ha alcanzado el límite permitido de productos. Proceda a generar la venta...');
        }*/
    }


    private function setUltimoTicket($ticket)
    {
        $hoy = date('Y-m-d');

        if ($ticket && $this->itemsQuantity == 0 && $ticket->fecha == $hoy) {
            $this->ultimoTotal = $ticket->total;
            $this->ultimoEfectivo = $ticket->efectivo;
            $this->ultimoCambio = $ticket->cambio;
        } else {
            $this->ultimoTotal = 0;
            $this->ultimoEfectivo = 0;
            $this->ultimoCambio = 0;
        }
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user_id = Auth::user()->id;
        $cart = tmpVentas::where('user', $user_id)
            ->where('esenario', 1)
            ->where('sucursal', session('sucursal'))
            ->where('empresa', session('empresa'))
            ->orderBy('created_at', 'desc')
            ->get();

        $ventasQuery = Caja::query()
            ->select('id', 'fecha', 'hora', 'caja', 'corte', 'correlativo', 'numero', 'total', 'cajero')
            ->with([
                'Rcajeros:id,name',
                'Rcajas:id,caja',
                'Rcortes:id,corte'
            ])
            ->where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereBetween('fecha', [
                Carbon::now()->startOfWeek()->toDateString(),
                Carbon::now()->endOfWeek()->toDateString()
            ]);

        if (strlen($this->search) > 0) {
            $ventasQuery->where(function ($q) {
                $q->where('correlativo', $this->search)
                    ->orWhereDate('fecha', $this->search)
                    ->orWhereHas('Rcajeros', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $ventas = $ventasQuery
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('correlativo')
            ->paginate($this->pagination);

        return view('livewire.pos.pos', ['cart' => $cart, 'ventas' => $ventas])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function updateDepto()
    {
        $this->municipios = Municipios::where('departamento', $this->departamento)->get();
    }

    public function updateMuni()
    {
        $this->distritos = Distritos::where('municipio', $this->municipio)->get();
    }

    public function Aperturar()
    {
        $rules = [
            'montoApertura' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/|min:0.01',
        ];

        $messages = [
            'montoApertura.required' => 'El campo de monto de apertura es obligatorio.',
            'montoApertura.numeric' => 'El monto de apertura debe ser un número.',
            'montoApertura.regex' => 'El monto de apertura debe ser numérico y puede tener hasta dos decimales.',
            'montoApertura.min' => 'El monto de apertura debe ser de al menos 0.01.',
        ];

        $this->validate($rules, $messages);

        $vali = Aperturas::where('caja', session('caja'))
            ->where('sucursal', session('sucursal'))
            ->where('empresa', session('empresa'))
            ->where('fechaApertura', $this->fechaApertura)
            ->first();
        if ($vali) {
            $this->emit('item-error', 'No se puede aperturar dos veces una caja');
        } else {
            $aper = Aperturas::create([
                'caja' => session('caja'),
                'sucursal' => session('sucursal'),
                'empresa' => session('empresa'),
                'fechaApertura' => $this->fechaApertura,
                'horaApertura' => $this->horaApertura,
                'inicio' => $this->montoApertura,
                'final' => null,
                'FcierreApertura' => null,
                'HcierreApertura' => null,
                'estado' => 'Aperturado',
                'cajero' => Auth::user()->id,
            ]);

            $cor = Cortes::where('caja', session('caja'))->where('sucursal', session('sucursal'))->where('empresa', session('empresa'))->latest()->first();

            if ($cor) {
                $numero = (int) substr($cor->corte, 1);
                $nuevoNumero = $numero + 1;
            } else {
                $nuevoNumero = 1;
            }

            $longitudMaxima = 7;
            $nuevoCorrelativo = 'Z' . str_pad($nuevoNumero, $longitudMaxima, '0', STR_PAD_LEFT);

            $corte = Cortes::create([
                'caja' => session('caja'),
                'sucursal' => session('sucursal'),
                'empresa' => session('empresa'),
                'corte' => $nuevoCorrelativo,
                'fecha' => $this->fechaApertura,
                'hora' => $this->horaApertura,
                'estado' => 'Activo',
                'efectivo' => 0,
                'tarjeta' => 0,
                'cheque' => 0,
                'credito' => 0,
                'subtotalPagos' => 0,
                'devoluciones' => 0,
                'anulaciones' => 0,
                'percepcion' => 0,
                'sumaTotales' => 0,
                'ticketDesde' => 0,
                'ticketHasta' => 0,
                'gravadosT' => 0,
                'ivaT' => 0,
                'subT' => 0,
                'totalT' => 0,
                'consumidorDesde' => 0,
                'consumidorHasta' => 0,
                'gravadosCon' => 0,
                'ivaCon' => 0,
                'subCon' => 0,
                'totalCon' => 0,
                'CreDesde' => 0,
                'CreHasta' => 0,
                'gravadosCre' => 0,
                'ivaCre' => 0,
                'subCre' => 0,
                'totalCre' => 0,
                'dteDesde' => 0,
                'dteHasta' => 0,
                'gravadosDTE' => 0,
                'ivaDTE' => 0,
                'subDTE' => 0,
                'totalDTE' => 0,
                'creditosDesde' => 0,
                'creditosHasta' => 0,
                'gravadosCredi' => 0,
                'ivaCredi' => 0,
                'subCredi' => 0,
                'totalCredi' => 0,
                'totalGeneral' => 0,
                'ivaGeneral' => 0,
                'subGeneral' => 0,
                'totalPercepcion' => 0,
                'totalGlobal' => 0,
            ]);

            $this->resetUIApertura();
            return redirect()->route('pos');
            $this->emit('item-added', 'Caja Aperturada');
        }
    }

    public $disabledInputs = [];

    public function foco($inputIdAnterior, $inputIdSiguiente)
    {
        $this->CuadrarEfectivo();

        // Deshabilita el input anterior
        $this->disabledInputs[$inputIdAnterior] = true;

        // Envía el foco al siguiente
        $this->emit('focusInput', $inputIdSiguiente);
    }

    public function focoX($inputIdAnterior, $inputIdSiguiente)
    {
        $this->CuadrarEfectivoX();

        // Deshabilita el input anterior
        $this->disabledInputs[$inputIdAnterior] = true;

        // Envía el foco al siguiente
        $this->emit('focusInputX', $inputIdSiguiente);
    }

    public function resetUIApertura()
    {
        $this->fechaApertura = '';
        $this->horaApertura = '';
        $this->montoApertura = '';
    }

    protected $listeners = [
        //'scan-code' => 'ScanCode',
        'moverAEscenarioUno' => 'moverAEscenarioUno',
        'cambiarEscenario' => 'cambiarEscenario',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'scan-code-byid' => 'ScanCodeById',
        'print-ticket' => 'printTicket',
        'save-clientes' => 'SaveClientes',
        'Add' => 'ScanCode',
        'Add2' => 'ScanCode2',
        'update-canti' => 'updateUni',
        'DetalleAnulacion' => 'DetalleAnulacion',
        'CantiUpdate2' => 'CantiUpdate2',
        'AnulaDevo' => 'AnulaDevo',
        'GenerarDTE' => 'GenerarDTE',
        'procesarPago' => 'procesarPago',
        'focus-primer-cantidad' => 'focusPrimerCantidad'
    ];

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
                    ->where('i.sucursal', session('sucursal'));
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
        /*
        $p = Precios::find($barcode);
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.id',  $barcode )
            ->whereNull('p.deleted_at')
            ->where('i.sucursal', session('sucursal'))
            ->select('productos.id', 'productos.nombreProducto', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.pvventa', 'p.cantidad', 'i.sucursal', 'p.presentacion', 'p.cantidad as descargar', 'p.medida', 'p.id as pprecio', 'productos.familia', 'p.costociva', 'p.utilidad')
            ->first();

        if ($product == null)
        {
            $this->emit('scan-notfound', 'El producto no encontrado');
        }
        else
        {

            $user_id = Auth::user()->id;

            $price = $p->pvventa;
            $des = Descuentos::where('producto', $p->producto)
                ->where('inicio', '<=', Carbon::today()->endOfDay())
                ->where('fin', '>=', Carbon::today()->startOfDay())
                ->where('precio', $p->id)
                ->latest('created_at')
                ->first();

            if ($des) {
                $descuento = ($price * $des->descuento) / 100;
                $sub = $price - $descuento;
            } else {
                $descuento = 0;
                $sub = $price;
            }

            $existingCartItem = tmpVentas::where('producto', $p->producto)
            ->where('user', $user_id)
            ->where('sucursal', session('sucursal'))
            ->first();

            $validar = $cant * $p->cantidad;

            if ($existingCartItem)
            {
                $descargado = tmpVentas::where('producto', $p->producto)
                    ->where('user', $user_id)
                    ->where('sucursal', session('sucursal'))
                    ->sum('descargar');

                $validar = $cant * $p->cantidad;
                $total_descargar = $descargado + $validar;

                if ($total_descargar > $product->existencia) {
                    $this->emit('scan-notfound', 'Stock insuficiente, stock actual: ' . $product->existencia. ' unidades, unidades a ingresar:' . $total_descargar);
                    return;
                }
                else
                {
                    $tmp = tmpVentas::create([
                        'producto' => $product->id,
                        'pid' => $p->id,
                        'familia' => $product->familia,
                        'name' => $product->nombreProducto,
                        'price' => $sub,
                        'quantity' => $cant,
                        'sucursal' => $product->sucursal,
                        'codebar' => $product->codebar,
                        'descuento' => $descuento,
                        'total' => $sub * $cant,
                        'medida' => $product->presentacion,
                        'limit' => $product->descargar,
                        'descargar' => $cant * $product->descargar,
                        'uni' => $product->medida,
                        'user' => $user_id,
                        'caja' => session('caja'),
                        'empresa' => session('empresa'),
                        'esenario' => 1,
                        'uni' => $product->medida,
                        'costo' => $product->costociva,
                        'costo_total' => $product->costociva,
                        'utilidad_uni' => $product->utilidad,
                        'utilidad' => $product->utilidad,
                    ]);
                    //$this->CantiUpdate($product->id, $tmp->id, $barcode);
                }
            }
            else
            {
                $descargado = tmpVentas::where('producto', $p->producto)
                    ->where('user', $user_id)
                    ->where('sucursal', session('sucursal'))
                    ->sum('descargar');

                $validar = $cant * $p->cantidad;
                $total_descargar = $descargado + $validar;

                if ($total_descargar > $product->existencia) {
                     $this->emit('scan-notfound', 'Stock insuficiente, stock actual: ' . $product->existencia. ' unidades, unidades a ingresar:' . $total_descargar);
                    return;
                }
                else
                {
                    //dd($sub);
                    $tmp = tmpVentas::create([
                        'producto' => $product->id,
                        'pid' => $p->id,
                        'familia' => $product->familia,
                        'name' => $product->nombreProducto,
                        'price' => $sub,
                        'quantity' => $cant,
                        'sucursal' => $product->sucursal,
                        'codebar' => $product->codebar,
                        'descuento' => $descuento,
                        'total' => $sub * $cant,
                        'medida' => $product->presentacion,
                        'limit' => $product->descargar,
                        'descargar' => $cant * $product->descargar,
                        'uni' => $product->medida,
                        'user' => $user_id,
                        'caja' => session('caja'),
                        'empresa' => session('empresa'),
                        'esenario' => 1,
                        'costo' => $product->costosiva,
                        'costo_total' => $product->costosiva,
                        'utilidad_uni' => $product->utilidad,
                        'utilidad' => $product->utilidad,
                    ]);
                    //$this->CantiUpdate($product->id, $tmp->id, $barcode);
                }
            }
            $this->Carrito();
        }*/

        $user_id = Auth::id();

        // Obtener precio base
        $p = Precios::find($barcode);
        if (!$p) {
            $this->emit('scan-notfound', 'Precio no encontrado');
            return;
        }

        // Obtener producto con inventario
        $product = Productos::query()
            ->join('precios as p', 'productos.id', '=', 'p.producto')
            ->join('inventarios as i', function ($join) {
                $join->on('i.producto', '=', 'productos.id')
                    ->where('i.sucursal', session('sucursal'));
            })
            ->where('p.id', $barcode)
            ->whereNull('p.deleted_at')
            ->select(
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
                'p.costociva',
                'p.utilidad'
            )
            ->first();

        if (!$product) {
            $this->emit('scan-notfound', 'El producto no fue encontrado');
            return;
        }

        // Calcular descuento si aplica
        $price = $p->pvventa;
        $des = Descuentos::where('producto', $p->producto)
            ->whereDate('inicio', '<=', now())
            ->whereDate('fin', '>=', now())
            ->where('precio', $p->id)
            ->latest('created_at')
            ->first();

        $descuento = $des ? ($price * $des->descuento) / 100 : 0;
        $sub = $price - $descuento;

        // Validar stock disponible
        $descargado = tmpVentas::where('producto', $p->producto)
            ->where('user', $user_id)
            ->where('sucursal', session('sucursal'))
            ->sum('descargar');

        $descargarActual = $cant * $p->cantidad;
        $total_descargar = $descargado + $descargarActual;

        // --- Ajuste para productos por libra (medida = 28) ---
        $epsilon = 0.001;
        if ((int)$product->medida === 28) {
            // Si la existencia está entre 0.01 y 1, usar EXACTAMENTE lo que queda
            if ((float)$product->existencia < 1 && (float)$product->existencia > 0.01) {
                // Lo que aún queda disponible considerando lo ya "descargado" en el carrito del usuario
                $remaining = max((float)$product->existencia - (float)$descargado, 0);

                if ($remaining > 0.01) {
                    // Cantidad a vender en la unidad de venta (libras), respetando p->cantidad (factor de descarga)
                    // Si p->cantidad = 1 (1 libra descarga 1 existencia), esto será igual a $remaining
                    $cant = round($remaining / (float)$p->cantidad, 3);

                    // Fallback por si p->cantidad es 0 o algo raro
                    if ($cant <= 0 || !is_finite($cant)) {
                        $cant = round($remaining, 3);
                    }

                    // Recalcular descargas y totales con la nueva cantidad
                    $descargarActual = round($cant * (float)$p->cantidad, 3);
                    $total_descargar = round($descargado + $descargarActual, 3);
                }
            }
        }

        if (($total_descargar - (float)$product->existencia) > $epsilon) {
            $this->emit('scan-notfound', 'Stock insuficiente, stock actual: ' . $product->existencia . ' unidades, unidades a ingresar: ' . $total_descargar);
            return;
        }

        // Registrar en tmpVentas
        $tmp = tmpVentas::create([
            'producto' => $product->id,
            'pid' => $p->id,
            'familia' => $product->familia,
            'name' => $product->nombreProducto,
            'price' => $sub,
            'quantity' => $cant,
            'sucursal' => $product->sucursal,
            'codebar' => $product->codebar,
            'descuento' => $descuento,
            'total' => $sub * $cant,
            'medida' => $product->presentacion,
            'limit' => $product->descargar,
            'descargar' => $descargarActual,
            'uni' => $product->medida,
            'user' => $user_id,
            'caja' => session('caja'),
            'empresa' => session('empresa'),
            'esenario' => 1,
            'costo' => $product->costociva,
            'costo_total' => $product->costociva,
            'utilidad_uni' => $product->utilidad,
            'utilidad' => $product->utilidad,
        ]);

        // Actualizar carrito
        $this->Carrito();
    }

    ////////////para la escala de prodcutos
    public function CantiUpdate($id, $tmp, $pid)
    {
        $user_id = Auth::user()->id;

        // Buscar el item en tmpVentas
        $items = tmpVentas::find($tmp);
        if (!$items) {
            $this->emit('scan-notfound', 'Item no encontrado.');
            return;
        }

        // Obtener el producto para determinar su familia
        $producto = Productos::find($id);
        if (!$producto) {
            $this->emit('scan-notfound', 'Producto no encontrado.');
            return;
        }

        $familiaId = $producto->familia;

        // Contar los productos de la misma familia
        $conteoFamilia = tmpVentas::where('familia', $familiaId)
            ->where('user', $user_id)
            ->count();

        if ($familiaId == 1) {
            // Familia específica: aplicar escala solo al producto actual
            $precioAplicado = $this->obtenerPrecioEscala($id, $items->uni, $items->quantity, $pid);
            $this->actualizarProducto($items, $precioAplicado);
        } else {
            // Verificar productos de la misma familia en tmpVentas
            $productosFamilia = tmpVentas::where('familia', $familiaId)
                ->where('user', $user_id)
                ->get();

            // Sumar las cantidades de los productos de la misma familia
            $cantidadTotalFamilia = $productosFamilia->sum('quantity');

            // Buscar escala aplicable
            $escala = Precios::where('producto', $id)
                ->where('medida', $items->uni)
                ->where('escala', 'Si')
                ->where('cantidad', '<=', $cantidadTotalFamilia)
                ->orderBy('cantidad', 'desc')
                ->first();

            if ($escala) {
                // Aplicar la escala a todos los productos de la misma familia
                foreach ($productosFamilia as $productoFamilia) {
                    $this->actualizarProducto($productoFamilia, $escala->pventasiva);
                }
            } else {
                // No hay escala aplicable: usar el precio base para el producto actual
                $precioBase = $this->obtenerPrecioEscala($id, $items->uni, $items->quantity, $pid);
                $this->actualizarProducto($items, $precioBase);
            }
        }

        // Llamar a la función Carrito para actualizar la vista
        $this->Carrito();
    }

    private function actualizarProducto($producto, $precio)
    {
        $producto->price = $precio;
        $producto->total = $producto->quantity * $precio;
        $producto->save();
    }

    ////////para actualizar la cantidad del detalle/////////
    public function updateCanti($id)
    {
        $user_id = Auth::user()->id;

        $tmp = tmpVentas::find($id);
        // Obtener el producto junto con su inventario
        $product = Inventarios::where('sucursal', session('sucursal'))
            ->where('producto', $tmp->producto)->first();
        // Cantidad solicitada
        $cantidades = $this->can[$id];

        $validar =  $this->can[$id] * $tmp->limit;

        //dd($validar);

        // Verificar si hay suficiente stock
        if ($validar > $product->existencia) {
            $this->emit('scan-notfound', 'Stock insuficiente, Stoclk: ' . $product->existencia);
        } else {
            // Buscar descuento si aplica
            $des = Descuentos::where('producto', $tmp->producto)
                ->where('inicio', '<=', now())
                ->where('fin', '>=', now())
                ->latest('created_at')
                ->first();

            if ($des) {
                $descuento = ($tmp->price * $des->descuento) / 100;
                $sub = $tmp->price - $descuento;
            } else {
                $descuento = 0;
                $sub = $tmp->price;
            }

            $tmp->descargar = $tmp->limit * $cantidades;
            $tmp->quantity = $cantidades;
            $tmp->total = $cantidades * $sub;
            $tmp->save();
            $this->UpdateEscala22($tmp->producto, $tmp->id);
            $this->Carrito();
        }
    }

    //////////actualiza la medida para los precios
    public function updateUni($id)
    {
        $user = Auth::user();

        $datos = tmpVentas::find($id);
        if (!$datos) {
            $this->emit('item-error', 'Producto no encontrado', 'error');
            return;
        }

        $product = Precios::find($datos->producto);

        $this->ScanCode2($product->id);
        //$this->productoName = $product->nombreProducto;
        //$this->detallePrecios = Precios::where('producto', $product->id)->where('escala', 'No')
        //->orderBy('cantidad', 'asc')
        //->get();

        //$this->detalleEscalas = Precios::where('producto', $product->id)->where('escala', 'Si')
        //->orderBy('cantidad', 'asc')
        //->get();

        // $this->tmpventasId = $id; // Guardar el tmpVentas ID
        //$this->emit('abrirModall', 'detalleUnidades'); // Abrir la modal
    }

    public function CantiUpdate2($precioId, $tmpventasId)
    {
        $datos = tmpVentas::find($tmpventasId);
        if (!$datos) {
            $this->emit('item-error', 'Error al encontrar el item en tmpVentas', 'error');
            return;
        }

        $precio = Precios::find($precioId);
        if (!$precio) {
            $this->emit('item-error', 'Precio no encontrado', 'error');
            return;
        }

        // Actualizar tmpVentas con el nuevo precio y cantidad
        $datos->update([
            //'cantidad' => $precio->cantidad,
            'price' => $precio->pvventa,
            'total' => $precio->pvventa,
            'medida' => $precio->presentacion,
            'limit' => $precio->cantidad,
            'descargar' => $precio->cantidad * $datos->quantity,
            'uni' => $precio->medida
        ]);

        $this->Carrito();
    }

    //////escala de precios al actualizar la cantidad
    public function UpdateEscala($id, $tmp)
    {
        $user_id = Auth::user()->id;

        // Buscar el item en tmpVentas
        $items = tmpVentas::find($tmp);

        if (!$items) {
            $this->emit('scan-notfound', 'Item no encontrado.');
            return;
        }

        // Obtener la familia del producto
        $producto = Productos::find($id);
        if (!$producto) {
            $this->emit('scan-notfound', 'Producto no encontrado.');
            return;
        }

        $familiaId = $producto->familia;

        // Lógica para aplicar la escala
        if ($familiaId == 1) {
            // Familia específica: aplicar escala solo al producto actual
            $precioAplicado = $this->obtenerPrecioEscala($id, $items->uni, $items->quantity, $items->pid);
            $items->price = $precioAplicado;
            $items->total = $items->quantity * $precioAplicado;
            $items->save();
        } else {
            // Verificar productos de la misma familia en tmpVentas
            $productosFamilia = tmpVentas::where('familia', $familiaId)
                ->where('user', $user_id)
                ->get();

            // Sumar las cantidades de los productos de la misma familia
            $cantidadTotalFamilia = $productosFamilia->sum('quantity');

            // Buscar escala aplicable
            $escala = Precios::where('producto', $id)
                ->where('medida', $items->uni)
                ->where('escala', 'Si')
                ->where('cantidad', '<=', $cantidadTotalFamilia)
                ->orderBy('cantidad', 'desc')
                ->first();

            if ($escala) {
                // Aplicar la escala a todos los productos de la misma familia
                foreach ($productosFamilia as $productoFamilia) {
                    $productoFamilia->price = $escala->pventasiva;
                    $productoFamilia->total = $productoFamilia->quantity * $escala->pventasiva;
                    $productoFamilia->save();
                }
            } else {
                // No hay escala aplicable: usar el precio base para el producto actual
                $precioBase = $this->obtenerPrecioEscala($id, $items->uni, $items->quantity, $items->pid);
                $items->price = $precioBase;
                $items->total = $items->quantity * $precioBase;
                $items->save();
            }
        }

        // Actualizar carrito
        $this->Carrito();
    }

    //////escala de precios al actualizar la cantidad
    public function UpdateEscala22($id, $tmp)
    {
        $user_id = Auth::user()->id;

        // Buscar el item en tmpVentas
        $items = tmpVentas::find($tmp);

        if (!$items) {
            $this->emit('scan-notfound', 'Item no encontrado.');
            return;
        }

        // Obtener la familia del producto
        $producto = Productos::find($id);
        if (!$producto) {
            $this->emit('scan-notfound', 'Producto no encontrado.');
            return;
        }

        $familiaId = $producto->familia;

        // Lógica para aplicar la escala
        if ($familiaId == 1) {
            // Familia específica: aplicar escala solo al producto actual
            //dd($id, $items->uni, $items->descargar, $items->pid);
            $precioAplicado = $this->obtenerPrecioEscala2($id, $items->uni, $items->descargar, $items->pid);

            if ($items->uni == 22 && $items->descargar > 0.95) {
                $preee = $precioAplicado / $items->quantity;
            } else {
                $preee = $precioAplicado;
            }
            //dd($preee);
            $items->price = $preee;
            $items->total = $items->quantity * $preee;
            $items->save();
        } else {
            // Verificar productos de la misma familia en tmpVentas
            $productosFamilia = tmpVentas::where('familia', $familiaId)
                ->where('user', $user_id)
                ->get();

            // Sumar las cantidades de los productos de la misma familia
            $cantidadTotalFamilia = $productosFamilia->sum('quantity');

            // Buscar escala aplicable
            $escala = Precios::where('producto', $id)
                ->where('medida', $items->uni)
                ->where('escala', 'Si')
                ->where('cantidad', '<=', $cantidadTotalFamilia)
                ->orderBy('cantidad', 'desc')
                ->first();

            if ($escala) {
                // Aplicar la escala a todos los productos de la misma familia
                foreach ($productosFamilia as $productoFamilia) {
                    $productoFamilia->price = $escala->pventasiva;
                    $productoFamilia->total = $productoFamilia->quantity * $escala->pventasiva;
                    $productoFamilia->save();
                }
            } else {
                // No hay escala aplicable: usar el precio base para el producto actual
                $precioBase = $this->obtenerPrecioEscala2($id, $items->uni, $items->descargar, $items->pid);
                $items->price = $precioBase;
                $items->total = $items->quantity * $precioBase;
                $items->save();
            }
        }

        // Actualizar carrito
        $this->Carrito();
    }

    private function obtenerPrecioEscala($productoId, $medida, $cantidad, $pid)
    {
        if ($medida == 22) {
            $precioBase = Precios::find($pid);
            return $precioBase ? $precioBase->pvventa : 0;
        }

        // Buscar escala aplicable
        $escala = Precios::where('producto', $productoId)
            //->where('medida', $medida)
            //->where('escala', 'Si')
            ->where('cantidad', '<=', $cantidad)
            ->orderBy('cantidad', 'desc')
            ->first();

        if ($escala) {
            return $escala->pventasiva;
        }

        // Buscar precio base
        $precioBase = Precios::find($pid);

        return $precioBase ? $precioBase->pvventa : 0;
    }

    private function obtenerPrecioEscala2($productoId, $medida, $cantidad, $pid)
    {
        //dd($productoId, $medida, $cantidad, $pid);
        // Si la medida es 22 (granel), retorna precio base
        if ($medida == 22) {
            /*$precioBase = Precios::find($pid);
            return $precioBase ? $precioBase->pvventa : 0;*/

            $precioGranel = Precios::find($pid); // El precio de granel por unidad base (por ejemplo, 1 gramo)

            if (!$precioGranel) return 0;

            $cantidadGranel = $cantidad; // ← esta variable debes tenerla definida

            // Buscar el precio de la unidad normal
            $precioUnidad = Precios::where('producto', $precioGranel->producto)
                ->where('cantidad', 1)
                ->where('medida', '!=', 22) // ← medida diferente a granel
                ->first();

            if ($cantidadGranel >= 0.95 && $precioUnidad) {
                // Si la cantidad en granel equivale a una unidad o más, usar el precio normal
                $pre = $precioUnidad->pvventa / number_format($cantidadGranel, 1); // Calcular el precio proporcional
                return $pre;
            }

            // Si no ha llegado a la unidad, usar el precio proporcional del granel
            return $precioGranel->pvventa;
        }
        // Buscar escala aplicable
        $escala = Precios::where('producto', $productoId)
            ->where('medida', $medida)
            ->where('escala', 'Si')
            ->where('cantidad', '<=', $cantidad)
            ->orderBy('cantidad', 'desc')
            ->first();

        if ($escala) {
            return $escala->pventasiva;
        }

        // Buscar precio base
        $precioBase = Precios::find($pid);

        return $precioBase ? $precioBase->pvventa : 0;



        //dd($pid);

        // Buscar escala aplicable
        /*$escala = Precios::where('producto', $productoId)
            ->where('cantidad', '<=', $cantidad)
            //->where('medida', $medida)
            ->orderBy('cantidad', 'desc')
            ->first();

        if ($escala) {
            return $escala->pvventa;
        }

        // Si no encuentra escala, retorna precio base
        $precioBase = Precios::find($pid);
        return $precioBase ? $precioBase->pvventa : 0;*/
    }

    public function removeItem($id)
    {
        $tmp = tmpVentas::find($id);

        if ($tmp) {
            $tmp->delete();
        }
        $this->Carrito();
    }

    public function SaveClientes()
    {
        $rules = [
            'nombreCliente' => 'required|min:3',
            'dui' => 'required|unique:clientes',
            'correo' => 'required|email',
            'celular' => 'required',
            'departamento' => 'required|not_in:Elegir',
            'municipio' => 'required|not_in:Elegir',
        ];

        $messages = [
            'nombreCliente.required' => 'Nombre del Cliente es requerido',
            'nombreCliente.min' => 'El Nombre del cliente debe tener más de 3 caracteres',
            'dui.required' => 'El número de DUI es requerido',
            'dui.unique' => 'El número de DUI ya está registrado',
            'correo.required' => 'El correo electrónico del cliente es requerido',
            'correo.email' => 'El formato del correo electrónico no es válido',
            'celular.required' => 'El número de celular del cliente es requerido',
            'departamento.required' => 'El departamento es requerido',
            'departamento.not_in' => 'Por favor, elige un valor diferente a "Elegir" en el departamento',
            'municipio.required' => 'El municipio es requerido',
            'municipio.not_in' => 'Por favor, elige un valor diferente a "Elegir" en el municipio',
        ];
        //dd('entra');
        $this->validate($rules, $messages);

        $cli = Clientes::create([
            'nombreCliente' => strtoupper($this->nombreCliente),
            'tipoPersona' => $this->tipoPersona,
            'dui' => $this->dui,
            'nit' => $this->nit,
            'homologado' => $this->homologado,
            'registro' => $this->registro,
            'giro' => strtoupper($this->clienteAddSelectName),
            'direccion' => strtoupper($this->direccion),
            'telefono' => $this->telefono,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'distrito' => $this->distrito,
            'actividad' => $this->clienteAddSelectId,
            'idenReceptor' => $this->idenReceptor,
            'celular' => $this->celular,
            'email' => $this->correo,
            'desActividad' => strtoupper($this->clienteAddSelectName)
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

    public function Cash()
    {
        $user_id = Auth::user()->id;

        $this->cambio = floatval($this->efectivo) - $this->total;
    }

    private function ValidarVentas()
    {
        /*
        // 1) En vez de borrar cajas en 0.00, solo márcalas como Eliminado (operación idempotente)
        Caja::where('caja', session('caja'))
            ->where('sucursal', session('sucursal'))
            ->where('empresa', session('empresa'))
            ->whereDate('fecha', now()->toDateString())
            ->where('total', 0) // evita decimales; la columna suele ser DECIMAL(…)
            ->update(['estado' => 'Eliminado']);

        // 2) Obtén IDs de ventas sin detalle del día (solo los IDs, sin traer modelos)
        $ventasIds = Ventas::where('caja', session('caja'))
            ->where('sucursal', session('sucursal'))
            ->where('empresa', session('empresa'))
            ->whereDate('fecha', now()->toDateString())
            ->whereDoesntHave('RdetalleVentas') // usa whereDoesntHave (alias)
            ->pluck('id');

        // 3) Marca en un solo UPDATE las cajas asociadas a esas ventas
        if ($ventasIds->isNotEmpty()) {
            Caja::whereIn('venta', $ventasIds)->update(['estado' => 'Eliminado']);
            // Si también querés marcar la venta:
            // Ventas::whereIn('id', $ventasIds)->update(['estado' => 'Eliminado']);
        }
        */
    }

    /*public function SaveTicket()
    {
        if ($this->procesando) return; // 🚫 evita doble venta
        $this->procesando = true;

        $transaccionIniciada = false;

        $this->ValidarVentas();



        $cartItems = tmpVentas::where('user', Auth::user()->id)
            ->where('caja', session('caja'))
            ->where('esenario', 1)
            ->get();
        $totales = $cartItems->sum('total');


        $rules = [
            'metodo' => 'required',
            'efectivo' => ['required', 'numeric', 'gte:' . round($totales, 2)],
        ];

        $messages = [
            'metodo.required' => 'Seleccione un método de pago.',
            'efectivo.required' => 'Digite el efectivo para procesar la venta.',
            'efectivo.gte' => 'El efectivo digitado es menor al total de la venta (Total: ' . round($totales, 2) . ').'
        ];

        $this->validate($rules, $messages);


        $descuentos = $cartItems->sum('descuento');


        $this->itemsQuantity = $cartItems->sum('quantity');
        $sub = $totales;
        $tiva = $sub - $sub / 1.13;
        $tt = $sub - $tiva;

        $percecion = 0;

        if ($this->metodo == 4) {
            $estado = 'Credito';
        } else {
            $estado = 'Cancelado';
        }

        $para   = Parametros::find(session('caja'));
        $where  = [
            'usuario'  => Auth::id(),
            'empresa'  => session('empresa'),
            'sucursal' => session('sucursal'),
            'caja'     => session('caja'),
        ];
        $limite = (int) $para->envio;

        $registro = envioTmp::firstWhere($where);

        if (! $registro) {
            // No existe: primer envío → contar 1 y gratis
            envioTmp::create(array_merge($where, ['envio' => 1]));
            $enviar = 1;
        } else {
            if ($limite == 0 || $registro->envio < $limite) {
                // Aún dentro de los N gratis
                $registro->increment('envio');
                $enviar = 1;
            } else {
                // Ya agotó sus N gratis: éste es de pago y reiniciamos a 0
                $registro->update(['envio' => 0]);
                $enviar = 0;
            }
        }

        $correlativo        = $para->tcorrelativo;
        $control            = null;
        $codigoGeneracion   = null;
        $tipo               = 'Fisico';

        if ($enviar && $para->dte === 'Si') {
            // Generar DTE
            $control = $this->obtenerCodigoDTE(1);
            $this->GenerarToken();
            $tokenActivo = Tocken::where('estado', 'ok')
                ->whereDate('fecha', Carbon::now()->toDateString())
                ->first();
            $codigoGeneracion = strtoupper(Str::uuid());
            $tipo = 'DTE';
        }

        try {
            DB::beginTransaction();
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
                'caja' => session('caja'),
                'sucursal' => session('sucursal'),
                'empresa' => session('empresa'),
                'subtotal' => $sub,
                'descuento' => $descuentos,
                'iva' => $tiva,
                'percepcion' => $percecion,
                'total' => $sub,
                'estado' => $estado,
                'qr' => '',
                'envio' => $enviar,
            ]);

            if ($ingre->total <> 0) {
                $items = tmpVentas::where('user', Auth::user()->id)
                    ->where('caja', session('caja'))
                    ->where('sucursal', session('sucursal'))
                    ->where('empresa', session('empresa'))
                    ->where('esenario', 1)
                    ->get();

                foreach ($items as $item) {
                    $subb = $item->price - $item->descuento;
                    $iva = $subb - $subb / 1.13;
                    $ttt = $subb - $iva;

                    $total = ($ttt + $iva) * $item->quantity;

                    $costo_total = ($item->costo / $item->descargar) * $item->descargar;

                    $utilidad = ($total - $costo_total) / $total;

                    $detalle = VentasDetalles::create([
                        'venta' => $ingre->id,
                        'producto' => $item->producto,
                        'medida' => $item->uni,
                        'name' => $item->name,
                        'unidad' => $item->medida,
                        'descargar' => $item->descargar,
                        'cantidad' => $item->quantity,
                        'precio' => $item->price,
                        'descuento' => $item->descuento,
                        'subtotal' => $subb * $item->quantity,
                        'iva' => $iva * $item->quantity,
                        'total' => ($ttt + $iva) * $item->quantity,
                        'costo' => $item->costo / $item->descargar,
                        'costo_total' => $costo_total,
                        'utilidad_uni' => $total - $costo_total,
                        'utilidad' => ($utilidad * 100)
                    ]);

                    ///descargar del inventario
                    $in = Inventarios::where('producto', $item->producto)
                        ->where('sucursal', session('sucursal'))
                        //->where('empresa', session('empresa'))
                        ->first();

                    if ($in->existencia == 0) {
                        $newStock = 0;
                    } else {
                        $newStock = $in->existencia - $item->descargar;
                    }

                    $in->existencia = $newStock;
                    $in->save();

                    /////ingreso al kardex/////////////////
                    $p = Precios::where('producto', $item->producto)
                        ->where('medida', $item->uni)
                        ->withoutTrashed()
                        ->first();

                    $des = 'Venta con tikect numero ' . $correlativo . " realizado por " . Auth::user()->name;
                    $kardex = Kardex::create([
                        'producto' => $item->producto,
                        'inventario' => $in->id,
                        'descripcion' => $des,
                        'fecha' => date('Y-m-d'),
                        'hora' => date('H:i:s'),
                        'ingresoCantidad' => 0,
                        'ingresoValor' => 0,
                        'egresoCantidad' => $item->descargar,
                        'egresoValor' => $p->costociva * $item->descargar,
                        'saldoCantidad' => $newStock,
                        'saldoValor' => $newStock * $p->costociva,
                    ]);
                }
                //////agregar los datos a caja///////////
                $corte = Cortes::where('caja', session('caja'))
                    ->where('sucursal', session('sucursal'))
                    ->where('empresa', session('empresa'))
                    ->where('fecha', date('Y-m-d'))->select('id')->first();

                $ca = Caja::create([
                    'caja' => session('caja'),
                    'sucursal' => session('sucursal'),
                    'empresa' => session('empresa'),
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
                    'comprobante' => $this->comprobante,
                    'efectivo' => $this->efectivo,
                    'cambio' => $this->efectivo - $sub,
                    'subtotal' => $sub,
                    'descuento' => $descuentos,
                    'iva' => $tiva,
                    'percepcion' => $percecion,
                    'total' => $sub,
                    'estado' => $estado,
                    'arqueado' => false,
                    'envio' => $enviar,
                ]);

                if ($this->metodo == 4) {
                    $ultimoCorrelativo = Creditos::orderBy('correlativo', 'desc')->first();
                    $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo->correlativo + 1 : 1;

                    $credito = Creditos::create([
                        'venta' => $ingre->id,
                        'cliente' => 1,
                        'empresa' => session('empresa'),
                        'sucursal' => session('sucursal'),
                        'correlativo' => $nuevoCorrelativo,
                        'fechaCredito' => date('Y-m-d'),
                        'fechaPago' => Carbon::now()->addDays(15)->format('Y-m-d'),
                        'total' => $sub,
                        'saldo' => $sub,
                        'estado' => 'Pendiente',
                    ]);
                }

                $user = Auth::user();
                $emprea = Empresas::find($user->empresa);

                $ambiente = AmbienteDestino::find($emprea->ambiente);
                $tipoDte = TipoDocumento::where('status', 'Activo')->where('valor', 'FACTURA')->first();
                $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
                $tipoOpera = TipoTransmision::where('status', 'Activo')->first();
                $tipoContingencia = TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first();
                $tributo = Tributo::where('status', 'Activo')->whereNull('codigo')->first();
                $condipago = $this->metodo == 4 ? 2 : 1;

                if ($para->dte == 'Si' && $enviar) {
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
                        'emisor' => session('sucursal'),
                        'receptor' => 1,
                        'otrosDocuentos' => null,
                        'ventaTercero' => null,
                        'venta' => $ingre->id,
                        'tocken' => $tokenActivo->id,
                        'sello' => null,
                        'estado' => 'Creado',
                        'jsonDte' => null,
                        'caja' => session('caja'),
                        'sucursal' => session('sucursal'),
                        'empresa' => session('empresa'),
                    ]);

                    $detalleDTE = resumenDte::create([
                        'dte' => $dte->id,
                        'totalNoSuj' => 0,
                        'totalExenta' => 0,
                        'totalGravada' => ($sub + $descuentos),
                        'totalIva' => $tiva,
                        'subTotalVentas' => $sub,
                        'descuNoSuj' => 0,
                        'descuExenta' => 0,
                        'descuGravada' => 0,
                        'porcentajeDescuento' => 0,
                        'totalDescu' => $descuentos,
                        'tributo' => $tributo->id,
                        'codigo' => null,
                        'descripcion' => null,
                        'valor' => null,
                        'subTotal' => $sub,
                        'ivaPerci1' => 0,
                        'ivaRete1' => $percecion,
                        'reteRenta' => 0,
                        'montoTotalOperacion' => $sub -  $percecion,
                        'totalNoGravado' => 0,
                        'totalPagar' => ($sub -  $percecion),
                        'totalLetras' => strtoupper(Convertidor::montoALetras(round(($sub - $percecion), 2))),
                        'saldoFavor' => 0,
                        'condicionOperacion' => $condipago,
                        'pagos' => $this->metodo,
                        'montoPagado' => null,
                        'refencia' => null,
                        'palzo' => null,
                        'periodo' => null,
                        'numPagoElectronico' => null,
                    ]);
                }

                $this->resetPago();
            }

            ///actualizo el nuevo correlativo
            $para->tcorrelativo = $correlativo + 1;
            $para->save();
            $caja = $ca->id;

            DB::commit();
            if ($ingre->total <> 0) {
                if ($para->dte == 'Si' && $enviar == 1) {
                    if ($para->dteAutomatico == 'Si') {

                        $this->emit('startProcessing2', $dte->id);
                    } else {
                        //$this->ImprimirTicket($ca->id);
                        $this->emit('print-ticket', $this->TraitTikets($ca->id));
                    }
                } else {
                    $this->emit('print-ticket', $this->TraitTikets($caja));
                }
            } else {
                $this->emit('print-ticket', $this->TraitTikets($caja));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            //session()->flash('error', $e->getMessage());
            $this->emit('item-error', $e->getMessage());
        }
    }*/

    public function SaveTicket()
    {
        if ($this->procesando) return; // 🚫 evita doble venta
        $this->procesando = true;

        $transaccionIniciada = false;

        try {
            // =====================================================
            // 1) CARGAR CARRITO UNA SOLA VEZ
            // =====================================================
            $cartItems = tmpVentas::where('user', Auth::id())
                ->where('caja', session('caja'))
                ->where('sucursal', session('sucursal'))
                ->where('empresa', session('empresa'))
                ->where('esenario', 1)
                ->get();

            if ($cartItems->isEmpty()) {
                $this->emit('item-error', 'No hay productos en el carrito.');
                return;
            }

            $totales       = $cartItems->sum('total');
            $descuentos    = $cartItems->sum('descuento');
            $this->itemsQuantity = $cartItems->sum('quantity');

            $sub  = $totales;
            $tiva = $sub - ($sub / 1.13);
            $tt   = $sub - $tiva; // (no lo usas pero lo conservo)

            $percecion = 0;
            $estado    = ($this->metodo == 4) ? 'Credito' : 'Cancelado';

            // =====================================================
            // 2) VALIDACIÓN
            // =====================================================
            $rules = [
                'metodo'   => 'required',
                'efectivo' => 'required',
            ];

            $messages = [
                'metodo.required'   => 'Seleccione un método de pago.',
                'efectivo.required' => 'Digite el efectivo para procesar la venta.',
            ];

            $this->validate($rules, $messages);

            if ($this->efectivo < $totales) {
                $this->emit('item-error', 'El efectivo ingresado es menor al total de la venta.');
                return;
            }

            // =====================================================
            // 3) PARÁMETROS + CONTROL DE ENVÍO (FUERA DE TRANSACCIÓN)
            // =====================================================
            $para = Parametros::findOrFail(session('caja'));

            $whereEnvio = [
                'usuario'  => Auth::id(),
                'empresa'  => session('empresa'),
                'sucursal' => session('sucursal'),
                'caja'     => session('caja'),
            ];

            $limiteEnvio = max(1, (int) $para->envio);
            $registroEnvio = envioTmp::firstWhere($whereEnvio);

            if (!$registroEnvio) {
                // Primer envío → cuenta 1 y se envía
                envioTmp::create(array_merge($whereEnvio, ['envio' => 1]));
                $enviar = 1;
            } else {
                if ($registroEnvio->envio < $limiteEnvio) {
                    $registroEnvio->increment('envio');
                    $enviar = 1;
                } else {
                    // Límite alcanzado → este no se envía, reinicia conteo
                    $registroEnvio->update(['envio' => 0]);
                    $enviar = 0;
                }
            }

            // =====================================================
            // 4) FILTRO INVERSOR (parametros.filtro)
            // =====================================================
            if ((int) $para->filtro === 0) {
                $enviar = $enviar ? 0 : 1;
            }

            $correlativo      = $para->tcorrelativo;
            $control          = null;
            $codigoGeneracion = null;
            $tipo             = 'Fisico';
            $tokenActivo      = null;

            // =====================================================
            // 4) DTE: OBTENER CÓDIGO + TOKEN SOLO SI ES NECESARIO
            // =====================================================
            if ($enviar && $para->dte === 'Si') {
                $control = $this->obtenerCodigoDTE(1);

                // Busca token vigente primero
                $tokenActivo = Tocken::where('estado', 'ok')
                    ->whereDate('fecha', Carbon::now()->toDateString())
                    ->first();

                // Si no hay, lo genera una sola vez
                if (!$tokenActivo) {
                    $this->GenerarToken();

                    $tokenActivo = Tocken::where('estado', 'ok')
                        ->whereDate('fecha', Carbon::now()->toDateString())
                        ->first();
                }

                $codigoGeneracion = strtoupper(Str::uuid());
                $tipo = 'DTE';
            }

            // =====================================================
            // 5) DATOS COMUNES
            // =====================================================
            $user           = Auth::user();
            $fechaHoy       = date('Y-m-d');
            $horaHoy        = date('H:i:s');
            //$codigoVendedor = $this->codigoVendedor ?: ($user->codigo ?? null);

            // Cargar catálogos DTE solo si de verdad se usarán
            $ambiente = $tipoDte = $tipoModelo = $tipoOpera = $tipoContingencia = $tributo = null;
            $condipago = null;

            if ($para->dte == 'Si' && $enviar) {
                $empresaModel = Empresas::find($user->empresa);

                $ambiente = AmbienteDestino::select('id')->find($empresaModel->ambiente);
                $tipoDte = TipoDocumento::select('id')
                    ->where('status', 'Activo')
                    ->where('valor', 'FACTURA')
                    ->first();
                $tipoModelo = ModeloFacturacion::select('id')
                    ->where('status', 'Activo')
                    ->first();
                $tipoOpera = TipoTransmision::select('id')
                    ->where('status', 'Activo')
                    ->first();
                $tipoContingencia = TipoContigencia::select('id')
                    ->where('status', 'Activo')
                    ->whereNull('codigo')
                    ->first();
                $tributo = Tributo::select('id')
                    ->where('status', 'Activo')
                    ->whereNull('codigo')
                    ->first();

                $condipago = $this->metodo == 4 ? 2 : 1;
            }

            // =====================================================
            // 6) TRANSACCIÓN PRINCIPAL
            // =====================================================
            DB::beginTransaction();
            $transaccionIniciada = true;

            // 6.1 Crear cabecera de venta
            $ingre = Ventas::create([
                'cliente'        => 1,
                'tipoPago'       => $this->metodo,
                'facturador'     => 1,
                'correlativo'    => $correlativo,
                'fecha'          => $fechaHoy,
                'hora'           => $horaHoy,
                'tipo'           => $tipo,
                'codigo'         => $codigoGeneracion,
                'numero'         => $control,
                'sello'          => '',
                'vendedor'       => $user->id,
                'caja'           => session('caja'),
                'sucursal'       => session('sucursal'),
                'empresa'        => session('empresa'),
                'subtotal'       => $sub + $descuentos,
                'descuento'      => $descuentos,
                'iva'            => $tiva,
                'percepcion'     => $percecion,
                'total'          => $sub,
                'estado'         => $estado,
                'qr'             => '',
                //'codigoVendedor' => $codigoVendedor,
                'envio'          => $enviar,
            ]);

            $cajaId = null;
            $dte    = null;

            if ($ingre->total != 0) {
                $items = $cartItems; // ya los tenemos en memoria

                // ---------------------------------------------
                // 6.2 PRE-CARGAR INVENTARIOS DE TODOS LOS PRODUCTOS
                // ---------------------------------------------
                $productoIds = $items->pluck('producto')->unique()->values();
                $inventarios = Inventarios::whereIn('producto', $productoIds)
                    ->where('sucursal', session('sucursal'))
                    //->where('empresa', session('empresa'))
                    ->get()
                    ->keyBy('producto');

                $now              = now();
                $detalleRows      = [];
                $kardexRows       = [];
                $inventariosToUpd = [];

                $descripcionBase = 'Venta con tikect numero ' . $correlativo .
                    ', en caja ' . $para->caja .
                    ', cajero ' . $user->name;

                // Agrupamos por producto para simular correctamente el saldo en kardex
                foreach ($items->groupBy('producto') as $productoId => $itemsProducto) {

                    /** @var \App\Models\Inventarios|null $inv */
                    $inv = $inventarios->get($productoId);

                    if (!$inv) {
                        throw new \RuntimeException("No existe inventario para el producto {$productoId}");
                    }

                    $existenciaActual = $inv->existencia;

                    foreach ($itemsProducto as $item) {
                        $subb    = $item->price - $item->descuento;
                        $ivaUnit = $subb - $subb / 1.13;
                        $ttUnit  = $subb - $ivaUnit;

                        // Detalle de venta (ventas_detalles)
                        $detalleRows[] = [
                            'venta'        => $ingre->id,
                            'producto'     => $item->producto,
                            'medida'       => $item->uni,
                            'name'         => $item->name,
                            'unidad'       => $item->medida,
                            'descargar'    => $item->quantity,
                            'cantidad'     => $item->quantity,
                            'precio'       => $item->price,
                            'descuento'    => $item->descuento,
                            'subtotal'     => $subb * $item->quantity,
                            'iva'          => $ivaUnit * $item->quantity,
                            'total'        => ($ttUnit + $ivaUnit) * $item->quantity,
                            'costo'        => $item->costo,
                            'costo_total'  => $item->costo_total,
                            'utilidad_uni' => $item->utilidad_uni,
                            'utilidad'     => $item->utilidad,
                            'sincro_id'      => Str::uuid(),
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];

                        // Simular salida en inventario y kardex
                        $existenciaActual = max(0, $existenciaActual - $item->descargar);

                        $kardexRows[] = [
                            'producto'       => $item->producto,
                            'inventario'     => $inv->id,
                            'descripcion'    => $descripcionBase,
                            'fecha'          => $fechaHoy,
                            'hora'           => $horaHoy,
                            'ingresoCantidad' => 0,
                            'ingresoValor'   => 0,
                            'egresoCantidad' => $item->descargar,
                            'egresoValor'    => $item->costo * $item->descargar,
                            'saldoCantidad'  => $existenciaActual,
                            'saldoValor'     => $existenciaActual * $item->costo,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                            'sincro_id'      => Str::uuid(),
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }

                    // Al final, actualizar existencia de este inventario
                    $inventariosToUpd[] = [
                        'id'         => $inv->id,
                        'existencia' => $existenciaActual,
                        'updated_at' => $now,
                    ];
                }

                // ---------------------------------------------
                // 6.3 APLICAR CAMBIOS EN BLOQUE
                // ---------------------------------------------
                if (!empty($inventariosToUpd)) {
                    foreach ($inventariosToUpd as $inv) {
                        Inventarios::where('id', $inv['id'])
                            ->update([
                                'existencia' => $inv['existencia'],
                                'updated_at' => $inv['updated_at'],
                            ]);
                    }
                }

                if (!empty($detalleRows)) {
                    VentasDetalles::insert($detalleRows);
                }

                if (!empty($kardexRows)) {
                    Kardex::insert($kardexRows);
                }

                // ---------------------------------------------
                // 6.4 REGISTRAR EN CAJA
                // ---------------------------------------------
                $corte = Cortes::where('caja', session('caja'))
                    ->where('sucursal', session('sucursal'))
                    ->where('empresa', session('empresa'))
                    ->where('fecha', $fechaHoy)
                    ->select('id')
                    ->first();

                if (!$corte) {
                    throw new \RuntimeException('No existe un corte abierto para hoy.');
                }

                $ca = Caja::create([
                    'caja'        => session('caja'),
                    'sucursal'    => session('sucursal'),
                    'empresa'     => session('empresa'),
                    'corte'       => $corte->id,
                    'venta'       => $ingre->id,
                    'facturador'  => 1,
                    'tipoPago'    => $this->metodo,
                    'correlativo' => $correlativo,
                    'codigo'      => $codigoGeneracion,
                    'numero'      => $control,
                    'sello'       => null,
                    'fecha'       => $fechaHoy,
                    'hora'        => $horaHoy,
                    'cajero'      => $user->id,
                    'comprobante' => $this->comprobante,
                    'efectivo'    => $this->efectivo,
                    'cambio'      => $this->efectivo - $sub,
                    'subtotal'    => $sub + $descuentos,
                    'descuento'   => $descuentos,
                    'iva'         => $tiva,
                    'percepcion'  => $percecion,
                    'total'       => $sub,
                    'estado'      => $estado,
                    'arqueado'    => false,
                    'envio'       => $enviar,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                $cajaId = $ca->id;

                // ---------------------------------------------
                // 6.5 CREAR DTE + RESUMEN SI APLICA
                // ---------------------------------------------
                if ($para->dte == 'Si' && $enviar && $ambiente && $tipoDte && $tipoModelo && $tipoOpera && $tipoContingencia && $tributo && $tokenActivo) {
                    $dte = dte::create([
                        'motivoContin'     => null,
                        'version'          => 1,
                        'ambiente'         => $ambiente->id,
                        'tipoDte'          => $tipoDte->id,
                        'numeroControl'    => $control,
                        'codigoGeneracion' => $codigoGeneracion,
                        'tipoModelo'       => $tipoModelo->id,
                        'tipoOperacion'    => $tipoOpera->id,
                        'tipoContingencia' => $tipoContingencia->id,
                        'fecEmi'           => $fechaHoy,
                        'horEmi'           => $horaHoy,
                        'tipoMoneda'       => 'USD',
                        'documentoRelacionado' => null,
                        'emisor'           => session('sucursal'),
                        'receptor'         => 1,
                        'otrosDocuentos'   => null,
                        'ventaTercero'     => null,
                        'venta'            => $ingre->id,
                        'tocken'           => $tokenActivo->id,
                        'sello'            => null,
                        'estado'           => 'Creado',
                        'jsonDte'          => null,
                        'caja'             => session('caja'),
                        'sucursal'         => session('sucursal'),
                        'empresa'          => session('empresa'),
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);

                    $montoTotal     = $cartItems->sum('total');
                    $totalDescuento = $cartItems->sum('descuento');
                    $totalIva       = $montoTotal - ($montoTotal / 1.13);

                    resumenDte::create([
                        'dte'                 => $dte->id,
                        'totalNoSuj'          => 0,
                        'totalExenta'         => 0,
                        'totalGravada'        => $montoTotal,
                        'totalIva'            => $totalIva,
                        'subTotalVentas'      => $montoTotal,
                        'descuNoSuj'          => 0,
                        'descuExenta'         => 0,
                        'descuGravada'        => 0,
                        'porcentajeDescuento' => 0,
                        'totalDescu'          => $totalDescuento,
                        'tributo'             => $tributo->id,
                        'codigo'              => null,
                        'descripcion'         => null,
                        'valor'               => null,
                        'subTotal'            => $montoTotal,
                        'ivaPerci1'           => 0,
                        'ivaRete1'            => 0,
                        'reteRenta'           => 0,
                        'montoTotalOperacion' => $montoTotal,
                        'totalNoGravado'      => 0,
                        'totalPagar'          => $montoTotal,
                        'totalLetras'         => strtoupper(Convertidor::montoALetras(round($montoTotal, 2))),
                        'saldoFavor'          => 0,
                        'condicionOperacion'  => $condipago,
                        'pagos'               => $this->metodo,
                        'montoPagado'         => null,
                        'refencia'            => null,
                        'palzo'               => null,
                        'periodo'             => null,
                        'numPagoElectronico'  => null,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                }
            }

            // ---------------------------------------------
            // 6.6 ACTUALIZAR CORRELATIVO
            // ---------------------------------------------
            $para->tcorrelativo = $correlativo + 1;
            $para->save();

            DB::commit();
            $transaccionIniciada = false;

            // =====================================================
            // 7) RESET PAGO Y DISPARO DE IMPRESIÓN (FUERA TRANSACCIÓN)
            // =====================================================
            $this->resetPago();

            /*if ($ingre->total != 0) {
                if ($para->dte == 'Si' && $enviar == 1 && $para->dteAutomatico == 'Si' && $dte) {
                    $this->emit('startProcessing2', $dte->id);
                } else {
                    $this->emit('print-ticket', $this->TraitTikets($cajaId));
                }
            } else {
                $this->emit('print-ticket', $this->TraitTikets($cajaId));
            }*/

            //dd($dte->id);

            if ($ingre->total <> 0) {
                // Siempre imprimimos el ticket YA (sin esperar DTE)
                $this->emit('print-ticket', $this->TraitTikets($cajaId));

                // Si hay que generar DTE automático: se hace en background
                /* if ($para->dte == 'Si' && $enviar == 1 && $para->dteAutomatico == 'Si') {
                    //\App\Jobs\ProcesarDTEJob::dispatch($dte->id);
                    dispatch(new ProcesarDTEJob($dte->id));
                }*/
            }
        } catch (\Throwable $e) {
            if ($transaccionIniciada) {
                DB::rollBack();
            }
            $this->emit('item-error', $e->getMessage());
        } finally {
            $this->procesando = false;
        }
    }

    public function SaveConsumidor()
    {
        $this->ValidarVentas();

        $rules = [
            'metodo' => 'required',
            'idC' => 'Required'
        ];

        $messages = [
            'metodo.required' => 'Seleccione un metodo diferente de Elegir',
            'idC.required' => 'No ha seleccionado un cliente para guardar esta venta'
        ];

        $this->validate($rules, $messages);

        $user = Auth::user();

        $cartItems = tmpVentas::where('user', Auth::user()->id)
            ->where('caja', session('caja'))
            ->where('esenario', 1)
            ->get();
        $totales = $cartItems->sum('total');
        $descuentos = $cartItems->sum('descuento');


        $this->itemsQuantity = $cartItems->sum('quantity');
        $sub = $totales;
        $tiva = $sub - $sub / 1.13;
        $tt = $sub - $tiva;

        $c = Clientes::find($this->idC);

        if ($c->tipoPersona == 3) {
            $percecion = $tt * 0.01;
        } else {
            $percecion = 0;
        }

        if ($this->metodo == 4) {
            $estado = 'Credito';
        } else {
            $estado = 'Cancelado';
        }

        //$para = Parametros::find(session('caja'));
        //$correlativo = $para->concorrelativo;

        $para = Parametros::find(session('caja'));
        $correlativo = empty($this->correlativo) ? $para->concorrelativo : $this->correlativo;

        $fecha = empty($this->fecha) ? now()->format('Y-m-d') : $this->fecha;



        if ($para->dte == "Si") {
            $control = $this->obtenerCodigoDTE(1);
            $this->GenerarToken();
            $codigoGeneracion = strtoupper(Str::uuid()->toString());
            $fechaHoy = Carbon::now()->toDateString();
            $tokenActivo = Tocken::where('estado', 'ok')->whereDate('fecha', $fechaHoy)->first();
            $tipo = 'DTE';
        } else {
            $control = NULL;
            $codigoGeneracion = NULL;
            $tipo = 'Fisico';
        }

        try {
            DB::beginTransaction();
            $ingre = Ventas::create([
                'cliente' => $this->idC,
                'tipoPago' => $this->metodo,
                'facturador' => 2,
                'correlativo' => $correlativo,
                'fecha' => $fecha,
                'hora' => date('H:i:s'),
                'tipo' => $tipo,
                'codigo' => $codigoGeneracion,
                'numero' => $control,
                'sello' => '',
                'vendedor' => Auth::user()->id,
                'caja' => session('caja'),
                'sucursal' => session('sucursal'),
                'empresa' => session('empresa'),
                'subtotal' => $sub,
                'descuento' => $descuentos,
                'iva' => $tiva,
                'percepcion' => $percecion,
                'total' => $sub - $percecion,
                'estado' => $estado,
                'qr' => '',
            ]);

            if ($ingre) {
                foreach ($cartItems as $item) {
                    $subb = $item->price - $item->descuento;
                    $iva = $subb - $subb / 1.13;
                    $ttt = $subb - $iva;

                    $total = ($ttt + $iva) * $item->quantity;

                    $costo_total = ($item->costo / $item->descargar) * $item->descargar;

                    $utilidad = ($total - $costo_total) / $total;

                    VentasDetalles::create([
                        'venta' => $ingre->id,
                        'producto' => $item->producto,
                        'medida' => $item->uni,
                        'name' => $item->name,
                        'unidad' => $item->medida,
                        'descargar' => $item->descargar,
                        'cantidad' => $item->quantity,
                        'precio' => $item->price,
                        'descuento' => $item->descuento,
                        'subtotal' => $subb * $item->quantity,
                        'iva' => $iva * $item->quantity,
                        'total' => ($ttt + $iva) * $item->quantity,
                        'costo' => $item->costo / $item->descargar,
                        'costo_total' => $costo_total,
                        'utilidad_uni' => $total - $costo_total,
                        'utilidad' => ($utilidad * 100)
                    ]);

                    ///descargar del inventario
                    $in = Inventarios::where('producto', $item->producto)
                        ->where('sucursal', session('sucursal'))
                        //->where('empresa', session('empresa'))
                        ->first();
                    $newStock = $in->existencia - $item->descargar;

                    $in->existencia = $newStock;
                    $in->save();

                    /////ingreso al kardex/////////////////
                    $p = Precios::where('producto', $item->producto)
                        ->where('presentacion', $item->medida)
                        ->first();

                    $des = 'Venta con Factura numero ' . $correlativo;
                    $des = 'Venta con tikect numero ' . $correlativo . " realizado por " . Auth::user()->name;
                    $kardex = Kardex::create([
                        'producto' => $item->producto,
                        'inventario' => $in->id,
                        'descripcion' => $des,
                        'fecha' => date('Y-m-d'),
                        'hora' => date('H:i:s'),
                        'ingresoCantidad' => 0,
                        'ingresoValor' => 0,
                        'egresoCantidad' => $item->descargar,
                        'egresoValor' => $p->costociva * $item->descargar,
                        'saldoCantidad' => $newStock,
                        'saldoValor' => $newStock * $p->costociva,
                    ]);
                }
                //////agregar los datos a caja///////////

                $corte = Cortes::where('caja', session('caja'))->where('sucursal', session('sucursal'))->where('empresa', session('empresa'))->where('fecha', date('Y-m-d'))->select('id')->first();

                $ca = Caja::create([
                    'caja' => session('caja'),
                    'sucursal' => session('sucursal'),
                    'empresa' => session('empresa'),
                    'corte' => $corte->id,
                    'venta' => $ingre->id,
                    'facturador' => 2,
                    'tipoPago' => $this->metodo,
                    'correlativo' => $correlativo,
                    'codigo' => $codigoGeneracion,
                    'numero' => $control,
                    'sello' => null,
                    'fecha' => $fecha,
                    'hora' => date('H:i:s'),
                    'cajero' => Auth::user()->id,
                    'comprobante' => $this->comprobante,
                    'efectivo' => $this->efectivo,
                    'cambio' => $this->cambio,
                    'subtotal' => $sub,
                    'descuento' => $descuentos,
                    'iva' => $tiva,
                    'percepcion' => $percecion,
                    'total' => $sub - $percecion,
                    'estado' => $estado,
                    'arqueado' => false
                ]);

                if ($this->metodo == 4) {
                    $ultimoCorrelativo = Creditos::orderBy('correlativo', 'desc')->first();
                    $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo->correlativo + 1 : 1;

                    $credito = Creditos::create([
                        'venta' => $ingre->id,
                        'cliente' => $this->idC,
                        'empresa' => session('empresa'),
                        'sucursal' => session('sucursal'),
                        'correlativo' => $nuevoCorrelativo,
                        'fechaCredito' => date('Y-m-d'),
                        'fechaPago' => Carbon::now()->addDays(15)->format('Y-m-d'),
                        'total' => $sub - $percecion,
                        'saldo' => $sub - $percecion,
                        'estado' => 'Pendiente',
                    ]);
                }

                //$user = Auth::user();
                $emprea = Empresas::find($user->empresa);

                $ambiente = AmbienteDestino::find($emprea->ambiente);
                $tipoDte = TipoDocumento::where('status', 'Activo')->where('valor', 'FACTURA')->first();
                $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
                $tipoOpera = TipoTransmision::where('status', 'Activo')->first();
                $tipoContingencia = TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first();
                $tributo = Tributo::where('status', 'Activo')->whereNull('codigo')->first();
                $condipago = $this->metodo == 4 ? 2 : 1;
                if ($para->dte == "Si") {
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
                        'emisor' => session('sucursal'),
                        'receptor' => $this->idC,
                        'otrosDocuentos' => null,
                        'ventaTercero' => null,
                        'venta' => $ingre->id,
                        'tocken' => $tokenActivo->id,
                        'sello' => null,
                        'estado' => 'Creado',
                        'jsonDte' => null,
                        'caja' => session('caja'),
                        'sucursal' => session('sucursal'),
                        'empresa' => session('empresa'),
                    ]);
                    $detalleDTE = resumenDte::create([
                        'dte' => $dte->id,
                        'totalNoSuj' => 0,
                        'totalExenta' => 0,
                        'totalGravada' => $sub,
                        'totalIva' => $tiva,
                        'subTotalVentas' => $sub,
                        'descuNoSuj' => 0,
                        'descuExenta' => 0,
                        'descuGravada' => 0,
                        'porcentajeDescuento' => 0,
                        'totalDescu' => $descuentos,
                        'tributo' => $tributo->id,
                        'codigo' => null,
                        'descripcion' => null,
                        'valor' => null,
                        'subTotal' => $sub,
                        'ivaPerci1' => 0,
                        'ivaRete1' => $percecion,
                        'reteRenta' => 0,
                        'montoTotalOperacion' => $sub,
                        'totalNoGravado' => 0,
                        'totalPagar' => $sub - $percecion,
                        'totalLetras' => strtoupper(Convertidor::montoALetras($sub - $percecion)),
                        'saldoFavor' => 0,
                        'condicionOperacion' => $condipago,
                        'pagos' => $this->metodo,
                        'montoPagado' => null,
                        'refencia' => null,
                        'palzo' => null,
                        'periodo' => null,
                        'numPagoElectronico' => null,
                    ]);
                }
                $this->resetPago();
                $this->Carrito();

                ///actualizo el nuevo correlativo
                $para->concorrelativo = $correlativo + 1;
                $para->save();
            }
            DB::commit();
            if ($ingre->total <> 0) {
                if ($para->dte == 'Si') {
                    if ($para->dteAutomatico == 'Si') {

                        $this->emit('startProcessing2', $dte->id);
                    } else {
                        //$this->ImprimirTicket($ca->id);
                        $this->emit('print-ticket', $this->TraitTikets($ca->id));
                    }
                } else {
                    $this->emit('print-ticket', $this->TraitTikets($ca->id));
                }
            } else {
                return redirect()->route('pos');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            //session()->flash('error', $e->getMessage());
            $this->emit('item-error', $e->getMessage());
        }
    }

    public function SaveFiscal()
    {
        $this->ValidarVentas();

        $rules = [
            'metodo' => 'required',
            'idC' => 'Required'
        ];

        $messages = [
            'metodo.required' => 'Seleccione un metodo diferente de Elegir',
            'idC.required' => 'No ha seleccionado un cliente para guardar esta venta'
        ];

        $this->validate($rules, $messages);

        $user = Auth::user();

        $cartItems = tmpVentas::where('user', Auth::user()->id)
            ->where('caja', session('caja'))
            ->where('esenario', 1)
            ->get();
        $totales = $cartItems->sum('total');
        $descuentos = $cartItems->sum('descuento');


        $this->itemsQuantity = $cartItems->sum('quantity');
        $sub = $totales;
        $tiva = $sub - $sub / 1.13;
        $tt = $sub - $tiva;

        if ($this->metodo == 4) {
            $estado = 'Credito';
        } else {
            $estado = 'Cancelado';
        }

        $para = Parametros::find(session('caja'));
        $correlativo = empty($this->correlativo) ? $para->crecorrelativo : $this->correlativo;
        //dd($correlativo);
        $fecha = empty($this->fecha) ? now()->format('Y-m-d') : $this->fecha;

        if ($para->dte == "Si") {
            $control = $this->obtenerCodigoDTE(2);
            $this->GenerarToken();
            $codigoGeneracion = strtoupper(Str::uuid()->toString());
            $fechaHoy = Carbon::now()->toDateString();
            $tokenActivo = Tocken::where('estado', 'ok')->whereDate('fecha', $fechaHoy)->first();
            $tipo = 'DTE';
        } else {
            $control = NULL;
            $codigoGeneracion = NULL;
            $tipo = 'Fisico';
        }

        try {
            DB::beginTransaction();
            $ingre = Ventas::create([
                'cliente' => $this->idC,
                'tipoPago' => $this->metodo,
                'facturador' => 3,
                'correlativo' => $correlativo,
                'fecha' => $fecha,
                'hora' => date('H:i:s'),
                'tipo' => $tipo,
                'codigo' => $codigoGeneracion,
                'numero' => $control,
                'sello' => '',
                'vendedor' => Auth::user()->id,
                'caja' => session('caja'),
                'sucursal' => session('sucursal'),
                'empresa' => session('empresa'),
                'subtotal' => $sub,
                'descuento' => $descuentos,
                'iva' => $tiva,
                'total' => $sub,
                'estado' => $estado,
                'qr' => '',
            ]);

            if ($ingre) {

                foreach ($cartItems as $item) {
                    $subb = $item->price - $item->descuento;
                    $iva = $subb - $subb / 1.13;
                    $ttt = $subb - $iva;

                    $total = ($ttt + $iva) * $item->quantity;

                    $costo_total = ($item->costo / $item->descargar) * $item->descargar;

                    $utilidad = ($total - $costo_total) / $total;

                    VentasDetalles::create([
                        'venta' => $ingre->id,
                        'producto' => $item->producto,
                        'medida' => $item->uni,
                        'name' => $item->name,
                        'unidad' => $item->medida,
                        'descargar' => $item->descargar,
                        'cantidad' => $item->quantity,
                        'precio' => $item->price,
                        'descuento' => $item->descuento,
                        'subtotal' => $subb * $item->quantity,
                        'iva' => $iva * $item->quantity,
                        'total' => ($ttt + $iva) * $item->quantity,
                        'costo' => $item->costo / $item->descargar,
                        'costo_total' => $costo_total,
                        'utilidad_uni' => $total - $costo_total,
                        'utilidad' => ($utilidad * 100)
                    ]);

                    ///descargar del inventario
                    $in = Inventarios::where('producto', $item->producto)
                        ->where('sucursal', session('sucursal'))
                        //->where('empresa', session('empresa'))
                        ->first();
                    $newStock = $in->existencia - $item->descargar;

                    $in->existencia = $newStock;
                    $in->save();

                    /////ingreso al kardex/////////////////
                    $p = Precios::where('producto', $item->producto)
                        ->where('presentacion', $item->medida)
                        ->first();

                    $des = 'Venta con Credito Fiscal numero ' . $correlativo . " realizado por " . Auth::user()->name;
                    $kardex = Kardex::create([
                        'producto' => $item->producto,
                        'inventario' => $in->id,
                        'descripcion' => $des,
                        'fecha' => date('Y-m-d'),
                        'hora' => date('H:i:s'),
                        'ingresoCantidad' => 0,
                        'ingresoValor' => 0,
                        'egresoCantidad' => $item->descargar,
                        'egresoValor' => $p->costociva * $item->descargar,
                        'saldoCantidad' => $newStock,
                        'saldoValor' => $newStock * $p->costociva,
                    ]);
                }
                //////agregar los datos a caja///////////

                $corte = Cortes::where('caja', session('caja'))->where('sucursal', session('sucursal'))->where('empresa', session('empresa'))->where('fecha', date('Y-m-d'))->select('id')->first();

                $ca = Caja::create([
                    'caja' => session('caja'),
                    'sucursal' => session('sucursal'),
                    'empresa' => session('empresa'),
                    'corte' => $corte->id,
                    'venta' => $ingre->id,
                    'facturador' => 3,
                    'tipoPago' => $this->metodo,
                    'correlativo' => $correlativo,
                    'codigo' => $codigoGeneracion,
                    'numero' => $control,
                    'sello' => null,
                    'fecha' => $fecha,
                    'hora' => date('H:i:s'),
                    'cajero' => Auth::user()->id,
                    'comprobante' => $this->comprobante,
                    'efectivo' => $this->efectivo,
                    'cambio' => $this->cambio,
                    'subtotal' => $sub,
                    'descuento' => $descuentos,
                    'iva' => $tiva,
                    'total' => $sub,
                    'estado' => $estado,
                    'arqueado' => false
                ]);

                if ($this->metodo == 4) {
                    $ultimoCorrelativo = Creditos::orderBy('correlativo', 'desc')->first();
                    $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo->correlativo + 1 : 1;

                    $credito = Creditos::create([
                        'venta' => $ingre->id,
                        'cliente' => $this->idC,
                        'empresa' => session('empresa'),
                        'sucursal' => session('sucursal'),
                        'correlativo' => $nuevoCorrelativo,
                        'fechaCredito' => date('Y-m-d'),
                        'fechaPago' => Carbon::now()->addDays(15)->format('Y-m-d'),
                        'total' => $sub,
                        'saldo' => $sub,
                        'estado' => 'Pendiente',
                    ]);
                }

                $emprea = Empresas::find($user->empresa);

                $ambiente = AmbienteDestino::find($emprea->ambiente);
                $tipoDte = TipoDocumento::where('status', 'Activo')->where('codigo', '03')->first();
                $tipoModelo = ModeloFacturacion::where('status', 'Activo')->first();
                $tipoOpera = TipoTransmision::where('status', 'Activo')->first();
                $tipoContingencia = TipoContigencia::where('status', 'Activo')->whereNull('codigo')->first();
                $tributo = Tributo::where('status', 'Activo')->whereNull('codigo')->first();
                $condipago = $this->metodo == 4 ? 2 : 1;

                $p = $sub - $tiva;

                if ($p > 100.00) {
                    $perce = number_format(0 * 0.01);
                } else {
                    $perce = 0.00;
                }
                if ($para->dte == "Si") {
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
                        'documentoRelacionado' => null,
                        'emisor' => session('sucursal'),
                        'receptor' => $this->idC,
                        'otrosDocuentos' => null,
                        'ventaTercero' => null,
                        'venta' => $ingre->id,
                        'tocken' => $tokenActivo->id,
                        'sello' => null,
                        'estado' => 'Creado',
                        'jsonDte' => null,
                        'caja' => session('caja'),
                        'sucursal' => session('sucursal'),
                        'empresa' => session('empresa'),
                    ]);

                    $detalleDTE = resumenDte::create([
                        'dte' => $dte->id,
                        'totalNoSuj' => 0,
                        'totalExenta' => 0,
                        'totalGravada' => $sub,
                        'totalIva' => $tiva,
                        'subTotalVentas' => $sub,
                        'descuNoSuj' => 0,
                        'descuExenta' => 0,
                        'descuGravada' => 0,
                        'porcentajeDescuento' => 0,
                        'totalDescu' => $descuentos,
                        'tributo' => $tributo->id,
                        'codigo' => null,
                        'descripcion' => null,
                        'valor' => null,
                        'subTotal' => $sub,
                        'ivaPerci1' => 0,
                        'ivaRete1' => $perce,
                        'reteRenta' => 0,
                        'montoTotalOperacion' => $sub,
                        'totalNoGravado' => 0,
                        'totalPagar' => $sub,
                        'totalLetras' => strtoupper(Convertidor::montoALetras($sub + $perce)),
                        'saldoFavor' => 0,
                        'condicionOperacion' => $condipago,
                        'pagos' => $this->metodo,
                        'montoPagado' => null,
                        'refencia' => null,
                        'palzo' => null,
                        'periodo' => null,
                        'numPagoElectronico' => null,
                    ]);
                }
                $this->resetPago();
                $this->Carrito();

                ///actualizo el nuevo correlativo
                $para->crecorrelativo = $correlativo + 1;
                $para->save();
            }
            DB::commit();
            if ($ingre->total <> 0) {
                if ($para->dte == 'Si') {
                    if ($para->dteAutomatico == 'Si') {

                        $this->emit('startProcessing2', $dte->id);
                    } else {
                        //$this->ImprimirTicket($ca->id);
                        $this->emit('print-ticket', $this->TraitTikets($ca->id));
                    }
                } else {
                    $this->emit('print-ticket', $this->TraitTikets($ca->id));
                }
            } else {
                return redirect()->route('pos');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            //session()->flash('error', $e->getMessage());
            $this->emit('item-error', $e->getMessage());
        }
    }

    public function resetPago()
    {
        $this->comprobante = '';
        $this->efectivo = '';
        $this->cambio = 0;
        tmpVentas::where('user', Auth::user()->id)
            ->where('caja', session('caja'))
            ->where('esenario', 1)->delete();
    }

    public function printTicket($data)
    {
        //Log::info('Ejecutando printTicket con los datos: ' . $data);
        //return Redirect::to("print://$data");
    }

    public function ImprimirTicket($id)
    {
        $this->emit('print-ticket', $this->TraitTikets($id));
    }

    public function TicketgetJsonBase64($id)
    {
        $empresa = session('empresa');
        $sucursal = session('sucursal');

        $tipo = 'Tikect';

        $caja = Caja::find($id);

        $details = VentasDetalles::join('productos as p', 'p.id', 'ventas_detalles.producto')
            ->join('medidas as m', 'm.id', 'p.medida')
            ->select('p.nombreProducto as product', 'ventas_detalles.unidad as medida', 'ventas_detalles.cantidad', 'ventas_detalles.precio as costo', 'ventas_detalles.total')
            ->where('ventas_detalles.venta', $caja->venta)
            ->get();

        $venta = Caja::join('parametros as p', 'p.id', 'cajas.caja')->join('cortes as c', 'c.id', 'cajas.corte')->join('users as s', 's.id', 'cajas.cajero')->select('cajas.fecha', 'cajas.hora', 'cajas.venta', 'cajas.correlativo', 'p.caja', 'c.corte', DB::raw('ROUND(cajas.subtotal, 2) as subtotal'), DB::raw('ROUND(cajas.descuento, 2) as descuento'), DB::raw('ROUND(cajas.total, 2) as total'), 's.name as cajero', DB::raw('ROUND(cajas.efectivo, 2) as efectivo'), DB::raw('ROUND(cajas.cambio, 2) as cambio'), 'p.tresolucion as resolucion', 'p.tserie as serie')->find($id);

        $company = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->select('e.empresa', 'e.registro', 'e.giro', 'e.nit', 'sucursales.direccion', 'e.razon', 'sucursales.telefono', 'e.correo')->find($sucursal);

        $data = [
            'tipo' => $tipo,
        ];
        //convertir a json
        $json = "{\"tipo\":\"$tipo\"}|" . $details->toJson() . '|' . $venta->toJson() . '|' . $company->toJson();
        //$json = json_encode($data);
        //$final_json = str_replace(['{"', '":', ',"', '"}', '":null'], ['{', ':', ',', '}', ':""'], $json);
        //dd($final_json = str_replace(',', '|', $final_json));
        //encriptomi json
        $crypted = base64_encode(gzdeflate($json));

        return $crypted;
    }

    public bool $processingRemesa = false;

    public function SaveRemesa()
    {
        //$this->emit('remesa-processing');
        if ($this->processingRemesa) {
            return;
        }

        $this->processingRemesa = true;

        $rules = [
            'montoEnvio' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        ];

        $messages = [
            'montoEnvio.required' => 'Es necesario poner la cantidad a remesar',
            'montoEnvio.numeric' => 'El monto debe ser un número',
            'montoEnvio.regex' => 'El formato del monto no es válido. Debe tener hasta dos decimales.',
        ];

        try {

            $this->validate($rules, $messages);
            DB::beginTransaction();
            $ultimoRegistro = Remesas::latest()->first();
            $ultimoNumero = $ultimoRegistro ? $ultimoRegistro->numero + 1 : 1;

            $remesa = Remesas::create([
                'empresa' => session('empresa'),
                'sucursal' => session('sucursal'),
                'caja' => session('caja'),
                'cajero' => Auth::user()->id,
                'numero' => $ultimoNumero,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'monto' => $this->montoEnvio,
                'validador' => Auth::user()->id,
                'estado' => 'Remesado',
                'concepto' => $this->concepto
            ]);
            DB::commit();
            //$this->emit('remesa-done', $ultimoNumero); // 🔔 manda número de remesa al JS
            $this->emit('print-ticket', $this->RemesagetJsonBase64($remesa->id));
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->emit('item-error', $e->getMessage());
        } finally {
            // Libera el “candado” aunque falle
            //$this->emit('remesa-done');
        }
    }

    public function openAuthenticatedModal()
    {
        $user = User::where('user', $this->username)
            ->where('password2', $this->password)
            ->first();
        if ($user) {
            if ($user->profile === 'Administrador' || $user->profile === 'Super' || $user->profile === 'Encargado' || $user->profile === 'Cajeros') {
                $this->reset(['username', 'password']);
                $this->act = 1;
                $this->showModalAutenticate = false;
                $this->showModalCorteZ = true;

                //$this->dispatchBrawserEvent('modalUpdated');
                $this->emit('modal-updated', 'Valido');
            } else {
                $this->emit('item-error', 'No tienes los permisos necesarios para realizar cortes.');
            }
        } else {
            $this->emit('item-error', 'Autenticación fallida. Por favor, verifica tus credenciales.');
        }
    }

    public function openAuthenticatedModal2()
    {
        $user = User::where('user', $this->username2)
            ->where('password2', $this->password2)
            ->first();
        if ($user) {
            if ($user->profile === 'Administrador' || $user->profile === 'Super' || $user->profile === 'Encargado' || $user->profile === 'Cajeros') {
                $this->reset(['username', 'password']);
                $this->act = 1;
                $this->showModalAutenticate2 = false;
                $this->showModalCorteZ2 = true;

                //$this->dispatchBrawserEvent('modalUpdated');
                $this->emit('modal-cierre', 'Valido');
            } else {
                $this->emit('item-error', $this->username2 . ' No tienes los permisos necesarios para realizar cortes.');
            }
        } else {
            $this->emit('item-error', 'Autenticación fallida. Por favor, verifica tus credenciales.');
        }
    }

    public function openAuthenticatedModal3()
    {
        $user = User::where('user', $this->usernamex)
            ->where('password2', $this->passwordx)
            ->first();
        if ($user) {
            if ($user->profile === 'Administrador' || $user->profile === 'Super' || $user->profile === 'Encargado' || $user->profile === 'Cajeros') {
                $this->reset(['username', 'password']);
                $this->act = 1;
                $this->showModalAutenticateX = false;
                $this->showModalCorteX = true;

                //$this->dispatchBrawserEvent('modalUpdated');
                $this->emit('modal-cierrex', 'Valido');
            } else {
                $this->emit('item-error', $this->usernamex . ' No tienes los permisos necesarios para realizar cortes.');
            }
        } else {
            $this->emit('item-error', 'Autenticación fallida. Por favor, verifica tus credenciales.');
        }
    }

    public function AnulaDevo($id)
    {
        //$rules = [
        //'numeroDoc' => 'required',
        //'passwordd' => 'required',
        //'tipoTran' => 'required',
        //'fo'=> 'required'
        //];

        /*$messages = [
            'numeroDoc.required' => 'Nombre de Ticket o Factura requerido',
            'tipoTran.required' => 'Seleccione el tipo de transaccion',
            'passwordd.required' => 'La contraseña de autorización es requerida',
            'fo.required' => 'Seleccione el tipo de factura'
        ];
        $this->validate($rules, $messages);*/

        $vali = Caja::find($id);

        $user = Auth::user();

        $hoy = date('Y-m-d');

        if ($vali) {
            if ($vali->estado == 'Cancelado') {
                //$user = User::where('password2', $this->passwordd)->first();
                $venta = Ventas::find($vali->venta);

                //if ($user) {
                //if ($user->profile === 'Administrador' || $user->profile === 'Super' || $user->profile === 'Encargado') {
                //if ($this->tipoTran == 1) {
                //if ($vali->fecha == $hoy) {
                $ultimoCorrelativo = Anulaciones::where('sucursal', session('sucursal'))->where('empresa', session('empresa'))->where('caja', session('caja'))->latest('correlativo')->first();
                if ($ultimoCorrelativo) {
                    $correlativo = $ultimoCorrelativo->correlativo + 1;
                } else {
                    $correlativo = 1;
                }
                $anu = Anulaciones::create([
                    'caja' => $venta->caja,
                    'sucursal' => $venta->sucursal,
                    'empresa' => $venta->empresa,
                    'corte' => $vali->corte,
                    'venta' => $venta->id,
                    'cajas' => $vali->id,
                    'facturador' => $vali->facturador,
                    'tipoPago' => $vali->tipoPago,
                    'correlativo' => $correlativo,
                    'codigo' => $vali->numero,
                    'numero' => $vali->correlativo,
                    'sello' => $vali->sello,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'cajero' => $vali->cajero,
                    'autorizado' => $user->id,
                    'comprobante' => $vali->comprobante,
                    'efectivo' => $vali->efectivo,
                    'cambio' => $vali->cambio,
                    'subtotal' => $vali->subtotal,
                    'descuento' => $vali->descuento,
                    'iva' => $vali->iva,
                    'total' => $vali->total,
                    'estado' => 'Anulado',
                ]);

                if ($anu) {
                    $ventas = VentasDetalles::where('venta', $vali->venta)->get();
                    foreach ($ventas as $detalle) {
                        AnulacionesDetalle::create([
                            'anulacion' => $anu->id,
                            'producto' => $detalle->producto,
                            'medida' => $detalle->medida,
                            'unidad' => $detalle->unidad,
                            'descargar' => $detalle->descargar,
                            'cantidad' => $detalle->cantidad,
                            'precio' => $detalle->precio,
                            'descuento' => $detalle->descuento,
                            'subtotal' => $detalle->subtotal,
                            'iva' => $detalle->iva,
                            'total' => $detalle->total,
                        ]);

                        ////ingresarlo al inventario
                        $in = Inventarios::where('producto', $detalle->producto)
                            ->where('sucursal', session('sucursal'))
                            //->where('empresa', session('empresa'))
                            ->first();
                        $newStock = $in->existencia + $detalle->cantidad;

                        $in->existencia = $newStock;
                        $in->save();

                        /////ingreso al kardex/////////////////
                        $p = Precios::where('producto', $detalle->producto)
                            ->where('presentacion', $detalle->unidad)
                            ->first();

                        $des = 'Anulacion del tikect o factura ' . $this->numeroDoc;
                        $kardex = Kardex::create([
                            'producto' => $detalle->producto,
                            'inventario' => $in->id,
                            'descripcion' => $des,
                            'fecha' => date('Y-m-d'),
                            'hora' => date('H:i:s'),
                            'ingresoCantidad' => $detalle->descargar,
                            'ingresoValor' => $p->costociva,
                            'egresoCantidad' => 0,
                            'egresoValor' => 0,
                            'saldoCantidad' => $newStock,
                            'saldoValor' => $newStock * $p->costociva,
                        ]);
                    }
                }

                $vali->update(['estado' => 'Anulado']);
                $vali->save();
                $venta->update(['estado' => 'Anulado']);
                $venta->save();
                $this->ResetUIDevoAnu();
                ///id de la anulacion
                $this->ImprimirAnulacion($anu->id);
                //} else {
                //$this->ResetUIDevoAnu();
                //$this->emit('item-error', 'Error, no se puede realizar operacion, revise la fecha de facturacion.');
                //}
                //} else {
                /*
                            $hoy = Carbon::now();

                            $fechaInicio = $hoy->copy()->subDay()->startOfDay();
                            $fechaFin = $hoy->endOfDay();

                            $query = Caja::where('correlativo', $this->numeroDoc)
                                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                                ->first();

                            if ($query) {
                                $ultimoCorrelativo = Devoluciones::where('sucursal', session('sucursal'))->where('empresa', session('empresa'))->where('caja', session('caja'))->latest('correlativo')->first();

                                if ($ultimoCorrelativo) {
                                    $correlativo = $ultimoCorrelativo->correlativo + 1;
                                } else {
                                    $correlativo = 1;
                                }

                                $anu = Devoluciones::create([
                                    'caja' => $venta->caja,
                                    'sucursal' => $venta->sucursal,
                                    'empresa' => $venta->empresa,
                                    'corte' => $vali->corte,
                                    'venta' => $venta->id,
                                    'cajas' => $vali->id,
                                    'facturador' => $vali->facturador,
                                    'tipoPago' => $vali->tipoPago,
                                    'correlativo' => $correlativo,
                                    'codigo' => $vali->numero,
                                    'numero' => $vali->correlativo,
                                    'sello' => $vali->sello,
                                    'fecha' => date('Y-m-d'),
                                    'hora' => date('H:i:s'),
                                    'cajero' => $vali->cajero,
                                    'autorizado' => $user->id,
                                    'comprobante' => $vali->comprobante,
                                    'efectivo' => $vali->efectivo,
                                    'cambio' => $vali->cambio,
                                    'subtotal' => $vali->subtotal,
                                    'descuento' => $vali->descuento,
                                    'iva' => $vali->iva,
                                    'total' => $vali->total,
                                    'estado' => 'Devolucion',
                                ]);

                                if ($anu) {
                                    $ventas = VentasDetalles::where('venta', $vali->venta)->get();
                                    foreach ($ventas as $detalle) {
                                        DevolucionesDetalles::create([
                                            'devolucion' => $anu->id,
                                            'producto' => $detalle->producto,
                                            'medida' => $detalle->medida,
                                            'unidad' => $detalle->unidad,
                                            'descargar' => $detalle->descargar,
                                            'cantidad' => $detalle->cantidad,
                                            'precio' => $detalle->precio,
                                            'descuento' => $detalle->descuento,
                                            'subtotal' => $detalle->subtotal,
                                            'iva' => $detalle->iva,
                                            'total' => $detalle->total,
                                        ]);

                                        ////ingresarlo al inventario
                                        $in = Inventarios::where('producto', $detalle->producto)
                                            ->where('sucursal', session('sucursal'))
                                            ->where('empresa', session('empresa'))
                                            ->first();
                                        $newStock = $in->existencia + $detalle->cantidad;

                                        $in->existencia = $newStock;
                                        $in->save();

                                        /////ingreso al kardex/////////////////
                                        $p = Precios::where('producto', $detalle->producto)
                                            ->where('presentacion', $detalle->unidad)
                                            ->first();

                                        $des = 'Devolucion del tikect o factura ' . $this->numeroDoc;
                                        $kardex = Kardex::create([
                                            'producto' => $detalle->producto,
                                            'inventario' => $in->id,
                                            'descripcion' => $des,
                                            'fecha' => date('Y-m-d'),
                                            'hora' => date('H:i:s'),
                                            'ingresoCantidad' => $detalle->descargar,
                                            'ingresoValor' => $p->costociva,
                                            'egresoCantidad' => 0,
                                            'egresoValor' => 0,
                                            'saldoCantidad' => $newStock,
                                            'saldoValor' => $newStock * $p->costociva,
                                        ]);
                                    }
                                }

                                $vali->update(['estado' => 'Devolucion']);
                                //$vali->save();
                                $venta->update(['estado' => 'Devolucion']);
                                //$venta->save();

                                $this->ResetUIDevoAnu();
                                ///id de la devolucvion
                                $this->ImprimirDevolucion($anu->id);
                            } else {
                                $this->ResetUIDevoAnu();
                                $this->emit('item-error', 'No se puede realizar esta operacion ya que la fecha es mayor a 24 horas');
                            }
                        } */
                //} else {
                //$this->ResetUIDevoAnu();
                //$this->emit('item-error', 'No tienes los permisos necesarios para realizar esta operación.');
                //}
                //} else {
                //$this->ResetUIDevoAnu();
                //$this->emit('item-error', 'Autenticación fallida. Por favor, verifica tu contraseña.');
                //}
            } else {
                $this->ResetUIDevoAnu();
                $this->emit('item-error', 'Error, Este ticket o Factura ya fue Cancelado o Anulado');
            }
        } else {
            $this->ResetUIDevoAnu();
            $this->emit('item-error', 'Error, no se encontro ticket o Factura');
        }
    }

    public function CuadrarEfectivo()
    {
        // Asegurarte de que todas las variables son números

        $this->b100 = (float)($this->b100 ?? 0);
        $this->b50 = (float)($this->b50 ?? 0);
        $this->b20 = (float)($this->b20 ?? 0);
        $this->b10 = (float)($this->b10 ?? 0);
        $this->b5 = (float)($this->b5 ?? 0);
        $this->b1 = (float)($this->b1 ?? 0);
        $this->bd1 = (float)($this->bd1 ?? 0);
        $this->b025 = (float)($this->b025 ?? 0);
        $this->b010 = (float)($this->b010 ?? 0);
        $this->b005 = (float)($this->b005 ?? 0);
        $this->b001 = (float)($this->b001 ?? 0);

        // Realizar las multiplicaciones
        $this->b100R = $this->b100 * 100;
        $this->b50R = $this->b50 * 50;
        $this->b20R = $this->b20 * 20;
        $this->b10R = $this->b10 * 10;
        $this->b5R = $this->b5 * 5;
        $this->b1R = $this->b1 * 1;
        $this->bd1R = $this->bd1 * 1;
        $this->b025R = $this->b025 * 0.25;
        $this->b010R = $this->b010 * 0.1;
        $this->b005R = $this->b005 * 0.05;
        $this->b001R = $this->b001 * 0.01;

        // Calcular los totales de ventas, tarjetas, cheques, créditos, remesas, anulaciones y devoluciones
        $this->totalVentas = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Cancelado')->sum('total');

        $this->totalTarjetas = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 2)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->sum('total');

        $this->totalCheque = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 3)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->sum('total');

        $this->totalCreditos = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 4)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Credito')->sum('total');

        $this->totalRemesas = Remesas::where('fecha', date('Y-m-d'))->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('monto');

        $this->totalAnulaciones = Caja::where('fecha', date('Y-m-d'))->where('estado', 'Anulado')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

        $this->totalDevoluciones = Caja::where('fecha', date('Y-m-d'))->where('estado', 'Devolucion')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

        $this->cortes = Arqueos::where('fecha', date('Y-m-d'))->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('totalGeneral');

        // Calcular las sumas y diferencias
        $this->totalSumas = $this->totalVentas + $this->totalTarjetas + $this->totalCheque;
        $this->totalSumaResta = $this->totalSumas - $this->cortes - $this->totalRemesas;

        $this->totalEfectivo = $this->b100R + $this->b50R + $this->b20R + $this->b10R + $this->b5R + $this->b1R + $this->bd1R + $this->b025R + $this->b010R + $this->b005R + $this->b001R;

        $this->totalDiferencia = $this->totalEfectivo - ($this->totalSumaResta + $this->aperturas->inicio);
    }

    public function CuadrarEfectivo2()
    {
        // Asegurarte de que todas las variables son números
        /*$this->b100 = (float)($this->b100 ?? 0);
        $this->b50 = (float)($this->b50 ?? 0);
        $this->b20 = (float)($this->b20 ?? 0);
        $this->b10 = (float)($this->b10 ?? 0);
        $this->b5 = (float)($this->b5 ?? 0);
        $this->b1 = (float)($this->b1 ?? 0);
        $this->bd1 = (float)($this->bd1 ?? 0);
        $this->b025 = (float)($this->b025 ?? 0);
        $this->b010 = (float)($this->b010 ?? 0);
        $this->b005 = (float)($this->b005 ?? 0);
        $this->b001 = (float)($this->b001 ?? 0);
        */
        // Realizar las multiplicaciones
        /*$this->b100R = $this->b100 * 100;
        $this->b50R = $this->b50 * 50;
        $this->b20R = $this->b20 * 20;
        $this->b10R = $this->b10 * 10;
        $this->b5R = $this->b5 * 5;
        $this->b1R = $this->b1 * 1;
        $this->bd1R = $this->bd1 * 1;
        $this->b025R = $this->b025 * 0.25;
        $this->b010R = $this->b010 * 0.1;
        $this->b005R = $this->b005 * 0.05;
        $this->b001R = $this->b001 * 0.01;
        */

        // Calcular los totales de ventas, tarjetas, cheques, créditos, remesas, anulaciones y devoluciones
        $this->totalVentas2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Cancelado')->sum('total');

        $this->totalTarjetas2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 2)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->sum('total');

        $this->totalCheque2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 3)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->sum('total');

        $this->totalCreditos2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('tipoPago', 4)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Credito')->sum('total');

        $this->totalRemesas2 = Remesas::where('fecha', $this->aperturas2->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('monto');

        $this->totalAnulaciones2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('estado', 'Anulado')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

        $this->totalDevoluciones2 = Caja::where('fecha', $this->aperturas2->fechaApertura)->where('estado', 'Devolucion')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total');

        // Calcular las sumas y diferencias
        $this->totalSumas2 = $this->totalVentas2 + $this->totalTarjetas2 + $this->totalCheque2;
        $this->totalSumaResta2 = $this->totalSumas2 - $this->totalRemesas2 - $this->cortes2;

        //$this->totalEfectivo2 = $this->b100R + $this->b50R + $this->b20R + $this->b10R + $this->b5R + $this->b1R + $this->bd1R + $this->b025R + $this->b010R + $this->b005R + $this->b001R;

        $this->totalDiferencia2 = $this->totalEfectivo2 - ($this->totalSumaResta2 + $this->aperturas2->inicio);
    }

    public function CuadrarEfectivoX()
    {
        $user = Auth::user();

        // Asegurarte de que todas las variables son números

        $this->b100 = (float)($this->b100 ?? 0);
        $this->b50 = (float)($this->b50 ?? 0);
        $this->b20 = (float)($this->b20 ?? 0);
        $this->b10 = (float)($this->b10 ?? 0);
        $this->b5 = (float)($this->b5 ?? 0);
        $this->b1 = (float)($this->b1 ?? 0);
        $this->bd1 = (float)($this->bd1 ?? 0);
        $this->b025 = (float)($this->b025 ?? 0);
        $this->b010 = (float)($this->b010 ?? 0);
        $this->b005 = (float)($this->b005 ?? 0);
        $this->b001 = (float)($this->b001 ?? 0);

        // Realizar las multiplicaciones
        $this->b100R = $this->b100 * 100;
        $this->b50R = $this->b50 * 50;
        $this->b20R = $this->b20 * 20;
        $this->b10R = $this->b10 * 10;
        $this->b5R = $this->b5 * 5;
        $this->b1R = $this->b1 * 1;
        $this->bd1R = $this->bd1 * 1;
        $this->b025R = $this->b025 * 0.25;
        $this->b010R = $this->b010 * 0.1;
        $this->b005R = $this->b005 * 0.05;
        $this->b001R = $this->b001 * 0.01;

        // Calcular los totales de ventas, tarjetas, cheques, créditos, remesas, anulaciones y devoluciones
        $this->totalVentasx = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Cancelado')->where('cajero', $user->id)->where('arqueado', 0)->sum('total');

        $this->totalTarjetasx = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 2)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->where('cajero', $user->id)->where('arqueado', 0)->sum('total');

        $this->totalChequex = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 3)->where('sucursal', session('sucursal'))->where('estado', 'Cancelado')->where('caja', session('caja'))->where('cajero', $user->id)->where('arqueado', 0)->sum('total');

        $this->totalCreditosx = Caja::where('fecha', date('Y-m-d'))->where('tipoPago', 4)->where('sucursal', session('sucursal'))->where('estado', 'Credito')->where('caja', session('caja'))->where('cajero', $user->id)->where('arqueado', 0)->sum('total');

        $this->totalRemesasx = Remesas::where('fecha', date('Y-m-d'))->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('cajero', $user->id)->where('arqueado', 0)->sum('monto');

        $this->totalAnulacionesx = Caja::where('fecha', date('Y-m-d'))->where('estado', 'Anulado')->where('sucursal', session('sucursal'))->where('cajero', $user->id)->where('caja', session('caja'))->where('arqueado', 0)->sum('total');

        $this->totalDevolucionesx = Caja::where('fecha', date('Y-m-d'))->where('estado', 'Devolucion')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('cajero', $user->id)->where('arqueado', 0)->sum('total');

        // Calcular las sumas y diferencias
        $this->totalSumasx = $this->totalVentasx + $this->totalTarjetasx + $this->totalChequex;
        $this->totalSumaRestax = $this->totalSumasx - $this->totalRemesasx;

        $this->totalEfectivox = $this->b100R + $this->b50R + $this->b20R + $this->b10R + $this->b5R + $this->b1R + $this->bd1R + $this->b025R + $this->b010R + $this->b005R + $this->b001R;

        $this->totalDiferenciax = $this->totalEfectivox - ($this->totalSumaRestax + $this->aperturas->inicio);

        // 🔁 Control de foco (Livewire 2.5)
        $siguiente = match ($this->focused) {
            'b100x' => 'b50x',
            'b50x' => 'b20x',
            'b20x' => 'b10x',
            'b10x' => 'b5x',
            'b5x' => 'b1x',
            'b1x' => 'bd1x',
            'bd1x' => 'b025x',
            'b025x' => 'b010x',
            'b010x' => 'b05x',
            'b05x' => 'b001x',
            default => null,
        };

        if ($siguiente) {
            $this->emit('focus-next', $siguiente);
        }
    }

    public function ResetUIDevoAnu()
    {
        $this->numeroDoc = '';
        $this->tipoTran = 'Elegir';
        $this->passwordd = '';
    }

    public function CorteZ()
    {
        $user_id = Auth::user()->id;

        $apertura = Aperturas::where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('estado', 'Aperturado')
            ->first();

        $fecha = $apertura->fechaApertura;
        $sucursal = session('sucursal');
        $caja = session('caja');

        // === TOTALES DE PAGO ===
        $pagos = Caja::whereDate('fecha', $fecha)->where('sucursal', $sucursal)->where('caja', $caja);

        $totalEfectivo = (clone $pagos)->where('tipoPago', 1)->sum('total');
        $totalTarjetas = (clone $pagos)->where('tipoPago', 2)->sum('total');
        $totalCheque = (clone $pagos)->where('tipoPago', 3)->sum('total');
        $totalCreditos = (clone $pagos)->where('tipoPago', 4)->where('estado', 'Cancelado')->sum('total');

        // === FACTURADOR 1: TICKETS ===
        $fact1 = (clone $pagos)->where('facturador', 1)->where('tipoPago', '<>', 4)->where('estado', 'Cancelado');
        $primerTicket = (clone $fact1)->orderBy('id')->value('correlativo') ?? 0;
        $ultimoTicket = (clone $fact1)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosT = $fact1->sum('subtotal');
        $ivaT = $fact1->sum('iva');
        $totalT = $fact1->sum('total');

        // === FACTURADOR 2: CONSUMIDOR FINAL ===
        $fact2 = (clone $pagos)->where('facturador', 2)->where('tipoPago', '<>', 4)->where('estado', 'Cancelado');
        $consumidorDesde = (clone $fact2)->orderBy('id')->value('correlativo') ?? 0;
        $consumidorHasta = (clone $fact2)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosCon = $fact2->sum('subtotal');
        $ivaCon = $fact2->sum('iva');
        $totalCon = $fact2->sum('total');

        // === FACTURADOR 3: CRÉDITO ===
        $fact3 = (clone $pagos)->where('facturador', 3)->where('tipoPago', '<>', 4)->where('estado', 'Cancelado');
        $CreDesde = (clone $fact3)->orderBy('id')->value('correlativo') ?? 0;
        $CreHasta = (clone $fact3)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosCre = $fact3->sum('subtotal');
        $ivaCre = $fact3->sum('iva');
        $totalCre = $fact3->sum('total');

        // === CRÉDITOS ===
        $creditos = (clone $pagos)->where('tipoPago', 4)->where('estado', 'Cancelado');
        $creditosDesde = (clone $creditos)->orderBy('id')->value('correlativo') ?? 0;
        $creditosHasta = (clone $creditos)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosCredi = $creditos->sum('subtotal');
        $ivaCredi = $creditos->sum('iva');
        $totalCredi = $creditos->sum('total');

        // === DTE ===
        $dteCancelados = (clone $pagos)->where('estado', 'Cancelado');
        $dteDesde = (clone $dteCancelados)->orderBy('id')->value('numero') ?? 0;
        $dteHasta = (clone $dteCancelados)->orderByDesc('id')->value('numero') ?? 0;

        // === OTROS ===
        $devoluciones = Devoluciones::where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('fecha', $fecha)
            ->sum('total');

        $anulaciones = Anulaciones::where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('fecha', $fecha)
            ->sum('total');

        $remesas = Remesas::where('fecha', $fecha)->where('sucursal', $sucursal)->where('caja', $caja)->where('estado', 'Remesado')->sum('monto');
        $cortes = Arqueos::where('fecha', $fecha)->where('sucursal', $sucursal)->where('caja', $caja)->where('empresa', session('empresa'))->sum('totalGeneral');

        // === CIERRE DE APERTURA ===
        $aper = Aperturas::find($this->aperturas->id);
        $aper->update([
            'FcierreApertura' => now()->toDateString(),
            'HcierreApertura' => now()->toTimeString(),
            'estado' => 'Cerrado',
        ]);

        $corte = Cortes::find($this->corteActivo->id);
        $totalGravado = $gravadosT + $gravadosCon + $gravadosCre;
        $totalVentas = $totalT + $totalCon + $totalCre;
        $totalDescuentos = $devoluciones + $anulaciones;
        $totalFinal = $totalVentas - $totalDescuentos;
        $ivaGeneral = $totalFinal - ($totalFinal / 1.13);

        $corte->update([
            'estado' => 'Cerrado',
            'efectivo' => $totalEfectivo,
            'tarjeta' => $totalTarjetas,
            'cheque' => $totalCheque,
            'credito' => $totalCreditos,
            'subtotalPagos' => $totalEfectivo + $totalTarjetas + $totalCheque,
            'devoluciones' => $devoluciones,
            'anulaciones' => $anulaciones,
            'remesas' => $remesas,
            'cortes' => $cortes,
            'sumaTotales' => $totalEfectivo + $totalTarjetas + $totalCheque - $totalDescuentos,
            'ticketDesde' => $primerTicket,
            'ticketHasta' => $ultimoTicket,

            'gravadosT' => $gravadosT / 1.13,
            'ivaT' => $ivaT,
            'subT' => $totalT,
            'totalT' => $totalT,

            'consumidorDesde' => $consumidorDesde,
            'consumidorHasta' => $consumidorHasta,
            'gravadosCon' => $gravadosCon / 1.13,
            'ivaCon' => $ivaCon,
            'subCon' => $totalCon,
            'totalCon' => $totalCon,

            'CreDesde' => $CreDesde,
            'CreHasta' => $CreHasta,
            'gravadosCre' => $gravadosCre / 1.13,
            'ivaCre' => $ivaCre,
            'subCre' => $totalCre,
            'totalCre' => $totalCre,

            'dteDesde' => $dteDesde,
            'dteHasta' => $dteHasta,
            'gravadosDTE' => $gravadosCon + $gravadosCre,
            'ivaDTE' => $ivaCon + $ivaCre,
            'subDTE' => $totalCon + $totalCre,
            'totalDTE' => $totalCon + $totalCre,

            'creditosDesde' => $creditosDesde,
            'creditosHasta' => $creditosHasta,
            'gravadosCredi' => $gravadosCredi,
            'ivaCredi' => $ivaCredi,
            'subCredi' => $totalCredi,
            'totalCredi' => $totalCredi,

            'totalGeneral' => $totalFinal,
            'ivaGeneral' => $ivaGeneral,
            'subGeneral' => $totalVentas,
            'totalGlobal' => $totalFinal,
            'totalEfectivo' => $this->totalEfectivo - $apertura->inicio,
            'diferencia' => $this->totalDiferencia,
        ]);

        $acti = Actividades::where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('user', $user_id)
            ->latest('created_at')
            ->first();
        $acti->update(['status' => 'Cerrado']);

        Caja::whereDate('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('estado', 'Cancelado')
            ->update(['arqueado' => true]);

        Remesas::whereDate('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->update(['arqueado' => true]);

        $this->ImprimirCorteZ($corte->id);
    }

    public function CorteZ2($id)
    {
        $user_id = Auth::user()->id;
        $apertura = Aperturas::find($id);

        $totalEfectivo = Caja::where('fecha', $apertura->fechaApertura)->where('tipoPago', 1)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            //->where('estado', 'Cancelado')
            ->sum('total') ?? 0;

        $totalTarjetas = Caja::where('fecha', $apertura->fechaApertura)->where('tipoPago', 2)
            ->where('sucursal', session('sucursal'))
            //->where('estado', 'Cancelado')
            ->where('caja', session('caja'))->sum('total') ?? 0;

        $totalCheque = Caja::where('fecha', $apertura->fechaApertura)->where('tipoPago', 3)
            ->where('sucursal', session('sucursal'))
            //->where('estado', 'Cancelado')
            ->where('caja', session('caja'))->sum('total') ?? 0;

        $totalCreditos = Caja::where('fecha', $apertura->fechaApertura)->where('tipoPago', 4)->where('estado', 'Cancelado')->where('sucursal', session('sucursal'))->where('caja', session('caja'))->sum('total') ?? 0;

        $primerTicket = Caja::where('facturador', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->orderBy('id', 'asc')->value('correlativo') ?? 0;

        $ultimoTicket = Caja::where('facturador', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->orderBy('id', 'desc')->value('correlativo') ?? 0;

        $gravadosT = Caja::where('facturador', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('tipoPago', '<>', 4)->sum('subtotal') ?? 0;

        $ivaT = Caja::where('facturador', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('tipoPago', '<>', 4)->sum('iva') ?? 0;

        $totalT = Caja::where('facturador', 1)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('tipoPago', '<>', 4)->sum('total') ?? 0;

        $consumidorDesde = Caja::where('facturador', 2)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->orderBy('id', 'asc')->value('correlativo') ?? 0;

        $consumidorHasta = Caja::where('facturador', 2)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->orderBy('id', 'desc')->value('correlativo') ?? 0;

        $gravadosCon = Caja::where('facturador', 2)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('tipoPago', '<>', 4)->sum('subtotal') ?? 0;

        $ivaCon = Caja::where('facturador', 2)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('tipoPago', '<>', 4)->sum('iva') ?? 0;

        $totalCon = Caja::where('facturador', 2)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('tipoPago', '<>', 4)->sum('total') ?? 0;

        $CreDesde =
            Caja::where('facturador', 3)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->orderBy('id', 'asc')
            ->value('correlativo') ?? 0;

        $CreHasta = Caja::where('facturador', 3)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->orderBy('id', 'desc')->value('correlativo') ?? 0;

        $gravadosCre = Caja::where('facturador', 3)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->whereDate('fecha', $apertura->fechaApertura)->where('estado', 'Cancelado')->where('tipoPago', '<>', 4)->sum('subtotal') ?? 0;

        $ivaCre = Caja::where('facturador', 3)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->where('tipoPago', '<>', 4)
            ->sum('iva') ?? 0;

        $totalCre =
            Caja::where('facturador', 3)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->where('tipoPago', '<>', 4)
            ->sum('total') ?? 0;

        $dteDesde =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->orderBy('id', 'asc')
            ->value('numero') ?? 0;

        $dteHasta =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->orderBy('id', 'desc')
            ->value('numero') ?? 0;

        $creditosDesde =
            Caja::where('tipoPago', 4)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->orderBy('id', 'asc')
            ->value('correlativo') ?? 0;

        $creditosHasta =
            Caja::where('tipoPago', 4)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->orderBy('id', 'desc')
            ->value('correlativo') ?? 0;

        $gravadosCredi =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->where('tipoPago', 4)
            ->sum('subtotal') ?? 0;

        $ivaCredi =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->where('tipoPago', 4)
            ->sum('iva') ?? 0;

        $totalCredi =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Cancelado')
            ->where('tipoPago', 4)
            ->sum('total') ?? 0;

        $devoluciones =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Devolucion')
            ->sum('total') ?? 0;

        $anulaciones =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->where('estado', 'Anulado')
            ->sum('total') ?? 0;
        $percecion =
            Caja::where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereDate('fecha', $apertura->fechaApertura)
            ->sum('percepcion') ?? 0;

        $remesas = Remesas::where('fecha', $apertura->fechaApertura)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('estado', 'Remesado')
            ->sum('monto') ?? 0;

        $cortes = Arqueos::where('fecha', $apertura->fechaApertura)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('empresa', session('empresa'))
            ->sum('totalGeneral') ?? 0;

        $corte = Cortes::where('fecha', $apertura->fechaApertura)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('empresa', session('empresa'))
            ->first();
        $aper = Aperturas::find($id);
        //dd($id);

        $aper->FcierreApertura = date('Y-m-d');
        $aper->HcierreApertura = date('H:i:s');
        $aper->estado = 'Cerrado';
        $aper->save();

        $totales = ($gravadosCon + $gravadosCre + $gravadosT) - ($devoluciones + $anulaciones);
        $totalesiva = $totales - ($totales / 1.13);

        $corte->update([
            'estado' => 'Cerrado',
            'efectivo' => $totalEfectivo,
            'tarjeta' => $totalTarjetas,
            'cheque' => $totalCheque,
            'credito' => $totalCreditos,
            'subtotalPagos' => $totalEfectivo + $totalTarjetas + $totalCheque,
            'devoluciones' => $devoluciones,
            'anulaciones' => $anulaciones,
            'remesas' => $remesas,
            'percepcion' => $percecion,
            'cortes' => $cortes,
            'sumaTotales' => $totalEfectivo + $totalTarjetas + $totalCheque - ($devoluciones + $anulaciones),
            'ticketDesde' => $primerTicket,
            'ticketHasta' => $ultimoTicket,
            'gravadosT' => $gravadosT / 1.13,
            'ivaT' => $ivaT,
            'subT' => $totalT,
            'totalT' => $totalT,
            'consumidorDesde' => $consumidorDesde,
            'consumidorHasta' => $consumidorHasta,
            'gravadosCon' => $gravadosCon / 1.13,
            'ivaCon' => $ivaCon,
            'subCon' => $totalCon,
            'totalCon' => $totalCon,
            'CreDesde' => $CreDesde,
            'CreHasta' => $CreHasta,
            'gravadosCre' => $gravadosCre / 1.13,
            'ivaCre' => $ivaCre,
            'subCre' => $totalCre,
            'totalCre' => $totalCre,
            'dteDesde' => $dteDesde,
            'dteHasta' => $dteHasta,
            'gravadosDTE' => $gravadosCon + $gravadosCre,
            'ivaDTE' => $ivaCon + $ivaCre,
            'subDTE' => $totalCon + $totalCre,
            'totalDTE' => $totalCon + $totalCre,
            'creditosDesde' => $creditosDesde,
            'creditosHasta' => $creditosHasta,
            'gravadosCredi' => $gravadosCredi,
            'ivaCredi' => $ivaCredi,
            'subCredi' => $totalCredi,
            'totalCredi' => $totalCredi,
            'totalGeneral' => $gravadosCon + $gravadosCre + $gravadosT - ($anulaciones + $devoluciones),
            'ivaGeneral' =>  $totalesiva,
            'subGeneral' => $totalCon + $totalCre + $totalT,
            'totalGlobal' => $totalCon + $totalCre + $totalT - ($anulaciones + $devoluciones),
            'totalEfectivo' => $this->totalEfectivo2 - $apertura->inicio,
            'diferencia' => $this->totalDiferencia2
        ]);
        //$cor = Cortes::where('fecha', $apertura->fechaApertura)->first();
        //$corte->save();
        //d$aper->fechaApertura);
        $acti = Actividades::whereDate('created_at', $aper->fechaApertura)
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            //->where('status', 'Activo')
            ->first();
        $acti->status = 'Cerrado';
        $acti->save();

        Caja::where('fecha', $aper->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->where('estado', 'Cancelado')->update(['arqueado' => true]);

        Remesas::where('fecha', $aper->fechaApertura)->where('sucursal', session('sucursal'))->where('caja', session('caja'))->update(['arqueado' => true]);


        $this->ImprimirCorteZ($corte->id);
    }

    public function CorteX()
    {
        $user = Auth::user();
        $fecha = date('Y-m-d');
        $sucursal = session('sucursal');
        $caja = session('caja');

        $apertura = Aperturas::where('empresa', session('empresa'))
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('estado', 'Aperturado')
            ->first();

        // === PAGOS ===
        $pagos = Caja::whereDate('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->where('caja', $caja)
            ->where('cajero', $user->id)
            ->where('arqueado', false);

        $totalEfectivo = (clone $pagos)->where('tipoPago', 1)->sum('total');
        $totalTarjetas = (clone $pagos)->where('tipoPago', 2)->sum('total');
        $totalCheque = (clone $pagos)->where('tipoPago', 3)->sum('total');
        $totalCreditos = (clone $pagos)->where('tipoPago', 4)->where('estado', 'Cancelado')->sum('total');

        // === TICKETS ===
        $fact1 = (clone $pagos)->where('facturador', 1)->where('estado', 'Cancelado')->where('tipoPago', '<>', 4);
        $primerTicket = (clone $fact1)->orderBy('id')->value('correlativo') ?? 0;
        $ultimoTicket = (clone $fact1)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosT = $fact1->sum('subtotal');
        $ivaT = $fact1->sum('iva');
        $totalT = $fact1->sum('total');

        // === CONSUMIDOR FINAL ===
        $fact2 = (clone $pagos)->where('facturador', 2)->where('estado', 'Cancelado')->where('tipoPago', '<>', 4);
        $consumidorDesde = (clone $fact2)->orderBy('id')->value('correlativo') ?? 0;
        $consumidorHasta = (clone $fact2)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosCon = $fact2->sum('subtotal');
        $ivaCon = $fact2->sum('iva');
        $totalCon = $fact2->sum('total');

        // === CRÉDITO ===
        $fact3 = (clone $pagos)->where('facturador', 3)->where('estado', 'Cancelado')->where('tipoPago', '<>', 4);
        $CreDesde = (clone $fact3)->orderBy('id')->value('correlativo') ?? 0;
        $CreHasta = (clone $fact3)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosCre = $fact3->sum('subtotal');
        $ivaCre = $fact3->sum('iva');
        $totalCre = $fact3->sum('total');

        // === CRÉDITOS ===
        $creditos = (clone $pagos)->where('tipoPago', 4)->where('estado', 'Cancelado');
        $creditosDesde = (clone $creditos)->orderBy('id')->value('correlativo') ?? 0;
        $creditosHasta = (clone $creditos)->orderByDesc('id')->value('correlativo') ?? 0;
        $gravadosCredi = $creditos->sum('subtotal');
        $ivaCredi = $creditos->sum('iva');
        $totalCredi = $creditos->sum('total');

        // === DTE ===
        $dte = (clone $pagos)->where('estado', 'Cancelado');
        $dteDesde = (clone $dte)->orderBy('id')->value('numero') ?? 0;
        $dteHasta = (clone $dte)->orderByDesc('id')->value('numero') ?? 0;

        // === OTROS ===
        $devoluciones = Devoluciones::where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('fecha', date('Y-m-d'))
            ->sum('total');

        $anulaciones = Anulaciones::where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->where('fecha', date('Y-m-d'))
            ->sum('total');

        $percepcion = (clone $pagos)->where('estado', 'Cancelado')->sum('percepcion');
        $remesas = Remesas::where('fecha', $fecha)->where('sucursal', $sucursal)->where('caja', $caja)->where('cajero', $user->id)->where('estado', 'Remesado')->where('arqueado', false)->sum('monto');

        // === ARQUEO ===
        $numeroSiguiente = (Arqueos::where('caja', $caja)->where('sucursal', $sucursal)->latest('numero')->value('numero') ?? 0) + 1;

        $totalGravado = $gravadosCon + $gravadosCre + $gravadosT;
        $totalVentas = $totalT + $totalCon + $totalCre;
        $totalDescuentos = $devoluciones - $anulaciones + $remesas;
        $totalFinal = $totalGravado - $totalDescuentos;
        $ivaGeneral = $totalFinal - ($totalFinal / 1.13);

        $arqueo = Arqueos::create([
            'numero' => $numeroSiguiente,
            'fecha' => $fecha,
            'hora' => now()->toTimeString(),
            'caja' => $caja,
            'sucursal' => $sucursal,
            'empresa' => session('empresa'),
            'cajero' => $user->id,
            'tipo' => 'X',
            'efectivo' => $totalEfectivo,
            'tarjeta' => $totalTarjetas,
            'cheque' => $totalCheque,
            'credito' => $totalCreditos,
            'subtotalPagos' => $totalEfectivo + $totalTarjetas + $totalCheque,
            'devoluciones' => $devoluciones,
            'anulaciones' => $anulaciones,
            'remesas' => $remesas,
            'percepcion' => $percepcion,
            'sumaTotales' => $totalEfectivo + $totalTarjetas + $totalCheque - $totalDescuentos,

            'ticketDesde' => $primerTicket,
            'ticketHasta' => $ultimoTicket,

            'gravadosT' => $gravadosT / 1.13,
            'ivaT' => $ivaT,
            'subT' => $totalT,
            'totalT' => $totalT,

            'consumidorDesde' => $consumidorDesde,
            'consumidorHasta' => $consumidorHasta,
            'gravadosCon' => $gravadosCon / 1.13,
            'ivaCon' => $ivaCon,
            'subCon' => $totalCon,
            'totalCon' => $totalCon,

            'CreDesde' => $CreDesde,
            'CreHasta' => $CreHasta,
            'gravadosCre' => $gravadosCre / 1.13,
            'ivaCre' => $ivaCre,
            'subCre' => $totalCre,
            'totalCre' => $totalCre,

            'dteDesde' => $dteDesde,
            'dteHasta' => $dteHasta,
            'gravadosDTE' => $gravadosCon + $gravadosCre,
            'ivaDTE' => $ivaCon + $ivaCre,
            'subDTE' => $totalCon + $totalCre,
            'totalDTE' => $totalCon + $totalCre,

            'creditosDesde' => $creditosDesde,
            'creditosHasta' => $creditosHasta,
            'gravadosCredi' => $gravadosCredi,
            'ivaCredi' => $ivaCredi,
            'subCredi' => $totalCredi,
            'totalCredi' => $totalCredi,

            'totalGeneral' => $totalFinal,
            'ivaGeneral' => $ivaGeneral,
            'subGeneral' => $totalVentas,
            'totalPercepcion' => $percepcion,
            'totalGlobal' => $totalVentas,
            'totalEfectivo' => $this->totalEfectivox - $apertura->inicio,
            'diferencia' => $this->totalDiferenciax,
        ]);

        // === CIERRE DE ACTIVIDAD Y MARCADO ===
        Caja::whereDate('fecha', $fecha)->where('sucursal', $sucursal)->where('caja', $caja)->where('cajero', $user->id)->where('estado', 'Cancelado')->update(['arqueado' => true]);
        Remesas::whereDate('fecha', $fecha)->where('sucursal', $sucursal)->where('caja', $caja)->where('cajero', $user->id)->update(['arqueado' => true]);

        $act = Actividades::where('user', $user->id)->where('status', 'Activo')->where('sucursal', $sucursal)->where('caja', $caja)->first();
        $act?->update(['status' => 'Cerrado']);

        $this->ImprimirCorteX($arqueo->id);
        Auth::logout();

        return Redirect::to('login');
    }

    public function ImprimirCorteZ($id)
    {
        $this->emit('print-ticket', $this->GenerarJsonCorteZ($id));
    }

    public function ImprimirCorteX($id)
    {

        $this->emit('print-ticket', $this->GenerarJsonCorteX($id));
    }

    public function AnulacionGetJsonBase64($id)
    {
        $empresa = session('empresa');
        $sucursal = session('sucursal');

        $tipo = 'Anulacion';

        $details = AnulacionesDetalle::join('productos as p', 'p.id', 'anulaciones_detalles.producto')->join('medidas as m', 'm.id', 'p.medida')->select('p.nombreProducto as product', 'm.unidad as medida', 'anulaciones_detalles.cantidad', 'anulaciones_detalles.total')->where('anulaciones_detalles.anulacion', $id)->get();

        $anulacion = Anulaciones::join('parametros as p', 'p.id', 'anulaciones.caja')->join('cajas as ca', 'ca.id', 'anulaciones.cajas')->join('cortes as c', 'c.id', 'ca.corte')->join('users as s', 's.id', 'ca.cajero')->select('anulaciones.fecha', 'anulaciones.hora', 'anulaciones.venta', 'anulaciones.numero as correlativo', 'p.caja', 'c.corte', DB::raw('ROUND(anulaciones.subtotal, 2) as subtotal'), DB::raw('ROUND(anulaciones.descuento, 2) as descuento'), DB::raw('ROUND(anulaciones.total, 2) as total'), 's.name as cajero', DB::raw('ROUND(anulaciones.efectivo, 2) as efectivo'), DB::raw('ROUND(anulaciones.cambio, 2) as cambio'))->find($id);

        $company = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->select('e.empresa', 'e.registro', 'e.giro', 'e.nit', 'sucursales.direccion', 'e.razon', 'sucursales.telefono', 'e.correo')->find($sucursal);

        $data = [
            'tipo' => $tipo,
        ];
        //convertir a json
        $json = "{\"tipo\":\"$tipo\"}|" . $details->toJson() . '|' . $anulacion->toJson() . '|' . $company->toJson();
        $crypted = base64_encode(gzdeflate($json));

        return $crypted;
    }

    public function ImprimirDevolucion($id)
    {
        $this->emit('print-ticket', $this->DevolucionGetJsonBase64($id));
    }

    public function DevolucionGetJsonBase64($id)
    {
        $empresa = session('empresa');
        $sucursal = session('sucursal');

        $tipo = 'Devolucion';

        $details = DevolucionesDetalles::join('productos as p', 'p.id', 'devoluciones_detalles.producto')->join('medidas as m', 'm.id', 'p.medida')->select('p.nombreProducto as product', 'm.unidad as medida', 'devoluciones_detalles.cantidad', 'devoluciones_detalles.precio as costo', 'devoluciones_detalles.total')->where('devoluciones_detalles.devolucion', $id)->get();

        $devolucion = Devoluciones::join('parametros as p', 'p.id', 'devoluciones.caja')->join('cajas as ca', 'ca.id', 'devoluciones.cajas')->join('cortes as c', 'c.id', 'ca.corte')->join('users as s', 's.id', 'ca.cajero')->select('devoluciones.fecha', 'devoluciones.hora', 'devoluciones.venta', 'devoluciones.numero as correlativo', 'p.caja', 'c.corte', DB::raw('ROUND(devoluciones.subtotal, 2) as subtotal'), DB::raw('ROUND(devoluciones.descuento, 2) as descuento'), DB::raw('ROUND(devoluciones.total, 2) as total'), 's.name as cajero', DB::raw('ROUND(devoluciones.efectivo, 2) as efectivo'), DB::raw('ROUND(devoluciones.cambio, 2) as cambio'))->find($id);

        $company = Sucursales::join('empresas as e', 'e.id', 'sucursales.empresa')->select('e.empresa', 'e.registro', 'e.giro', 'e.nit', 'sucursales.direccion', 'e.razon', 'sucursales.telefono', 'e.correo')->find($sucursal);

        $data = [
            'tipo' => $tipo,
        ];
        //convertir a json
        $json = "{\"tipo\":\"$tipo\"}|" . $details->toJson() . '|' . $devolucion->toJson() . '|' . $company->toJson();
        $crypted = base64_encode(gzdeflate($json));

        return $crypted;
    }

    public function ImprimirAnulacion($id)
    {
        $this->emit('print-ticket', $this->AnulacionGetJsonBase64($id));
    }

    public function SearchClientDui()
    {
        $bus = Clientes::where(function ($query) {
            $query->where('dui', $this->duiC)
                ->orWhere('nit', $this->duiC);
        })->whereNotIn('id', [1])->first();

        if ($bus) {
            $this->duiC = ($bus->idenReceptor == 2) ? $bus->dui : $bus->nit;
            $this->idC = $bus->id;
            $this->nombreC = $bus->nombreCliente;
            $this->direccionC = $bus->direccion;
            $this->telefonoC = $bus->telefono;
            $this->correoC = $bus->email;
            $this->nrcC = $bus->registro;
            $this->actividadC = $bus->desActividad;
            $this->tipoC = $bus->idenReceptor;
        } else {
            $this->emit('item-errorSearch', 'Desea Registrarlo ?');
        }
    }

    public function SearchClientName()
    {
        $bus = Clientes::where('nombreCliente', 'like', '%' . $this->nombreC . '%')->first();

        if ($bus) {
            $this->duiC = ($bus->idenReceptor == 2) ? $bus->dui : $bus->nit;
            $this->idC = $bus->id;
            $this->nombreC = $bus->nombreCliente;
            $this->direccionC = $bus->direccion;
            $this->telefonoC = $bus->telefono;
            $this->correoC = $bus->email;
            $this->nrcC = $bus->registro;
            $this->actividadC = $bus->desActividad;
            $this->tipoC = $bus->idenReceptor;
        } else {
            $this->emit('item-errorSearch', 'Cliente no Encontrado, Desea Registrarlo ?');
        }
    }

    public function SearchClientNrc()
    {
        $bus = Clientes::where('registro', $this->nrcC)->whereNotIn('id', [1])->first();

        if ($bus) {
            $this->duiC = ($bus->idenReceptor == 2) ? $bus->dui : $bus->nit;
            $this->idC = $bus->id;
            $this->nombreC = $bus->nombreCliente;
            $this->direccionC = $bus->direccion;
            $this->telefonoC = $bus->telefono;
            $this->correoC = $bus->email;
            $this->nrcC = $bus->registro;
            $this->actividadC = $bus->desActividad;
            $this->tipoC = $bus->idenReceptor;
        } else {
            $this->emit('item-errorSearch', 'Cliente no Encontrado, Desea Registrarlo ?');
        }
    }

    public function ScanCodeById($barcode)
    {
        $user = Auth::user();
        $barcode = trim($barcode);

        if (empty($barcode)) return $barccode = '';

        $product = Productos::where('activo', 1)
            ->where('caja', 1)
            ->where(function ($query) use ($barcode) {
                $query->where('codebar1', $barcode)
                    ->orWhere('codebar2', $barcode)
                    ->orWhere('codebar3', $barcode)
                    ->orWhere('codealternativo', $barcode);
            })
            ->first();

        if (!$product) {
            //$this->emit('item-error', 'Producto no encontrado', 'error');
            return; // ✅ Evita que el código siga ejecutándose si no hay producto
        }

        $this->productoName = $product->nombreProducto;
        $this->detallePrecios = Precios::with(['RsucursalPrecios' => function ($query) use ($user) {
            $query->where('sucursal', $user->sucursal)->where('activo', 1);
        }])
            ->where('producto', $product->id)
            ->where('escala', 'No')
            ->orderBy('cantidad', 'asc')
            ->get();

        $this->detalleEscalas = Precios::with(['RsucursalPrecios' => function ($query) use ($user) {
            $query->where('sucursal', $user->sucursal)->where('activo', 1);
        }])
            ->where('producto', $product->id)
            ->where('escala', 'Si')
            ->orderBy('cantidad', 'asc')
            ->get();


        $this->emit('abrirModal', 'detalleprecios');
    }

    public function openAuthenticatedReimpresion()
    {
        $user = User::where('user', $this->usernameR)
            ->where('password2', $this->passwordR)
            ->first();
        if ($user) {
            //if ($user->profile === 'Administrador' || $user->profile === 'Super' || $user->profile === 'Encargado') {
            $this->reset(['username', 'password']);
            $this->act = 1;
            $this->showModalAutenticateR = false;
            $this->showModalReimpresion = true;
            //$this->CargaImpresiones();

            //$this->dispatchBrawserEvent('modalUpdated');
            $this->emit('modal-reimpresion', 'Valido');
            //} else {
            //$this->emit('item-error', $this->usernamex .' No tienes los permisos necesarios para realizar cortes.');
            //}
        } else {
            $this->emit('item-error', 'Autenticación fallida. Por favor, verifica tus credenciales.');
        }
    }

    public function ImprimirReimpresion($id)
    {
        $this->emit('print-ticket', $this->TraitTiketsRe($id));
    }

    public function DetalleAnulacion()
    {

        $ventasQuery = Caja::query()
            ->select('id', 'fecha', 'hora', 'caja', 'corte', 'correlativo', 'numero', 'total', 'cajero', 'facturador')
            ->with([
                'Rcajeros:id,name',
                'Rcajas:id,caja',
                'Rcortes:id,corte',
                'Rfacturadores:id,facturador',
            ])
            ->where('empresa', session('empresa'))
            ->where('sucursal', session('sucursal'))
            ->where('caja', session('caja'))
            ->whereBetween('fecha', [
                Carbon::now()->startOfWeek()->toDateString(),
                Carbon::now()->endOfWeek()->toDateString()
            ]);

        if (strlen($this->search) > 0) {
            $ventasQuery->where(function ($q) {
                $q->where('correlativo', $this->search)
                    ->orWhereDate('fecha', $this->search)
                    ->orWhereHas('Rcajeros', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $this->detalleAnulaciones = $ventasQuery
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('correlativo')
            ->get();

        $this->emit('anulaciones-show');
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
        $dte = dte::find($id);
        if ($dte->tipoDte == 1) {
            $json = $this->GeneraJsonF($id);
            //$firma = $this->FirmadorDTE($id, $json);
            $firma = $this->FirmadorLocal($id, $json);
            $this->RecepcionDTEF($id);
        } else {
            $json = $this->GeneraJsonC($id);
            //$firma = $this->FirmadorDTE($id, $json);
            $firma = $this->FirmadorLocal($id, $json);
            $this->RecepcionDTEC($id);
        }

        $dte2 = dte::find($id);

        $caja = Caja::where('venta', $dte2->venta)->where('codigo', $dte->codigoGeneracion)->first();
        $ticketData = $this->TraitTikets($caja->id);

        if ($dte2->estado == 'PROCESADO') {
            //$this->ImprimirTicket($ca->id);
            $this->emit('item-addedd', 'DTE Firmado y Procesado', $ticketData);
        } else {
            $this->emit('item-errorr', 'DTE Rechazado por Hacienda, Revisar los DTE generado para mas informacion',);
        }
        //$this->emit('item-added', 'DTE Firmado y Procesado');
    }
    ////////END GENERAR DTE?//////////

    public function lanzarAlertaPago()
    {
        $this->emit('mostrarFormularioPago', number_format($this->total, 2));
    }

    public function procesarPago($data)
    {
        $this->metodo = $data['metodo'];
        $this->comprobante = $data['comprobante'];
        $this->efectivo = $data['efectivo'];

        $this->Cash();
        $this->SaveTicket();
    }

    public function actualizarCantidadYFoco($id)
    {
        $this->updateCanti($id);
        $this->emit('focus-barcode');
    }

    public function focusPrimerCantidad()
    {
        $this->emit('focus-first-cantidad');
    }

    ////////// MANEJO DE LOS ESCENARIOS Y CAMBIO DE ESCENARIOS
    public function cambiarEscenario()
    {
        $user = Auth::user()->id;
        $caja = session('caja');
        $sucursal = session('sucursal');
        $empresa = session('empresa');

        // Obtener todos los escenarios ocupados por el usuario actual
        $escenarios = tmpVentas::where('user', $user)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->pluck('esenario')
            ->unique()
            ->toArray();

        // Encontrar el siguiente escenario libre
        $nuevoEscenario = 1;
        while (in_array($nuevoEscenario, $escenarios)) {
            $nuevoEscenario++;
        }

        // Mover todos los registros del escenario 1 al nuevo escenario disponible
        tmpVentas::where('user', $user)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->where('esenario', 1)
            ->update(['esenario' => $nuevoEscenario]);

        // Recargar lista de escenarios
        $this->loadEscenarios();
    }

    public function loadEscenarios()
    {
        $user     = auth()->id();
        $caja     = session('caja');
        $sucursal = session('sucursal');
        $empresa  = session('empresa');


        $rows = tmpVentas::query()
            ->select(
                'esenario',
                DB::raw('SUM(total) as total'),
                DB::raw('SUM(quantity) as items')
            )
            ->where('user', $user)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->where('esenario', '!=', 1)
            ->groupBy('esenario')
            ->get();

        /*$this->escenarios = tmpVentas::where('user', Auth::id())
            ->where('caja', session('caja'))
            ->where('sucursal', session('sucursal'))
            ->where('empresa', session('empresa'))
            ->pluck('esenario')
            ->unique()
            ->sort()
            ->values()
            ->toArray();*/
        $this->escenarios = $rows->pluck('esenario')->sort()->values()->all();
        $this->escResumen = $rows->keyBy('esenario')->map(function ($r) {
            return [
                'total' => (float) $r->total,
                'items' => (int) $r->items,
            ];
        })->toArray();
    }

    public function moverAEscenarioUno($escenarioId)
    { //dd('sds');
        $user = Auth::id();
        $caja = session('caja');
        $sucursal = session('sucursal');
        $empresa = session('empresa');

        // Verificar si escenario 1 tiene productos
        $hayProductos = tmpVentas::where('user', $user)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->where('esenario', 1)
            ->exists();

        if ($hayProductos) {
            // Si escenario 1 ya tiene productos, mostramos alerta
            $this->emit(
                'item-error',
                'La venta principal ya tiene productos, realizar venta o guarda la venta para carga la que quieras facturar.'
            );
            return;
        }

        // Pasar todos los registros de $escenarioId a escenario 1
        //dd($escenarioId);
        tmpVentas::where('user', $user)
            ->where('caja', $caja)
            ->where('sucursal', $sucursal)
            ->where('empresa', $empresa)
            ->where('esenario', $escenarioId)
            ->update(['esenario' => 1]);

        // Recargar escenarios
        $this->loadEscenarios();
        $this->Carrito();
    }

    private function cargarVentasParaModal(): void
    {
        $inicio = Carbon::now('America/El_Salvador')->startOfDay()->subDays(10);
        $fin    = Carbon::now('America/El_Salvador'); // ahora

        $q = Caja::select([
            'id',
            'fecha',
            'hora',
            'correlativo',
            'total',
            'caja',      // FK para Rcajas
            'cajero',    // FK para Rcajeros (ajusta si tu FK se llama distinto)
            'sucursal',
            'empresa' // usados en filtros
        ])
            ->with([
                'Rventas',
                'Rcajas:id,caja',
                'Rcortes:id,corte',
                'Rcajeros:id,name'
            ])
            ->where('sucursal', session('sucursal'))
            ->where('empresa', session('empresa'))
            ->where('caja', session('caja'))
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('correlativo', 'desc');

        if (strlen($this->search2) > 0) {
            $s = $this->search2;
            $q->where(function ($qq) use ($s) {
                $qq->where('correlativo', $s)
                    ->orWhereDate('fecha', $s)
                    ->orWhereHas('Rvendedor', function ($qq2) use ($s) {
                        $qq2->where('name', 'like', "%{$s}%");
                    });
            });
        } else {
            //$q->limit($this->limit);
        }

        $this->ventasModal = $q->get();
    }
}
