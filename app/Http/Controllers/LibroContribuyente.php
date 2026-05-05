<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class LibroContribuyente extends Controller
{
    public function generarPDF($empresa, $sucursal, $caja, $desde, $hasta)
    {

        $data = Ventas::select(
            'fecha',
            'empresa',
            'sucursal',
            'caja',
            'tipo',
            'cliente',
            'numero',
            'sello',
            'codigo',
            'total'
        )
        ->with([
            'Rsucursal:id,nombre',
            'Rcajas:id,caja,tresolucion,conresolucion,tserie,conserie',
            'Rfacturadors:id,facturador',
            'Rclientes:id,nombreCliente,registro,dui,nit',
        ])
        ->where('empresa', $empresa)
        ->when($sucursal != '0', function ($query) use ($sucursal) {
            return $query->where('sucursal', $sucursal);
        })
        ->when($caja != '0', function ($query) use ($caja) {
            return $query->where('caja', $caja);
        })
        ->whereNotNull('sello')         //asegura que no sea NULL
        ->where('sello', '!=', '')      //asegura que no esté vacío
        ->where('facturador', 3)
        ->whereIn('estado', ['Cancelado', 'Credito'])
        ->whereBetween('fecha', [$desde, $hasta])
        ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('dtes')                         // ajusta si tu tabla se llama distinto
                ->whereColumn('dtes.venta', 'ventas.id')
                ->where('dtes.estado', 'Procesado');
            })
        ->orderBy('fecha', 'asc')
        ->get();

        $totalVentas = $data->sum('total');

        $empresa = Empresas::find($empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $sucursales = Sucursales::find($sucursal);

        $pdf = PDF::loadView('pdf.libroContribuyente', compact('data', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta', 'totalVentas')) ->setPaper('letter', 'landscape');

        return $pdf->stream('LibroContribuyente.pdf');
        /*return view('pdf.libroContribuyente', compact(
            'data', 'sucursales', 'empresa', 'imagenUrl', 'desde', 'hasta', 'totalVentas'
        ));*/
    }
}