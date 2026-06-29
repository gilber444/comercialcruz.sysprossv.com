<?php



namespace App\Http\Controllers;



use App\Exports\ReportInventarioCategoriaExport;

use App\Exports\ReportInventzrioExport;

use App\Models\Categorias;

use App\Models\Empresas;

use App\Models\Inventarios;

use App\Models\Sucursales;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

use App\Traits\HasLogoBase64;



class ExportInventarioController extends Controller

{



    use HasLogoBase64;



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


}
