<?php
namespace App\Http\Livewire;
use App\Models\Compras;
use App\Models\ComprasDetalles;
use App\Models\CuentasPagar;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Medidas;
use App\Models\Pagos;
use App\Models\Precios;
use App\Models\precompra;
use App\Models\precompraDetalle;
use App\Models\Productos;
use App\Models\Proveedores;
use App\Models\Sucursales;
use App\Models\tipoCompras;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
class ProcesaController extends Component
{
    public $precompra, $select_id, $proveedores, $tipos, $proveedor, $Telproveedor, $Dirproveedor, $factura, $correlativo, $serie, $fecha, $detalles, $productos, $productoSelectId, $productoSelectName, $detallesCount, $totalVentaGravada, $totalIva, $subTotal, $precepcion, $totalPagar, $idDetalle, $productoDetalle, $condiPago, $proveedorSelectId, $medidas =[], $medida, $sucursales, $sucursal;
    public function mount($id)
    {
        $this->precompra = precompra::with('Proveedores')->withCount('detalles')->find($id);
        $this->sucursales = Sucursales::all();
        $this->totalPagar = $this->precompra->totalPagar;
        $this->precepcion = $this->precompra->ivaPerci1;
        $this->totalIva = $this->precompra->totalIva;
        $this->subTotal = $this->precompra->subTotal;
        $this->detallesCount = $this->precompra->detalles_count;
        $this->totalVentaGravada = $this->precompra->totalGravada;
        $this->proveedor = $this->precompra->Proveedores->nombre;
        $this->proveedorSelectId = $this->precompra->emisor;
        $this->Telproveedor = $this->precompra->Proveedores->telefono;
        $this->Dirproveedor = $this->precompra->Proveedores->direccion;
        $this->correlativo = $this->precompra->numeroControl;
        $this->serie = $this->precompra->codigoGeneracion;
        if ($this->precompra && $this->precompra->fecEmi && $this->precompra->horEmi) {
            // Combina fecha y hora en el formato correcto para datetime-local
            $this->fecha = $this->precompra->fecEmi . 'T' . substr($this->precompra->horEmi, 0, 5);
        } else {
            $this->fecha = null; // O establece un valor predeterminado si es necesario
        }
        if($this->precompra->tipoDte == 2)
        {
            $this->factura = 1;
            $this->tipos = 'Credito Fiscal';
        }
        elseif($this->precompra->tipoDte == 1)
        {
            $this->factura = 2;
            $this->tipos = 'Consumidor Final';
        }
        $this->select_id = $id;
        $this->detalles = precompraDetalle::where('precompra', $id)->get();
        $this->productos = Productos::all();
    }
    public function render()
    {
        return view('livewire.precompra.procesa')
        ->extends('layouts.theme.app')
        ->section('content');
    }
    public function Validar($id)
    {
        $this->idDetalle = $id;
        $prod = precompraDetalle::find($id);
        $this->productoDetalle = $prod->codigo . ' ' . $prod->descripcion;
        $this->productos = Productos::all();
        $this->productoSelectId = $prod->producto;
        $this->medidas = Precios::where('producto', $prod->producto )->get();
        $this->medida = $prod->medida;
        $this->emit('show-modal', 'Show Modal');
    }
    public function Cambiar()
    {
        $d = precompraDetalle::find($this->idDetalle);
        $produs = Precios::where('producto', $this->productoSelectId)->where('costosiva', $d->precioUni)->first();
        $produs;
        if($produs)
        {
            $d->producto = $this->productoSelectId;
            $d->medida = $this->medida;
            $d->status = 'Validado';
            $d->save();
            $this->emit('item-added', 'Se Actualizo producto');
            $this->mount($this->select_id);
        }
        else
        {
            $d->producto = $this->productoSelectId;
            $d->medida = $this->medida;
            $d->status = 'Validado';
            $d->save();
            $this->emit('item-error', 'El precio de costo es diferente al de sistema');
            $this->mount($this->select_id);
        }
    }
    public function GuardarCompra()
    {
        $rules = [
            'condiPago' => 'required|not_in:Elegir',
            'sucursal' => 'required'
        ];
        $messages = [
            'condiPago.required' => 'El campo condicion de pago es requerido',
            'condiPago.not_in' => 'Elige una condicion de pago diferente de Elegir',
            'sucursal.required' => 'Selecciona una sucursal donde almacenar la compra'
        ];
        $this->validate($rules, $messages);
        $user_id = Auth::user()->id;
        $horaActual = date('H:i:s');
        $ultimoNumeroCompra = Compras::orderBy('numero', 'desc')->select('numero')->first();
        $nuevoNumeroCompra = $ultimoNumeroCompra ? ($ultimoNumeroCompra->numero + 1) : 1;
        DB::beginTransaction();
        try
        {
            \Log::info('INICIO GuardarCompra Precompra', [
                'user_id' => $user_id,
                'sucursal' => $this->sucursal,
                'condiPago' => $this->condiPago,
                'precompra_id' => $this->select_id,
            ]);
            $compra = Compras::create([
                'numero' => $nuevoNumeroCompra,
                'tipo' => $this->factura,
                'correlativo' => $this->correlativo,
                'serie' => $this->serie,
                'fecha' => $this->fecha,
                'condi_pago' => $this->condiPago,
                'vendedor' => NULL,
                'estado' => 'Pendiente',
                'fechaPago' => null,
                'proveedor' => $this->proveedorSelectId,
                'user' => $user_id,
                'sucursal' => $this->sucursal
            ]);
            if($compra)
            {
                $items = precompraDetalle::where('precompra', $this->select_id)->get();
                foreach($items as $item)
                {
                    //dd($item->attributes['vencimiento']);
                    $venci= null;
                    $prod = Productos::find($item->producto);
                    $pre = Precios::where('producto', $item->producto)->where('medida', $item->medida)->first();
                    $ingresoCantidad = $item->cantidad;
                    ComprasDetalles::create([
                        'compra' => $compra->id,
                        'producto' => $item->producto,
                        'medida' => $item->medida,
                        'cantidad' => $item->cantidad,
                        'ingreso' => $ingresoCantidad,
                        'costo' => $item->precioUni,
                        'total' => $item->ventaGravada,
                        'fechaVencimiento' => $venci,
                    ]);
                    $existencia = Inventarios::where('producto', $item->producto)->where('sucursal', $this->sucursal)->first();
                    if(!$existencia)
                    {
                        $sucursal = Sucursales::find($this->sucursal);
                        if(!$sucursal)
                        {
                            throw new \RuntimeException('No se encontró la sucursal seleccionada.');
                        }
                        $existencia = Inventarios::create([
                            'producto' => $item->producto,
                            'empresa' => $sucursal->empresa,
                            'sucursal' => $sucursal->id,
                            'existencia' => 0
                        ]);
                        \Log::info('Inventario creado en GuardarCompra Precompra', [
                            'inventario_id' => $existencia->id,
                            'producto' => $item->producto,
                            'sucursal' => $this->sucursal,
                        ]);
                    }
                    $nuevaExistencia = $ingresoCantidad + $existencia->existencia;
                    $existencia->existencia = $nuevaExistencia;
                    $existencia->save();
                    $saldoCantidad = $nuevaExistencia;
                    $saldoValor = $item->precioUni * $saldoCantidad;
                    Kardex::create([
                        'producto' => $item->producto,
                        'inventario' => $existencia->id,
                        'sucursal' => $this->sucursal,
                        'descripcion' => 'Compra de productos a ' . $this->proveedor . ' Factura ' .$this->correlativo,
                        'fecha' => $this->fecha ,
                        'hora' => $horaActual,
                        'ingresoCantidad' => $ingresoCantidad,
                        'ingresoValor' => $item->ventaGravada,
                        'egresoCantidad' => 0.00,
                        'egresoValor' => 0.00,
                        'saldoCantidad' => $saldoCantidad,
                        'saldoValor' => $saldoValor
                    ]);
                }
            }
            $precompras = precompra::find($this->select_id);
            $precompras->estado ='Procesado';
            $precompras->save();
            /**Manejar Cuentas por Pagar o Pagos**/
            if ($this->condiPago === 'Credito')
            {
                // Registrar deuda en `cuentas_pagar`
                CuentasPagar::create([
                    'compra' => $compra->id,
                    'proveedor' => $this->proveedorSelectId,
                    'monto_total' => $this->totalPagar,
                    'saldo' => $this->totalPagar,
                    'estado' => 'pendiente',
                    'fecha_vencimiento' => now()->addDays(30), // Ejemplo: 30 días para pagar
                ]);
            }
            else
            {
                // Registrar deuda en `cuentas_pagar`
                $cuenta = CuentasPagar::create([
                    'compra' => $compra->id,
                    'proveedor' => $this->proveedorSelectId,
                    'monto_total' => $this->totalPagar,
                    'saldo' => 0.00,
                    'estado' => 'pagada',
                    'fecha_vencimiento' => now(), // Ejemplo: 30 días para pagar
                ]);
                // Registrar pago en `pagos`
                Pagos::create([
                    'correlativo' => Pagos::max('correlativo') + 1,
                    'fecha' => $this->fecha,
                    'hora' => $horaActual,
                    'user' => $user_id,
                    'concepto' => 'Compra al contado - Factura ' . $this->correlativo,
                    'total' => $items->sum('total')*1.13,
                    'cuenta_pagar' => $cuenta->id,
                    'tipo_pago' => 1
                ]);
            }
            DB::commit();
            $this->emit('item-added', 'Compra registrada con exito');
            return Redirect::to('/precompra');
        }
        catch(\Throwable $e)
        {
            DB::rollback();
            \Log::error('ERROR GuardarCompra Precompra', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->emit('scan-notfound', $e->getMessage());
        }
    }
}
