<?php



namespace App\Http\Controllers;



use App\Exports\ReportVentasExport;

use App\Exports\ReportVentasSintetizadoExport;

use App\Models\Empresas;

use App\Models\Facturadores;

use App\Models\Sucursales;

use App\Models\Ventas;

use Barryvdh\DomPDF\Facade\Pdf;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Maatwebsite\Excel\Facades\Excel;

use App\Traits\HasLogoBase64;



class ExportVentaController extends Controller

{



    use HasLogoBase64;



    public function reportPDFVentas($sucursal, $caja, $reportType, $dateFrom = null, $dateTo = null)

    {

        $data = [];



        if ($reportType == 0) {

            $from = Carbon::parse(Carbon::now())->format('Y-m-d');

            $to = Carbon::parse(Carbon::now())->format('Y-m-d');

            $today = date('Y-m-d');

        } else {

            $from = $dateFrom;

            $to = $dateTo;

            $today = date('Y-m-d');

        }





        $query = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')

            ->join('clientes as c', 'c.id', 'ventas.cliente')

            ->join('facturadores as f', 'f.id', 'ventas.facturador')

            ->select('ventas.id', 's.nombre', 'ventas.fecha', 'ventas.hora', 'ventas.numero', 'ventas.codigo', 'ventas.total', 'c.nombreCliente', 'f.facturador as facturadors')

            ->whereBetween('ventas.fecha', [$from, $to])

            ->where('ventas.estado', 'cancelado')

            ->orderBy('ventas.fecha', 'asc');



        if ($sucursal != 0) {

            $query->where('ventas.sucursal', $sucursal);

        }



        if ($caja != 0) {

            $query->where('ventas.caja', $caja);

        }



        $data = $query->get();



        $totales = $query->sum('ventas.total');



        $sucursales = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)->nombre;



        $user = Auth::user();



        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = PDF::loadView('pdf.pdfVentas', compact('data', 'reportType', 'sucursales', 'dateFrom', 'dateTo', 'empresa', 'imagenUrl', 'totales'));



        return $pdf->stream('ReporteVentas.pdf');

    }


    public function reportExcelVentas($sucursal, $caja, $reportType, $dateFrom = null, $dateTo = null)

    {

        $reportName = 'Reporte_de_Ventas_' . uniqid() . '.xlsx';

        return Excel::download(new ReportVentasExport($sucursal, $reportType, $dateFrom, $dateTo), $reportName);

    }


    public function reportPDFVentasSintetizado($sucursal, $type, $facturador, $f1 = null, $f2 = null)

    {

        // FECHAS

        if ($type == 0) {

            $from = Carbon::now()->format('Y-m-d');

            $to = Carbon::now()->format('Y-m-d');

        } else {

            $from = $f1;

            $to = $f2;

        }



        // CONSULTA

        $query = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')

            ->select(

                's.nombre as sucursal',

                \DB::raw('COUNT(ventas.id) as total_ventas'),

                \DB::raw('SUM(ventas.total) as total_monto')

            )

            ->whereBetween('ventas.fecha', [$from, $to])

            ->where('ventas.estado', 'cancelado')

            ->groupBy('s.nombre')

            ->orderBy('s.nombre');



        if ($sucursal != 0) {

            $query->where('ventas.sucursal', $sucursal);

        }



        if ($facturador != 0) {

            $query->where('ventas.facturador', $facturador);

        }



        $data = $query->get();

        $totalSales = $data->sum('total_monto');

        $facturadores = $facturador == 0

            ? 'Todos los Facturadores'

            : Facturadores::find($facturador)->facturador;

        // NOMBRE DE LA SUCURSAL

        $sucursales = $sucursal == 0

            ? 'Todas las Sucursales'

            : Sucursales::find($sucursal)->nombre;



        // EMPRESA Y LOGO

        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);



        // PDF

        return view('pdf.pdfVentasSintetizado', compact(

            'data',

            'from',

            'to',

            'facturadores',

            'sucursales',

            'totalSales',

            'imagenUrl',

            'empresa'

        ));

    }


    public function reportExcelVentasSintetizado($sucursal, $type, $facturador, $f1 = null, $f2 = null)

    {

        $reportName = 'Reporte_de_Ventas_Sintetizado' . uniqid() . '.xlsx';

        return Excel::download(new ReportVentasSintetizadoExport($sucursal, $type, $facturador, $f1, $f2), $reportName);

    }


}
