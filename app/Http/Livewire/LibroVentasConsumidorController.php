<?php
namespace App\Http\Livewire;

use App\Exports\AnexoContribuyenteExport;
use App\Models\ClaseDocumento;
use App\Models\Empresas;
use App\Models\LibroVentasConsumidor;
use App\Models\LibroVentasConsumidorDetalles;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class LibroVentasConsumidorController extends Component
{
    public $desde,
        $hasta,
        $sales = [],
        $documentos,
        $empresa,
        $empresas,
        $sucursal,
        $sucursales = [],
        $caja,
        $cajas = [];

    public function mount()
    {
        $this->desde = Carbon::now()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');
        $this->documentos = ClaseDocumento::all();
        $this->empresas = Empresas::all();
        //$this->sucursales = Sucursales::all();
        //$this->cajas = Parametros::all();
    }

    public function render()
    {
        return view('livewire.libros.libro-ventas-consumidor')->extends('layouts.theme.app')->section('content');
    }

    protected $listeners = [
        'closeAndOpenBook' => 'closeAndOpenBook',
        'processSales' => 'processSales',
    ];

    /*public function closeAndOpenBook()
    {
        $year = Carbon::parse($this->startDate)->year;
        $month = Carbon::parse($this->startDate)->month;
        $previousMonth = Carbon::create($year, $month)->subMonth();

        $previousBook = LibroVentasConsumidor::where('year', $previousMonth->year)
            ->where('period', $previousMonth->month)
            ->where('status', 'Aperturado')
            ->first();

        if ($previousBook) {
            $previousBook->status = 'Cerrado';
            $previousBook->save();
        }

        $this->createNewBook($year, $month);
    }

    public function verificarVentasMesAnterior($year, $month)
    {
        $fecha = Carbon::create($year, $month)->startOfMonth();

        while ($fecha->lte(Carbon::create($year, $month)->endOfMonth())) {
            $this->procesarVentasDiarias($fecha, 1);
            $this->procesarVentasDiarias($fecha, 2);
            $fecha->addDay();
        }
    }*/

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
        $this->sales = Ventas::select('fecha', 'empresa', 'sucursal', 'caja', 'tipo', DB::raw('MIN(correlativo) as primer_correlativo'), DB::raw('MAX(correlativo) as ultimo_correlativo'), DB::raw('MIN(codigo) as primer_codigo'), DB::raw('MAX(codigo) as ultimo_codigo'), DB::raw('MIN(numero) as primer_numero'), DB::raw('MAX(numero) as ultimo_numero'), DB::raw('SUM(total) as ventaGravada'))
            ->with(['Rsucursal:id,nombre', 'Rcajas:id,caja,tresolucion,conresolucion,tserie,conserie', 'Rfacturadors:id,facturador'])
            ->where('empresa', $this->empresa)
            ->when($this->sucursal != '0', function ($query) {
                return $query->where('sucursal', $this->sucursal);
            })
            ->when($this->caja != '0', function ($query) {
                return $query->where('caja', $this->caja);
            })
            ->whereIn('facturador', [1, 2])
            ->whereIn('estado', ['Cancelado', 'Credito'])
            ->whereNotNull('sello')         // 👈 asegura que no sea NULL
            ->where('sello', '!=', '')      // 👈 asegura que no esté vacío
            ->where('tipo', 'DTE')
            ->whereBetween('fecha', [$this->desde, $this->hasta])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('dtes')                         // ajusta si tu tabla se llama distinto
                ->whereColumn('dtes.venta', 'ventas.id')
                ->where('dtes.estado', 'Procesado');
            })
            ->groupBy('fecha', 'empresa', 'sucursal', 'caja', 'tipo')
            ->orderBy('fecha', 'asc')
            ->get();
    }

    public function updateSucursal()
    {
        $this->sucursales = Sucursales::where('empresa', $this->empresa)->get();
    }

    public function updateCaja()
    {
        $this->cajas = Parametros::where('empresa', $this->empresa)->where('sucursal', $this->sucursal)->get();
    }

    public function exportCSV()
    {
        // Validar que haya datos cargados
        if (!$this->empresa || !$this->desde || !$this->hasta) {
            $this->emit('item-warning', 'Faltan datos para generar el anexo.');
            return;
        }

        $sales = Ventas::select('fecha', 'empresa', 'sucursal', 'caja', 'tipo', DB::raw('MIN(correlativo) as primer_correlativo'), DB::raw('MAX(correlativo) as ultimo_correlativo'), DB::raw('MIN(codigo) as primer_codigo'), DB::raw('MAX(codigo) as ultimo_codigo'), DB::raw('MIN(numero) as primer_numero'), DB::raw('MAX(numero) as ultimo_numero'), DB::raw('SUM(total) as ventaGravada'))
            ->with(['Rsucursal:id,nombre', 'Rcajas:id,caja,tresolucion,conresolucion,tserie,conserie', 'Rfacturadors:id,facturador'])
            ->where('empresa', $this->empresa)
            ->when($this->sucursal != '0', function ($query) {
                return $query->where('sucursal', $this->sucursal);
            })
            ->when($this->caja != '0', function ($query) {
                return $query->where('caja', $this->caja);
            })
            ->whereIn('facturador', [1, 2])
            ->whereIn('estado', ['Cancelado', 'Credito'])
            ->whereNotNull('sello')         // 👈 asegura que no sea NULL
            ->where('sello', '!=', '')      // 👈 asegura que no esté vacío
            ->where('tipo', 'DTE')
            ->whereBetween('fecha', [$this->desde, $this->hasta])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('dtes')                         // ajusta si tu tabla se llama distinto
                ->whereColumn('dtes.venta', 'ventas.id')
                ->where('dtes.estado', 'Procesado');
            })
            ->groupBy('fecha', 'empresa', 'sucursal', 'caja', 'tipo')
            ->orderBy('fecha', 'asc')
            ->get();

        $sales;

        //$nombreArchivo .= '.csv'; // Asegurar la extensión CSV
        $nombreArchivo = "Anexo-{$this->desde}-{$this->hasta}.csv";

        return Response::stream(function () use ($sales) {
            $handle = fopen('php://output', 'w');

            foreach ($sales as $sale) {
                $esFisico = $sale->tipo === 'Fisico';

                fputcsv($handle, [
                    //// A. Fecha de emisión
                    Carbon::parse($sale->fecha)->format('d/m/Y'),
                    // B. Clase de documento
                    '4',
                    // C. Tipo de documento
                    '01',
                    // D. Número de resolución
                    str_replace('-', '', $sale->primer_codigo),
                    // E. Serie
                    str_replace('-', '', $sale->ultimo_codigo),
                    // F. Número de control (del)
                    '0',
                    // G. Número de control (al)
                    '0',
                    // H. Número de documento (del)
                    '0',
                    // I. Número de documento (al)
                    '0',
                    // J. Caja
                    null,
                    // K. Ventas exentas
                    number_format((float) ($sale->ventasExenta ?? 0), 2, '.', ''),
                    // L. Exportaciones dentro
                    number_format((float) ($sale->ventasInternaExenta ?? 0), 2, '.', ''),
                    // M. Ventas no sujetas
                    number_format((float) ($sale->ventaNoSujera ?? 0), 2, '.', ''),
                    // N. Ventas gravadas
                    number_format((float) ($sale->ventaGravada ?? 0), 2, '.', ''),
                    // O. Exportaciones dentro
                    number_format((float) ($sale->exportacionesDentro ?? 0), 2, '.', ''),
                    // P. Exportaciones fuera
                    number_format((float) ($sale->exportacionesFuera ?? 0), 2, '.', ''),
                    // Q. Exportaciones servicios
                    number_format((float) ($sale->exportacionesServicios ?? 0), 2, '.', ''),
                    // R. Ventas exentas
                    number_format((float) ($sale->ventasZonaFranca ?? 0), 2, '.', ''),
                    // S. Ventas internas exentas
                    number_format((float) ($sale->ventaCuentaTerceros ?? 0), 2, '.', ''),
                    // T. Ventas gravadas
                    number_format((float) ($sale->ventaGravada ?? 0), 2, '.', ''),
                    // U. Tipo de Operacion
                    '01',
                    // V. Tipo de Ingreso
                    '03',
                    // W. Numero de Anexo
                    '2',
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$nombreArchivo}",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
