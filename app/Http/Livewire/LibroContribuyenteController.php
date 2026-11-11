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
 * Libro de ventas a contribuyentes + Anexo 1 y Anexo 7 del F07.
 *
 * Ruta viva: GET libroVentasContribuyente (permiso LibroVentasContribuyente_Index).
 * OJO: la vista livewire/libros/libro-ventas-contribuyente.blade.php es
 * HUERFANA (no hay componente que la cargue); la vista viva es
 * libro-contribuyente.blade.php.
 *
 * La consulta vive en LibrosIvaService, la misma que usa el PDF
 * (App\Http\Controllers\LibroContribuyente).
 */
class LibroContribuyenteController extends Component
{
    public $desde, $hasta, $sales = [], $empresa, $empresas = [], $sucursal, $sucursales = [], $caja, $cajas = [];
    public $consultado = false;

    public function mount()
    {
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');
        $this->empresas = app(LibrosIvaService::class)->empresasPermitidas(Auth::user());
    }

    public function render()
    {
        $totales = app(LibrosIvaService::class)->totales($this->sales, ['exentas', 'no_sujetas', 'gravada', 'debito', 'total', 'retencion']);

        return view('livewire.libros.libro-contribuyente', compact('totales'))
            ->extends('layouts.theme.app')
            ->section('content');
    }

    /**
     * Al cambiar un filtro se limpia el resultado: el PDF y los CSV se arman
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
            ->ventasContribuyentes($this->empresa, $this->sucursal, $this->caja, $this->desde, $this->hasta)
            ->all();
        $this->consultado = true;
    }

    /**
     * ANEXO 1 - Detalle de ventas a contribuyentes (20 columnas A..T).
     * Ver App\Support\AnexosF07::filaAnexo1().
     */
    public function exportCSV()
    {
        if (!$this->validarPeriodoAnexo()) {
            return;
        }

        $ventas = app(LibrosIvaService::class)
            ->ventasContribuyentes($this->empresa, $this->sucursal, $this->caja, $this->desde, $this->hasta);

        if ($ventas->isEmpty()) {
            $this->emit('item-warning', 'No hay creditos fiscales DTE finalizados en ese periodo.');
            return;
        }

        $periodo = $this->desde;

        return $this->descargar(
            AnexosF07::csv($ventas->map(fn ($v) => AnexosF07::filaAnexo1($v, $periodo))),
            AnexosF07::nombreArchivo(1, $this->desde)
        );
    }

    /**
     * ANEXO 7 - Retencion 1% IVA efectuada AL Declarante (casilla 162).
     *
     * Por el Art. 162 CT el gran contribuyente retiene el 1% cuando compra:
     * el CLIENTE categoria GRANDE le retiene a la empresa, la retencion queda
     * en el DTE que la empresa emite (ivaRete1) y baja el total a cobrar. Por
     * eso este anexo sale de VENTAS y el agente que se informa es el cliente.
     * La columna se llama ventas.percepcion por un error historico de nombre;
     * el calculo (se RESTA del total) confirma que es retencion. No es el
     * anexo 9 (percepcion efectuada POR el declarante).
     */
    public function exportAnexoRetencion()
    {
        if (!$this->validarPeriodoAnexo()) {
            return;
        }

        $retenciones = app(LibrosIvaService::class)
            ->retencionesClientes($this->empresa, $this->sucursal, $this->caja, $this->desde, $this->hasta);

        if ($retenciones->isEmpty()) {
            $this->emit('item-warning', 'No hay ventas con retencion del 1% en ese periodo.');
            return;
        }

        return $this->descargar(
            AnexosF07::csv($retenciones->map(fn ($r) => AnexosF07::filaAnexo7($r))),
            AnexosF07::nombreArchivo(7, $this->desde)
        );
    }

    private function validarPeriodoAnexo(): bool
    {
        $this->validate($this->reglas(), $this->mensajes());

        if (!AnexosF07::mismoPeriodo($this->desde, $this->hasta)) {
            $this->emit('item-warning', 'El anexo se carga por mes: Desde y Hasta deben estar en el mismo mes.');
            return false;
        }

        if (!app(LibrosIvaService::class)->puedeVerEmpresa(Auth::user(), $this->empresa)) {
            $this->emit('item-warning', 'No tiene acceso a la empresa seleccionada.');
            return false;
        }

        return true;
    }

    private function descargar(string $csv, string $nombre)
    {
        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $nombre, [
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
