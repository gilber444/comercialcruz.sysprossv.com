<?php

namespace App\Http\Livewire;

use App\Models\ClaseDocumento;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\Ventas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LibroInvalidacionesController extends Component
{
    public $desde, $hasta, $sales = [], $documentos, $empresa, $empresas = [], $sucursal, $sucursales = [], $facturador;

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
        return view('livewire.libros.libro-invalidaciones')->extends('layouts.theme.app')->section('content');
    }

    protected $listeners = [
        'closeAndOpenBook' => 'closeAndOpenBook',
        'processSales' => 'processSales',
    ];

    public function processSales()
    {
        $rules = [
            'empresa' => 'required|integer',
            'sucursal' => 'required|integer',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'facturador' => 'required'
        ];

        $messages = [
            'empresa.required' => 'Debe seleccionar una empresa.',
            'sucursal.required' => 'Debe seleccionar una sucursal.',
            'desde.required' => 'El campo "Desde" es obligatorio.',
            'desde.date' => 'La fecha "Desde" debe ser una fecha válida.',
            'hasta.required' => 'El campo "Hasta" es obligatorio.',
            'hasta.date' => 'La fecha "Hasta" debe ser una fecha válida.',
            'hasta.after_or_equal' => 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
            'facturador.required' => 'Debe seleccionar un facturador.',
        ];

        $this->validate($rules, $messages);

        $this->loadSales();
    }

    public function updateSucursal()
    {
        $this->sucursales = Sucursales::where('empresa', $this->empresa)->get();
    }

    public function loadSales()
    {
        $this->sales = dte::with('Rventa', 'RtipoDte')
        ->whereBetween('fecEmi', [$this->desde, $this->hasta])
        ->when($this->facturador != 0, function ($q) {
            $q->where('tipoDte', $this->facturador);
        })
        ->where('empresa', $this->empresa)
        ->where('sucursal', $this->sucursal)
        ->where('estado', 'INVALIDADO')
        ->orderBy('fecEmi', 'asc')
        ->orderBy('horEmi', 'asc')
        ->orderBy('numeroControl', 'asc')
        ->get();


    }

    public function exportCSV()
    {
        // Validar que haya datos cargados
        if (!$this->empresa || !$this->desde || !$this->hasta) {
            $this->emit('item-warning', 'Faltan datos para generar el anexo.');
            return;
        }

        $sales = dte::with('Rventa', 'RtipoDte')
        ->whereBetween('fecEmi', [$this->desde, $this->hasta])
        ->when($this->facturador != 0, function ($q) {
            $q->where('tipoDte', $this->facturador);
        })
        ->where('empresa', $this->empresa)
        ->where('sucursal', $this->sucursal)
        ->where('estado', 'INVALIDADO')
        ->orderBy('fecEmi', 'asc')
        ->orderBy('horEmi', 'asc')
        ->orderBy('numeroControl', 'asc')
        ->get();;

        $sales;

        //$nombreArchivo .= '.csv'; // Asegurar la extensión CSV
        $nombreArchivo = "Anexo-{$this->desde}-{$this->hasta}.csv";

        return Response::stream(function () use ($sales) {
            $handle = fopen('php://output', 'w');

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    //// A. Numero de Resolucion
                    $sale->numeroControl,
                    // B. Clase de documento
                    '4',
                    // C. Desde
                    '0',
                    // D. Hasta
                    '0',
                    // E. Tipo de Documento
                    $sale->RtipoDte->codigo,
                    // F. Tipo Detalle
                    'D',
                    // G. Serie
                    str_replace('-', '', $sale->sello),
                    // H. Desde
                    '0',
                    // I. Hasta
                    '0',
                    // J. Codigo de Generacion
                    str_replace('-', '', $sale->codigoGeneracion),
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
