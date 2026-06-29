<?php



namespace App\Http\Controllers;



use App\Exports\ReportComprasExport;

use App\Models\Compras;

use App\Models\Empresas;

use App\Models\Proveedores;

use Barryvdh\DomPDF\Facade\Pdf;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

use App\Traits\HasLogoBase64;



class ExportCompraController extends Controller

{



    use HasLogoBase64;



        public function reportPDFCompras($proveedor, $reportType, $dateFrom = null, $dateTo = null)
    {
        if ($reportType == 0) {
            $from = Carbon::now()->format('Y-m-d');
            $to   = Carbon::now()->format('Y-m-d');
        } else {
            $from = $dateFrom;
            $to   = $dateTo;
        }

        $query = Compras::join('proveedores as p', 'p.id', 'compras.proveedor')
            ->join('tipo_compras as tc', 'tc.id', 'compras.tipo')
            ->select(
                'compras.correlativo', 'compras.serie', 'compras.fecha',
                'tc.tipo as facturadors', 'p.nombre', 'compras.id',
                DB::raw('(SELECT SUM(cd.cantidad) FROM compras_detalles as cd WHERE cd.compra = compras.id) as items'),
                DB::raw('(SELECT SUM(cd.total)/1.13 FROM compras_detalles as cd WHERE cd.compra = compras.id) as subtotal'),
                DB::raw('(SELECT SUM(cd.total) - SUM(cd.total)/1.13 FROM compras_detalles as cd WHERE cd.compra = compras.id) as iva'),
                DB::raw('0 as percepcion'),
                DB::raw('(SELECT SUM(cd.total) FROM compras_detalles as cd WHERE cd.compra = compras.id) as total')
            )
            ->whereBetween('compras.fecha', [$from, $to]);

        if ($proveedor != 0) {
            $query->where('compras.proveedor', $proveedor);
        }

        $data = $query->get();

        $subtotal    = $data->sum('subtotal');
        $iva         = $data->sum('iva');
        $percepcion  = 0;
        $totales     = $data->sum('total');
        $sucursales  = 'Todas';
        $proveedores = $proveedor == 0 ? 'Todos los Proveedores' : optional(Proveedores::find($proveedor))->nombre;

        $user      = Auth::user();
        $empresa   = Empresas::find($user->empresa);
        $imagenUrl = $this->logoBase64($empresa->image);

        $pdf = PDF::loadView('pdf.pdfCompras', compact(
            'data', 'reportType', 'proveedores', 'empresa', 'imagenUrl',
            'totales', 'from', 'to', 'sucursales', 'subtotal', 'iva', 'percepcion'
        ));

        return $pdf->stream('ReporteCompras.pdf');
    }


    public function reportExcelCompras($proveedor, $reportType, $dateFrom = null, $dateTo = null)
    {
        $reportName = 'Reporte_de_Compras_' . uniqid() . '.xlsx';
        return Excel::download(new ReportComprasExport($proveedor, $reportType, $dateFrom, $dateTo), $reportName);
    }


}
