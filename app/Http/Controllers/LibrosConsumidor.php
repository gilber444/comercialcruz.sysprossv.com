<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\Ventas;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibrosConsumidor extends Controller
{ 
    public function generarPDF($empresa, $sucursal, $caja, $desde, $hasta)
    {

        $data = Ventas::select(
            'fecha',
            'empresa',
            'sucursal',
            'tipo',
            DB::raw('MIN(correlativo) as primer_correlativo'),
            DB::raw('MAX(correlativo) as ultimo_correlativo'),
            DB::raw('MIN(codigo) as primer_codigo'),
            DB::raw('MAX(codigo) as ultimo_codigo'),
            DB::raw('MIN(numero) as primer_numero'),
            DB::raw('MAX(numero) as ultimo_numero'),
            DB::raw('SUM(total) as ventaGravada'),
        )
            ->with([
                'Rsucursal:id,nombre',
                'Rcaja:id,caja,tresulucion,conresolucion,tserie,conserie',
                'Rfacturadors:id,facturador',
            ])
            ->where('empresa', $empresa)
            ->when($sucursal != '0', function ($query) use ($sucursal) {
                return $query->where('sucursal', $sucursal);
            })
            ->when($caja != '0', function ($query) use ($caja) {
                return $query->where('caja', $caja);
            })
            ->whereIn('facturador', [1, 2])
            ->whereNotNull('sello')         //asegura que no sea NULL
            ->where('sello', '!=', '')      //asegura que no esté vacío
            ->whereIn('estado', ['Cancelado', 'Credito'])
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('dtes')                         // ajusta si tu tabla se llama distinto
                ->whereColumn('dtes.venta', 'ventas.id')
                ->where('dtes.estado', 'Procesado');
            })
            ->groupBy('fecha', 'empresa', 'sucursal', 'tipo')
            ->orderBy('fecha', 'asc')
            ->get();

        $totalVentas = $data->sum('ventaGravada');

        $empresa = Empresas::find($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $sucursales = Sucursales::find($sucursal);

        $pdf = PDF::loadView('pdf.libroConsumidor', compact('data', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta', 'totalVentas')) ->setPaper('letter', 'landscape');

        return $pdf->stream('LibroConsumidor.pdf');
    }
}
