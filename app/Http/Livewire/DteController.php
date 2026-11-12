<?php



namespace App\Http\Livewire;



use App\Models\dte;

use App\Models\RecepcionDte;
use App\Models\Sucursales;
use App\Traits\enviarCorreoDTE;

use App\Traits\Firmador2;

use App\Traits\FirmadorLocal;

use App\Traits\GeneraJsonC;

use App\Traits\RecepcionDTEC;

use App\Traits\RecepcionDTEF;
use App\Traits\RecepcionDTENota;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Livewire\Component;

use Livewire\WithPagination;



class DteController extends Component

{

    use WithPagination;

    use RecepcionDTEC;

    use RecepcionDTEF;

    use RecepcionDTENota;

    use enviarCorreoDTE;

    use GeneraJsonC;

    use Firmador2;

    use FirmadorLocal;



    public $search, $selected_id, $pageTitle, $componentName, $jsons;

    // Filtros avanzados
    public $filterEstado    = '';
    public $filterTipo      = '';
    public $filterSucursal  = 0;
    public $fechaDesde;
    public $fechaHasta;
    public $perPage         = 20;
    public $canViewAll      = false;

    private $pagination   = 20;



    public function mount()
    {
        $this->pageTitle    = 'Listado';
        $this->componentName = 'DTE Generados';
        $this->fechaDesde = now()->toDateString();
        $this->fechaHasta = now()->toDateString();

        $user = Auth::user();
        $this->canViewAll = $user->profile === 'Super'
            || $user->profile === 'Administrador'
            || $user->can('DTE_ViewAll');

        $this->filterSucursal = $this->canViewAll ? 0 : (int) ($user->sucursal ?? 0);
    }

    // Resetear paginación al cambiar cualquier filtro
    public function updatingSearch()         { $this->resetPage(); }
    public function updatingFilterEstado()  { $this->resetPage(); }
    public function updatingFilterTipo()    { $this->resetPage(); }
    public function updatingFilterSucursal(){ $this->resetPage(); }
    public function updatingFechaDesde()    { $this->resetPage(); }
    public function updatingFechaHasta()    { $this->resetPage(); }
    public function updatingPerPage()       { $this->resetPage(); }

    // Limpiar todos los filtros y volver al mes actual
    public function limpiarFiltros()
    {
        $this->search         = '';
        $this->filterEstado   = '';
        $this->filterTipo     = '';
        $this->filterSucursal = $this->canViewAll ? 0 : (int) (Auth::user()->sucursal ?? 0);
        $this->fechaDesde     = now()->startOfMonth()->toDateString();
        $this->fechaHasta     = now()->endOfMonth()->toDateString();
        $this->perPage        = 20;
        $this->resetPage();
    }

    // Rangos rápidos de fecha
    public function setRango(string $rango)
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



    public function paginationView()

    {

        return 'vendor.livewire.bootstrap';

    }



