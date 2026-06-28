<?php

namespace App\Http\Livewire;

use App\Models\Empresas;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Productos;
use App\Models\Sucursales;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class KardexController extends Component
{
    use WithPagination;

    public $fechaDesde = [],
        $fechaHasta = [],
        $search,
        $selected_id,
        $pageTitle,
        $componentName,
        $desde,
        $hasta,
        $producto,
        $kardes = [],
        $inicial = [],
        $sucursal = [],
        $sucursales = [],
        $product,
        $suc,
        $idC,
        $prod,
        $sucur;
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Kardex de Productos';

        $user = Auth::user();

        if (in_array($user->profile, ['Super', 'Administrador', 'Gerente'])) {
            // Muestra todas las sucursales
            $this->sucursales = Sucursales::all();
        } else {
            // Solo la sucursal del usuario
            $this->sucursales = Sucursales::where('id', $user->sucursal)->get();
        }
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $query = Productos::query();

        // Filtro de búsqueda
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nombreProducto', 'like', '%' . $this->search . '%')
                ->orWhere('codebar3', 'like', '%' . $this->search . '%');
            });
        } else {
            $query->orderBy('id', 'asc');
        }

        // Paginación
        $data = $query->paginate($this->pagination);


        return view('livewire.kardex.kardex', ['data' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Generar($id)
    {
        $this->idC = $id;
        $pro = Productos::find($id);
        $this->producto = $pro->id;
        $this->desde = $this->fechaDesde[$id] ?? null;
        $this->hasta = $this->fechaHasta[$id] ?? null;
        $this->sucursal = $this->sucursal[$id] ?? null;
        $this->selected_id = $id;

        $this->product = $pro->nombreProducto;

        // Si la sucursal no es 0, obtener su nombre
        if ($this->sucursal != 0) {
            $s = Sucursales::find($this->sucursal);
            $this->suc = $s->nombre;
            $this->sucur = $s->id;
        } else {
            $this->suc = 'Todas las sucursales';
            $this->sucur = 0;
        }

        $this->prod = $pro->id;
        // Verifica si 'desde' está vacío y asigna la fecha de hace un mes si es necesario
        $this->desde = $this->desde ? Carbon::parse($this->desde)->format('Y-m-d') : Carbon::now()->subMonth()->format('Y-m-d');

        // Verifica si 'hasta' está vacío y asigna la fecha de hoy si es necesario
        $this->hasta = $this->hasta ? Carbon::parse($this->hasta)->format('Y-m-d') : null; // Hoy

        // Obtener el saldo inicial en la(s) sucursal(es) correspondiente(s)
        // $this->inicial = Kardex::where('producto', $pro->id)
        //     ->when($this->sucursal != 0, function ($query) {
        //         $query->whereHas('Rinventario', function ($q) {
        //             $q->where('sucursal', $this->sucursal);
        //         });
        //     })
        //     ->whereDate('fecha', '<', $this->desde)
        //     ->orderByDesc('fecha')
        //     ->limit(1)
        //     ->get();

        //dd($this->desde);

        // Obtener movimientos del Kardex en la(s) sucursal(es) y fechas seleccionadas
        if ($this->hasta != null) {
            //dd('hooo');
            $this->kardes = Kardex::where('producto', $pro->id)
                ->when($this->sucursal != 0, function ($query) {
                    $query->whereHas('Rinventario', function ($q) {
                        $q->where('sucursal', $this->sucursal);
                    });
                })
                ->whereBetween('fecha', [$this->desde, $this->hasta])
                ->orderBy('id', 'asc')
                ->get();
        } else {
            $this->kardes = Kardex::where('producto', $pro->id)
                ->when($this->sucursal != 0, function ($query) {
                    $query->whereHas('Rinventario', function ($q) {
                        $q->where('sucursal', $this->sucursal);
                    });
                })
                ->whereBetween('fecha', [$this->desde, now()])
                ->orderBy('id', 'asc')
                ->get();
        }

        $this->emit('show-modal', 'show modal');
    }

    public function reportPDFKardex($sucur, $prod, $desde = null, $hasta = null)
    {
        $pro = Productos::find($prod);
        $producto = $pro->nombreProducto;

        if ($sucur != 0) {
            $s = Sucursales::find($sucur);
            $sucursal = $s->nombre;
        } else {
            $sucursal = 'Todas las sucursales';
        }

        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $desde = $desde ? Carbon::parse($desde)->format('Y-m-d') : Carbon::now()->subMonth()->format('Y-m-d');

        // Verifica si 'hasta' está vacío y asigna la fecha de hoy si es necesario
        $hasta = $hasta ? Carbon::parse($hasta)->format('Y-m-d') : null; // Hoy

        if (isset($s) && !empty($s->id) && $s->id != 0) {
            // Si $s->id existe y es distinto de 0, se filtra por sucursal
            $kardes = Kardex::where('producto', $prod)
                ->whereHas('Rinventario', function ($q) use ($s) {
                    $q->where('sucursal', $s->id);
                })
                ->whereBetween('fecha', [$desde, $hasta ?? now()])
                ->orderBy('id', 'desc')
                ->get();
        } else {
            // Si $s->id no existe o es 0, se buscan todas las sucursales
            $kardes = Kardex::where('producto', $prod)
                ->whereBetween('fecha', [$desde, $hasta ?? now()])
                ->orderBy('id', 'desc')
                ->get();
        }

        // Generar el PDF con los datos
        $pdf = PDF::loadView('pdf.pdfKardex', compact('producto', 'sucursal', 'desde', 'hasta', 'imagenUrl', 'kardes', 'empresa'));

        return $pdf->stream();
    }
}
