<?php

namespace App\Http\Livewire;

use App\Models\Solicitudes;
use App\Models\Sucursales;
use Livewire\Component;

// [2026-04-02] Formulario de reporte de solicitudes — mismo patrón que ReportAjustesController
class ReportSolicitudesController extends Component
{
    public $pageTitle     = 'Reporte de Solicitudes';
    public $componentName = 'Reporte de Solicitudes';
    public $origen        = 0;
    public $destino       = 0;
    public $estado        = '0';
    public $reporteType   = 0;
    public $dateFrom      = '';
    public $dateTo        = '';

    public function render()
    {
        $sucursales = Sucursales::where('id', '!=', 12)->orderBy('nombre')->get();
        $estados    = Solicitudes::select('estado')->distinct()->whereNotNull('estado')->orderBy('estado')->pluck('estado');

        return view('livewire.reports.report-solicitudes', compact('sucursales', 'estados'))
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
