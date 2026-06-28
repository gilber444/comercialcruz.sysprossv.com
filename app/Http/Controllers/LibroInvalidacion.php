<?php

namespace App\Http\Controllers;

use App\Models\dte;
use App\Models\Empresas;
use App\Models\Sucursales;
use Barryvdh\DomPDF\Facade\Pdf;

class LibroInvalidacion extends Controller
{
    public function generarPDF($empresa, $sucursal, $facturador, $desde, $hasta)
    {
        $data = dte::with('Rventa', 'RtipoDte')
            ->whereBetween('fecEmi', [$desde, $hasta])
            ->when($facturador != 0, function ($q) use ($facturador) {
                $q->where('tipoDte', $facturador);
            })
            ->where('empresa', $empresa)
            ->where('sucursal', $sucursal)
            ->where('estado', 'INVALIDADO')
            ->orderBy('fecEmi', 'asc')
            ->orderBy('horEmi', 'asc')
            ->orderBy('numeroControl', 'asc')
            ->get();

        $empresa = Empresas::find($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $sucursales = Sucursales::find($sucursal);

        $pdf = PDF::loadView('pdf.libroInvalidaciones', compact('data', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta'))->setPaper('letter', 'landscape');

        return $pdf->stream('LibroInvalidaciones.pdf');
    }
}
