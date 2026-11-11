<?php
namespace App\Http\Livewire;

use App\Models\Parametros;
use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use App\Support\AnexosF07;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Libro de ventas a consumidor final + Anexo 2 del F07.
 *
 * Ruta viva: GET libroVentasConsumidor (permiso LibroVentasConsumidor_Index).
 * La consulta vive en LibrosIvaService::ventasConsumidor(), la misma que usa
 * el PDF (App\Http\Controllers\LibrosConsumidor): solo ventas DTE finalizadas
 * (Procesado + sello) del ambiente de la empresa, agrupadas por dia y caja.
 */
class LibroVentasConsumidorController extends Component
{
    public $desde,
        $hasta,
        $sales = [],
        $empresa,
        $empresas = [],
        $sucursal,
        $sucursales = [],
        $caja,
        $cajas = [],
        $consultado = false;

    public function mount()
    {
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');
        $this->empresas = app(LibrosIvaService::class)->empresasPermitidas(Auth::user());
    }

    public function render()
    {
        $totales = app(LibrosIvaService::class)->totales($this->sales, ['exentas', 'no_sujetas', 'gravadas', 'total']);

        return view('livewire.libros.libro-ventas-consumidor', compact('totales'))
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

    public function loadSales()
    {
        $servicio = app(LibrosIvaService::class);

        if (!$servicio->puedeVerEmpresa(Auth::user(), $this->empresa)) {
            $this->emit('item-warning', 'No tiene acceso a la empresa seleccionada.');
            return;
        }

        $this->sales = $servicio
            ->ventasConsumidor($this->empresa, $this->sucursal, $this->caja, $this->desde, $this->hasta)
            ->all();
        $this->consultado = true;
    }

    /**
     * Al cambiar un filtro se limpia el resultado: el PDF y el CSV se arman
     * con los filtros vigentes y no deben diferir de la tabla en pantalla.
     */
    public function updated($propiedad)
    {
        if (in_array($propiedad, ['empresa', 'sucursal', 'caja', 'desde', 'hasta'], true)) {
            $this->sales = [];
            $this->consultado = false;
        }
    }

    public function updateSucursal()
    {
        $this->sucursales = app(LibrosIvaService::class)->puedeVerEmpresa(Auth::user(), $this->empresa)
            ? Sucursales::where('empresa', $this->empresa)->get()
            : [];
        // La sucursal y la caja elegidas eran de la empresa anterior.
        $this->sucursal = '';
        $this->caja = '';
        $this->cajas = [];
    }

    public function updateCaja()
    {
        $this->cajas = Parametros::where('empresa', $this->empresa)->where('sucursal', $this->sucursal)->get();
        $this->caja = '';
    }

    /**
     * ANEXO 2 - Detalle de ventas a consumidor final (23 columnas A..W).
     * Ver App\Support\AnexosF07::filaAnexo2() para el detalle por columna.
     */
    public function exportCSV()
    {
        $this->validate($this->reglas(), $this->mensajes());

        if (!AnexosF07::mismoPeriodo($this->desde, $this->hasta)) {
            $this->emit('item-warning', 'El anexo se carga por mes: Desde y Hasta deben estar en el mismo mes.');
            return;
        }

        $servicio = app(LibrosIvaService::class);

        if (!$servicio->puedeVerEmpresa(Auth::user(), $this->empresa)) {
            $this->emit('item-warning', 'No tiene acceso a la empresa seleccionada.');
            return;
        }

        $grupos = $servicio->ventasConsumidor($this->empresa, $this->sucursal, $this->caja, $this->desde, $this->hasta);

        if ($grupos->isEmpty()) {
            $this->emit('item-warning', 'No hay ventas DTE finalizadas en ese periodo.');
            return;
        }

        $periodo = $this->desde;
        $csv = AnexosF07::csv($grupos->map(fn ($g) => AnexosF07::filaAnexo2($g, $periodo)));

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, AnexosF07::nombreArchivo(2, $this->desde), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function reglas(): array
    {
        return [
            'empresa' => 'required|integer',
            'sucursal' => 'required|integer',
            'caja' => 'required|integer',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ];
    }

    private function mensajes(): array
    {
        return [
            'empresa.required' => 'Debe seleccionar una empresa.',
            'sucursal.required' => 'Debe seleccionar una sucursal.',
            'caja.required' => 'Debe seleccionar una caja.',
            'desde.required' => 'El campo "Desde" es obligatorio.',
            'desde.date' => 'La fecha "Desde" debe ser una fecha válida.',
            'hasta.required' => 'El campo "Hasta" es obligatorio.',
            'hasta.date' => 'La fecha "Hasta" debe ser una fecha válida.',
            'hasta.after_or_equal' => 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
        ];
    }
}
