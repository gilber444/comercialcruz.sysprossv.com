<?php



namespace App\Http\Controllers;



use App\Exports\ReportCortesZExport;

use App\Models\Cortes;

use App\Models\Empresas;

use App\Models\Parametros;

use App\Models\Sucursales;

use Barryvdh\DomPDF\Facade\Pdf;

use Maatwebsite\Excel\Facades\Excel;

use App\Traits\HasLogoBase64;



class ExportCorteZController extends Controller

{



    use HasLogoBase64;



    public function reportPDFCorteZ($sucursal, $caja, $dateTo, $dateFrom)

    {

        $data = [];



        $query = Cortes::join('parametros as p', 'p.id', 'cortes.caja')->select('cortes.id', 'cortes.corte', 'cortes.fecha', 'cortes.hora', 'p.caja', 'cortes.totalGlobal', 'cortes.totalEfectivo', 'cortes.diferencia');



        $query->where('cortes.estado', 'Cerrado');



        if ($sucursal != 0) {

            $query->where('cortes.sucursal', $sucursal);

        }



        if ($caja != 0) {

            $query->where('p.id', $caja);

        }



        if (!empty($dateTo) && !empty($dateFrom)) {

            $query->whereBetween('cortes.fecha', [$dateTo, $dateFrom]);

        }





        $data = $query->get();



        $sucursales = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)->nombre;



        $cajas = $caja == 0 ? 'Todas las Cajas' : Parametros::find($caja)->caja;



        $empresa = Empresas::find(session('empresa'));

        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = PDF::loadView('pdf.pdfCorteZ', compact('data', 'sucursales', 'cajas', 'empresa', 'imagenUrl'));



        return $pdf->stream('ReporteCorteZ.pdf');

    }


    public function reportExcelCorteZ($sucursal, $caja, $dateTo, $dateFrom)

    {



        $reportName = 'Reporte_de_Corte_Z_' . uniqid() . '.xlsx';

        return Excel::download(new ReportCortesZExport($sucursal, $caja, $dateTo, $dateFrom), $reportName);

    }


}
