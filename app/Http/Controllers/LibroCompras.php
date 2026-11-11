<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Sucursales;
use App\Services\LibrosIvaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LibroCompras extends Controller
{
    /**
     * PDF del libro de compras, respaldo del Anexo 3 del F07. Usa la MISMA
     * consulta que la pantalla (LibrosIvaService::compras). Aqui no se filtra
     * por dtes: la compra la registra el usuario y no siempre viene de un DTE
     * del proveedor; si se respeta el aislamiento por empresa.
     */
    public function generarPDF($empresa, $sucursal, $desde, $hasta)
    {
        $servicio = app(LibrosIvaService::class);
        abort_unless($servicio->puedeVerEmpresa(Auth::user(), $empresa), 403);

        $data = $servicio->compras($empresa, $sucursal, $desde, $hasta);
        $campos = ['compras_exentas', 'compras_gravadas', 'credito_fiscal', 'total_compra'];
        $totales = $servicio->totales($data, $campos);
        $totalesAnexo = $servicio->totales($data->where('en_anexo', true), $campos);

        $empresa = Empresas::findOrFail($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);
        $sucursales = Sucursales::where('empresa', $empresa->id)->find($sucursal);

        $pdf = Pdf::loadView('pdf.libroCompras', compact('data', 'totales', 'totalesAnexo', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream('LibroCompras.pdf');
    }
}
