<?php



namespace App\Http\Controllers;



use App\Exports\ReportUtilidadExport;

use App\Exports\ReportUtilidadSinExport;

use App\Models\Empresas;

use App\Models\Sucursales;

use App\Models\VentasDetalles;

use Barryvdh\DomPDF\Facade\Pdf;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

use App\Traits\HasLogoBase64;



class ExportUtilidadController extends Controller

{



    use HasLogoBase64;



    public function pdfUtilidad($sucur, $caja, $reportType, $facturador, $dateFrom = null, $dateTo = null)

    {

        if (!\App\Models\Feature::isEnabled('reporte_utilidad_detallado')) {
            abort(403, 'Funcionalidad no disponible.');
        }

        // Definir fechas según tipo de reporte

        if ($reportType == 0) {

            $from = Carbon::now()->format('Y-m-d');

            $to = Carbon::now()->format('Y-m-d');

        } else {

            $from = $dateFrom;

            $to = $dateTo;

        }



        // Consulta agrupada por producto, facturador y sucursal

        $query = VentasDetalles::join('ventas', 'ventas.id', 'ventas_detalles.venta')

            ->join('sucursales as s', 's.id', 'ventas.sucursal')

            ->join('facturadores as f', 'f.id', 'ventas.facturador')

            ->join('productos as p', 'p.id', 'ventas_detalles.producto')

            /*->join('precios as pr', function($join) {

                $join->on('pr.producto', '=', 'p.id')

                    ->on('pr.medida', '=', 'ventas_detalles.medida')

                    ->on('pr.presentacion', '=', 'ventas_detalles.unidad')

                    ->whereRaw('pr.costociva = (

                        SELECT MIN(p2.costociva)

                        FROM precios p2

                        WHERE p2.producto = p.id

                        AND p2.medida = ventas_detalles.medida

                        AND p2.presentacion = ventas_detalles.unidad

                    )');

            })*/

            ->whereBetween('ventas.fecha', [$from, $to])

            ->where('ventas.estado', 'Cancelado');



        // Aplicar filtros

        if ($sucur != 0) $query->where('ventas.sucursal', $sucur);

        if ($caja != 0) $query->where('ventas.caja', $caja);

        if ($facturador != 0) $query->where('ventas.facturador', $facturador);



        // Obtener datos agrupados

        $data = $query->select(

            's.nombre as sucursal',

            'f.facturador as facturador',

            'p.nombreProducto',

            'ventas_detalles.cantidad',

            'ventas_detalles.costo',

            DB::raw('(ventas_detalles.cantidad * ventas_detalles.costo) as costo_total'),

            'ventas_detalles.precio as precio',

            'ventas_detalles.total as total_venta',

            'ventas_detalles.utilidad_uni as utilidad_uni',

            'ventas_detalles.utilidad as total_utilidad'

        )

            ->orderBy('s.nombre')

            ->get();



        // Calcular totales generales

        $totalCosto = $data->sum('costo_total');

        $totalSales = $data->sum('total_venta');

        $totalUtilidad = $totalSales - $totalCosto;



        // Información adicional

        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);

        $sucursal = ($sucur == 0)

            ? 'Todas las Sucursales'

            : Sucursales::find($sucur)?->nombre ?? 'Sucursal desconocida';

        $sucurId = $sucur;



