<?php

namespace App\Http\Livewire;

use App\Models\Ajustes;
use App\Models\Sucursales;
use Livewire\Component;

class ReportAjustesController extends Component
{
    public $pageTitle     = 'Reporte de Ajustes';
    public $componentName = 'Reporte de Ajustes';
    public $sucursal      = 0;
    public $tipo          = '0';
    public $reporteType   = 0;
    public $dateFrom      = '';
    public $dateTo        = '';

    public function render()
    {
        $sucursales = Sucursales::where('id', '!=', 12)->orderBy('nombre')->get();
        $tipos      = Ajustes::select('tipo')->distinct()->whereNotNull('tipo')->orderBy('tipo')->pluck('tipo');

        return view('livewire.reports.report-ajustes', compact('sucursales', 'tipos'))
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
