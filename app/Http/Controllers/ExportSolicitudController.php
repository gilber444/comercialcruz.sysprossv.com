<?php



namespace App\Http\Controllers;



use App\Models\Empresas;

use App\Models\Solicitudes;

use App\Models\SolicitudesDetalles;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;

use App\Traits\HasLogoBase64;



class ExportSolicitudController extends Controller

{



    use HasLogoBase64;



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


}
