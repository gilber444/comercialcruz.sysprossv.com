<?php

namespace App\Http\Livewire;

use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use App\Support\AnexosF07;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Libro de Compras: respaldo documental del Anexo 3 del F07.
 *
 * El desglose fiscal se lee de las columnas que la compra guarda desde la
 * migracion 2026_08_08_120000 (NuevaCompraController/EditarCompraController
 * lo calculan al guardar). Las compras anteriores tienen el desglose en cero:
 * la pantalla las cuenta y avisa, porque saldrian en cero en el anexo.
 *
 * La consulta vive en LibrosIvaService::compras(), la misma del PDF
 * (App\Http\Controllers\LibroCompras).
 */
class LibroComprasController extends Component
{
    public $desde, $hasta, $empresa, $sucursal = '0';
    public $compras = [];
    public $empresas = [], $sucursales = [];
    public $consultado = false;

    public function mount()
    {
        $user = Auth::user();
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');
        $this->empresas = app(LibrosIvaService::class)->empresasPermitidas($user);
        $this->empresa = $user->empresa;
        $this->sucursales = app(LibrosIvaService::class)->puedeVerEmpresa(Auth::user(), $this->empresa)
            ? Sucursales::where('empresa', $this->empresa)->get()
            : [];
    }

    public function render()
    {
        $servicio = app(LibrosIvaService::class);
        $filas = collect($this->compras);
        $enAnexo = $filas->where('en_anexo', true);

        return view('livewire.libros.libro-compras', [
            'totales' => $servicio->totales($filas, ['compras_exentas', 'compras_gravadas', 'credito_fiscal', 'total_compra']),
            'totalesAnexo' => $servicio->totales($enAnexo, ['compras_exentas', 'compras_gravadas', 'credito_fiscal', 'total_compra']),
            'enAnexo' => $enAnexo->count(),
            'sinDesglose' => $filas->where('sin_desglose', true)->count(),
        ])->extends('layouts.theme.app')->section('content');
    }

    /**
     * Al cambiar un filtro se limpia el resultado: el PDF y el CSV se arman
     * con los filtros vigentes y no deben diferir de la tabla en pantalla.
     */
    public function updated($propiedad)
    {
        if (in_array($propiedad, ['empresa', 'sucursal', 'desde', 'hasta'], true)) {
            $this->compras = [];
            $this->consultado = false;
        }
    }

    public function updateSucursal()
    {
        $this->sucursales = app(LibrosIvaService::class)->puedeVerEmpresa(Auth::user(), $this->empresa)
            ? Sucursales::where('empresa', $this->empresa)->get()
            : [];
        $this->sucursal = '0';
    }

    public function processCompras()
    {
        $this->validate($this->reglas(), $this->mensajes());

        $this->loadCompras();
    }

    public function loadCompras()
    {
        $servicio = app(LibrosIvaService::class);

        if (!$servicio->puedeVerEmpresa(Auth::user(), $this->empresa)) {
            $this->emit('item-warning', 'No tiene acceso a la empresa seleccionada.');
            return;
        }

        $this->compras = $servicio->compras($this->empresa, $this->sucursal, $this->desde, $this->hasta)->all();
        $this->consultado = true;
    }

    /**
     * ANEXO 3 - Detalle de compras (21 columnas A..U). Solo entran los
     * documentos que el anexo admite (03, 05, 06, 11, 12, 13) con monto; las
     * facturas de consumidor final no dan credito fiscal y no van al anexo.
     * Ver App\Support\AnexosF07::filaAnexo3().
     */
    public function exportAnexoCompras()
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

        $compras = $servicio->compras($this->empresa, $this->sucursal, $this->desde, $this->hasta)
            ->where('en_anexo', true)
            ->values();

        if ($compras->isEmpty()) {
            $this->emit('item-warning', 'No hay compras con credito fiscal (con desglose) en ese periodo.');
            return;
        }

        $periodo = $this->desde;
        $csv = AnexosF07::csv($compras->map(fn ($c) => AnexosF07::filaAnexo3($c, $periodo)));

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, AnexosF07::nombreArchivo(3, $this->desde), [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
        ];
    }

    private function mensajes(): array
    {
        return [
            'empresa.required' => 'Debe seleccionar una empresa.',
            'sucursal.required' => 'Debe seleccionar una sucursal.',
            'desde.required' => 'Seleccione la fecha inicial.',
            'desde.date' => 'La fecha "Desde" debe ser una fecha válida.',
            'hasta.required' => 'Seleccione la fecha final.',
            'hasta.date' => 'La fecha "Hasta" debe ser una fecha válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }
}
