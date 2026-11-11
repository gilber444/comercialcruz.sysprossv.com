<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LibrosConsumidor extends Controller
{
    /**
     * PDF del libro de ventas a consumidor final. Usa la MISMA consulta que la
     * pantalla (LibrosIvaService::ventasConsumidor): solo DTE finalizados del
     * ambiente de la empresa, agrupados por dia y caja.
     */
    public function generarPDF($empresa, $sucursal, $caja, $desde, $hasta)
    {
        $servicio = app(LibrosIvaService::class);
        abort_unless($servicio->puedeVerEmpresa(Auth::user(), $empresa), 403);

        $data = $servicio->ventasConsumidor($empresa, $sucursal, $caja, $desde, $hasta);
        $totales = $servicio->totales($data, ['exentas', 'no_sujetas', 'gravadas', 'total']);

        $empresa = Empresas::findOrFail($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);
        $sucursales = Sucursales::where('empresa', $empresa->id)->find($sucursal);

        $pdf = Pdf::loadView('pdf.libroConsumidor', compact('data', 'totales', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream('LibroConsumidor.pdf');
    }
}