        return view('pdf.pdfUtilidad', compact(

            'data',

            'dateFrom',

            'dateTo',

            'sucursal',

            'imagenUrl',

            'empresa',

            'totalCosto',

            'totalSales',

            'totalUtilidad',

            'sucurId',

            'caja',

            'reportType',

            'facturador'

        ));

    }


    public function reportExcelUtilidad($sucursal, $caja, $reportType, $facturador, $dateFrom = null, $dateTo = null)

    {

        if (!\App\Models\Feature::isEnabled('reporte_utilidad_detallado')) {
            abort(403, 'Funcionalidad no disponible.');
        }

        $reportName = 'Reporte_de_Utilidad_' . uniqid() . '.xlsx';

        return Excel::download(new ReportUtilidadExport($sucursal, $caja, $reportType, $facturador, $dateFrom, $dateTo), $reportName);

    }


    public function pdfUtilidadSin($sucursal, $caja, $reportType, $dateFrom = null, $dateTo = null)

    {

        if (!\App\Models\Feature::isEnabled('reporte_utilidad_sintetizado')) {
            abort(403, 'Funcionalidad no disponible.');
        }

        if ($reportType == 0) {

            $dateFrom = now()->format('Y-m-d');

            $dateTo = now()->format('Y-m-d');

        }



        $from = $dateFrom;

        $to = $dateTo;



        $base = VentasDetalles::query()

            ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')

            ->join('sucursales as s', 's.id', '=', 'ventas.sucursal')

            /*->join('precios', function ($join) {

                $join->on('precios.producto', '=', 'ventas_detalles.producto')

                    ->where('precios.cantidad', '=', 1);

            })*/

            ->whereBetween('ventas.fecha', [$from, $to])

            ->where('ventas.estado', 'Cancelado');



        if ($sucursal != 0) {

            $base->where('ventas.sucursal', $sucursal);

        }

        if ($caja != 0) {

            $base->where('ventas.caja', $caja);

        }



        $groupCols = [];

        if ($caja != 0) {

            $groupCols = ['ventas.caja'];

        } elseif ($sucursal != 0) {

            $groupCols = ['ventas.caja'];

        } else {

            $groupCols = ['ventas.sucursal', 'ventas.caja'];

        }



        $data = (clone $base)

            ->selectRaw(

            '

                s.nombre as nombre_sucursal,

                ventas.sucursal,

                ventas.caja,

                SUM(ventas_detalles.costo * ventas_detalles.descargar)   as total_costo,

                SUM(ventas_detalles.total)         as total_venta,

                SUM(ventas_detalles.cantidad)      as total_cantidad

            ',

            )

            ->groupBy('s.nombre', 'ventas.sucursal', 'ventas.caja')

            ->orderBy('s.nombre')

            ->orderBy('ventas.caja')

            ->get();



        // Totales globales desde el agregado

        $totalCosto = $data->sum('total_costo');

        $totalSales = $data->sum('total_venta');

        // utilidad como monto (venta - costo)

        $totalUtilidad = $totalSales - $totalCosto;



        // Información adicional

        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);

        $sucursalId = $sucursal;

        $sucursal = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)?->nombre ?? 'todas las sucursales';



        return view('pdf.pdfUtilidadSin', compact('data', 'dateFrom', 'dateTo', 'sucursal', 'imagenUrl', 'empresa', 'totalCosto', 'totalSales', 'totalUtilidad', 'sucursalId', 'caja', 'reportType'));

    }


    public function generarPdfUtilidadSin($sucursal, $caja, $reportType, $dateFrom = null, $dateTo = null)

    {

        set_time_limit(180);

        ini_set('memory_limit', '512M');

        if (!\App\Models\Feature::isEnabled('reporte_utilidad_sintetizado')) {

            abort(403, 'Funcionalidad no disponible.');

        }

        if ($reportType == 0) {

            $dateFrom = now()->format('Y-m-d');

            $dateTo = now()->format('Y-m-d');

        }



        $from = $dateFrom;

        $to = $dateTo;



        $base = VentasDetalles::query()

            ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')

            ->join('sucursales as s', 's.id', '=', 'ventas.sucursal')

            ->whereBetween('ventas.fecha', [$from, $to])

            ->where('ventas.estado', 'Cancelado');



        if ($sucursal != 0) {

            $base->where('ventas.sucursal', $sucursal);

        }

        if ($caja != 0) {

            $base->where('ventas.caja', $caja);

        }



        $data = (clone $base)

            ->selectRaw(

            '

                s.nombre as nombre_sucursal,

                ventas.sucursal,

                ventas.caja,

                SUM(ventas_detalles.costo * ventas_detalles.descargar)   as total_costo,

                SUM(ventas_detalles.total)         as total_venta,

                SUM(ventas_detalles.cantidad)      as total_cantidad

            ',

            )

            ->groupBy('s.nombre', 'ventas.sucursal', 'ventas.caja')

            ->orderBy('s.nombre')

            ->orderBy('ventas.caja')

            ->get();



        $totalCosto = $data->sum('total_costo');

        $totalSales = $data->sum('total_venta');

        $totalUtilidad = $totalSales - $totalCosto;



        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);

        $sucursal = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)?->nombre ?? 'todas las sucursales';



        $pdf = PDF::loadView('pdf.pdfUtilidadSin', compact('data', 'dateFrom', 'dateTo', 'sucursal', 'imagenUrl', 'empresa', 'totalCosto', 'totalSales', 'totalUtilidad'));

        return $pdf->stream('Reporte_de_Utilidad_Sintetizado.pdf');

    }


    public function reportExcelUtilidadSinExcel($sucursal, $caja, $reportType, $dateFrom = null, $dateTo = null)

    {

        if (!\App\Models\Feature::isEnabled('reporte_utilidad_sintetizado')) {
            abort(403, 'Funcionalidad no disponible.');
        }

        $reportName = 'Reporte_de_Utilidad_Sintetico_' . uniqid() . '.xlsx';

        return Excel::download(new ReportUtilidadSinExport($sucursal, $caja, $reportType, $dateFrom, $dateTo), $reportName);

    }


}
