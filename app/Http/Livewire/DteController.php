<?php

namespace App\Http\Livewire;

use App\Models\dte;
use App\Models\RecepcionDte;
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
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'DTE Generados';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();

        $query = Dte::join('ventas as v', 'v.id', '=', 'dtes.venta')
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
                DB::raw('COALESCE(r.totalPagar,0) as totalPagar')
            )
            ->where('dtes.empresa', $user->empresa)
            ->whereMonth('dtes.fecEmi', now()->month)
            ->whereYear('dtes.fecEmi', now()->year)
            //->where('dtes.fecEmi', date('Y-m-d'))
            ->where('dtes.tipoDte', 2)
            ->where('dtes.estado', '<>', 'ELIMINADO');
        //->where('dtes.estado', '<>', 'PROCESADO'); 

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('dtes.numeroControl', 'like', "%{$this->search}%")
                    ->orWhere('dtes.codigoGeneracion', $this->search)
                    ->orWhere('c.nombreCliente', 'like', "%{$this->search}%")
                    ->orWhere('dtes.fecEmi', 'like', "%{$this->search}%")
                    ->orWhere('dtes.estado', 'like', "%{$this->search}%");
            });
        }

        $dtes = $query->orderByDesc('dtes.id')->paginate($this->pagination);

        return view('livewire.dtes.dte', compact('dtes'))
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function FirmarDTE($id)
    {
        $dte = dte::find($id);
        //$dtes = dte::where('estado', 'Creado')
        //->where('sucursal', 8)  // Corregido "suursal" por "sucursal"
        //->orderBy('id', 'asc')
        //->get();

        //foreach($dtes as $dte)
        //{
        if ($dte->tipoDte == 1) {
            $json = $this->GeneraJsonF($dte->id);
            //$firma = $this->FirmadorDTE($id, $json);
            $firma = $this->FirmadorLocal($dte->id, $json);
            $this->RecepcionDTEF($dte->id);
        } else {
            $json = $this->GeneraJsonC($dte->id);
            //$firma = $this->FirmadorDTE($id, $json);
            $firma = $this->FirmadorLocal($dte->id, $json);
            $this->RecepcionDTEC($dte->id);
        }
        //}
        $this->emit('item-added', 'DTE Firmado y Procesado');
    }

    public function enviarCorreo($id)
    {
        $correo = $this->enviarCorreoDTE($id);

        $this->emit('item-added', $correo);
    }

    public function GenerarDTE($id)
    {
        $dte = dte::find($id);
        /*$dtes = dte::where('estado','Creado')
        ->where('tipoDte', 1)
        //->where('fecEmi', date('Y-m-d'))  // Corregido "suursal" por "sucursal"
        ->orderBy('id', 'asc')
        ->get()
        ->take(75);

        foreach($dtes as $dte)
        {*/
        // $id = $dte->id;
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
        /*}*/
        $this->emit('item-added', 'DTE Firmado y Procesado');
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