    public function render()
    {
        $user = Auth::user();

        $query = Dte::query()
            ->join('ventas as v', 'v.id', '=', 'dtes.venta')
            ->join('clientes as c', 'c.id', '=', 'v.cliente')
            ->join('tipo_documentos as td', 'td.id', '=', 'dtes.tipoDte')
            ->leftJoin('resumen_dtes as r', 'r.dte', '=', 'dtes.id')
            ->select(
                'dtes.id',
                'dtes.numeroControl',
                'dtes.codigoGeneracion',
                'dtes.fecEmi',
                'dtes.estado',
                'td.valor as tipo',
                'c.nombreCliente',
                DB::raw('COALESCE(r.totalPagar, 0) as totalPagar')
            )
            ->where('dtes.empresa', $user->empresa)
            ->where('dtes.estado', '<>', 'ELIMINADO');

        // Filtro por rango de fechas
        if ($this->fechaDesde && $this->fechaHasta) {
            $query->whereBetween('dtes.fecEmi', [$this->fechaDesde, $this->fechaHasta]);
        } elseif ($this->fechaDesde) {
            $query->where('dtes.fecEmi', '>=', $this->fechaDesde);
        } elseif ($this->fechaHasta) {
            $query->where('dtes.fecEmi', '<=', $this->fechaHasta);
        }

        // Filtro por estado
        if ($this->filterEstado) {
            $query->where('dtes.estado', $this->filterEstado);
        }

        // Filtro por tipo de DTE
        if ($this->filterTipo) {
            $query->where('td.valor', $this->filterTipo);
        }

        // Filtro por sucursal
        if (!$this->canViewAll && $this->filterSucursal) {
            $query->where('dtes.sucursal', $this->filterSucursal);
        } elseif ($this->canViewAll && $this->filterSucursal > 0) {
            $query->where('dtes.sucursal', $this->filterSucursal);
        }

        // Búsqueda
        if ($this->search) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('dtes.numeroControl',    'like', "%{$s}%")
                  ->orWhere('dtes.codigoGeneracion', 'like', "%{$s}%")
                  ->orWhere('c.nombreCliente',        'like', "%{$s}%");
            });
        }

        // Listas para los filtros desplegables
        $tiposDte = DB::table('tipo_documentos')
            ->where('status', 'Activo')
            ->orderBy('valor')
            ->pluck('valor');

        $estados = collect(['PROCESADO', 'RECHAZADO', 'Creado', 'Firmado', 'ANULADO']);

        $sucursales = $this->canViewAll
            ? Sucursales::orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $totalRegistros = $query->count();

        $dtes = $query->orderByDesc('dtes.id')->paginate($this->perPage);

        return view('livewire.dtes.dte', compact('dtes', 'tiposDte', 'estados', 'totalRegistros', 'sucursales'))
            ->extends('layouts.theme.app')
            ->section('content');
    }



    /**
     * Firma y transmite un DTE bajo un lock atomico por id.
     *
     * Sin el lock, dos cajas pulsando "procesar" sobre el mismo DTE firmaban y
     * transmitian dos veces, y la respuesta tardia de la segunda podia dejar en
     * RECHAZADO un documento que Hacienda ya habia sellado. La segunda peticion
     * ahora responde EN_PROCESO sin llegar a Hacienda.
     *
     * El lock usa el cache: con varios servidores web hay que apuntar
     * CACHE_DRIVER a redis o database, porque un lock de archivos solo sirve
     * para un unico servidor con almacenamiento comun.
     */
    private function procesarConLock(int $id): void
    {
        try {
            $servicio = app(\App\Services\ProcesamientoDteService::class);

            $resultado = $servicio->ejecutar($id, function () use ($id) {
                $dte = dte::find($id);

                if (!$dte) {
                    throw new \Exception("No se encontro el DTE con ID {$id}.");
                }

                // Por codigo del CAT-002: cada tipo con su generador (antes una
                // nota de credito se armaba como CCF).
                $codigo = str_pad((string) optional($dte->tipoDte()->first())->codigo, 2, '0', STR_PAD_LEFT);

                if ($codigo === '01') {
                    $json = $this->GeneraJsonF($dte->id);
                    $this->FirmadorLocal($dte->id, $json);
                    $this->RecepcionDTEF($dte->id);
                } elseif ($codigo === '05') {
                    $this->RecepcionDTENota($dte->id);
                } elseif ($codigo === '03') {
                    $json = $this->GeneraJsonC($dte->id);
                    $this->FirmadorLocal($dte->id, $json);
                    $this->RecepcionDTEC($dte->id);
                } else {
                    throw new \Exception("El DTE tipo {$codigo} se procesa desde su propia pantalla.");
                }

                return null;
            });

            switch ($resultado) {
                case \App\Services\ProcesamientoDteService::EN_PROCESO:
                    $this->emit('item-errorr', 'Este DTE ya se esta procesando desde otra sesion. Espere unos segundos y vuelva a consultarlo.');
                    break;

                case \App\Services\ProcesamientoDteService::YA_PROCESADO:
                    $this->emit('item-addedd', 'Este DTE ya habia sido procesado y sellado por Hacienda.');
                    break;

                case \App\Services\ProcesamientoDteService::CONSULTA_NO_DISPONIBLE:
                    $this->emit('item-errorr', 'No se pudo confirmar el estado del DTE con Hacienda. No se retransmitio para no duplicarlo; intente de nuevo mas tarde.');
                    break;

                case 'PROCESADO':
                    $this->emit('item-addedd', 'DTE Firmado y Procesado correctamente.');
                    break;

                case 'RECHAZADO':
                    $this->emit('item-errorr', 'DTE Rechazado por Hacienda. Revise el DTE generado para mas informacion.');
                    break;

                default:
                    $this->emit('item-errorr', 'El DTE no pudo ser procesado. Estado actual: ' . $resultado);
            }
        } catch (\Throwable $e) {
            \Log::error('Error en DteController::procesarConLock', [
                'dte_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->emit('item-errorr', 'Error al procesar el DTE: ' . $e->getMessage());
        }
    }

    public function FirmarDTE($id)
    {
        $this->procesarConLock((int) $id);
    }




    public function enviarCorreo($id)

    {

        $correo = $this->enviarCorreoDTE($id);



        $this->emit('item-added', $correo);

    }



    public function GenerarDTE($id)
    {
        $this->procesarConLock((int) $id);
    }




    public function Detalle($id)

    {

        $re = RecepcionDte::where('dte', $id)->latest()->first();



        $this->jsons = $re->josn;

        $this->emit('sshoww-modal', 'show modal');

    }



    public function show($id)

    {

        // Recupera el registro de la base de datos

        $rec = dte::findOrFail($id);

        $json = $this->inflateJson($rec->jsonDte);

        //$json = $record->jsonDte; // Asegúrate de usar el campo correcto



        if ($json === null) {

            abort(415, 'No se pudo descomprimir jsonDte.');

        }



        $json = $this->prettyJson($json);

        $fname = $this->fileName($rec->codigoGeneracion ?? 'dte');



        // Devuelve el JSON como respuesta

        return response($json, 200)

            ->header('Content-Type', 'application/json')

            ->header('Content-Disposition', 'inline; filename="' . $fname . '.json"');

    }



    public function download($id)

    {

        $rec  = Dte::findOrFail($id);

        // 🔹 OJO: usar el mismo campo jsonDte (no json_field)

        $json = $this->inflateJson($rec->jsonDte);



        if ($json === null) {

            abort(415, 'No se pudo descomprimir jsonDte.');

        }



        $json = $this->prettyJson($json);

        $fname = $this->fileName($rec->codigoGeneracion ?? 'dte');



        return response($json, 200)

            ->header('Content-Type', 'application/json; charset=utf-8')

            ->header('Content-Disposition', 'attachment; filename="'.$fname.'.json"');

    }



    public function confirmProcessing($id)

    {

        // Emitir el evento para mostrar la alerta de procesamiento

        $this->emit('processingDTE');



        // Llamar a la función GenerarDTE

        $this->GenerarDTE($id);

    }



    protected $listeners = [

        'GenerarDTE' => 'GenerarDTE',

    ];



    private function inflateJson(?string $bin): ?string
    {
        // El accessor del modelo ya entrega JSON normal. Se conserva el metodo
        // porque hay llamadores, pero la logica de formatos vive en un solo
        // lugar: App\Support\JsonComprimido.
        return \App\Support\JsonComprimido::descomprimir($bin);
    }



     private function prettyJson(string $json): string

    {

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            return $json; // Devuelve tal cual si no es JSON válido

        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }



    /** Limpia nombre de archivo. */

    private function fileName(string $base): string

    {

        $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $base);

        return $name !== '' ? $name : 'dte_'.now()->format('Ymd_His');

    }



}
