<?php



namespace App\Http\Controllers;



use App\Models\dte;

use App\Models\Empresas;

use App\Models\resumenDte;

use App\Models\SujetoExcluido;

use App\Models\SujetoExcluidosDetalles;

use App\Models\Sucursales;

use App\Models\Ventas;

use App\Models\VentasDetalles;

use Barryvdh\DomPDF\Facade\Pdf;

use Dompdf\Dompdf;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Traits\HasLogoBase64;



class ExportDteController extends Controller

{



    use HasLogoBase64;



    /**
     * Base publica del sistema. Sale de APP_URL para que el QR no quede atado
     * al dominio en el que se escribio el codigo.
     */
    private function appUrl(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * QR oficial de Hacienda para la consulta publica del DTE. Va a la par del
     * QR propio, que abre la representacion grafica de este sistema.
     */
    private function qrHacienda(dte $dte): string
    {
        // El codigo del CAT-001 sale del catalogo, no de un mapeo adivinado:
        // 00 = pruebas, 01 = produccion.
        $ambiente = optional($dte->ambiente()->first())->codigo ?: '01';

        return rtrim((string) config('dte.consulta_publica_url'), '/')
            . '?ambiente=' . $ambiente
            . '&codGen=' . $dte->codigoGeneracion
            . '&fechaEmi=' . \Carbon\Carbon::parse($dte->fecEmi)->format('Y-m-d');
    }

    /**
     * Receptor y detalle del documento, resolviendo el caso de Sujeto Excluido
     * (tipoDte 10), que no vive en ventas sino en sujeto_excluidos.
     *
     * Los left join territoriales y c.registro / c.desActividad faltaban en el
     * select: la vista ya los pedia y salian siempre vacios.
     */
    private function cargarReceptor(dte $dte): array
    {
        if ((int) $dte->tipoDte === 10) {
            $venta = SujetoExcluido::join('clientes as c', 'c.id', 'sujeto_excluidos.cliente')
                ->join('identificacion_receptors as ir', 'ir.id', 'c.idenReceptor')
                ->leftJoin('departamentos as dep', 'dep.id', 'c.departamento')
                ->leftJoin('municipios as mun', 'mun.id', 'c.municipio')
                ->leftJoin('distritos as dis', 'dis.id', 'c.distrito')
                // Si el cliente tiene distrito pero no municipio/departamento, se toman del distrito
                ->leftJoin('municipios as mun2', 'mun2.id', 'dis.municipio')
                ->leftJoin('departamentos as dep2', 'dep2.id', 'mun2.departamento')
                ->select(
                    'sujeto_excluidos.*',
                    'c.nombreCliente',
                    'ir.valor as identificacion',
                    'c.dui',
                    'c.nit',
                    'c.email',
                    'c.direccion',
                    'c.registro',
                    'c.desActividad',
                    DB::raw('COALESCE(dep.departamento, dep2.departamento) as departamentoNombre'),
                    DB::raw('COALESCE(mun.municipio, mun2.municipio) as municipioNombre'),
                    'dis.distrito as distritoNombre'
                )
                ->find($dte->venta);

            $detalleVentas = SujetoExcluidosDetalles::join('productos as p', 'p.id', 'sujeto_excluidos_detalles.producto')
                ->where('sujeto_excluido_id', $dte->venta)
                ->select(
                    'sujeto_excluidos_detalles.cantidad',
                    'sujeto_excluidos_detalles.unidad',
                    'sujeto_excluidos_detalles.precio_unitario as precio',
                    'sujeto_excluidos_detalles.descuento',
                    'sujeto_excluidos_detalles.ventas as total',
                    'p.nombreProducto',
                    'p.codebar3'
                )
                ->get();

            return [$venta, $detalleVentas];
        }

        $venta = Ventas::join('clientes as c', 'c.id', 'ventas.cliente')
            ->join('identificacion_receptors as ir', 'ir.id', 'c.idenReceptor')
            ->leftJoin('departamentos as dep', 'dep.id', 'c.departamento')
            ->leftJoin('municipios as mun', 'mun.id', 'c.municipio')
            ->leftJoin('distritos as dis', 'dis.id', 'c.distrito')
            // Si el cliente tiene distrito pero no municipio/departamento, se toman del distrito
            ->leftJoin('municipios as mun2', 'mun2.id', 'dis.municipio')
            ->leftJoin('departamentos as dep2', 'dep2.id', 'mun2.departamento')
            ->select(
                'ventas.*',
                'c.nombreCliente',
                'ir.valor as identificacion',
                'c.dui',
                'c.nit',
                'c.email',
                'c.direccion',
                'c.registro',
                'c.desActividad',
                DB::raw('COALESCE(dep.departamento, dep2.departamento) as departamentoNombre'),
                DB::raw('COALESCE(mun.municipio, mun2.municipio) as municipioNombre'),
                'dis.distrito as distritoNombre'
            )
            ->find($dte->venta);

        $detalleVentas = VentasDetalles::join('productos as p', 'p.id', 'ventas_detalles.producto')
            ->where('venta', $dte->venta)
            ->select('ventas_detalles.*', 'p.nombreProducto', 'p.codebar3')
            ->get();

        return [$venta, $detalleVentas];
    }

    /**
     * Documento relacionado que se imprime en la Nota de Credito.
     */
    private function documentoRelacionado(dte $dte): array
    {
        $dte2 = (int) $dte->tipoDte === 4
            ? dte::where('venta', (int) $dte->documentoRelacionado)->first()
            : dte::find($dte->id);

        // dtes.tipoDte es columna y relacion a la vez: hay que pedir la
        // relacion explicitamente o Eloquent devuelve el entero.
        return [$dte2, $dte2 ? $dte2->tipoDte()->first() : null];
    }

    public function reportPDF($id)

    {

        // Acceso público para clientes mediante QR/enlace, pero solo por UUID
        if (!Str::isUuid($id)) {
            abort(403, 'Identificador no válido.');
        }

        $dte = dte::join('modelo_facturacions as mf', 'mf.id', 'dtes.tipoModelo')
            ->join('tipo_transmisions as tt', 'tt.id', 'dtes.tipoOperacion')
            ->select('dtes.*', 'mf.valor as modelo', 'tt.valor as tipo')
            ->where('dtes.codigoGeneracion', $id)
            ->first();

        if (!$dte) {
            abort(404, 'DTE no encontrado.');
        }



        $empresa = Empresas::join('actividad_economicas as ae', 'ae.id', 'empresas.actividad')

            ->select('empresas.*', 'ae.valor as actiEco')

            ->find($dte->empresa);

        $sucursal = Sucursales::join('tipo_establecimientos as te', 'te.id', 'sucursales.tipo')

            ->leftJoin('departamentos as dep', 'dep.id', 'sucursales.departamento')

            ->leftJoin('municipios as mun', 'mun.id', 'sucursales.municipio')

            ->leftJoin('distritos as dis', 'dis.id', 'sucursales.distrito')

            // Si la sucursal tiene distrito pero no municipio/departamento, se toman del distrito
            ->leftJoin('municipios as mun2', 'mun2.id', 'dis.municipio')

            ->leftJoin('departamentos as dep2', 'dep2.id', 'mun2.departamento')

            ->select(
                'sucursales.*',
                'te.valor as establecimiento',
                DB::raw('COALESCE(dep.departamento, dep2.departamento) as departamentoNombre'),
                DB::raw('COALESCE(mun.municipio, mun2.municipio) as municipioNombre'),
                'dis.distrito as distritoNombre'
            )

            ->find($dte->sucursal);

        [$venta, $detalleVentas] = $this->cargarReceptor($dte);

        [$dte2, $tipoRelacionado] = $this->documentoRelacionado($dte);



        $resumen = resumenDte::join('condicion_operacions as co', 'co.id', 'resumen_dtes.condicionOperacion')

            ->select('resumen_dtes.*', 'co.valor as condicion')

            ->where('dte', $dte->id)

            ->first();



        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = new Dompdf();

        $totalPages = $pdf->getCanvas()->get_page_count();



        $qrCodeText = $this->appUrl("report/pdf/" . $dte->codigoGeneracion); // Puedes cambiar esto según tu lógica

        $qrCode = QrCode::size(300)->generate($qrCodeText);



        // Obtener la imagen del código QR en base64

        $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($qrCode);

        // QR oficial de consulta publica de Hacienda, exigido por la 2.0.
        $qrHaciendaDataUri = 'data:image/png;base64,' . base64_encode(
            QrCode::size(300)->generate($this->qrHacienda($dte))
        );





        // Cargar vista y pasar datos

        $viewData = [

            'dte' => $dte,
            'dte2' => $dte2,
            'tipoRelacionado' => $tipoRelacionado,

            'empresa' => $empresa,

            'imagenUrl' => $imagenUrl,

            'sucursal' => $sucursal,

            'venta' => $venta,

            'detalleVentas' => $detalleVentas,

            'resumen' => $resumen,

            'pageNumber' => $totalPages,

            'qrCode' => $qrCodeDataUri,
            'qrHacienda' => $qrHaciendaDataUri,
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

        $dte = dte::join('modelo_facturacions as mf', 'mf.id', 'dtes.tipoModelo')->join('tipo_transmisions as tt', 'tt.id', 'dtes.tipoOperacion')->select('dtes.*', 'mf.valor as modelo', 'tt.valor as tipo')->where('dtes.codigoGeneracion', $id)->first();

        abort_if(!$dte, 404, 'DTE no encontrado.');

        $empresa = Empresas::join('actividad_economicas as ae', 'ae.id', 'empresas.actividad')

            ->select('empresas.*', 'ae.valor as actiEco')

            ->find($dte->empresa);

        $sucursal = Sucursales::join('tipo_establecimientos as te', 'te.id', 'sucursales.tipo')

            ->leftJoin('departamentos as dep', 'dep.id', 'sucursales.departamento')

            ->leftJoin('municipios as mun', 'mun.id', 'sucursales.municipio')

            ->leftJoin('distritos as dis', 'dis.id', 'sucursales.distrito')

            // Si la sucursal tiene distrito pero no municipio/departamento, se toman del distrito
            ->leftJoin('municipios as mun2', 'mun2.id', 'dis.municipio')

            ->leftJoin('departamentos as dep2', 'dep2.id', 'mun2.departamento')

            ->select(
                'sucursales.*',
                'te.valor as establecimiento',
                DB::raw('COALESCE(dep.departamento, dep2.departamento) as departamentoNombre'),
                DB::raw('COALESCE(mun.municipio, mun2.municipio) as municipioNombre'),
                'dis.distrito as distritoNombre'
            )

            ->find($dte->sucursal);

        [$venta, $detalleVentas] = $this->cargarReceptor($dte);

        [$dte2, $tipoRelacionado] = $this->documentoRelacionado($dte);



        $resumen = resumenDte::join('condicion_operacions as co', 'co.id', 'resumen_dtes.condicionOperacion')

            ->select('resumen_dtes.*', 'co.valor as condicion')

            ->where('dte', $dte->id)

            ->first();



        $imagenUrl = $this->logoBase64($empresa->image);



        $pdf = new Dompdf();

        $totalPages = $pdf->getCanvas()->get_page_count();


        $qrCodeText = $this->appUrl("report/pdf/" . $dte->codigoGeneracion); // Puedes cambiar esto según tu lógica

        $qrCode = QrCode::size(300)->generate($qrCodeText);



        // Obtener la imagen del código QR en base64

        $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($qrCode);

        // QR oficial de consulta publica de Hacienda, exigido por la 2.0.
        $qrHaciendaDataUri = 'data:image/png;base64,' . base64_encode(
            QrCode::size(300)->generate($this->qrHacienda($dte))
        );





        // Cargar vista y pasar datos

        $viewData = [

            'dte' => $dte,
            'dte2' => $dte2,
            'tipoRelacionado' => $tipoRelacionado,

            'empresa' => $empresa,

            'imagenUrl' => $imagenUrl,

            'sucursal' => $sucursal,

            'venta' => $venta,

            'detalleVentas' => $detalleVentas,

            'resumen' => $resumen,

            'pageNumber' => $totalPages,

            'qrCode' => $qrCodeDataUri,
            'qrHacienda' => $qrHaciendaDataUri,
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
