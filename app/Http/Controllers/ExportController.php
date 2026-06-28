<?php



namespace App\Http\Controllers;



use App\Exports\ReportArqueosExport;

use App\Exports\ReportComprasExport;

use App\Exports\ReportCortesZExport;

use App\Exports\ReportInventarioCategoriaExport;

use App\Exports\ReportInventzrioExport;

use App\Exports\ReportUtilidadExport;

use App\Exports\ReportUtilidadSinExport;

use App\Exports\ReportVentasExport;

use App\Exports\ReportVentasSintetizadoExport;

use App\Models\Arqueos;

use App\Models\Categorias;

use App\Models\Compras;

use App\Models\ComprasDetalles;

use App\Models\Cortes;

use App\Models\Cotizaciones;

use App\Models\CotizacionesDetalle;

use App\Models\dte;

use App\Models\Empresas;

use App\Models\Facturadores;

use App\Models\Inventarios;

use App\Models\Parametros;

use App\Models\Proveedores;

use App\Models\resumenDte;

use App\Models\Solicitudes;

use App\Models\SolicitudesDetalles;

use App\Models\Sucursales;

use App\Models\User;

use App\Models\Ventas;

use App\Models\VentasDetalles;

use Barryvdh\DomPDF\Facade\Pdf;

use Carbon\Carbon;

use Dompdf\Dompdf;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Symfony\Component\HttpFoundation\ParameterBag;



class ExportController extends Controller

{



    public function reportPDF($id)

    {

        $dte = dte::join('modelo_facturacions as mf', 'mf.id', 'dtes.tipoModelo')->join('tipo_transmisions as tt', 'tt.id', 'dtes.tipoOperacion')->select('dtes.*', 'mf.valor as modelo', 'tt.valor as tipo')->where('dtes.codigoGeneracion', $id)->orWhere('dtes.id', $id)->first();



        $empresa = Empresas::join('actividad_economicas as ae', 'ae.id', 'empresas.actividad')

            ->select('empresas.*', 'ae.valor as actiEco')

            ->find($dte->empresa);

        $sucursal = Sucursales::join('tipo_establecimientos as te', 'te.id', 'sucursales.tipo')

            ->select('sucursales.*', 'te.valor as establecimiento')

            ->find($dte->sucursal);

        $venta = Ventas::join('clientes as c', 'c.id', 'ventas.cliente')

            ->join('identificacion_receptors as ir', 'ir.id', 'c.idenReceptor')

            ->select('ventas.id', 'c.nombreCliente', 'ir.valor as identificacion', 'c.dui', 'c.nit', 'c.email', 'c.direccion')

            ->find($dte->venta);



        $detalleVentas = VentasDetalles::join('productos as p', 'p.id', 'ventas_detalles.producto')

            ->where('venta', $dte->venta)

            ->select('ventas_detalles.*', 'p.nombreProducto', 'p.codebar3')

            ->get();



        $resumen = resumenDte::join('condicion_operacions as co', 'co.id', 'resumen_dtes.condicionOperacion')

            ->select('resumen_dtes.*', 'co.valor as condicion')

            ->where('dte', $dte->id)

            ->first();



        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = new Dompdf();

        $totalPages = $pdf->getCanvas()->get_page_count();



        $qrCodeText = "https://comercialcruz.sysprossv.com/report/pdf/" . $dte->codigoGeneracion; // Puedes cambiar esto según tu lógica

        $qrCode = QrCode::size(300)->generate($qrCodeText);



        // Obtener la imagen del código QR en base64

        $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($qrCode);





        // Cargar vista y pasar datos

        $viewData = [

            'dte' => $dte,

            'empresa' => $empresa,

            'imagenUrl' => $imagenUrl,

            'sucursal' => $sucursal,

            'venta' => $venta,

            'detalleVentas' => $detalleVentas,

            'resumen' => $resumen,

            'pageNumber' => $totalPages,

            'qrCode' => $qrCodeDataUri,
            'mostrarMarcaAgua' => $dte->estado === 'INVALIDADO',

        ];



        $pdf = PDF::loadView('pdf.dteFactura', $viewData);

        $pdf->setPaper('letter', 'portrait');

        //$pdf->setOptions(['isPhpEnabled' => true]); // Permitir el uso de PHP en las opciones

        // Obtener el número total de páginas

        // Renderizar el PDF

        $pdf->render();



        $totalPages = $pdf->get_canvas()->get_page_count();



        // Agregar número de página en cada página

        /*for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {

            // Renderizar el PDF nuevamente con el número de página actual

            $pdf->render();



            // Agregar el número de página al PDF

            $pdf->getCanvas()->page_text(15, 765, "Comercial Idalia | Versión " . $dte->version, null, 8, array(0, 0, 0));

            $pdf->getCanvas()->page_text(550, 765, "Página $pageNumber de $totalPages", null, 8, array(0, 0, 0));



            // Si no es la última página, agregar una página en blanco

            if ($pageNumber != $totalPages) {

                $pdf->addPage();

            }

        }*/



        return response($pdf->output(), 200, [

            'Content-Type' => 'application/pdf',

            'Content-Disposition' => "inline; filename=\"{$dte->numeroControl}.pdf\"",

        ]);

    }



