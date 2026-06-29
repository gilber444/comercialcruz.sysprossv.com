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



    public function FirmarDTE($id)
    {
        try {
            $dte = dte::find($id);

            if (!$dte) {
                throw new \Exception("No se encontró el DTE con ID {$id}.");
            }

            if ($dte->tipoDte == 1) {
                $json = $this->GeneraJsonF($dte->id);
                $this->FirmadorLocal($dte->id, $json);
                $this->RecepcionDTEF($dte->id);
            } else {
                $json = $this->GeneraJsonC($dte->id);
                $this->FirmadorLocal($dte->id, $json);
                $this->RecepcionDTEC($dte->id);
            }

            $dte2 = dte::find($id);

            if ($dte2->estado == 'PROCESADO') {
                $this->emit('item-addedd', 'DTE Firmado y Procesado correctamente.');
            } elseif ($dte2->estado == 'RECHAZADO') {
                $this->emit('item-errorr', 'DTE Rechazado por Hacienda. Revise el DTE generado para más información.');
            } else {
                $this->emit('item-errorr', 'El DTE no pudo ser procesado. Estado actual: ' . $dte2->estado);
            }
        } catch (\Throwable $e) {
            \Log::error('Error en DteController::FirmarDTE', [
                'dte_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->emit('item-errorr', 'Error al procesar el DTE: ' . $e->getMessage());
        }
    }



    public function enviarCorreo($id)

    {

        $correo = $this->enviarCorreoDTE($id);



        $this->emit('item-added', $correo);

    }



    public function GenerarDTE($id)
    {
        try {
            $dte = dte::find($id);

            if (!$dte) {
                throw new \Exception("No se encontró el DTE con ID {$id}.");
            }

            if ($dte->tipoDte == 1) {
                $json = $this->GeneraJsonF($id);
                $this->FirmadorLocal($id, $json);
                $this->RecepcionDTEF($id);
            } else {
                $json = $this->GeneraJsonC($id);
                $this->FirmadorLocal($id, $json);
                $this->RecepcionDTEC($id);
            }

            $dte2 = dte::find($id);

            if ($dte2->estado == 'PROCESADO') {
                $this->emit('item-addedd', 'DTE Firmado y Procesado correctamente.');
            } elseif ($dte2->estado == 'RECHAZADO') {
                $this->emit('item-errorr', 'DTE Rechazado por Hacienda. Revise el DTE generado para más información.');
            } else {
                $this->emit('item-errorr', 'El DTE no pudo ser procesado. Estado actual: ' . $dte2->estado);
            }
        } catch (\Throwable $e) {
            \Log::error('Error en DteController::GenerarDTE', [
                'dte_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->emit('item-errorr', 'Error al procesar el DTE: ' . $e->getMessage());
        }
    }



    public function Detalle($id)

    {

        $re = RecepcionDte::where('dte', $id)->latest()->first();



        $this->jsons = $re->josn;

        $this->emit('sshoww-modal', 'show modal');

    }



    /**
     * Verifica que el usuario autenticado pueda acceder al DTE.
     * Super/Admin/DTE_ViewAll pueden ver cualquiera; el resto solo de su empresa/sucursal.
     */
    protected function authorizeDteAccess(dte $dte): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'No autenticado.');
        }

        $canViewAll = $user->profile === 'Super'
            || $user->profile === 'Administrador'
            || $user->can('DTE_ViewAll');

        if (!$canViewAll) {
            if ((int) $dte->empresa !== (int) $user->empresa || (int) $dte->sucursal !== (int) $user->sucursal) {
                abort(403, 'No tiene permiso para acceder a este DTE.');
            }
        }
    }

    public function show($id)

    {

        // Recupera el registro de la base de datos

        $rec = dte::findOrFail($id);

        $this->authorizeDteAccess($rec);

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

        $this->authorizeDteAccess($rec);

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

        if ($bin === null) return null;



        // 1) Intento normal (gzcompress de PHP)

        $out = @gzuncompress($bin);

        if ($out !== false) return $out;



        // 2) Intento COMPRESS() de MySQL (zlib con cabecera de 4 bytes)

        $out = @gzuncompress(substr($bin, 4));

        if ($out !== false) return $out;



        // 3) Por si tienes filas antiguas sin comprimir (texto plano JSON)

        $trim = ltrim($bin);

        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {

            return $bin;

        }



        return null;

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
