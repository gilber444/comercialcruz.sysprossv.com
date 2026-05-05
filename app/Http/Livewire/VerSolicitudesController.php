<?php



namespace App\Http\Livewire;



use Livewire\Component;

use App\Models\Inventarios;

use App\Models\Kardex;

use App\Models\Solicitudes;

use App\Models\SolicitudesDetalles;

use App\Models\Sucursales;

use Illuminate\Support\Facades\Auth;

use Livewire\WithPagination;

use DB;

use Illuminate\Support\Facades\Redirect;



class VerSolicitudesController extends Component

{

    use WithPagination;



    public $search,

        $selected_id,

        $pageTitle,

        $componentName,

        $sucursalSeleccionada,

        $ubicacion,

        $numero,

        $fecha,

        $origenes,

        $destinos,

        $solicitud,

        $detalle,

        $desde,

        $selectedDestination = [],

        $sid,

        $snombre,

        $cantidad = [],

        $estados,

        $estado,

        $accion,

        $detalleSolicitud = [],

        $totalSolicitud = 0;

    public $fechaDesde;
    public $fechaHasta;
    public $filterEstado = '';
    public $filterOrigen = '';
    public $filterDestino = '';
    public $perPage = 15;

    private $pagination = 15;



    public function mount()

    {

        $user_id = Auth::user()->id;

        $this->pageTitle = 'Listado';

        $this->componentName = 'Solicitudes Realizadas';

        $this->fechaDesde = now()->startOfMonth()->toDateString();

        $this->fechaHasta = now()->endOfMonth()->toDateString();

    }



    public function cargaDetalle()

    {

        $this->detalle = SolicitudesDetalles::join('productos as p', 'p.id', 'solicitudes_detalles.producto')

            ->select('solicitudes_detalles.*', 'p.codebar3', 'p.nombreProducto')

            ->where('solicitud', $this->selected_id)

            ->get();

    }



    public function paginationView()

    {

        return 'vendor.livewire.bootstrap';

    }



    public function render()

    {

        $user = Auth::user();

        $data = Solicitudes::with('Rorigen', 'Rdestino', 'Rsolicitante')

            ->whereNull('deleted_at')

            ->orderBy('id', 'desc');

        if ($user->profile !== 'Administrador' && $user->profile !== 'Super' && $user->profile !== 'Auditor' && $user->profile !== 'BODEGA') {

            $data->where('solicitudes.destino', $user->sucursal);

        }

        if ($this->fechaDesde && $this->fechaHasta) {

            $data->whereBetween('solicitudes.fecha', [$this->fechaDesde, $this->fechaHasta]);

        } elseif ($this->fechaDesde) {

            $data->where('solicitudes.fecha', '>=', $this->fechaDesde);

        } elseif ($this->fechaHasta) {

            $data->where('solicitudes.fecha', '<=', $this->fechaHasta);

        }

        if ($this->filterEstado) {

            $data->where('solicitudes.estado', $this->filterEstado);

        }

        if ($this->filterOrigen) {

            $data->where('solicitudes.origen', $this->filterOrigen);

        }

        if ($this->filterDestino) {

            $data->where('solicitudes.destino', $this->filterDestino);

        }

        if ($this->search) {

            $search = '%' . trim($this->search) . '%';

            $data->where(function ($q) use ($search) {

                $q->where('solicitudes.numero', 'like', $search)

                    ->orWhereHas('Rorigen', function ($q2) use ($search) {

                        $q2->where('nombre', 'like', $search);

                    })

                    ->orWhereHas('Rdestino', function ($q2) use ($search) {

                        $q2->where('nombre', 'like', $search);

                    })

                    ->orWhereHas('Rsolicitante', function ($q2) use ($search) {

                        $q2->where('name', 'like', $search);

                    });

            });

        }

        $totalRegistros = $data->count();

        $data = $data->paginate($this->perPage);

        $sucursalesList = Sucursales::orderBy('nombre')->get();

        return view('livewire.existencias.ver-solicitudes', [

            'data' => $data,
            'totalRegistros' => $totalRegistros,
            'sucursalesList' => $sucursalesList

        ])

            ->extends('layouts.theme.app')

            ->section('content');

    }

    public function setRango(string $rango): void

