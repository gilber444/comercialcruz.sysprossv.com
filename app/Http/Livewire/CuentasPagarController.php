<?php

namespace App\Http\Livewire;

use App\Models\Compras;
use App\Models\CuentasPagar;
use App\Models\Pagos;
use App\Models\tipoCompras;
use App\Models\TipoPagos;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CuentasPagarController extends Component
{
    use WithPagination;

    public  $search, $selected_id, $pageTitle, $componentName,
    $correlativo, $monto, $saldo, $metodo, $proveedor, $tipos = [], $factura;

    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Cuentas por Pagar a Proveedores';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $query = CuentasPagar::with(['Rcompras' => function ($q) {
            $q->with('RtipoCompra');
        }, 'Rproveedores']);

        $query->where('saldo', '<>', 0.00);
        $query->where('estado', 'pendiente');

        if (strlen($this->search) > 0) {
            $query->where(function ($q) {
                $q->whereHas('Rproveedores', function ($q2) {
                    $q2->where('nombre', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('Rcompras', function ($q3) {
                    $q3->where('tipo', 'like', '%' . $this->search . '%')
                        ->orWhere('correlativo', 'like', '%' . $this->search . '%')
                       ->orWhere('fecha', 'like', '%' . $this->search . '%');
                })
                ->orWhere('fecha_vencimiento', 'like', '%' . $this->search . '%')
                ->orWhere('monto_total', 'like', '%' . $this->search . '%');
            });
        }

        $data = $query->orderBy('id', 'desc')->paginate(10);

        return view('livewire.cuentas_pagar.cuentas-pagar', [
            'data' => $data
        ]) ->extends('layouts.theme.app')
        ->section('content');
    }

    public function CargaDatos($id)
    {
        $cuenta = CuentasPagar::with(['Rproveedores', 'Rcompras' => function ($query) {
            $query->with('Rtipocompra');
        }])->find($id);

        $this->tipos = TipoPagos::all();
        $this->selected_id = $cuenta->id;
        $this->saldo = number_format($cuenta->saldo, 2);
        $this->proveedor = $cuenta->Rproveedores->nombre;
        $this->factura = $cuenta->Rcompras->Rtipocompra->tipo . ": " . $cuenta->Rcompras->correlativo;

        $this->emit('show-modal', 'Show Modal');
    }

    public function Procesar()
    {
        $rules = [
            'monto' => 'required|numeric|min:0',
            'metodo' => 'required',
        ];

        $messages = [
            'monto.required' => 'El monto es requerido',
            'monto.numeric' => 'El monto debe ser un número',
            'monto.min' => 'El monto debe ser mayor o igual a 0',
            'metodo.required' => 'El método de pago es requerido',
        ];

        $this->validate($rules, $messages);

        $cuenta = CuentasPagar::find($this->selected_id);

        $cuenta->saldo = $cuenta->saldo - $this->monto;

        // Verificar si el saldo es igual a cero después del pago
        if ($cuenta->saldo <= 0) {
            $cuenta->estado = 'Pagada';
            $concepto = 'Pago completo de la deuda del proveedor '. $this->proveedor. ' por la factura '. $this->factura;
            // Actualizar el estado de la compra relacionada
            $compra = Compras::find($cuenta->compra_id);
            $compra->estado = 'Pagada';
            $compra->save();
        } else {
            $concepto = 'Pago parcial a la cuenta del proveedor '. $this->proveedor. ' por la factura '. $this->factura;
        }

        $cuenta->save();

        $correlativo =Pagos::count() + 1;

        // Registrar el pago en la tabla pagos
        Pagos::create([
            'correlativo' => $correlativo,
            'fecha' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'user' => Auth::user()->id,
            'concepto' => $concepto,
            'total' => $this->monto,
            'cuenta_pagar' => $cuenta->id,
            'tipo_pago' => $this->metodo,
        ]);

        //$this->resetUI();
        $this->emit('item-updated', 'Cuenta actualizada correctamente');
    }

    public function resetUI()
    {
        $this->monto = '';
        $this->metodo = '';
        $this->selected_id = 0;
        $this->search = '';
        $this->factura ='';
        $this->proveedor = '';
        $this->resetValidation();
        $this->resetPage();
    }
}

