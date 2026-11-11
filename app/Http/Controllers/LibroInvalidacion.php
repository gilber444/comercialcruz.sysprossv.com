<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LibroInvalidacion extends Controller
{
    /**
     * PDF del libro de invalidaciones. Usa la MISMA consulta que la pantalla
     * (LibrosIvaService::invalidaciones): DTE invalidados con sello y del
     * ambiente de la empresa.
     */
    public function generarPDF($empresa, $sucursal, $facturador, $desde, $hasta)
    {
        $servicio = app(LibrosIvaService::class);
        abort_unless($servicio->puedeVerEmpresa(Auth::user(), $empresa), 403);

        $data = $servicio->invalidaciones($empresa, $sucursal, $facturador, $desde, $hasta);
        $totales = $servicio->totales($data, ['total']);

        $empresa = Empresas::findOrFail($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);
        $sucursales = Sucursales::where('empresa', $empresa->id)->find($sucursal);

        $pdf = Pdf::loadView('pdf.libroInvalidaciones', compact('data', 'totales', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream('LibroInvalidaciones.pdf');
    }
}