    public function generarPDF($id)

    {

        $dte = dte::join('modelo_facturacions as mf', 'mf.id', 'dtes.tipoModelo')->join('tipo_transmisions as tt', 'tt.id', 'dtes.tipoOperacion')->select('dtes.*', 'mf.valor as modelo', 'tt.valor as tipo')->where('dtes.codigoGeneracion', $id)->orWhere('dtes.id', $id)->first();

        $empresa = Empresas::join('actividad_economicas as ae', 'ae.id', 'empresas.actividad')

            ->select('empresas.*', 'ae.valor as actiEco')

            ->find($dte->empresa);

        $sucursal = Sucursales::join('tipo_establecimientos as te', 'te.id', 'sucursales.tipo')

            ->select('sucursales.*', 'te.valor as establecimiento')

            ->find($dte->sucursal);

        $venta = Ventas::join('clientes as c', 'c.id', 'ventas.cliente')

            ->join('identificacion_receptors as ir', 'ir.id', 'c.idenReceptor')

            ->select('ventas.id', 'c.nombreCliente', 'ir.valor as identificacion', 'c.dui', 'c.nit', 'c.email', 'c.direccion')

            ->find($dte->venta);



        $detalleVentas = VentasDetalles::join('productos as p', 'p.id', 'ventas_detalles.producto')

            ->where('venta', $dte->venta)

            ->select('ventas_detalles.*', 'p.nombreProducto', 'p.codebar3')

            ->get();



        $resumen = resumenDte::join('condicion_operacions as co', 'co.id', 'resumen_dtes.condicionOperacion')

            ->select('resumen_dtes.*', 'co.valor as condicion')

            ->where('dte', $dte->id)

            ->first();



        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = new Dompdf();

        $totalPages = $pdf->getCanvas()->get_page_count();


        $qrCodeText = "https://comercialcruz.sysprossv.com/report/pdf/" . $dte->codigoGeneracion; // Puedes cambiar esto según tu lógica

        $qrCode = QrCode::size(300)->generate($qrCodeText);



        // Obtener la imagen del código QR en base64

        $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($qrCode);





        // Cargar vista y pasar datos

        $viewData = [

            'dte' => $dte,

            'empresa' => $empresa,

            'imagenUrl' => $imagenUrl,

            'sucursal' => $sucursal,

            'venta' => $venta,

            'detalleVentas' => $detalleVentas,

            'resumen' => $resumen,

            'pageNumber' => $totalPages,

            'qrCode' => $qrCodeDataUri,
            'mostrarMarcaAgua' => $dte->estado === 'INVALIDADO',

        ];



        $pdf = PDF::loadView('pdf.dteFactura', $viewData);

        $pdf->setPaper('letter', 'portrait');

        //$pdf->setOptions(['isPhpEnabled' => true]); // Permitir el uso de PHP en las opciones

        // Obtener el número total de páginas

        // Renderizar el PDF

        $pdf->render();



        $totalPages = $pdf->get_canvas()->get_page_count();



        // Agregar número de página en cada página

        /*for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {

            // Renderizar el PDF nuevamente con el número de página actual

            $pdf->render();



            // Agregar el número de página al PDF

            $pdf->getCanvas()->page_text(15, 765, "Comercial Idalia | Versión " . $dte->version, null, 8, array(0, 0, 0));

            $pdf->getCanvas()->page_text(550, 765, "Página $pageNumber de $totalPages", null, 8, array(0, 0, 0));



            // Si no es la última página, agregar una página en blanco

            if ($pageNumber != $totalPages) {

                $pdf->addPage();

            }

        }*/



        return $pdf->output();

    }



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