    {

        switch ($rango) {

            case 'hoy':

                $this->fechaDesde = now()->toDateString();

                $this->fechaHasta = now()->toDateString();

                break;

            case 'semana':

                $this->fechaDesde = now()->startOfWeek()->toDateString();

                $this->fechaHasta = now()->endOfWeek()->toDateString();

                break;

            case 'mes':

                $this->fechaDesde = now()->startOfMonth()->toDateString();

                $this->fechaHasta = now()->endOfMonth()->toDateString();

                break;

            case 'anio':

                $this->fechaDesde = now()->startOfYear()->toDateString();

                $this->fechaHasta = now()->endOfYear()->toDateString();

                break;

            case 'todo':

                $this->fechaDesde = '';

                $this->fechaHasta = '';

                break;

        }

        $this->resetPage();

    }

    public function limpiarFiltros(): void

    {

        $this->search = '';

        $this->filterEstado = '';

        $this->filterOrigen = '';

        $this->filterDestino = '';

        $this->fechaDesde = now()->startOfMonth()->toDateString();

        $this->fechaHasta = now()->endOfMonth()->toDateString();

        $this->perPage = 15;

        $this->resetPage();

    }



    protected $listeners = [

        'print-ticket' => 'printTicket',

    ];



    public function Autorizar($id)

    {

        $user = Auth::user();

        $record = Solicitudes::with('Rorigen', 'Rdestino', 'Rsolicitante')->find($id);

        $this->numero = $record->numero;

        $this->selected_id = $record->id;

        $this->fecha = $record->fecha;

        $this->origenes = $record->Rorigen->nombre;

        $this->destinos = $record->Rdestino->nombre;

        $this->solicitud = $record->Rsolicitante->name;

        $this->sid = $record->destino;

        $this->snombre = $record->Rdestino->nombre;

        $this->estados = $record->estado;

        $this->accion = 'Autorizar';



        //$this->desde = $record->destino;



        $this->detalle = SolicitudesDetalles::with(['Rproducto', 'Rorigen', 'Rdestino'])

            ->where('solicitud', $this->selected_id)

            ->get();

        foreach ($this->detalle as $det) {

            $this->selectedDestination[$det->id] = $det->destino;

            $this->cantidad[$det->id] = $det->cantidad;

        }



        $this->mount();

        $this->emit('show-modal', 'show modal');

    }



    public function Aprobar($id)

    {

        $valorSeleccionado = $this->selectedDestination[$id] ?? null;

        $cantidadSeleccionado = $this->cantidad[$id] ?? null;



        if (empty($valorSeleccionado)) {

            $this->emit('item-error', 'Seleccione una Sucursal para su aprobacion');

        } else {

            $detalle = SolicitudesDetalles::find($id);



            if (empty($cantidadSeleccionado)) {

                $cantidadMover = $detalle->cantidad;

            } else {

                $cantidadMover = $cantidadSeleccionado;

            }



            $exis = Inventarios::where('producto', $detalle->producto)

                ->where('sucursal', $valorSeleccionado)

                ->first();



            if ($cantidadMover > $exis->existencia) {

                $this->emit('no-stock', 'Existecia insuficiente en esta sucursal');

            } else {

                $user_id = Auth::user()->id;



                $detalle->update([

                    'destino' => $valorSeleccionado,

                    'cantidad' => $cantidadMover,

                    'autorizado' => $user_id,

                    'estado' => 'Autorizado',

                ]);

                //dd('aqui');

                $detalle->save();

                $this->mount();

                $this->emit('show-modal', 'show modal');

            }

        }

    }



    public function resetUI()

    {

        $this->sucursalSeleccionada = '';

        $this->search = '';

        $this->ubicacion = '';

        $this->selected_id = 0;

        $this->ubicacion = '';

        $this->numero = '';

        $this->fecha = '';

        $this->origenes = '';

        $this->destinos = '';

        $this->solicitud = '';

        $this->detalle = '';

        $this->desde = '';

        $this->selectedDestination = [];

        $this->sid = '';

        $this->snombre = '';

        $this->cantidad = [];

        $this->resetValidation();

        $this->resetPage();

    }



    public function Update()

    {

        $soli = Solicitudes::find($this->selected_id);



        $detallesAutorizados = SolicitudesDetalles::where('solicitud', $this->selected_id)

            ->where('estado', 'Solicitado')

            ->count();



        if ($detallesAutorizados > 0) {

            $this->emit('item-error', 'No se puede autorizar la solicitud porque hay productos sin autorizar.');

        } else {

            // Actualizar el estado de la solicitud principal a 'Autorizado'

            $soli->update([

                'estado' => 'Autorizado',

            ]);



            $this->resetUI();

            $this->mount();

            $this->emit('item-updated', 'Solicitud Aprobada');

        }

    }





    public function Despacho($id)

