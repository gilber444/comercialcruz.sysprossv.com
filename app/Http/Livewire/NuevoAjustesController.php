<?php
namespace App\Http\Livewire;
use \Cart;
use App\Models\Actividades;
use App\Models\Ajustes;
use App\Models\AjustesDetalles;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Medidas;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\Sucursales;
use App\Models\tmpAjuste;
use App\Traits\GenerarJsonAjuste;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
class NuevoAjustesController extends Component
{
    use GenerarJsonAjuste;
    //variables para el diseño
    public  $pageTitle, $componentName;
    //para los datos de los productos
    public $total, $itemsQuantity, $can = [], $pri = [], $uni = [], $cart = [], $existenciaActual = [], $existenciaAjustada = [];
    public  $productos, $sucursal, $producto, $sucursalOrigen, $cantidad, $fecha, $detalle, $movimiento, $status, $tipo, $search, $selected_id, $select_id, $productoName, $detallePrecios = [], $detalleEscalas = [];
    public function mount()
    {
        $this->pageTitle = 'Nuevo';
        $this->componentName = 'Ajuste';
        $user_id = Auth::user()->id;
        //$this->clearCart();
        $this->fecha = date('Y-m-d');
        $this->tipo = 'Ingreso';
        $acti = Actividades::where('user', $user_id)->whereDate('created_at', Carbon::today())->where('status', 'Activo')->first();
        if ($acti) {
            $this->sucursal = $acti->sucursal;
        }
        $this->Carrito();
    }
    public function Carrito()
    {
        $user_id = Auth::user()->id;
        $ca = tmpAjuste::where('usuario', $user_id)->get();
        if ($ca) {
            foreach ($ca as $c) {
                /*$priceid = precios::where('producto', $c->producto)
                    ->where('medida', $c->unidad)
                    ->whereNull('deleted_at')
                    ->where('costosiva', $c->price)
                    ->first();
                $this->uni[$c->id] = $c->unidad . '|'.$priceid->id;
                $this->pri[$c->id] = $c->price;
                $this->can[$c->id] = $c->quantity;*/
                $priceid = precios::where('producto', $c->producto)
                    ->where('medida', $c->unidad)
                    ->whereNull('deleted_at')
                    ->where('costosiva', $c->price)
                    ->first();
                if ($priceid) {
                    $this->uni[$c->id] = $c->unidad . '|' . $priceid->id;
                    $this->pri[$c->id] = $c->price;
                    $this->can[$c->id] = $c->quantity;
                } else {
                    // Eliminar desde tmpAjuste
                    tmpAjuste::where('id', $c->id)->delete();
                    // Limpia los datos en memoria
                    unset($this->uni[$c->id], $this->pri[$c->id], $this->can[$c->id]);
                }
                if ($this->sucursal) {
                    $inventario = Inventarios::where('producto', $c->producto)
                        ->where('sucursal', $this->sucursal)
                        ->first();
                    $existencia = $inventario->existencia ?? 0;
                } else {
                    $existencia = 0;
                }
                // Guardar existencia actual y ajustada por ID de producto temporal
                $this->existenciaActual[$c->id] = $existencia;
                $tipoLogica = $this->getTipoLogica($this->tipo);
                if ($tipoLogica === 'Egreso') {
                    $this->existenciaAjustada[$c->id] = $existencia - $c->ingreso;
                } else {
                    $this->existenciaAjustada[$c->id] = $c->ingreso + $existencia;
                }
            }
        }
        $this->total = tmpAjuste::where('usuario', $user_id)->sum('total');
        $this->itemsQuantity = tmpAjuste::where('usuario', $user_id)->count();
    }
    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
    public function render()
    {
        $user_id = Auth::user()->id;
        if (in_array(auth()->user()->profile, ['Super', 'Administrador'])) {
            // Usuarios con perfil alto ven todas las sucursales
            $sucursal = Sucursales::all();
        } else {
            // Usuarios normales solo ven su propia sucursal
            $sucursal = Sucursales::where('id', auth()->user()->sucursal)->get();
        }
        $this->cart = tmpAjuste::where('usuario', $user_id)->orderby('id', 'desc')->get();
        return view('livewire.ajustes.nuevo-ajuste', ['sucursales' => $sucursal])
            ->extends('layouts.theme.app')
            ->section('content');
    }
    protected $listeners = [
        'Add' => 'ScanCodeById',
        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'Store' => 'Store',
        'scan-code-byid' => 'ScanCode2',
        'print-ticket' => 'printTicket'
    ];
    public function ScanCodeById($id)
    {
        $this->increaseQty($id);
    }
    public function increaseQty($productId)
    {
        $cant = 1;
        $user_id = Auth::user()->id;
        $product = Precios::with('Rproductos:id,nombreProducto')->find($productId);

        if (!$product) {
            $this->emit('item-error', 'Producto no encontrado');
            return;
        }

        if (blank($product->cantidad) || (float) $product->cantidad <= 0) {
            $this->emit('item-error', "El producto '{$product->Rproductos->nombreProducto}' tiene medida con cantidad 0. Corrígalo antes de agregarlo.");
            return;
        }

        if (blank($product->costosiva) || (float) $product->costosiva <= 0) {
            $this->emit('item-error', "El producto '{$product->Rproductos->nombreProducto}' tiene precio de costo 0. Corrígalo antes de agregarlo.");
            return;
        }

        $tmp = tmpAjuste::create([
            'producto' => $product->producto,
            'name' => $product->Rproductos->nombreProducto,
            'price' => $product->costosiva,
            'quantity' => $cant,
            'sucursal' => NULL,
            'codebar' => $product->codebar,
            'unidad' => $product->medida,
            'medida' => $product->presentacion,
            'total' => $cant * $product->costosiva,
            'limit' => $product->cantidad,
            'ingreso' => $cant * $product->cantidad,
            'inventario' => Null,
            'usuario' => $user_id
        ]);

        $this->Carrito();
        $this->emit('focus-input', ['id' => $tmp->id]);
    }
    public function updateUni($id)
    {
        $user_id = Auth::user()->id;

        if (empty($this->uni[$id]) || !str_contains($this->uni[$id], '|')) {
            $this->emit('item-error', 'Medida seleccionada inválida.');
            return;
        }

        $valor = $this->uni[$id];
        [$medidaId, $precio] = explode('|', $valor);

        $tmp = tmpAjuste::find($id);
        if (!$tmp) {
            $this->emit('item-error', 'Item no encontrado en el carrito.');
            return;
        }

        $PreCan = Precios::find($precio);
        if (!$PreCan) {
            $this->emit('item-error', "El precio/medida seleccionado para '{$tmp->name}' ya no existe.");
            return;
        }

        if (blank($PreCan->cantidad) || (float) $PreCan->cantidad <= 0) {
            $this->emit('item-error', "La medida de '{$tmp->name}' tiene cantidad 0. Seleccione otra medida o corrígala.");
            return;
        }

        if (blank($PreCan->costosiva) || (float) $PreCan->costosiva <= 0) {
            $this->emit('item-error', "La medida de '{$tmp->name}' tiene precio de costo 0. Seleccione otra medida o corrígala.");
            return;
        }

        $tmp->unidad = $medidaId;
        $tmp->limit = $PreCan->cantidad;
        $tmp->price = $PreCan->costosiva;
        $tmp->ingreso = $tmp->quantity * $PreCan->cantidad;
        $tmp->total = $PreCan->costosiva * $tmp->quantity;
        $tmp->save();
        $this->Carrito();
    }
    public function updateQty($id)
    {
        $cantidades = $this->can[$id];

        if (blank($cantidades) || $cantidades <= 0) {
            $this->can[$id] = 1;
            $this->emit('item-error', 'La cantidad debe ser mayor a 0');
            return;
        }

        $tmp = tmpAjuste::find($id);
        if (!$tmp) {
            return;
        }

        if (blank($tmp->limit) || (float) $tmp->limit <= 0) {
            $this->emit('item-error', "El producto '{$tmp->name}' tiene cantidad por medida en 0. Elimínelo y vuélvalo a agregar.");
            return;
        }

        if (blank($tmp->price) || (float) $tmp->price <= 0) {
            $this->emit('item-error', "El producto '{$tmp->name}' tiene precio en 0. Elimínelo y vuélvalo a agregar.");
            return;
        }

        $tmp->quantity = $cantidades;
        $tmp->ingreso = $cantidades * $tmp->limit;
        $tmp->total = $tmp->price * $cantidades;
        $tmp->save();
        $this->Carrito();
    }
    public function ScanCode2($barcode, $cant = 1)
    {
        //dd($barcode);
        $user = Auth::user();
        /*$product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
        ->join('inventarios as i', 'i.producto', 'productos.id')
        ->where('p.id',  $barcode )
        ->where('p.escala', 'No')
        ->whereNull('p.deleted_at')
        ->where('i.sucursal', session('sucursal'))
        ->select('productos.id', 'productos.nombreProducto', 'i.existencia', 'p.codebar', 'i.id as inventario', 'p.pvventa', 'p.cantidad', 'i.sucursal', 'p.presentacion', 'p.cantidad as descargar', 'p.medida', 'p.id as pprecio', 'productos.familia')
        ->first();*/
        $product = Productos::query()
            ->select([
                'productos.id',
                'productos.nombreProducto',
                'i.existencia',
                'p.codebar',
                'i.id as inventario',
                'p.pvventa',
                'p.cantidad',
                'i.sucursal',
                'p.presentacion',
                'p.cantidad as descargar',
                'p.medida',
                'p.id as pprecio',
                'productos.familia',
            ])
            ->join('precios as p', 'p.producto', '=', 'productos.id')
            ->leftJoin('inventarios as i', function ($join) {
                $join->on('i.producto', '=', 'productos.id')
                    ->where('i.sucursal', $this->sucursal);
            })
            ->where('p.id', $barcode)
            ->where('p.escala', 'No')
            ->whereNull('p.deleted_at')
            ->first();
        if ($product) {
            /*$this->productoName = $product->nombreProducto;
            $this->detallePrecios = Precios::where('producto', $product->id)->where('escala', 'No')
            ->orderBy('cantidad', 'asc')
            ->get();
            $this->detalleEscalas = Precios::where('producto', $product->id)->where('escala', 'Si')
            ->orderBy('cantidad', 'asc')
            ->get();
            //dd($this->detallePrecios);*/
            $this->productoName = $product->nombreProducto;
            // Usar una sola consulta base para evitar código duplicado
            $preciosQuery = Precios::where('producto', $product->id)
                ->orderBy('cantidad', 'asc');
            $this->detallePrecios = (clone $preciosQuery)->where('escala', 'No')->get();
            $this->detalleEscalas = (clone $preciosQuery)->where('escala', 'Si')->get();
            $this->emit('abrirModal', 'detalleprecios');
        } else {
            $this->emit('item-error', 'Producto no encontrado');
        }
    }
    public function ScanCode($barcode, $cant = 1)
    {
        //dd($barcode);
        $user_id = Auth::user()->id;
        $product = Productos::join('precios as p', 'productos.id', '=', 'p.producto')
            ->leftJoin('inventarios as i', 'i.producto', 'productos.id')
            ->where('p.codebar', 'like', '%' . $barcode . '%')
            ->select('productos.id')
            ->first();
        if ($product) {
            $this->ScanCode2($product->id);
        } else {
            $this->emit('item-error', 'El producto no esta registrado');
        }
    }
    public function InCart($productId)
    {
        $user_id = Auth::user()->id;
        $exist = tmpAjuste::where('usuario', $user_id)->get();
        if ($exist)
            return true;
        else
            return false;
    }
    public function removeItem($productId)
    {
        $user_id = Auth::user()->id;
        $tmp = tmpAjuste::find($productId);
        $tmp->delete();
        $this->Carrito();
    }
    public function clearCart()
    {
        $user_id = Auth::user()->id;
        $tmp = tmpAjuste::where('usuario', $user_id)->delete();
        $this->Carrito();
    }
    /**
     * Determina si un tipo de ajuste es lógicamente un Ingreso o un Egreso de inventario.
     */
    private function getTipoLogica(?string $tipo): string
    {
        $tiposIngreso = [
            'Ingreso Fac. Comercial',
            'Ingreso por Traslado',
            'Ingreso',
        ];

        return in_array(trim($tipo ?? ''), $tiposIngreso, true) ? 'Ingreso' : 'Egreso';
    }

