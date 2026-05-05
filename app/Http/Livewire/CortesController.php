<?php
namespace App\Http\Livewire;

use App\Models\Actividades;
use App\Models\Aperturas;
use App\Models\Arqueos;
use App\Models\Caja;
use App\Models\Cortes;
use App\Models\Remesas;
use App\Traits\GenerarJsonCorteZ;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CortesController extends Component
{
    use WithPagination;
    use GenerarJsonCorteZ;

    public $pageTitle, $componentName, $search, $selected_id, $pagination = 10;

    public $totalVentas, $totalTarjetas, $totalCheque, $totalRemesas, $totalSumas, $totalCreditos, $totalSumaResta, $totalAnulaciones, $totalDevoluciones, $arqueos, $cajaChica, $totalEfectivo, $totalDiferencia;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Cortes Z';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
        {
            $data = Cortes::with([
                'Rparametros:id,caja',
                'Rsucursal:id,nombre'
            ])
            //->where('estado', 'Cerrado')
            ->where(function ($query) {
                $query->where('corte', 'like', '%' . $this->search . '%')
                    ->orWhereHas('Rparametros', function ($q) {
                        $q->where('caja', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('Rsucursal', function ($q) {
                        $q->where('nombre', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere(function ($query) {
                        $query->whereRaw('DATE_FORMAT(fecha, "%d/%m/%Y") like ?', ['%' . $this->search . '%']);
                    });
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->paginate($this->pagination);
        }
        else
        {
            // Consulta normal sin búsqueda
            $data = Cortes::with([
                'Rparametros:id,caja',
                'Rsucursal:id,nombre'
            ])
            //->where('estado', 'Cerrado')
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->paginate($this->pagination);
        }

        return view('livewire.cortes.cortes', ['cortes' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Print($id)
    {
        $this->emit('print-ticket2', $this->GenerarJsonCorteZ($id));
    }

    public function CorteZ($id)
    {
        $user_id = Auth::user()->id;

        $cor = Cortes::find($id);

        $this->selected_id = $cor->id;

        $apertura = Aperturas::where('empresa', $cor->empresa)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->where('fechaApertura', $cor->fecha)
            ->first();
        $this->cajaChica = $apertura->inicio;

        // Calcular los totales de ventas, tarjetas, cheques, créditos, remesas, anulaciones y devoluciones
        $this->totalVentas = Caja::where('fecha', $cor->fecha)
            ->where('tipoPago', 1)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->where('estado', 'Cancelado')
            ->sum('total') ?? 0;

        $this->totalTarjetas = Caja::where('fecha', $cor->fecha)
            ->where('tipoPago', 2)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->where('estado', 'Cancelado')
            ->sum('total') ?? 0;

        $this->totalCheque = Caja::where('fecha', $cor->fecha)
            ->where('tipoPago', 3)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->where('estado', 'Cancelado')
            ->sum('total') ?? 0;

        $this->totalCreditos = Caja::where('fecha', $cor->fecha)
            ->where('tipoPago', 4)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->where('estado', 'Cancelado')
            ->sum('total') ?? 0;

        $this->totalRemesas = Remesas::where('fecha', $cor->fecha)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->where('estado', 'Remesado')
            ->sum('monto') ?? 0;

        $this->totalAnulaciones = Caja::where('fecha', $cor->fecha)
            ->where('estado', 'Anulado')
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->sum('total') ?? 0;

        $this->totalDevoluciones = Caja::where('fecha', $cor->fecha)
            ->where('estado', 'Devolucion')
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->sum('total') ?? 0;

        $this->arqueos = Arqueos::where('fecha', $cor->fecha)
            ->where('sucursal', $cor->sucursal)
            ->where('caja', $cor->caja)
            ->sum('totalGeneral') ?? 0;


        // Calcular las sumas y diferencias
        $this->totalSumas = $this->totalVentas + $this->totalTarjetas + $this->totalCheque;
        $this->totalSumaResta = $this->totalSumas - $this->arqueos - $this->totalRemesas - $this->totalAnulaciones - $this->totalDevoluciones;


        $this->totalDiferencia = $this->totalEfectivo - ($this->totalSumaResta + $this->cajaChica);

        $this->emit('modal-updated', 'Valido');
    }

    public function ProcesarCorteZ()
    {
        $user_id = Auth::user()->id;


        DB::beginTransaction();  // Iniciar una transacción

        try {
            $cor = Cortes::find($this->selected_id);
            $apertura = Aperturas::where('empresa', $cor->empresa)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->where('fechaApertura', $cor->fecha)
                ->first();

            $this->cajaChica = $apertura->inicio;

            $totalEfectivo = Caja::where('fecha', $cor->fecha)
                ->where('tipoPago', 1)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->where('estado', 'Cancelado')
                ->sum('total') ?? 0;

            $totalTarjetas =Caja::where('fecha', $cor->fecha)
                ->where('tipoPago', 2)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->where('estado', 'Cancelado')
                ->sum('total') ?? 0;

            $totalCheque = Caja::where('fecha', $cor->fecha)
                ->where('tipoPago', 3)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->where('estado', 'Cancelado')
                ->sum('total') ?? 0;

            $totalCreditos = Caja::where('fecha', $cor->fecha)
                ->where('tipoPago', 4)
                ->where('estado', 'Cancelado')
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->sum('total') ?? 0;

            $primerTicket = Caja::where('facturador', 1)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->orderBy('id', 'asc')
                ->value('correlativo') ?? 0;

            $ultimoTicket = Caja::where('facturador', 1)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->orderBy('id', 'desc')
                ->value('correlativo') ?? 0;

            $gravadosT = Caja::where('facturador', 1)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('subtotal') ?? 0;

            $ivaT = Caja::where('facturador', 1)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('iva') ?? 0;

            $totalT = Caja::where('facturador', 1)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('total') ?? 0;

            $consumidorDesde = Caja::where('facturador', 2)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->orderBy('id', 'asc')
                ->value('correlativo') ?? 0;

            $consumidorHasta = Caja::where('facturador', 2)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->orderBy('id', 'desc')
                ->value('correlativo') ?? 0;

            $gravadosCon = Caja::where('facturador', 2)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('subtotal') ?? 0;

            $ivaCon = Caja::where('facturador', 2)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('iva') ?? 0;

            $totalCon = Caja::where('facturador', 2)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('total') ?? 0;

            $CreDesde = Caja::where('facturador', 3)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->orderBy('id', 'asc')
                ->value('correlativo') ?? 0;

            $CreHasta = Caja::where('facturador', 3)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->orderBy('id', 'desc')
                ->value('correlativo') ?? 0;

            $gravadosCre = Caja::where('facturador', 3)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->whereDate('fecha', $cor->fecha)
                ->where('estado', 'Cancelado')
                ->where('tipoPago', '<>', 4)
                ->sum('subtotal') ?? 0;

            $ivaCre =
                Caja::where('facturador', 3)
                    ->where('sucursal', $cor->sucursal)
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->where('tipoPago', '<>', 4)
                    ->sum('iva') ?? 0;

            $totalCre =
                Caja::where('facturador', 3)
                    ->where('sucursal', $cor->sucursal)
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->where('tipoPago', '<>', 4)
                    ->sum('total') ?? 0;

            $dteDesde =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->orderBy('id', 'asc')
                    ->value('numero') ?? 0;

            $dteHasta =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->orderBy('id', 'desc')
                    ->value('numero') ?? 0;

            $creditosDesde =
                Caja::where('tipoPago', 4)
                    ->where('sucursal', $cor->sucursal)
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->orderBy('id', 'asc')
                    ->value('correlativo') ?? 0;

            $creditosHasta =
                Caja::where('tipoPago', 4)
                    ->where('sucursal', $cor->sucursal)
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->orderBy('id', 'desc')
                    ->value('correlativo') ?? 0;

            $gravadosCredi =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->where('tipoPago', 4)
                    ->sum('subtotal') ?? 0;

            $ivaCredi =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->where('tipoPago', 4)
                    ->sum('iva') ?? 0;

            $totalCredi =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Cancelado')
                    ->where('tipoPago', 4)
                    ->sum('total') ?? 0;

            $devoluciones =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Devolucion')
                    ->sum('total') ?? 0;

            $anulaciones =
                Caja::where('sucursal', session('sucursal'))
                    ->where('caja', $cor->caja)
                    ->whereDate('fecha', $cor->fecha)
                    ->where('estado', 'Anulado')
                    ->sum('total') ?? 0;

            $remesas = Remesas::where('fecha', $cor->fecha)
                    ->where('sucursal', $cor->sucursal)
                    ->where('caja', $cor->caja)
                    ->where('estado', 'Remesado')
                    ->sum('monto') ?? 0;

            $cortes = Arqueos::where('fecha', $cor->fecha)
                ->where('sucursal', $cor->sucursal)
                ->where('caja', $cor->caja)
                ->where('empresa', session('empresa'))
                ->sum('totalGeneral') ?? 0;

            $apertura->FcierreApertura = date('Y-m-d');
            $apertura->HcierreApertura = date('H:i:s');
            $apertura->estado = 'Cerrado';
            $apertura->save();

            $cor->update([
                'estado' => 'Cerrado',
                'efectivo' => $totalEfectivo,
                'tarjeta' => $totalTarjetas,
                'cheque' => $totalCheque,
                'credito' => $totalCreditos,
                'subtotalPagos' => $totalEfectivo + $totalTarjetas + $totalCheque,
                'devoluciones' => $devoluciones,
                'anulaciones' => $anulaciones,
                'remesas' => $remesas,
                'percepcion' => 0,
                'cortes' => $cortes,
                'sumaTotales' => $totalEfectivo + $totalTarjetas + $totalCheque,
                'ticketDesde' => $primerTicket,
                'ticketHasta' => $ultimoTicket,
                'gravadosT' => $gravadosT / 1.13,
                'ivaT' => $ivaT,
                'subT' => $totalT,
                'totalT' => $totalT,
                'consumidorDesde' => $consumidorDesde,
                'consumidorHasta' => $consumidorHasta,
                'gravadosCon' => $gravadosCon / 1.13,
                'ivaCon' => $ivaCon,
                'subCon' => $totalCon,
                'totalCon' => $totalCon,
                'CreDesde' => $CreDesde,
                'CreHasta' => $CreHasta,
                'gravadosCre' => $gravadosCre / 1.13,
                'ivaCre' => $ivaCre,
                'subCre' => $totalCre,
                'totalCre' => $totalCre,
                'dteDesde' => $dteDesde,
                'dteHasta' => $dteHasta,
                'gravadosDTE' => $gravadosCon + $gravadosCre,
                'ivaDTE' => $ivaCon + $ivaCre,
                'subDTE' => $totalCon + $totalCre,
                'totalDTE' => $totalCon + $totalCre,
                'creditosDesde' => $creditosDesde,
                'creditosHasta' => $creditosHasta,
                'gravadosCredi' => $gravadosCredi,
                'ivaCredi' => $ivaCredi,
                'subCredi' => $totalCredi,
                'totalCredi' => $totalCredi,
                'totalGeneral' => $gravadosCon + $gravadosCre + $gravadosT,
                'ivaGeneral' => $ivaCon + $ivaCre + $ivaT,
                'subGeneral' => $totalCon + $totalCre + $totalT,
                'totalGlobal' => $totalCon + $totalCre + $totalT,
                'totalEfectivo' => $this->totalEfectivo - $apertura->inicio,
                'diferencia' => $this->totalDiferencia
            ]);

            $fecha = Carbon::parse($cor->fecha);

            $acti = Actividades::where('sucursal', $cor->sucursal)->where('caja', $cor->caja)->whereDate('created_at', $fecha->toDateString())->first();
            $acti->status = 'Cerrado';
            $acti->save();

            Caja::where('fecha', $cor->fecha)->where('sucursal', $cor->sucursal)->where('caja', $cor->caja)->where('estado', 'Cancelado')->update(['arqueado' => true]);

            Remesas::where('fecha', $cor->fecha)->where('sucursal', $cor->sucursal)->where('caja', $cor->caja)->update(['arqueado' => true]);
            $this->print($this->selected_id);

            DB::commit();
            return redirect()->route('cortes');
        }
        catch (Exception $e)
        {
            DB::rollBack();  // Hacer rollback si ocurre algún error
            $this->emit('item-error', 'Error al procesar el corte: ' . $e->getMessage());
        }
    }
}