    public function reportPDFInventario($sucursal)

    {

        $query = Inventarios::with([

            'Rproductos.Rcategoria',

            'Rproductos.Rmedidas',

            'Rproductos.precioBase',

            'Rsucursales'

        ])->where('existencia', '>', 0);



        // Filtro por sucursal si se envía un ID distinto de cero

        if ($sucursal != 0) {

            $query->where('sucursal', $sucursal);

        }



        $inventarios = $query->get();



        $data = $inventarios->groupBy(function ($item) {

            return $item->Rproductos->id . '-' . $item->Rsucursales->id;

        })->map(function ($items) {

            $first = $items->first();

            $precio = optional($first->Rproductos->precioBase);



            return [

                'codebar3'       => $first->Rproductos->codebar3,

                'nombreProducto' => $first->Rproductos->nombreProducto,

                'categoria'      => $first->Rproductos->Rcategoria->categoria ?? '',

                'medida'         => $first->Rproductos->Rmedidas->unidad ?? '',

                'sucursal'       => $first->Rsucursales->nombre ?? '',

                'costociva'      => $precio->costociva ?? 0,

                'existencia'     => $items->sum('existencia'),

                'total_costo'    => $items->sum(fn($i) => $i->existencia * ($precio->costociva ?? 0)),

            ];

        })->values();



        $totales = $data->sum('total_costo');

        $sucursales = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)->nombre;



        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = PDF::loadView('pdf.pdfInventario', compact('data', 'sucursales', 'empresa', 'imagenUrl', 'totales'));