    {

        $record = Solicitudes::join('sucursales as s1', 's1.id', 'solicitudes.origen')->join('sucursales as s2', 's2.id', 'solicitudes.destino')->select('solicitudes.*', 's1.nombre as origen', 's2.nombre as destinos')->find($id);

        $this->numero = $record->numero;

        $this->selected_id = $record->id;

        $this->fecha = $record->fecha;

        $this->origenes = $record->origen;

        $this->destinos = $record->destinos;

        $this->solicitud = $record->solicitud;

        $this->sid = $record->destino;

        $this->snombre = $record->destinos;

        $this->estado = $record->estado;

        $this->accion = 'Despachar Productos';

        //$this->desde = $record->destino;



        $this->detalle = SolicitudesDetalles::join('productos as p', 'p.id', 'solicitudes_detalles.producto')

            ->select('solicitudes_detalles.*', 'p.codebar3', 'p.nombreProducto')

            ->where('solicitud', $this->selected_id)

            ->get();

        $this->mount();

        $this->emit('show-modal', 'show modal');

    }



    public function Despachar($id)

    {

        $detalle = SolicitudesDetalles::find($id);

        $user_id = Auth::user()->id;

        $fecha = date('Y-m-d');

        $hora = date('H:i:s');



        $inven = Inventarios::where('sucursal', $detalle->destino)->where('producto', $detalle->producto)->first();



        $newExis = $inven->existencia - $detalle->cantidad;

        try {



            $detalle->despachado = $user_id;

            $detalle->estado  = 'Despachado';

            $detalle->save();



            $inven->existencia = $newExis;

            $inven->save();



            $kardex = Kardex::where('producto', $detalle->producto)->where('inventario', $inven->id)

                ->orderBy('id', 'desc')

                ->first();



            $saldoCantidad = $kardex->saldoCantidad - $detalle->cantidad;

            $saldoValor = $saldoCantidad * $detalle->costo;



            $kar = Kardex::create([

                'producto' => $detalle->producto,

                'inventario' => $inven->id,

                'descripcion' => 'Despacho de producto de la sucursal con id ' . $this->selected_id,

                'fecha' => $fecha,

                'hora' => $hora,

                'ingresoCantidad' => 0,

                'ingresoValor' => 0,

                'egresoCantidad' => $detalle->cantidad,

                'egresoValor' => $detalle->total,

                'saldoCantidad' => $saldoCantidad,

                'saldoValor' => $saldoValor,

            ]);

            $this->emit('item-confirmar', 'Producto despachado');

            $this->mount();

        } catch (\Exception $e) {

            // Manejar la excepción

            // Puedes imprimir el mensaje de error o guardar en un registro de logs

            $this->emit('item-error', 'Error al despachar producto: ' . $e->getMessage());

        }

    }



    public function DespacharTodos($id)

    {

        $user_id = Auth::user()->id;

        $soli = Solicitudes::find($id);

        $fecha = date('Y-m-d');

        $hora = date('H:i:s');



        $detallesPendientes = SolicitudesDetalles::where('solicitud', $id)->where('estado', 'Autorizado')->get();



        $soli->estado = 'Despachado';

        $soli->save();



        foreach ($detallesPendientes as $detalle) {

            try {

                $inven = Inventarios::where('sucursal', $detalle->destino)

                    ->where('producto', $detalle->producto)

                    ->first();



                if ($inven) {

                    $newExis = $inven->existencia - $detalle->cantidad;



                    $detalle->despachado = $user_id;

                    $detalle->estado = 'Despachado';

                    $detalle->save();



                    $inven->existencia = $newExis;

                    $inven->save();



                    $kardex = Kardex::where('producto', $detalle->producto)->where('inventario', $inven->id)

                        ->orderBy('id', 'desc')

                        ->first();



                    if ($kardex) {

                        $saldoCantidad = $kardex->saldoCantidad - $detalle->cantidad;

                        $saldoValor = $saldoCantidad * $detalle->costo;



                        $kar = Kardex::create([

                            'producto' => $detalle->producto,

                            'inventario' => $inven->id,

                            'descripcion' => 'Despacho de producto de la sucursal con id ' . $this->selected_id,

                            'fecha' => $fecha,

                            'hora' => $hora,

                            'ingresoCantidad' => 0,

                            'ingresoValor' => 0,

                            'egresoCantidad' => $detalle->cantidad,

                            'egresoValor' => $detalle->total,

                            'saldoCantidad' => $saldoCantidad,

                            'saldoValor' => $saldoValor,

                        ]);

                    } else {

                        // Manejar el caso donde no se encuentra un registro en Kardex

                    }

                } else {

                    // Manejar el caso donde no se encuentra un registro en Inventario

                }

            } catch (Exception $e) {

                // Manejar la excepción

                $this->emit('item-error', 'Error al despachar detalle: ' . $e->getMessage());

            }

        }



        $this->emit('item-updated', 'Productos despachados');

        $this->mount();

    }



