<?php



namespace App\Http\Controllers;



use App\Models\Cotizaciones;

use App\Models\CotizacionesDetalle;

use App\Models\Empresas;

use App\Models\Sucursales;

use Barryvdh\DomPDF\Facade\Pdf;

use Dompdf\Dompdf;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Traits\HasLogoBase64;



class ExportCotizacionController extends Controller

{



    use HasLogoBase64;



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


}