    public function Store()
    {
        $user = Auth::user();
        $user_id = $user->id;

        $rules = [
            'sucursal' => 'required',
            'fecha' => 'required',
            'detalle' => 'required',
            'tipo'     => 'required',
        ];
        $messages = [
            'sucursal.required' => 'La sucursal es requerida',
            'fecha.required' => 'La fecha del ingreso es requerida',
            'detalle.required' => 'El detalle del ingreso es requerido',
            'tipo.required'     => 'El tipo de ajuste es requerido',
        ];
        $this->validate($rules, $messages);

        $items = tmpAjuste::where('usuario', $user_id)->get();
        if ($items->isEmpty()) {
            $this->emit('item-error', 'No hay items para procesar. Agregue al menos un producto al carrito.');
            return;
        }

        $tipo = trim($this->tipo);
        $tipo_logica = $this->getTipoLogica($tipo);

        // =================================================================
        // VALIDACIÓN EXHAUSTIVA PREVIA: si falla UN SOLO producto, NO se
        // crea el ajuste, NO se toca inventario y se alerta al usuario.
        // =================================================================
        $errores = [];
        foreach ($items as $item) {
            // 1. El producto debe existir
            $producto = Productos::find($item->producto);
            if (!$producto) {
                $errores[] = "El producto '{$item->name}' ya no existe en el catálogo.";
                continue;
            }

            // 2. La medida/precio debe seguir vigente
            $precio = Precios::where('producto', $item->producto)
                ->where('medida', $item->unidad)
                ->whereNull('deleted_at')
                ->first();
            if (!$precio) {
                $errores[] = "El producto '{$item->name}' tiene una medida/precio inválido o fue eliminado.";
                continue;
            } 

            // 3. Cantidades deben ser válidas y mayores a cero
            if (blank($item->quantity) || $item->quantity <= 0) {
                $errores[] = "El producto '{$item->name}' tiene cantidad inválida ({$item->quantity}).";
            }
            if (blank($item->ingreso) || $item->ingreso <= 0) {
                $errores[] = "El producto '{$item->name}' tiene cantidad de ajuste inválida ({$item->ingreso}).";
            }
            if (blank($item->price) || $item->price <= 0) {
                $errores[] = "El producto '{$item->name}' tiene precio inválido ({$item->price}).";
            }

            // 4. Para egresos, debe existir stock suficiente
            if ($tipo_logica === 'Egreso') {
                $existencia = Inventarios::where('producto', $item->producto)
                    ->where('sucursal', $this->sucursal)
                    ->first();
                $stockActual = $existencia ? (float) $existencia->existencia : 0;
                if ($item->ingreso > $stockActual) {
                    $errores[] = "Stock insuficiente para '{$item->name}'. Disponible: {$stockActual}, requerido: {$item->ingreso}.";
                }
            }
        }

        if (!empty($errores)) {
            $mensaje = "No se puede guardar el ajuste por los siguientes errores:\n\n• " . implode("\n• ", $errores);
            $this->emit('item-error', $mensaje);
            return;
        }
        // =================================================================
        // FIN VALIDACIÓN PREVIA
        // =================================================================

        $appModo = config('app.modo', 'local');
        $sucursalModo = Sucursales::find($this->sucursal)?->modo ?? 'local';
        $esLocalYSeAplica = $sucursalModo === $appModo;

        try {
            \Log::info('INICIO Store Ajuste', [
                'tipo' => $tipo,
                'tipo_logica' => $tipo_logica,
                'items_count' => $items->count(),
                'sucursal' => $this->sucursal,
                'user_id' => $user_id,
            ]);

            $ajuste = DB::transaction(function () use ($items, $tipo, $tipo_logica, $esLocalYSeAplica, $user, $user_id) {
                $ajuste = Ajustes::create([
                    'sucursal' => $this->sucursal,
                    'fecha' => $this->fecha,
                    'detalle' => $this->detalle,
                    'status' => $esLocalYSeAplica ? 'Finalizado' : 'Ingresado',
                    'tipo' => $tipo,
                    'user' => $user_id,
                    'aplicado_local' => $esLocalYSeAplica ? now() : null
                ]);

                \Log::info('Ajuste padre creado', ['ajuste_id' => $ajuste->id, 'tipo' => $tipo]);

                if (!$ajuste || !$ajuste->id) {
                    throw new \Exception('No se pudo crear el encabezado del ajuste.');
                }

                foreach ($items as $index => $item) {
                    \Log::debug("Procesando item #{$index} para ajuste #{$ajuste->id}", [
                        'tmp_id' => $item->id,
                        'producto' => $item->producto,
                        'name' => $item->name,
                        'unidad' => $item->unidad,
                        'quantity' => $item->quantity,
                        'ingreso' => $item->ingreso,
                        'price' => $item->price,
                    ]);

                    // Bloqueo pesimista para evitar condiciones de carrera en inventario
                    $existencia = Inventarios::where('producto', $item->producto)
                        ->where('sucursal', $this->sucursal)
                        ->lockForUpdate()
                        ->first();

                    if (!$existencia) {
                        $existencia = Inventarios::create([
                            'producto' => $item->producto,
                            'sucursal' => $this->sucursal,
                            'existencia' => 0.00,
                        ]);
                    }

                    if (!$existencia || !$existencia->id) {
                        throw new \Exception("No se pudo obtener o crear inventario para el producto '{$item->name}'.");
                    }

                    // Recálculo defensivo del total del detalle
                    $totalDetalle = (float) $item->price * (float) $item->quantity;

                    \Log::info('Creando detalle', [
                        'ajuste_id' => $ajuste->id,
                        'producto' => $item->producto,
                        'inventario' => $existencia->id,
                        'medida' => $item->unidad,
                        'cantidad' => $item->quantity,
                        'ingreso' => $item->ingreso,
                        'costo' => $item->price,
                        'total' => $totalDetalle,
                    ]);

                    $detalleData = [
                        'ajuste' => $ajuste->id,
                        'producto' => $item->producto,
                        'inventario' => $existencia->id,
                        'medida' => $item->unidad,
                        'cantidad' => $item->quantity,
                        'ingreso' => $item->ingreso,
                        'costo' => $item->price,
                        'total' => $totalDetalle,
                        'status' => $esLocalYSeAplica ? 'Finalizado' : 'Ingresado',
                        'aplicado_local' => $esLocalYSeAplica ? now() : null,
                        'sincro_id' => (string) \Illuminate\Support\Str::uuid(),
                    ];

                    \Log::info('Insertando detalle', [
                        'ajuste_id' => $ajuste->id ?? null,
                        'producto_id' => $item->producto ?? null,
                    ]);

                    $detalleCreado = AjustesDetalles::create($detalleData);

                    \Log::info('Resultado create detalle', [
                        'detalle_creado' => $detalleCreado ? 'SI' : 'NO',
                        'detalle_id' => $detalleCreado->id ?? 'NULL',
                    ]);

                    if (!$detalleCreado || !$detalleCreado->id) {
                        throw new \Exception("No se pudo crear el detalle del ajuste para el producto '{$item->name}'.");
                    }

                    \Log::debug("Detalle creado correctamente", [
                        'detalle_id' => $detalleCreado->id,
                        'ajuste_id' => $ajuste->id,
                        'producto' => $item->producto,
                    ]);

                    if ($esLocalYSeAplica) {
                        if ($tipo_logica == 'Ingreso') {
                            $nuevaExistencia = (float) $existencia->existencia + (float) $item->ingreso;
                            $existencia->existencia = $nuevaExistencia;
                            $existencia->save();

                            $saldoCantidad = $nuevaExistencia;
                            $saldoValor = $saldoCantidad * ((float) $item->price / (float) ($item->limit ?: 1));

                            Kardex::create([
                                'producto' => $item->producto,
                                'inventario' => $existencia->id,
                                'descripcion' => 'Ingreso por Ajuste #' . $ajuste->id . ' ' . $this->detalle . ', realizado por ' . $user->name,
                                'fecha' => date('Y-m-d'),
                                'hora' => date('H:i:s'),
                                'ingresoCantidad' => $item->ingreso,
                                'ingresoValor' => $totalDetalle,
                                'egresoCantidad' => 0.00,
                                'egresoValor' => 0.00,
                                'saldoCantidad' => $saldoCantidad,
                                'saldoValor' => $saldoValor,
                                'user' => $user_id
                            ]);
                        } else {
                            // Como ya validamos stock previamente, hacemos la resta directa
                            $nuevaExistencia = (float) $existencia->existencia - (float) $item->ingreso;
                            $existencia->existencia = $nuevaExistencia;
                            $existencia->save();

                            $ultiR = Kardex::where('producto', $item->producto)
                                ->where('inventario', $existencia->id)
                                ->latest()
                                ->first();
                            $saldoCantidad = $nuevaExistencia;
                            $saldoValor = $ultiR ? (float) $ultiR->saldoValor - $totalDetalle : 0;

                            Kardex::create([
                                'producto' => $item->producto,
                                'inventario' => $existencia->id,
                                'descripcion' => 'Egreso por Ajuste #' . $ajuste->id . ' ' . $this->detalle . ', realizado por ' . $user->name,
                                'fecha' => date('Y-m-d'),
                                'hora' => date('H:i:s'),
                                'ingresoCantidad' => 0.00,
                                'ingresoValor' => 0.00,
                                'egresoCantidad' => $item->ingreso,
                                'egresoValor' => $totalDetalle,
                                'saldoCantidad' => $saldoCantidad,
                                'saldoValor' => $saldoValor,
                                'user' => $user_id
                            ]);
                        }
                    }
                }

                // Verificación defensiva final: confirmar que se crearon todos los detalles
                $detallesCreados = AjustesDetalles::where('ajuste', $ajuste->id)->count();
                \Log::info('Verificación defensiva', [
                    'ajuste_id' => $ajuste->id,
                    'detalles_esperados' => $items->count(),
                    'detalles_creados' => $detallesCreados,
                ]);
                if ($detallesCreados !== $items->count()) {
                    throw new \Exception("Inconsistencia en detalles: se esperaban {$items->count()} registros pero se crearon {$detallesCreados}.");
                }

                \Log::info('FIN transacción exitosa', ['ajuste_id' => $ajuste->id]);
                return $ajuste;
            });

            // Solo si la transacción fue exitosa llegamos aquí
            $this->clearCart();
            $this->emit('print-ticket2', $this->GenerarJsonAjuste($ajuste->id));
            $this->ResetUI();
            $this->emit('ajuste-procesado', 'Ajuste procesado con exito');
        } catch (\Throwable $e) {
            \Log::error('Error en Store NuevoAjuste: ' . $e->getMessage(), [
                'user' => $user_id,
                'sucursal' => $this->sucursal,
                'tipo' => $this->tipo,
            ]);
            $this->emit('item-error', 'Hubo un error al registrar el ajuste: ' . $e->getMessage());
        }
    }
    public function resetUI()
    {
        $this->sucursalOrigen = 'Elegir sucursal';
        $this->detalle = '';
    }
    public function cargaSucursal()
    {
        $this->emitTo('modal-ajuste', 'updateSucursal', $this->sucursal);
    }
}
