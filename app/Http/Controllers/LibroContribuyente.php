<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LibroContribuyente extends Controller
{
    /**
     * PDF del libro de ventas a contribuyentes. Usa la MISMA consulta que la
     * pantalla (LibrosIvaService::ventasContribuyentes): solo creditos
     * fiscales DTE finalizados del ambiente de la empresa.
     */
    public function generarPDF($empresa, $sucursal, $caja, $desde, $hasta)
    {
        $servicio = app(LibrosIvaService::class);
        abort_unless($servicio->puedeVerEmpresa(Auth::user(), $empresa), 403);

        $data = $servicio->ventasContribuyentes($empresa, $sucursal, $caja, $desde, $hasta);
        $totales = $servicio->totales($data, ['exentas', 'no_sujetas', 'gravada', 'debito', 'total', 'retencion']);

        $empresa = Empresas::findOrFail($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);
        $sucursales = Sucursales::where('empresa', $empresa->id)->find($sucursal);

        $pdf = Pdf::loadView('pdf.libroContribuyente', compact('data', 'totales', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream('LibroContribuyente.pdf');
    }
}
