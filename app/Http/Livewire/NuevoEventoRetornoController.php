<?php

namespace App\Http\Livewire;

use App\Models\dte;
use App\Models\EventoRetorno;
use App\Models\EventoRetornoDetalle;
use App\Models\Kardex;
use App\Models\Precios;
use App\Models\Sucursales;
use App\Models\TempEventoRetorno;
use App\Models\Ventas;
use App\Models\VentasDetalles;
use App\Services\GeneradorEventoRetorno;
use App\Services\InventarioSucursalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Alta del Evento de Retorno (CAT-002 codigo 18).
 *
 * Solo aplica a Factura, Factura de Exportacion y Sujeto Excluido con sello de
 * recepcion. El Credito Fiscal se corrige con Nota de Credito, no con este evento.
 *
 * Al guardar, los productos vuelven al inventario y se registra el ingreso en el
 * Kardex; la cabecera queda marcada con 'reingresado' para que un reproceso del
 * evento no los sume dos veces.
 */
class NuevoEventoRetornoController extends Component
{
    use WithPagination;

    public $pageTitle, $componentName;

    public $search = '';
    public $venta;
    public $fecha;
    public $cantidades = [];

    /** Motivos por los que la factura elegida no admite retorno. */
    public $motivos = [];

    public function mount()
    {
        $this->pageTitle = 'Nuevo Evento de Retorno';
        $this->componentName = 'Documentos DTE';
        $this->fecha = now()->format('Y-m-d');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $cart = collect();
        $facturas = collect();
        $ventaSel = null;

        // render() no puede reventar: una relacion o tabla faltante dejaria al
        // usuario en la pantalla de excepcion sin poder salir.
        try {
            $cart = TempEventoRetorno::where('usuario', Auth::id())->orderBy('id')->get();
            $facturas = $this->facturas();
            $ventaSel = $this->venta ? Ventas::find($this->venta) : null;
        } catch (\Throwable $e) {
            $this->reportar($e, 'No se pudo cargar la pantalla de retornos');
        }

        return view('livewire.evento_retorno.nuevo', [
            'facturas' => $facturas,
            'cart' => $cart,
            'totalRetorno' => round($cart->sum('total'), 2),
            'ventaSel' => $ventaSel,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    /**
     * Avisa al usuario con item-error y deja la traza completa en el log. Que no
     * vea la pantalla de excepcion no significa esconder el problema.
     */
    private function reportar(\Throwable $e, string $contexto): void
    {
        Log::error($contexto, [
            'componente' => static::class,
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile() . ':' . $e->getLine(),
        ]);

        $this->emit('item-error', $contexto . ': ' . $e->getMessage());
    }

    /**
     * Solo facturas con DTE sellado de los tipos que admiten retorno y dentro
     * del plazo. La busqueda es por correlativo, codigo de generacion o cliente.
     */
    private function facturas()
    {
        if ($this->venta) {
            return collect();
        }

        $usuario = Auth::user();

        return Ventas::query()
            ->join('dtes as d', 'd.venta', 'ventas.id')
            ->join('tipo_documentos as td', 'td.id', 'd.tipoDte')
            ->leftJoin('clientes as c', 'c.id', 'ventas.cliente')
            ->where('ventas.empresa', $usuario->empresa)
            ->whereNotNull('d.sello')
            ->where('d.sello', '!=', '')
            // La invalidacion de este proyecto deja dtes.estado = 'INVALIDADO'.
            ->where(function ($q) {
                $q->whereNull('d.estado')->orWhere('d.estado', '!=', 'INVALIDADO');
            })
            ->whereIn('td.codigo', EventoRetorno::codigosAplicables())
            ->when(trim($this->search) !== '', function ($q) {
                $s = trim($this->search);
                $q->where(function ($w) use ($s) {
                    $w->where('ventas.correlativo', 'like', '%' . $s . '%')
                        ->orWhere('d.codigoGeneracion', 'like', '%' . $s . '%')
                        ->orWhere('c.nombreCliente', 'like', '%' . $s . '%');
                });
            })
            // Mas reciente primero; el id desempata dentro del mismo dia.
            ->orderByDesc('ventas.fecha')
            ->orderByDesc('ventas.id')
            ->limit(15)
            ->select(
                'ventas.id',
                'ventas.correlativo',
                'ventas.fecha',
                'ventas.total',
                'c.nombreCliente as cliente',
                'd.codigoGeneracion',
                'd.fecEmi',
                'td.codigo as tipoCodigo',
                'td.valor as tipoNombre'
            )
            ->get();
    }

    public function Seleccionar($ventaId)
    {
        try {
            $venta = Ventas::find($ventaId);

            if (!$venta) {
                $this->emit('item-error', 'No se encontro la venta.');
                return;
            }

            $this->motivos = app(GeneradorEventoRetorno::class)->motivosNoAplicable($venta, $this->fecha);

            if ($this->motivos) {
                $this->venta = null;
                return;
            }

            $this->venta = $venta->id;
            $this->CargarItems();
        } catch (\Throwable $e) {
            $this->venta = null;
            $this->reportar($e, 'No se pudo cargar la factura');
        }
    }

    /**
     * Trae los items de la factura al carrito con cantidad 0, descontando lo que
     * ya se retorno en eventos anteriores sobre la misma linea.
     */
    private function CargarItems()
    {
        $usuario = Auth::user();

        TempEventoRetorno::where('usuario', $usuario->id)->delete();

        $detalles = VentasDetalles::with('Rproductos')->where('venta', $this->venta)->get();

        foreach ($detalles as $d) {
            $disponible = round(((float) $d->cantidad) - $this->retornadoDeLinea($d->id), 2);

            if ($disponible <= 0) {
                continue;
            }

            TempEventoRetorno::create([
                'usuario' => $usuario->id,
                'venta' => $d->venta,
                'ventaDetalle' => $d->id,
                'producto' => $d->producto,
                'name' => $d->name,
                'unidad' => $d->unidad,
                'uniMedida' => (int) (optional(optional($d->Rproductos)->RmedidasMH)->codigo ?: 59),
                'cantidad' => 0,
                'vendida' => $disponible,
                'descargar' => 0,
                'precio' => $d->precio,
                'descuento' => $d->descuento,
                'iva' => 0,
                'total' => 0,
            ]);
        }

        $this->cantidades = [];

        if (TempEventoRetorno::where('usuario', $usuario->id)->count() === 0) {
            $this->emit('item-error', 'Esta factura ya fue retornada en su totalidad.');
            $this->venta = null;
        }
    }

    /** Cantidad ya retornada de una linea de la factura, en eventos vigentes. */
    private function retornadoDeLinea(int $ventaDetalleId, ?int $excluirEvento = null): float
    {
        return (float) EventoRetornoDetalle::query()
            ->join('evento_retornos as e', 'e.id', 'evento_retorno_detalles.evento')
            ->where('evento_retorno_detalles.ventaDetalle', $ventaDetalleId)
            ->whereNull('e.deleted_at')
            ->whereNotIn('e.estado', ['RECHAZADO', 'Anulado'])
            ->when($excluirEvento, fn ($q) => $q->where('e.id', '!=', $excluirEvento))
            ->sum('evento_retorno_detalles.cantidad');
    }

    public function updateCantidad($id)
    {
        try {
            $this->aplicarCantidad($id);
        } catch (\Throwable $e) {
            $this->reportar($e, 'No se pudo actualizar la cantidad');
        }
    }

    private function aplicarCantidad($id): void
    {
        $fila = TempEventoRetorno::find($id);

        if (!$fila) {
            return;
        }

        $cantidad = round((float) ($this->cantidades[$id] ?? 0), 2);

        if ($cantidad < 0) {
            $cantidad = 0;
        }

        if ($cantidad > (float) $fila->vendida) {
            $cantidad = (float) $fila->vendida;
            $this->emit('item-error', 'No puede retornar mas de ' . $fila->vendida . ' de "' . $fila->name . '".');
        }

        // El precio de la factura ya incluye IVA (igual que en la Factura DTE).
        // En ventas_detalles el descuento es POR UNIDAD (el POS cobra
        // precio - descuento por cada unidad), asi que se resta igual.
        $neto = max(0, (float) $fila->precio - (float) $fila->descuento);
        $total = round($cantidad * $neto, 8);
        $iva = round($total - ($total / 1.13), 8);

        $fila->cantidad = $cantidad;
        $fila->total = $total;
        $fila->iva = $iva;
        $fila->descargar = $this->descargarProporcional($fila, $cantidad);
        $fila->save();

        $this->cantidades[$id] = $cantidad;
    }

    /**
     * Lo que vuelve fisicamente al inventario. La venta descarga "descargar",
     * que no siempre coincide con "cantidad" (presentaciones), asi que el
     * reingreso se hace en la misma proporcion que se devuelve.
     */
    private function descargarProporcional(TempEventoRetorno $fila, float $cantidad): float
    {
        $detalle = VentasDetalles::find($fila->ventaDetalle);

        if (!$detalle || (float) $detalle->cantidad == 0.0) {
            return $cantidad;
        }

        return round(((float) $detalle->descargar) * ($cantidad / (float) $detalle->cantidad), 2);
    }

    public function Cancelar()
    {
        try {
            TempEventoRetorno::where('usuario', Auth::id())->delete();
        } catch (\Throwable $e) {
            $this->reportar($e, 'No se pudo limpiar el carrito');
        }

        $this->reset(['venta', 'search', 'cantidades', 'motivos']);
        $this->fecha = now()->format('Y-m-d');
    }

    public function Guardar()
    {
        // Todo dentro del try: cualquier fallo (relacion, tabla, inventario)
        // sale por item-error y queda en el log, nunca como pantalla de error.
        try {
            $this->guardarEvento();
        } catch (\Throwable $e) {
            $this->reportar($e, 'No se pudo guardar el evento de retorno');
        }
    }

    private function guardarEvento(): void
    {
        $usuario = Auth::user();

        $cart = TempEventoRetorno::with('Rproducto')
            ->where('usuario', $usuario->id)
            ->where('cantidad', '>', 0)
            ->get();

        if ($cart->isEmpty()) {
            $this->emit('item-error', 'Indique la cantidad a retornar en al menos un producto.');
            return;
        }

        $venta = $this->venta ? Ventas::find($this->venta) : null;

        if (!$venta) {
            $this->emit('item-error', 'La factura seleccionada ya no esta disponible. Vuelva a elegirla.');
            $this->venta = null;
            return;
        }

        $generador = app(GeneradorEventoRetorno::class);
        $motivos = $generador->motivosNoAplicable($venta, $this->fecha);

        if ($motivos) {
            $this->motivos = $motivos;
            $this->emit('item-error', implode(' ', $motivos));
            return;
        }

        $dteVenta = dte::where('venta', $venta->id)->latest('id')->first();
        $sucursal = Sucursales::find($venta->sucursal);

        if (!$dteVenta) {
            $this->emit('item-error', 'La venta no tiene un DTE asociado.');
            return;
        }

        if (!$sucursal) {
            $this->emit('item-error', 'No se encontro la sucursal de la venta.');
            return;
        }

        // El tope del monto se revisa ANTES de tocar inventario: si se dejara
        // para el generador, el stock ya habria entrado aunque el evento no valga.
        $monto = round((float) $cart->sum('total'), 2);
        $previo = round($generador->retornadoPrevio($venta->id), 2);
        $tope = round((float) $venta->total, 2);

        if (round($monto + $previo, 2) > $tope) {
            $this->emit('item-error', 'El monto a retornar (' . number_format($monto, 2) . ') mas lo ya retornado ('
                . number_format($previo, 2) . ') supera el total del DTE (' . number_format($tope, 2) . ').');
            return;
        }

        $eventoId = null;

        DB::transaction(function () use ($usuario, $venta, $dteVenta, $sucursal, $cart, &$eventoId) {
            $evento = EventoRetorno::create([
                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                'ambiente' => $dteVenta->ambiente,
                'tipoModelo' => 1,
                'tipoOperacion' => 1,
                'fecEmi' => $this->fecha,
                'horEmi' => now()->format('H:i:s'),
                'venta' => $venta->id,
                'dte' => $dteVenta->id,
                'cliente' => $venta->cliente,
                'responsable' => $usuario->id,
                'caja' => $venta->caja,
                'sucursal' => $venta->sucursal,
                'empresa' => $venta->empresa,
                'estado' => 'Creado',
                'reingresado' => false,
            ]);

            $numItem = 0;
            $totalGravada = 0.0;
            $totalIva = 0.0;

            foreach ($cart as $fila) {
                // Otra pantalla pudo retornar la misma linea mientras tanto.
                $detalleVenta = VentasDetalles::find($fila->ventaDetalle);
                $disponible = round(
                    (float) optional($detalleVenta)->cantidad - $this->retornadoDeLinea((int) $fila->ventaDetalle, $evento->id),
                    2
                );

                if ((float) $fila->cantidad > $disponible) {
                    throw new \RuntimeException('Ya no puede retornar ' . $fila->cantidad . ' de "' . $fila->name
                        . '": solo quedan ' . max(0, $disponible) . ' disponibles. Vuelva a elegir la factura.');
                }

                $numItem++;
                $totalGravada += (float) $fila->total;
                $totalIva += (float) $fila->iva;

                $cantidad = (float) $fila->cantidad;
                $descuentoUnitario = max(0, (float) $fila->descuento);

                EventoRetornoDetalle::create([
                    'evento' => $evento->id,
                    'numItem' => $numItem,
                    'tipoItem' => 1,
                    'producto' => $fila->producto,
                    'ventaDetalle' => $fila->ventaDetalle,
                    'codigoGeneracion' => $dteVenta->codigoGeneracion,
                    'codigo' => optional($fila->Rproducto)->codebar3,
                    'descripcion' => $fila->name,
                    'cantidad' => $cantidad,
                    'descargar' => $fila->descargar,
                    'uniMedida' => $fila->uniMedida,
                    'precioUni' => $fila->precio,
                    // El descuento del POS es por unidad: aqui va el del item.
                    'montoDescu' => round($descuentoUnitario * $cantidad, 4),
                    'ventaNoSuj' => 0,
                    'ventaExenta' => 0,
                    'ventaGravada' => $fila->total,
                    'ivaItem' => $fila->iva,
                    'psv' => 0,
                    'noGravado' => 0,
                ]);

                $this->reingresarInventario($fila, $sucursal, $venta, $detalleVenta);
            }

            $evento->totalGravada = round($totalGravada, 4);
            $evento->subTotalVentas = round($totalGravada, 4);
            $evento->totalIva = round($totalIva, 4);
            $evento->montoTotalOperacion = round($totalGravada, 4);
            $evento->totalPagar = round($totalGravada, 4);
            // Bandera: el stock ya entro; un reproceso o reenvio no lo vuelve a sumar.
            $evento->reingresado = true;
            $evento->save();

            $eventoId = $evento->id;
        });

        TempEventoRetorno::where('usuario', $usuario->id)->delete();
        $this->reset(['venta', 'search', 'cantidades', 'motivos']);
        $this->fecha = now()->format('Y-m-d');

        // El JSON se arma fuera de la transaccion: si falla, el evento queda
        // guardado (con su reingreso) y se regenera desde el listado al transmitir.
        try {
            $generador->generar($eventoId);
        } catch (\Throwable $e) {
            Log::warning('Evento de retorno guardado sin JSON', ['evento' => $eventoId, 'mensaje' => $e->getMessage()]);
            $this->emit('item-error', 'El evento se guardo y el inventario se reingreso, pero no se pudo generar el JSON: '
                . $e->getMessage() . ' Puede reintentarlo desde Eventos de Retorno.');
            return;
        }

        $this->emit('item-added', 'Evento de retorno creado. Ya puede transmitirlo a Hacienda.');
    }

    /**
     * Devuelve el producto al stock y deja el movimiento en el Kardex como
     * ingreso. El inventario se obtiene con InventarioSucursalService: en el
     * VPS lo crea si falta; en una PC local lo jala del VPS.
     */
    private function reingresarInventario(TempEventoRetorno $fila, Sucursales $sucursal, Ventas $venta, ?VentasDetalles $detalleVenta): void
    {
        if (!$fila->producto || (float) $fila->descargar <= 0) {
            return;
        }

        $inventario = InventarioSucursalService::obtener((int) $fila->producto, (int) $sucursal->id, true);

        $nuevoStock = (float) $inventario->existencia + (float) $fila->descargar;
        $inventario->existencia = $nuevoStock;
        $inventario->save();

        // Mismo criterio del POS: el precio de la presentacion vendida
        // (ventas_detalles.medida = precios.medida); si no, el primero del producto.
        $precio = null;
        if ($detalleVenta && $detalleVenta->medida) {
            $precio = Precios::where('producto', $fila->producto)->where('medida', $detalleVenta->medida)->first();
        }
        $precio = $precio ?: Precios::where('producto', $fila->producto)->first();

        $costo = $precio ? (float) $precio->costociva : 0;

        Kardex::create([
            'producto' => $fila->producto,
            'inventario' => $inventario->id,
            'sucursal' => $sucursal->id,
            'descripcion' => 'Evento de retorno sobre venta numero ' . $venta->correlativo
                . ' realizado por ' . (string) optional(Auth::user())->name,
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:s'),
            'ingresoCantidad' => $fila->descargar,
            'ingresoValor' => $costo * (float) $fila->descargar,
            'egresoCantidad' => 0,
            'egresoValor' => 0,
            'saldoCantidad' => $nuevoStock,
            'saldoValor' => $nuevoStock * $costo,
        ]);
    }
}
