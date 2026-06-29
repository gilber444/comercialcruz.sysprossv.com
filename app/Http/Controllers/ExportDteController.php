<?php



namespace App\Http\Controllers;



use App\Models\dte;

use App\Models\Empresas;

use App\Models\resumenDte;

use App\Models\Sucursales;

use App\Models\Ventas;

use App\Models\VentasDetalles;

use Barryvdh\DomPDF\Facade\Pdf;

use Dompdf\Dompdf;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Traits\HasLogoBase64;



class ExportDteController extends Controller

{



    use HasLogoBase64;



    public function reportPDF($id)

    {

        // Solo se permite acceso por UUID (codigoGeneracion); rechazar IDs secuenciales
        if (!Str::isUuid($id)) {
            abort(403, 'Identificador no válido.');
        }

        $user = Auth::user();
        if (!$user) {
            abort(401, 'No autenticado.');
        }

        $canViewAll = $user->profile === 'Super'
            || $user->profile === 'Administrador'
            || $user->can('DTE_ViewAll');

        $dte = dte::join('modelo_facturacions as mf', 'mf.id', 'dtes.tipoModelo')
            ->join('tipo_transmisions as tt', 'tt.id', 'dtes.tipoOperacion')
            ->select('dtes.*', 'mf.valor as modelo', 'tt.valor as tipo')
            ->where('dtes.codigoGeneracion', $id)
            ->first();

        if (!$dte) {
            abort(404, 'DTE no encontrado.');
        }

        // Verificar propiedad: Super/Admin/DTE_ViewAll pueden ver cualquiera; el resto solo de su empresa/sucursal
        if (!$canViewAll) {
            if ((int) $dte->empresa !== (int) $user->empresa || (int) $dte->sucursal !== (int) $user->sucursal) {
                abort(403, 'No tiene permiso para ver este DTE.');
            }
        }



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


}
