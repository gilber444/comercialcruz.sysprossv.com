<?php

namespace App\Http\Livewire;

use App\Exports\AnexoContribuyentesExport;
//use Livewire\WithFileDownloads;
use App\Models\ClaseDocumento;
use App\Models\Empresas;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class LibroContribuyenteController extends Component
{
    //use WithFileDownloads;

    public $desde, $hasta, $sales = [], $documentos, $empresa, $empresas, $sucursal, $sucursales = [], $caja, $cajas = [];

    public function mount()
    {
        $this->desde = Carbon::now()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');
        $this->documentos = ClaseDocumento::all();
        $this->empresas = Empresas::all();
    }

    public function render()
    {
        return view('livewire.libros.libro-contribuyente')
        ->extends("layouts.theme.app")
        ->section("content");
    }

    public function updateSucursal()
    {
        $this->sucursales = Sucursales::where('empresa', $this->empresa)->get();
    }

    public function updateCaja()
    {
        $this->cajas = Parametros::where('empresa', $this->empresa)->where('sucursal', $this->sucursal)->get();
    }

    public function processSales()
    {
        $rules = [
            'empresa' => 'required|integer',
            'sucursal' => 'required|integer',
            'caja' => 'required|integer',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ];

        $messages = [
            'empresa.required' => 'Debe seleccionar una empresa.',
            'sucursal.required' => 'Debe seleccionar una sucursal.',
            'caja.required' => 'Debe seleccionar una caja.',
            'desde.required' => 'El campo "Desde" es obligatorio.',
            'desde.date' => 'La fecha "Desde" debe ser una fecha válida.',
            'hasta.required' => 'El campo "Hasta" es obligatorio.',
            'hasta.date' => 'La fecha "Hasta" debe ser una fecha válida.',
            'hasta.after_or_equal' => 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
        ];

        $this->validate($rules, $messages);

        $this->loadSales();
    }

    public function loadSales()
    {
        $this->sales = Ventas::select(
            'fecha',
            'empresa',
            'sucursal',
            'caja',
            'tipo',
            'cliente',
            'numero',
            'sello',
            'codigo',
            'total'
        )
        ->with([
            'Rsucursal:id,nombre',
            'Rcajas:id,caja,tresolucion,conresolucion,tserie,conserie',
            'Rfacturadors:id,facturador',
            'Rclientes:id,nombreCliente,registro,dui,nit',
        ])
        ->where('empresa', $this->empresa)
        ->when($this->sucursal != '0', function ($query) {
            return $query->where('sucursal', $this->sucursal);
        })
        ->when($this->caja != '0', function ($query) {
            return $query->where('caja', $this->caja);
        })
        ->where('facturador', 3)
        ->whereNotNull('sello')         // 👈 asegura que no sea NULL
        ->where('sello', '!=', '')      // 👈 asegura que no esté vacío
       ->whereIn('estado', ['Cancelado', 'Credito'])
        ->whereBetween('fecha', [$this->desde, $this->hasta])
        ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('dtes')                         // ajusta si tu tabla se llama distinto
                ->whereColumn('dtes.venta', 'ventas.id')
                ->where('dtes.estado', 'Procesado');
            })
        ->orderBy('fecha', 'asc')
        ->get();
    }

    public function exportCSV()
    {

        // Validar que haya datos cargados
        if (!$this->empresa || !$this->desde || !$this->hasta) {
            $this->emit('item-warning', 'Faltan datos para generar el anexo.');
            return;
        }

        $sales = Ventas::select(
            'fecha',
            'empresa',
            'sucursal',
            'caja',
            'tipo',
            'cliente',
            'numero',
            'sello',
            'codigo',
            'total'
        )
        ->with([
            'Rsucursal:id,nombre',
            'Rcajas:id,caja,tresolucion,conresolucion,tserie,conserie',
            'Rfacturadors:id,facturador',
            'Rclientes:id,nombreCliente,registro,dui,nit',
        ])
        ->where('empresa', $this->empresa)
        ->when($this->sucursal != '0', function ($query) {
            return $query->where('sucursal', $this->sucursal);
        })
        ->when($this->caja != '0', function ($query) {
            return $query->where('caja', $this->caja);
        })
        ->where('facturador', 3)
        ->whereNotNull('sello')         // 👈 asegura que no sea NULL
        ->where('sello', '!=', '')      // 👈 asegura que no esté vacío
        ->whereIn('estado', ['Cancelado', 'Credito'])
        ->whereBetween('fecha', [$this->desde, $this->hasta])
        ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('dtes')                         // ajusta si tu tabla se llama distinto
                ->whereColumn('dtes.venta', 'ventas.id')
                ->where('dtes.estado', 'Procesado');
            })
        ->orderBy('fecha', 'asc')
        ->get();

        $sales;
        $nombreBase = "Anexo-{$this->desde}-{$this->hasta}";
        $this->ResetInput();


        $nombreArchivo = strlen($nombreBase) > 25
            ? substr($nombreBase, 0, 25)
            : $nombreBase;

        $nombreArchivo .= '.csv'; // Asegurar la extensión CSV

        // Crear el archivo CSV
        $export = new AnexoContribuyentesExport($sales);
        return ExcelFacade::download(
            new AnexoContribuyentesExport($sales),
            $nombreArchivo,
            Excel::CSV
        );

        return redirect()->route('libroVentasConsumidor');
    }

    public function ResetInput()
    {
        $this->reset([
            'desde',
            'hasta',
            'sales',
            'empresa',
            'sucursal',
            'caja',
        ]);
        //$this->empresas = Empresas::all();
        $this->sucursales = [];
        $this->cajas = [];
        $this->sales = [];
        $this->desde = '';
        $this->hasta = '';
        $this->empresa = '';
        $this->sucursal = '';
    }
}