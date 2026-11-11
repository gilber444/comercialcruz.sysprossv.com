<?php

namespace App\Http\Livewire;

use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Livewire\Component;

/**
 * Libro de invalidaciones (DTE invalidados).
 *
 * Ruta viva: GET libroInvalidaciones (permiso LibroInvalidaciones_Index).
 * La consulta vive en LibrosIvaService::invalidaciones(), la misma del PDF
 * (App\Http\Controllers\LibroInvalidacion): DTE con sello, estado INVALIDADO
 * y del ambiente de la empresa.
 */
class LibroInvalidacionesController extends Component
{
    public $desde, $hasta, $sales = [], $empresa, $empresas = [], $sucursal, $sucursales = [], $facturador;
    public $consultado = false;

    public function mount()
    {
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');
        $this->empresas = app(LibrosIvaService::class)->empresasPermitidas(Auth::user());
    }

    public function render()
    {
        $totales = app(LibrosIvaService::class)->totales($this->sales, ['total']);

        return view('livewire.libros.libro-invalidaciones', compact('totales'))
            ->extends('layouts.theme.app')
            ->section('content');
    }

    protected $listeners = [
        'processSales' => 'processSales',
    ];

    public function processSales()
    {
        $this->validate($this->reglas(), $this->mensajes());

        $this->loadSales();
    }

    /**
     * Al cambiar un filtro se limpia el resultado: el PDF se arma con los
     * filtros vigentes y no debe diferir de la tabla en pantalla.
     */
    public function updated($propiedad)
    {
        if (in_array($propiedad, ['empresa', 'sucursal', 'facturador', 'desde', 'hasta'], true)) {
            $this->sales = [];
            $this->consultado = false;
        }
    }

    public function updateSucursal()
    {
        $this->sucursales = app(LibrosIvaService::class)->puedeVerEmpresa(Auth::user(), $this->empresa)
            ? Sucursales::where('empresa', $this->empresa)->get()
            : [];
        // La sucursal elegida era de la empresa anterior.
        $this->sucursal = '';
    }

    public function loadSales()
    {
        $servicio = app(LibrosIvaService::class);

        if (!$servicio->puedeVerEmpresa(Auth::user(), $this->empresa)) {
            $this->emit('item-warning', 'No tiene acceso a la empresa seleccionada.');
            return;
        }

        $this->sales = $servicio
            ->invalidaciones($this->empresa, $this->sucursal, $this->facturador, $this->desde, $this->hasta)
            ->all();
        $this->consultado = true;
    }

    /**
     * Archivo de documentos invalidados (formato heredado del sistema, sin
     * cambios de columnas). No forma parte de los anexos 1, 2, 3 y 7; su
     * estructura no se reviso contra la seccion XIX del manual F-07.
     */
    public function exportCSV()
    {
        $this->validate($this->reglas(), $this->mensajes());

        $servicio = app(LibrosIvaService::class);

        if (!$servicio->puedeVerEmpresa(Auth::user(), $this->empresa)) {
            $this->emit('item-warning', 'No tiene acceso a la empresa seleccionada.');
            return;
        }

        $sales = $servicio->invalidaciones($this->empresa, $this->sucursal, $this->facturador, $this->desde, $this->hasta);

        if ($sales->isEmpty()) {
            $this->emit('item-warning', 'No hay documentos invalidados en ese periodo.');
            return;
        }

        $nombreArchivo = "Anexo-{$this->desde}-{$this->hasta}.csv";

        return Response::stream(function () use ($sales) {
            $handle = fopen('php://output', 'w');

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    // A. Numero de resolucion
                    $sale['numero_control'],
                    // B. Clase de documento
                    '4',
                    // C. Desde
                    '0',
                    // D. Hasta
                    '0',
                    // E. Tipo de documento
                    $sale['tipo_codigo'],
                    // F. Tipo de detalle
                    'D',
                    // G. Serie
                    str_replace('-', '', $sale['sello']),
                    // H. Desde
                    '0',
                    // I. Hasta
                    '0',
                    // J. Codigo de generacion
                    str_replace('-', '', $sale['codigo']),
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$nombreArchivo}",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function reglas(): array
    {
        return [
            'empresa' => 'required|integer',
            'sucursal' => 'required|integer',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'facturador' => 'required',
        ];
    }

    private function mensajes(): array
    {
        return [
            'empresa.required' => 'Debe seleccionar una empresa.',
            'sucursal.required' => 'Debe seleccionar una sucursal.',
            'desde.required' => 'El campo "Desde" es obligatorio.',
            'desde.date' => 'La fecha "Desde" debe ser una fecha válida.',
            'hasta.required' => 'El campo "Hasta" es obligatorio.',
            'hasta.date' => 'La fecha "Hasta" debe ser una fecha válida.',
            'hasta.after_or_equal' => 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
            'facturador.required' => 'Debe seleccionar un tipo de documento.',
        ];
    }
}
