<?php

namespace App\Http\Livewire;

use App\Exports\HojaAperturaExport;
use App\Exports\HojaExport;
use App\Models\AperturaInventario;
use App\Models\Empresas;
use App\Models\HojaInventario as ModelsHojaInventario;
use App\Models\HojaInventarioDetalles;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Sucursales;
use App\Models\tmpHojaInventario;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class HojaInventario extends Component
{
    use WithPagination;

    public  $search, $selected_id, $pageTitle, $componentName, $detalleInventario = [];
    private $pagination = 10;
    public $detalles = [];
    public $aperturaActiva; // bool o modelo
    public $responsableApertura, $observacionApertura, $username, $password, $sucursal, $sucursales = [], $hojas = [];

    public $aperturaSeleccionada;

    public $productosNoContados = [];
    public $tituloModalNoContados = 'Productos no contados';

    public $hojaFecha          = '';
    public $hojaCorrelativo    = '';
    public $hojaSucursal       = '';
    public $hojaSucursalNombre = '';


    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Hojas de inventario';

        $user = Auth::user();

        $this->aperturaActiva = AperturaInventario::where('empresa', $user->empresa)
            ->where('estado', 'Abierto')
            ->first();

        $this->sucursales = Sucursales::where('empresa', $user->empresa)
            ->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();

        $data = AperturaInventario::where('empresa', $user->empresa)
            ->orderBy('id', 'desc')
            ->paginate($this->pagination);


        return view('livewire.hoja_inventarios.hoja-inventario', ['inventarios' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    protected $listeners = [
        'deleteRow'                => 'Destroy',
        'anulaRow'                 => 'anular',
        'cerrarAperturaInventario' => 'cerrarAperturaInventario',
        'reabrirHoja'              => 'reabrirHoja',
    ];

    // =====================================================
    // VISTA HTML — HOJA INDIVIDUAL
    // =====================================================
    public function vistaPrevia($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);

        $hojas = ModelsHojaInventario::findOrFail($id);
        $sucursal = Sucursales::where('id', $hojas->sucursal)->first();

        $hojasDetalle = HojaInventarioDetalles::where('hoja', $id)
            ->orderBy('nombre', 'asc')
            ->get();
        $totalGeneral = HojaInventarioDetalles::where('hoja', $id)->sum('total');

        return view('livewire.hoja_inventarios.vista-hoja', compact('hojas', 'hojasDetalle', 'empresa', 'sucursal', 'totalGeneral'));
    }

    // =====================================================
    // PDF — HOJA INDIVIDUAL
    // =====================================================
    public function pdfHoja($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $hojas = ModelsHojaInventario::findOrFail($id);
        $sucursal = Sucursales::where('id', $hojas->sucursal)->first();
        $hojasDetalle = HojaInventarioDetalles::where('hoja', $id)
            ->orderBy('nombre', 'asc')
            ->get();
        $totalGeneral = HojaInventarioDetalles::where('hoja', $id)->sum('total');

        return view('pdf.pdfhojas', compact('hojas', 'hojasDetalle', 'imagenUrl', 'empresa', 'sucursal', 'totalGeneral'));
    }

    // =====================================================
    // EXCEL — HOJA INDIVIDUAL
    // =====================================================
    public function excelHoja($id)
    {
        $hojas = ModelsHojaInventario::findOrFail($id);
        return Excel::download(new HojaExport($id), 'hoja-inventario-' . $hojas->correlativo . '.xlsx');
    }

    // =====================================================
    // VISTA HTML — APERTURA CONSOLIDADA
    // =====================================================
    public function vistaPreviaLibro($id)
    {
        $apertura = AperturaInventario::findOrFail($id);

        $user = Auth::user();
        $empresa = Empresas::findOrFail($user->empresa);

        $hojaIds = ModelsHojaInventario::where('apertura_id', $apertura->id)->pluck('id');

        $sucursalId = $apertura->sucursal ?? ModelsHojaInventario::where('apertura_id', $apertura->id)->value('sucursal');
        $sucursal = $sucursalId ? Sucursales::find($sucursalId) : null;

        // Primer conteo por producto
        $first = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->selectRaw('producto, MIN(id) as first_id')
            ->groupBy('producto');

        // Sumas por producto
        $sumas = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->selectRaw('producto,
                SUM(cantidadActual) as cont_fisico,
                AVG(costo) as costo_prom,
                MAX(medida) as medida_show,
                MAX(codebar) as codebar_show,
                MAX(nombre) as nombre_show
            ')
            ->groupBy('producto');

        // Consolidado
        $hojasDetalle = HojaInventarioDetalles::query()
            ->joinSub($first, 'f', function ($join) {
                $join->on('hoja_inventario_detalles.producto', '=', 'f.producto')
                    ->on('hoja_inventario_detalles.id', '=', 'f.first_id');
            })
            ->joinSub($sumas, 's', function ($join) {
                $join->on('hoja_inventario_detalles.producto', '=', 's.producto');
            })
            ->selectRaw('
                hoja_inventario_detalles.producto as producto,
                s.codebar_show as codebar,
                s.nombre_show as nombre,
                s.medida_show as medida,
                hoja_inventario_detalles.cantidadAnterior as cantidadAnterior,
                s.cont_fisico as cantidadActual,
                (s.cont_fisico - hoja_inventario_detalles.cantidadAnterior) as diferencia,
                s.costo_prom as costo,
                (s.cont_fisico * s.costo_prom) as total,
                (hoja_inventario_detalles.cantidadAnterior * s.costo_prom) as totalAnterior,
                (s.cont_fisico * s.costo_prom) - (hoja_inventario_detalles.cantidadAnterior * s.costo_prom) as totalDiferencia
            ')
            ->orderBy('nombre', 'asc')
            ->get();

        // Breakdown por producto (para popovers en la vista HTML)
        $breakdownRaw = HojaInventarioDetalles::whereIn('hoja_inventario_detalles.hoja', $hojaIds)
            ->join('hoja_inventarios', 'hoja_inventario_detalles.hoja', '=', 'hoja_inventarios.id')
            ->select(
                'hoja_inventario_detalles.producto',
                'hoja_inventarios.correlativo',
                'hoja_inventario_detalles.cantidadActual as cantidad',
                'hoja_inventarios.responsable as usuario'
            )
            ->get()
            ->groupBy('producto');

        $hojasDetalle = $hojasDetalle->map(function ($item) use ($breakdownRaw) {
            $item->breakdown = $breakdownRaw->get($item->producto, collect())
                ->map(fn($b) => [
                    'correlativo' => $b->correlativo,
                    'cantidad'    => $b->cantidad,
                    'usuario'     => $b->usuario,
                ])->toArray();
            return $item;
        });

        $totalGeneral            = $hojasDetalle->sum('total');
        $totalinventarioAnterior = $hojasDetalle->sum('totalAnterior');
        $totalDiferencia         = $hojasDetalle->sum('totalDiferencia');

        return view('livewire.hoja_inventarios.vista-apertura', compact(
            'hojasDetalle',
            'empresa',
            'sucursal',
            'totalGeneral',
            'apertura',
            'totalinventarioAnterior',
            'totalDiferencia'
        ));
    }

    // =====================================================
    // PDF — APERTURA CONSOLIDADA
    // =====================================================
    public function pdfApertura($id)
    {
        $apertura = AperturaInventario::findOrFail($id);

        $user = Auth::user();
        $empresa = Empresas::findOrFail($user->empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $hojaIds = ModelsHojaInventario::where('apertura_id', $apertura->id)->pluck('id');

        $sucursalId = $apertura->sucursal ?? ModelsHojaInventario::where('apertura_id', $apertura->id)->value('sucursal');
        $sucursal = $sucursalId ? Sucursales::find($sucursalId) : null;

        $first = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->selectRaw('producto, MIN(id) as first_id')
            ->groupBy('producto');

        $sumas = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->selectRaw('producto,
                SUM(cantidadActual) as cont_fisico,
                AVG(costo) as costo_prom,
                MAX(medida) as medida_show,
                MAX(codebar) as codebar_show,
                MAX(nombre) as nombre_show
            ')
            ->groupBy('producto');

        $hojasDetalle = HojaInventarioDetalles::query()
            ->joinSub($first, 'f', function ($join) {
                $join->on('hoja_inventario_detalles.producto', '=', 'f.producto')
                    ->on('hoja_inventario_detalles.id', '=', 'f.first_id');
            })
            ->joinSub($sumas, 's', function ($join) {
                $join->on('hoja_inventario_detalles.producto', '=', 's.producto');
            })
            ->selectRaw('
                s.codebar_show as codebar,
                s.nombre_show as nombre,
                s.medida_show as medida,
                hoja_inventario_detalles.cantidadAnterior as cantidadAnterior,
                s.cont_fisico as cantidadActual,
                (s.cont_fisico - hoja_inventario_detalles.cantidadAnterior) as diferencia,
                s.costo_prom as costo,
                (s.cont_fisico * s.costo_prom) as total,
                (hoja_inventario_detalles.cantidadAnterior * s.costo_prom) as totalAnterior,
                (s.cont_fisico * s.costo_prom) - (hoja_inventario_detalles.cantidadAnterior * s.costo_prom) as totalDiferencia
            ')
            ->orderBy('nombre', 'asc')
            ->get();

        $totalGeneral            = $hojasDetalle->sum('total');
        $totalinventarioAnterior = $hojasDetalle->sum('totalAnterior');
        $totalDiferencia         = $hojasDetalle->sum('totalDiferencia');

        return view('pdf.pdfhojas_apertura', compact(
            'hojasDetalle',
            'imagenUrl',
            'empresa',
            'sucursal',
            'totalGeneral',
            'apertura',
            'totalinventarioAnterior',
            'totalDiferencia'
        ));
    }

    // =====================================================
    // EXCEL — APERTURA CONSOLIDADA
    // =====================================================
    public function excelApertura($id)
    {
        $apertura = AperturaInventario::findOrFail($id);
        return Excel::download(new HojaAperturaExport($id), 'inventario-apertura-' . $apertura->id . '.xlsx');
    }

    // =====================================================
    // MODAL NUEVA HOJA
    // =====================================================

    public function abrirModalNuevaHoja()
    {
        if (!$this->aperturaActiva) {
            $this->emit('item-error', 'No hay apertura de inventario activa.');
            return;
        }

        $sucursal = Sucursales::find($this->aperturaActiva->sucursal);
        $this->hojaSucursal       = $this->aperturaActiva->sucursal;
        $this->hojaSucursalNombre = $sucursal->nombre ?? '';
        $this->hojaFecha          = $this->aperturaActiva->fecha_apertura ?? now()->toDateString();
        $this->hojaCorrelativo    = '';
        $this->resetErrorBag();

        $this->dispatchBrowserEvent('open-nueva-hoja-modal');
    }

    public function crearHoja()
    {
        if (!$this->aperturaActiva) {
            $this->emit('item-error', 'No hay apertura de inventario activa.');
            return;
        }

        $this->validate([
            'hojaCorrelativo' => 'required|string|max:20',
        ], [
            'hojaCorrelativo.required' => 'El correlativo es requerido.',
        ]);

        $hojaActiva = ModelsHojaInventario::where('apertura_id', $this->aperturaActiva->id)
            ->where('sucursal', $this->hojaSucursal)
            ->where('correlativo', $this->hojaCorrelativo)
            ->where('estado', 'Activa')
            ->latest('id')
            ->first();

        if ($hojaActiva) {
            return redirect()->route('editar-hoja', ['hojaId' => $hojaActiva->id]);
        }

        $usuario = Auth::user();
        $hora    = now()->format('H:i:s');

        $hoja = ModelsHojaInventario::create([
            'apertura_id'  => $this->aperturaActiva->id,
            'fecha'        => $this->hojaFecha,
            'hora'         => $hora,
            'fecha_inicio' => $this->hojaFecha,
            'hora_inicio'  => $hora,
            'fecha_fin'    => null,
            'hora_fin'     => null,
            'correlativo'  => $this->hojaCorrelativo,
            'responsable'  => $usuario->id,
            'user'         => $usuario->id,
            'empresa'      => $usuario->empresa,
            'sucursal'     => $this->hojaSucursal,
            'estado'       => 'Activa',
        ]);

        $this->reset(['hojaCorrelativo']);
        $this->dispatchBrowserEvent('close-nueva-hoja-modal');

        return redirect()->route('editar-hoja', ['hojaId' => $hoja->id]);
    }

    // =====================================================
    // NO CONTADOS — PRODUCTOS SIN CONTAR EN UNA APERTURA
    // =====================================================
    public function noContados($id)
    {
        $apertura  = AperturaInventario::findOrFail($id);
        $user      = Auth::user();
        $empresa   = Empresas::findOrFail($user->empresa);
        $sucursal  = Sucursales::find($apertura->sucursal);
        $productos = $this->getProductosNoContadosQuery($apertura)->get();

        return view('livewire.hoja_inventarios.no-contados', compact('apertura', 'empresa', 'sucursal', 'productos'));
    }

    public function noContadosPdf($id)
    {
        $apertura  = AperturaInventario::findOrFail($id);
        $user      = Auth::user();
        $empresa   = Empresas::findOrFail($user->empresa);
        $sucursal  = Sucursales::find($apertura->sucursal);
        $productos = $this->getProductosNoContadosQuery($apertura)->get();
        $imagenUrl = asset('logo/' . $empresa->image);

        $pdf = Pdf::loadView('pdf.pdf_no_contados', compact('productos', 'apertura', 'sucursal', 'empresa', 'imagenUrl'));
        return $pdf->stream('productos-no-contados-' . $id . '.pdf');
    }

    public function noContadosExcel($id)
    {
        $apertura = AperturaInventario::findOrFail($id);
        return Excel::download(new \App\Exports\NoContadosExport($id), 'productos-no-contados-' . $id . '.xlsx');
    }

    private function getProductosNoContadosQuery($apertura)
    {
        $hojaIds = ModelsHojaInventario::where('apertura_id', $apertura->id)->pluck('id');

        $productosContados = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->distinct()
            ->pluck('producto')
            ->toArray();

        return DB::table('inventarios as inv')
            ->join('productos as p', 'inv.producto', '=', 'p.id')
            ->where('inv.sucursal', $apertura->sucursal)
            ->when(!empty($productosContados), fn($q) => $q->whereNotIn('inv.producto', $productosContados))
            ->select('p.id', 'p.nombreProducto as nombre', 'p.codebar3 as codebar', 'inv.existencia')
            ->orderBy('p.nombreProducto');
    }

    // =====================================================
    // CARGA DETALLES (usado internamente)
    // =====================================================
    public function cargarDetalles($id)
    {
        $hoja = HojaInventarioDetalles::where('hoja', $id)->get();

        if ($hoja) {
            $this->detalles = $hoja;
        } else {
            $this->detalles = [];
        }

        $this->emit('inventarios-modal', 'show modal');
    }

    // =====================================================
    // VALIDACIÓN (incluye sucursal y fecha)
    // =====================================================
    protected function rules()
    {
        return [
            'sucursal' => 'required|not_in:0',
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    protected function messages()
    {
        return [
            'sucursal.required' => 'La sucursal es requerida',
            'sucursal.not_in'   => 'Elegí una sucursal válida',
            'username.required' => 'El usuario es requerido',
            'password.required' => 'La contraseña es requerida',
        ];
    }

    // =====================================================
    // VALIDAR CREDENCIALES Y CREAR/CONTINUAR HOJA
    // =====================================================
    public function Validar()
    {
        $this->validate($this->rules(), $this->messages());

        $validador = User::where(function ($query) {
            $query->where('user', $this->username)
                ->orWhere('email', $this->username);
        })
            ->whereIn('profile', ['Super', 'Administrador', 'Gerente', 'BODEGA', 'Contabilidad', 'Contador'])
            ->first();

        if (!$validador || !Hash::check($this->password, $validador->password)) {
            $this->emit('item-error', 'Credenciales inválidas. Verifique su usuario y contraseña');
            return;
        }

        $this->abrirInventario($validador);
    }

    public function preValidar()
    {
        $this->validate([
            'sucursal' => 'required|not_in:0',
            'fecha'    => 'required|date',
            'correlativo' => 'required|string',
        ], [
            'sucursal.not_in' => 'Elegí una sucursal válida',
            'fecha.required'  => 'La fecha es requerida',
            'correlativo.required' => 'El correlativo es requerido',
        ]);
    }

    public function abrirInventario(User $validador)
    {
        $usuario = Auth::user();

        try {
            $apertura = DB::transaction(function () use ($usuario, $validador) {
                // Re-verificar en BD para evitar aperturas duplicadas si otra PC abrió una mientras
                // esta pantalla estaba cargada (Livewire mantiene $aperturaActiva en memoria).
                $yaExiste = AperturaInventario::where('empresa', $usuario->empresa)
                    ->where('sucursal', $this->sucursal)
                    ->where('estado', 'Abierto')
                    ->whereDate('fecha_apertura', now()->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($yaExiste) {
                    $this->aperturaActiva = $yaExiste;
                    $this->emit('item-error', 'Ya existe una apertura de inventario abierta para esta sucursal el día de hoy. Actualice la página.');
                    return null;
                }

                return AperturaInventario::create([
                    'empresa' => $usuario->empresa,
                    'sucursal' => $this->sucursal,
                    'user' => $validador->id,
                    'responsable' => $validador->name,
                    'observacion' => $this->observacionApertura,
                    'fecha_apertura' => now()->toDateString(),
                    'hora_apertura' => now()->format('H:i:s'),
                    'estado' => 'Abierto',
                ]);
            });
        } catch (\Throwable $e) {
            $this->emit('item-error', 'Error al abrir inventario: ' . $e->getMessage());
            return;
        }

        if (!$apertura) {
            return;
        }

        return redirect()->route('hoja_inventarios');
    }


    public function confirmCerrarApertura()
    {
        if (!$this->aperturaActiva) {
            $this->emit('error', 'No hay inventario activo.');
            return;
        }

        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => 'Cerrar inventario',
            'text'  => '¿Seguro que deseas cerrar la apertura? Ya no podrás crear más hojas en esta apertura.',
            'icon'  => 'warning',
            'confirmButtonText' => 'Sí, cerrar',
            'cancelButtonText'  => 'Cancelar',
            'event' => 'cerrarAperturaInventario',
        ]);
    }

    public function cerrarAperturaInventario()
    {
        $user = Auth::user();

        $apertura = AperturaInventario::where('empresa', $user->empresa)
            ->where('estado', 'Abierto')
            ->first();

        if (!$apertura) {
            $this->emit('error', 'No hay inventario activo.');
            return;
        }

        $hayHojasAbiertas = ModelsHojaInventario::where('apertura_id', $apertura->id)
            ->where('estado', '<>', 'Cerrada')
            ->exists();

        if ($hayHojasAbiertas) {
            $this->emit('error', 'Aún hay hojas sin cerrar. Cierra todas las hojas primero.');
            return;
        }

        DB::beginTransaction();

        try {
            $hojaIds = ModelsHojaInventario::where('apertura_id', $apertura->id)->pluck('id');

            $productosContados = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
                ->distinct()
                ->pluck('producto')
                ->toArray();

            $sucursalId = $apertura->sucursal;

            // Productos no contados con existencia > 0: generar kardex de baja y poner a 0
            $noContados = Inventarios::where('sucursal', $sucursalId)
                ->when(!empty($productosContados), fn($q) => $q->whereNotIn('producto', $productosContados))
                ->where('existencia', '>', 0)
                ->get();

            foreach ($noContados as $inv) {
                $existencia = (float) $inv->existencia;

                $ultimoKardex = Kardex::where('producto', $inv->producto)
                    ->where('inventario', $inv->id)
                    ->latest('id')
                    ->first();

                $costoUnit = ($ultimoKardex && (float) $ultimoKardex->saldoCantidad > 0)
                    ? (float) $ultimoKardex->saldoValor / (float) $ultimoKardex->saldoCantidad
                    : 0;

                Kardex::create([
                    'producto'        => $inv->producto,
                    'inventario'      => $inv->id,
                    'descripcion'     => 'Ajuste Inventario - No contado (Apertura #' . $apertura->id . ')',
                    'fecha'           => now()->toDateString(),
                    'hora'            => now()->toTimeString(),
                    'ingresoCantidad' => 0,
                    'ingresoValor'    => 0,
                    'egresoCantidad'  => $existencia,
                    'egresoValor'     => $existencia * $costoUnit,
                    'saldoCantidad'   => 0,
                    'saldoValor'      => 0,
                    'user'            => $user->id,
                ]);

                $inv->update(['existencia' => 0]);
            }

            // Productos no contados con existencia = 0: solo asegurar que queden en 0
            Inventarios::where('sucursal', $sucursalId)
                ->when(!empty($productosContados), fn($q) => $q->whereNotIn('producto', $productosContados))
                ->where('existencia', '<=', 0)
                ->update(['existencia' => 0, 'updated_at' => now()]);

            $totales = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
                ->selectRaw('
                SUM(cantidadAnterior * costo)  as total_sistema_detalle,
                SUM(cantidadActual * costo)      as total_fisico,
                SUM(diferencia * costo)        as total_diferencia_detalle
            ')
                ->first();

            $apertura->update([
                'estado'        => 'Cerrado',
                'fecha_cierre'  => now()->toDateString(),
                'hora_cierre'   => now()->toTimeString(),
                'total_sistema' => $totales->total_sistema_detalle,
                'total_fisico' => $totales->total_fisico,
                'total_diferencia' => $totales->total_diferencia_detalle,
            ]);

            DB::commit();

            $this->aperturaActiva = null;

            $this->emit('item-added', 'Apertura cerrada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->emit('item-error', 'Error al cerrar apertura: ' . $e->getMessage());
        }
    }

    public function verHojas($aperturaId)
    {
        $this->aperturaSeleccionada = $aperturaId;

        $this->hojas = ModelsHojaInventario::with(['Rusuarios:id,name', 'Rempresas:id,empresa', 'Rsucursales:id,nombre'])
            ->select('hoja_inventarios.*')
            ->where('apertura_id', $aperturaId)
            ->orderBy('hoja_inventarios.id', 'desc')
            ->get();
    }

    public function reabrirHoja($id)
    {
        DB::beginTransaction();
        try {
            $hoja     = ModelsHojaInventario::findOrFail($id);
            $detalles = HojaInventarioDetalles::where('hoja', $id)->get();

            foreach ($detalles as $detalle) {
                // 1) Revertir existencia al valor anterior a esta hoja
                Inventarios::where('producto', $detalle->producto)
                    ->where('sucursal', $hoja->sucursal)
                    ->update(['existencia' => $detalle->cantidadAnterior]);

                // 2) Eliminar kardex generado por esta hoja
                Kardex::where('producto', $detalle->producto)
                    ->where('descripcion', 'like', '%Hoja #' . $id . '%')
                    ->delete();

                // 3) Restaurar tmpHojaInventario para que la hoja no quede en blanco
                tmpHojaInventario::create([
                    'producto'     => $detalle->producto,
                    'hoja'         => $id,
                    'sucursal'     => $hoja->sucursal,
                    'name'         => $detalle->nombre,
                    'codebar'      => $detalle->codebar,
                    'medida'       => $detalle->medida,
                    'existencia'   => $detalle->cantidadAnterior,
                    'conteoFisico' => $detalle->cantidadActual,
                    'cantidad'     => $detalle->cantidadActual,
                    'limit'        => 1,
                    'diferencia'   => $detalle->diferencia,
                    'costo'        => $detalle->costo,
                    'total'        => $detalle->total,
                    'user'         => Auth::id(),
                ]);
            }

            HojaInventarioDetalles::where('hoja', $id)->delete();

            $hoja->update([
                'estado'    => 'Activa',
                'fecha_fin' => null,
                'hora_fin'  => null,
            ]);

            DB::commit();
            return redirect()->route('editar-hoja', ['hojaId' => $id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->emit('item-error', 'Error al reabrir: ' . $e->getMessage());
        }
    }

    public function generarPdf($id)
    {
        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        $imagenUrl = asset('logo/' . $empresa->image);

        $hojas = ModelsHojaInventario::findOrFail($id);
        $sucursal = Sucursales::where('id', $hojas->sucursal)->first();
        $hojasDetalle = HojaInventarioDetalles::where('hoja', $id)->orderBy('nombre', 'asc')->get();
        $totalGeneral = HojaInventarioDetalles::where('hoja', $id)->sum('total');

        $pdf = Pdf::loadView('pdf.pdfhojas', compact('hojas', 'hojasDetalle', 'imagenUrl', 'empresa', 'sucursal', 'totalGeneral'));
        return $pdf->stream();
    }
}