        return $pdf->stream('ReporteInventario.pdf');

    }



    public function reportExcelInventario($sucursal)

    {

        $reportName = 'Reporte_de_Inventario_' . uniqid() . '.xlsx';

        return Excel::download(new ReportInventzrioExport($sucursal), $reportName);

    }



    public function reportPDFInventarioCategoria($sucursal, $categoria)

    {

        $data = [];



        if ($sucursal == 0) {

            if ($categoria == 0) {

                $data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')

                    ->join('categorias as c', 'c.id', 'p.categoria')

                    ->join('medidas as m', 'm.id', 'p.medida')

                    ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))

                    ->groupBy('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad')

                    ->get();

            } else {

                $data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')

                    ->join('categorias as c', 'c.id', 'p.categoria')

                    ->join('medidas as m', 'm.id', 'p.medida')

                    ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))

                    ->where('p.categoria', $categoria)

                    ->groupBy('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad')

                    ->get();

            }

        } else {

            if ($categoria == 0) {

                $data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')

                    ->join('categorias as c', 'c.id', 'p.categoria')

                    ->join('medidas as m', 'm.id', 'p.medida')

                    ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))

                    ->where('sucursal', $sucursal)

                    ->groupBy('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad')

                    ->get();

            } else {

                $data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')

                    ->join('categorias as c', 'c.id', 'p.categoria')

                    ->join('medidas as m', 'm.id', 'p.medida')

                    ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))

                    ->where('p.categoria', $categoria)

                    ->where('sucursal', $sucursal)

                    ->groupBy('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad')

                    ->get();

            }

        }



        $sucursales = $sucursal == 0 ? 'Todas las Sucursales' : Sucursales::find($sucursal)->nombre;



        $categorias = $categoria == 0 ? 'Todas las Categorias' : Categorias::find($categoria)->categoria;



        $empresa = Empresas::find(session('empresa'));

        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = PDF::loadView('pdf.pdfInventarioCategoria', compact('data', 'sucursales', 'empresa', 'imagenUrl', 'categorias'));



        return $pdf->stream('ReporteInventarioCategoria.pdf');

    }



    public function reportExcelInventarioCategoria($sucursal, $categoria)

    {

        $reportName = 'Reporte_de_Inventario_Categoria_' . uniqid() . '.xlsx';

        return Excel::download(new ReportInventarioCategoriaExport($sucursal, $categoria), $reportName);

    }



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





    public function reportCotizacion($id)

    {

        $cotizacion = Cotizaciones::with('Rcliente')->find($id);



        $empresa = Empresas::join('actividad_economicas as ae', 'ae.id', 'empresas.actividad')

            ->select('empresas.*', 'ae.valor as actiEco')

            ->find($cotizacion->empresa);

        $sucursal = Sucursales::join('tipo_establecimientos as te', 'te.id', 'sucursales.tipo')

            ->select('sucursales.*', 'te.valor as establecimiento')

            ->find($cotizacion->sucursal);



        $detalleCotizacion = CotizacionesDetalle::join('productos as p', 'p.id', 'cotizaciones_detalles.producto')

            ->where('cotizacion', $cotizacion->id)

            ->select('cotizaciones_detalles.*', 'p.nombreProducto', 'p.codebar3')

            ->get();



        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = new Dompdf();

        $totalPages = $pdf->getCanvas()->get_page_count();



        $qrCodeText = "https://supermercadojosue.sysprossv.com/report/pdf/cotizacion/" . $cotizacion->id; // Puedes cambiar esto según tu lógica

        $qrCode = QrCode::size(300)->generate($qrCodeText);



        // Obtener la imagen del código QR en base64

        $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($qrCode);





        // Cargar vista y pasar datos

        $viewData = [

            'cotizacion' => $cotizacion,

            'empresa' => $empresa,

            'imagenUrl' => $imagenUrl,

            'sucursal' => $sucursal,

            'detalleCotizacion' => $detalleCotizacion,

            'pageNumber' => $totalPages,

            'qrCode' => $qrCodeDataUri

        ];



        $pdf = PDF::loadView('pdf.pdfCotizacion', $viewData);

        $pdf->setPaper('letter', 'portrait');

        $pdf->render();



        $totalPages = $pdf->get_canvas()->get_page_count();



        // Agregar número de página en cada página

        for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {

            $pdf->render();



            // Agregar el número de página al PDF

            $pdf->getCanvas()->page_text(15, 765, $empresa->razon, null, 8, array(0, 0, 0));

            $pdf->getCanvas()->page_text(550, 765, "Página $pageNumber de $totalPages", null, 8, array(0, 0, 0));



            // Si no es la última página, agregar una página en blanco

            if ($pageNumber != $totalPages) {

                //$pdf->addPage();

            }

        }



        return response($pdf->output(), 200, [

            'Content-Type' => 'application/pdf',

            'Content-Disposition' => "inline; filename=\"{$cotizacion->correlativo}.pdf\"",

        ]);

    }



    public function ReportSolicitud($id)

    {

        $user = Auth::user();



        $encabezado = Solicitudes::with(['Rorigen', 'Rdestino'])->find($id);



        $data = SolicitudesDetalles::join('productos as p', 'p.id', '=', 'solicitudes_detalles.producto')

            ->with(['Rproducto', 'Rmedidas'])

            ->where('solicitud', $id)

            ->orderBy('p.nombreProducto', 'asc')

            ->select('solicitudes_detalles.*')

            ->get();





        $totales = $data->sum('total');



        $user = Auth::user();



        $empresa = Empresas::find($user->empresa);

        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = PDF::loadView(

            'livewire.existencias.pdf',

            compact(

                'data',

                'id',

                'encabezado',

                'empresa',

                'imagenUrl',

                'totales'

            )

        );



        return $pdf->stream('ReporteVentas.pdf');

    }



    /**
     * Vista previa HTML del reporte de utilidad detallado.
     */
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



    /**
     * Vista previa HTML del reporte de utilidad sintetizado.
     */
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







    public function reportExcelUtilidadSinExcel($sucursal, $caja, $reportType, $dateFrom = null, $dateTo = null)

    {

        if (!\App\Models\Feature::isEnabled('reporte_utilidad_sintetizado')) {
            abort(403, 'Funcionalidad no disponible.');
        }

        $reportName = 'Reporte_de_Utilidad_Sintetico_' . uniqid() . '.xlsx';

        return Excel::download(new ReportUtilidadSinExport($sucursal, $caja, $reportType, $dateFrom, $dateTo), $reportName);

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



    /**
     * Devuelve el logo de la empresa como data URI base64 para evitar
     * que DomPDF haga peticiones HTTP externas al generar PDFs.
     */
    private function logoBase64($imageName)

    {

        $path = public_path('logo/' . $imageName);

        if (!file_exists($path)) {

            return asset('logo/' . $imageName);

        }



        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mime = match ($ext) {

            'png'  => 'image/png',

            'jpg', 'jpeg' => 'image/jpeg',

            'gif'  => 'image/gif',

            'svg'  => 'image/svg+xml',

            default => 'image/png',

        };



        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));

    }

}
