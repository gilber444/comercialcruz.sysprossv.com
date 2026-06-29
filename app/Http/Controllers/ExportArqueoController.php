<?php



namespace App\Http\Controllers;



use App\Exports\ReportArqueosExport;

use App\Models\Arqueos;

use App\Models\Empresas;

use App\Models\Parametros;

use App\Models\Sucursales;

use App\Models\User;

use Barryvdh\DomPDF\Facade\Pdf;

use Maatwebsite\Excel\Facades\Excel;

use App\Traits\HasLogoBase64;



class ExportArqueoController extends Controller

{



    use HasLogoBase64;



    public function reportPDFArqueos($sucursal, $caja, $user, $dateTo, $dateFrom)

    {

        $data = [];



        $query = Arqueos::join('parametros as p', 'p.id', 'arqueos.caja')->join('users as u', 'u.id', 'arqueos.cajero')->select('arqueos.id', 'arqueos.numero', 'arqueos.fecha', 'arqueos.hora', 'p.caja', 'u.name as cajero', 'arqueos.totalGlobal', 'arqueos.totalEfectivo', 'arqueos.remesas', 'arqueos.diferencia', 'arqueos.efectivo', 'arqueos.tarjeta', 'arqueos.subtotalPagos as totalVentas');



        if ($sucursal != 0) {

            $query->where('arqueos.sucursal', $sucursal);

        }



        if ($caja != 0) {

            $query->where('p.id', $caja);

        }



        if ($user != 0) {

            $query->where('arqueos.cajero', $user);

        }



        if (!empty($dateTo) && !empty($dateFrom)) {

            $query->whereBetween('arqueos.fecha', [$dateTo, $dateFrom]);

        }



        $data = $query->get();



        $sucursales = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)->nombre;



        $cajas = $caja == 0 ? 'Todas las Cajas' : Parametros::find($caja)->caja;



        $cajeros = $user == 0 ? 'Todos los Cajeros' : User::find($user)->name;



        $empresa = Empresas::find(session('empresa'));



        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = PDF::loadView('pdf.pdfArqueos', compact('data', 'sucursales', 'cajas', 'cajeros', 'empresa'));



        $pdf = PDF::loadView('pdf.pdfArqueos', compact('data', 'sucursales', 'cajas', 'cajeros', 'empresa', 'imagenUrl'));



        // Configurar el tamaño y orientación

        $pdf->setPaper('letter', 'landscape'); // Oficio y horizontal



        return $pdf->stream('ReporteArqueos.pdf');

    }


    public function reportExcelArqueos($sucursal, $caja, $user, $dateTo, $dateFrom)

    {

        $reportName = 'Reporte_de_Arqueos_' . uniqid() . '.xlsx';

        return Excel::download(new ReportArqueosExport($sucursal, $caja, $user, $dateTo, $dateFrom), $reportName);

    }


}