    public function Ingreso($id)

    {

        //dd($id);

        $record = Solicitudes::join('sucursales as s1', 's1.id', 'solicitudes.origen')

            ->join('sucursales as s2', 's2.id', 'solicitudes.destino')

            ->select('solicitudes.*', 's1.nombre as origen', 's2.nombre as destinos')

            ->find($id);

        $this->numero = $record->numero;

        $this->selected_id = $record->id;

        $this->fecha = $record->fecha;

        $this->origenes = $record->origen;

        $this->destinos = $record->destinos;

        $this->solicitud = $record->solicitud;

        $this->sid = $record->destino;

        $this->snombre = $record->destinos;

        $this->estado = $record->estado;

        $this->accion = 'Ingresar Productos de la ';



        $this->detalle = SolicitudesDetalles::join('productos as p', 'p.id', 'solicitudes_detalles.producto')

            ->select('solicitudes_detalles.*', 'p.codebar3', 'p.nombreProducto')

            ->where('solicitud', $this->selected_id)

            ->whereNull('solicitudes_detalles.deleted_at')

            ->get();

        $this->cargaDetalle();

        $this->emit('show-modal', 'show modal');

    }



    public function Ingresar($id)

    {

        $user = Auth::user();

        $detalle = SolicitudesDetalles::find($id);

        $user_id = Auth::user()->id;

        $fecha = date('Y-m-d');

        $hora = date('H:i:s');



        //dd($detalle->destino);

        $inven = Inventarios::firstOrCreate(

            [

                //'empresa' => 1,

                'sucursal' => $detalle->destino,

                'producto' => $detalle->producto,

            ],

            //[

            //     'existencia' => 0, // si no existe lo crea con 0

            // ]

        );



        $newExis = $inven->existencia + $detalle->descargar;



        $detalle->despachado = $user_id;

        $detalle->estado = 'Finalizado';

        $detalle->save();



        $inven->existencia = $newExis;

        $inven->save();



        $kardex = Kardex::where('producto', $detalle->producto)->where('inventario', $inven->id)

            ->orderBy('id', 'desc')

            ->first();



        $sucur = Sucursales::find($detalle->destino);

        $origen = Sucursales::find($detalle->origen);



        $saldoCantidad = $newExis;

        $saldoValor = $saldoCantidad * $detalle->costo;



        $kar = Kardex::create([

            'producto' => $detalle->producto,

            'inventario' => $inven->id,

            'descripcion' => 'Ingreso de producto a la sucursal ' . $sucur->nombre . ' desde la sucursal ' . $origen->nombre . 'Ingresado por ' . $user->name,

            'fecha' => $fecha,

            'hora' => $hora,

            'ingresoCantidad' => $detalle->descargar,

            'ingresoValor' => $detalle->total,

            'egresoCantidad' => 0,

            'egresoValor' => 0,

            'saldoCantidad' => $saldoCantidad,

            'saldoValor' => $saldoValor,

        ]);

        $this->emit('item-confirmar', 'Producto Ingresado');

        $this->cargaDetalle();

    }



    public function Finalizar($id)

    {

        $detalle = Solicitudes::find($id);

        $detalle->estado = 'Finalizado';

        $detalle->save();



        $dets = SolicitudesDetalles::where('solicitud', $id)->where('estado', 'Despachado')->get();

        foreach ($dets as $d) {

            $this->Ingresar($d->id);

        }

        $this->emit('item-updated', 'Solicitud Finalizada y productos ingresados');

    }



    public function Imprimir($id)

    {

        $this->emit('print-ticketR', $this->getJsonBase64($id));

    }



    public function cargarDetallesSolicitud($id)

    {

        $this->detalleSolicitud = SolicitudesDetalles::join('productos as p', 'p.id', 'solicitudes_detalles.producto')

            ->join('medidas as m', 'm.id', 'p.medida')

            ->join('solicitudes', 'solicitudes.id', 'solicitudes_detalles.solicitud')

            ->select(

                'solicitudes_detalles.id',

                'solicitudes_detalles.cantidad',

                'solicitudes_detalles.costo',

                'solicitudes_detalles.total',

                'p.nombreProducto as producto',

                'p.codebar3',

                'm.unidad as medida'

            )

            ->where('solicitudes_detalles.solicitud', $id)

            ->get();



        $this->totalSolicitud = $this->detalleSolicitud->sum('total');



        $this->emit('detalle-modal', 'show modal');

    }

}