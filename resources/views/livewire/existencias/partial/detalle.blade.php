@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;
@endphp
@if ($itemsQuantity > 0)
    <div class="row p-2">
        <div style="max-height: 200px; overflow-y: auto;">
            <table class="table table-hover table-sm">
                <thead>
                    <th class="text-center">CODIGO</th>
                    <th class="text-center">DESCRIPCION</th>
                    <th class="text-center">MEDIDA</th>
                    <th class="text-center">CANTIDAD</th>
                    <th class="text-center">COSTO</th>
                    <th class="text-center">TOTAL</th>
                    <th class="text-center"></th>
                </thead>
                <tbody>

                    @forelse ($cart as $item)
                        <tr>
                            <td class="text-center">{{ $item->codebar }}</td>
                            <td 
                            {{-- style="cursor: pointer"  --}}
                            {{-- class="product-cell" --}}
                                {{-- wire:click="mostrarInfoProducto({{ $item->id }})" --}}
                                >
                                <h6>{{ $item->name }}</h6>
                            </td>
                            <td class="text-center" width='25%'>
                                @php
                                    $minCant = DB::table('precios')
                                        ->where('producto', $item->producto)
                                        ->min('cantidad');

                                    $maxCant = DB::table('precios')
                                        ->where('producto', $item->producto)
                                        ->max('cantidad');

                                    $medidas = DB::table('precios')
                                        ->where('producto', $item->producto)
                                        ->whereIn('cantidad', [$minCant, $maxCant])
                                        ->get();
                                @endphp

                                {{-- <select class="form-control form-control-sm" wire:model.lazy="uni.{{ $item->id }}"
                                    wire:change="updateUni({{ $item->id }})">
                                    <option value="">Elegir</option>
                                    @foreach ($medidas as $medi)
                                        <option value="{{ $medi->medida }}">{{ $medi->presentacion }}</option>
                                    @endforeach
                                </select> --}}
                                {{ $item->medida }}
                            </td>
                            <td class="text-center" width='15%'>
                                <input type="text" class="form-control form-control-sm" id="can-{{ $item->id }}"
                                    wire:model="can.{{ $item->id }}"
                                    wire:keydown.enter="updateQty({{ $item->id }})"
                                    onclick="this.select()">{{ $item->descargar }}
                            </td>
                            <td class="text-end"> $ {{ number_format($item->costo, 2) }} /
                                {{ number_format($item->costo * 1.13, 2) }}</td>
                            <td class="text-end"> $ {{ number_format($item->costo * $item->cantidad, 2) }} /
                                {{ number_format($item->costo * 1.13 * $item->cantidad, 2) }}</td>
                            <td class="text-center">
                                <button wire:click.prevent='removeItem({{ $item->id }})' type="button"
                                    class="btn btn-sm btn-label-primary">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">SIN RESULTADOS</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="alert alert-primary mb-4" role="alert">
        <div class="d-flex gap-3 text-center">
            <div class="flex-shrink-0">
                <span class="badge badge-center rounded-pill bg-primary border-label-primary p-5 me-2">
                    <i class="fa-solid fa-cart-shopping fs-1"></i>
                </span>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">Agrega productos a la Solicitud</div>
            </div>
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Mantiene el evento de enfoque actual
        window.livewire.on('focus-input', function(event) {
            setTimeout(() => {
                const input = document.getElementById('can-' + event.id);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 300);
        });

        // Flechas y tecla Supr
        document.addEventListener('keydown', function(e) {
            if (e.target && e.target.id && e.target.id.startsWith('can-')) {
                const currentInput = e.target;
                const inputs = Array.from(document.querySelectorAll('input[id^="can-"]'));
                const index = inputs.indexOf(currentInput);

                // ↓ siguiente input
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextInput = inputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                        nextInput.select();
                    }
                }

                // ↑ input anterior
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevInput = inputs[index - 1];
                    if (prevInput) {
                        prevInput.focus();
                        prevInput.select();
                    }
                }

                // Suprimir → eliminar producto
                if (e.key === 'Delete') {
                    e.preventDefault();
                    const id = currentInput.id.replace('can-', ''); // usa el mismo ID del enlace
                    Livewire.emit('removeItem', parseInt(id));
                }
            }
        });
    });
</script>

{{--
<div id="inputModal" class="modal_detalle">
    <div class="modal-content">
        <span id="closeModal" class="close">&times;</span>
        <div id="modalContent" style="padding: 10px;">
            <!-- Tabla con los precios -->
            <div class="table-responsive">
                <p>
                    <strong>Producto: </strong>

                    {{ $nombreProductoSeleccionado }}
                </p>
                <table class="table table-borderless table-sm text-center"
                    style="width: 100%; font-size: 12px; table-layout: fixed;" id="table-detalleexistencia">
                    <thead>
                        <th></th>
                        @foreach ($sucursales as $sucursal)
                            <th class="text-center" style="padding: 2px; min-width: 50px;">
                                Suc {{ $sucursal->numero }}
                            </th>
                        @endforeach
                    </thead>
                    <tbody class="remove_padding_table table-bordered">
                        <tr wire:init="loadExistencias({{ $productoSeleccionado }})">

                            <td>E</td>
                            @foreach ($sucursales as $sucursal)
                                <td class="text-center"
                                    style="background-color:
                                        @php
                                        // Obtener existencias del producto para la sucursal
                                            $existencia = DB::table('inventarios')
                                                ->where('producto', $productoSeleccionado)
                                                ->where('sucursal', $sucursal->id)
                                                ->first();

                                            // Verificar ventas del producto en los últimos 30 días
                                            $ventasUltimos30 = DB::table('ventas_detalles as vd')
                                                ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                                ->where('v.sucursal', $sucursal->id)
                                                ->where('vd.producto', $productoSeleccionado)
                                                ->whereBetween('v.created_at', [Carbon::now()->subDays(30), Carbon::now()])
                                                ->sum('vd.cantidad');

                                            // Calcular ventas promedio diarias en los últimos 30 días
                                            $ventasPromedio30 = $ventasUltimos30 > 0 ? $ventasUltimos30 / 30 : 0;

                                            // Calcular las ventas promedio para los próximos 90 días (esto lo calculamos basado en las ventas de los últimos 30 días)
                                            $ventasPromedio90 = $ventasPromedio30 * 90;

                                            // Si tiene existencias y las existencias son mayores a lo que puede vender en 90 días, color rojo
                                            if ($existencia && $existencia->existencia > 0) {
                                                if ($existencia->existencia > $ventasPromedio90) {
                                                    echo '#ff9292'; // Rojo (si tiene más existencias que lo que puede vender en 90 días)
                                                } else {
                                                    echo '#ffffff'; // Blanco (si tiene suficientes existencias)
                                                }
                                            }
                                            // Si no tiene existencias y nunca ha tenido ventas, color gris
                                            elseif (!$existencia || $existencia->existencia == 0) {
                                                echo '#d1d1cf'; // Gris
                                            }
                                            // Si no tiene existencias pero ha tenido ventas en los últimos 90 días, color verde
                                            elseif ($existencia->existencia == 0 && $ventasTotales > 0) {
                                                echo '#b0d4f1'; // Verde
                                            }
                                            // Si no tiene existencias pero ha tenido ventas hace más de 90 días, color celeste
                                            elseif ($existencia->existencia == 0 && $ventasTotales > 0) {
                                                $ventasUltimos90 = DB::table('ventas_detalles as vd')
                                                    ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                                    ->where('vd.producto', $productoSeleccionado)
                                                    ->where('v.sucursal', $sucursal->id)
                                                    ->where('v.created_at', '<', Carbon::now()->subDays(90)) // Ventas hace más de 90 días
                                                    ->sum('vd.cantidad');

                                                if ($ventasUltimos90 > 0) {
                                                    echo '#00b5e2'; // Celeste
                                                } else {
                                                    echo ''; // Sin color si no cumple las condiciones
                                                }
                                            }
                                            // Si tiene existencias y no ha vendido en los últimos 91 a 180 días, color naranja
                                            elseif ($existencia && $existencia->existencia > 0) {
                                                $ventasUltimos180 = DB::table('ventas_detalles as vd')
                                                    ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                                    ->where('vd.producto', $productoSeleccionado)
                                                    ->where('v.sucursal', $sucursal->id)
                                                    ->whereBetween('v.created_at', [Carbon::now()->subDays(180), Carbon::now()->subDays(91)])
                                                    ->sum('vd.cantidad');

                                                if ($ventasUltimos180 == 0) {
                                                    echo '#ffa500'; // Naranja (si no ha tenido ventas entre 91 y 180 días)
                                                } else {
                                                    echo ''; // Sin color si ha tenido ventas en ese rango
                                                }
                                            }
                                            // Si tiene existencias y no ha vendido en los últimos 181 a 365 días, color rojo
                                            elseif ($existencia && $existencia->existencia > 0) {
                                                $ventasUltimos365 = DB::table('ventas_detalles as vd')
                                                    ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                                    ->where('vd.producto', $productoSeleccionado)
                                                    ->where('v.sucursal', $sucursal->id)
                                                    ->whereBetween('v.created_at', [Carbon::now()->subDays(365), Carbon::now()->subDays(181)])
                                                    ->sum('vd.cantidad');

                                                if ($ventasUltimos365 == 0) {
                                                    echo '#91160d'; // Rojo (si no ha tenido ventas entre 181 y 365 días)
                                                } else {
                                                    echo ''; // Sin color si ha tenido ventas en ese rango
                                                }
                                            }
                                            else {
                                                echo ''; // Sin color si no cumple ninguna de las condiciones anteriores
                                        } @endphp
                                        ">
                                    <h6>{{ $existencia ? $existencia->existencia : 0 }}</h6>
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>V30</td>
                            @foreach ($sucursales as $sucursal)
                                @php
                                    $ventasUltimos30 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(30)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasUltimos90 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(90)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasUltimos180 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(180)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasUltimos365 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(365)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasTotales = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->sum('vd.cantidad');

                                    // Obtener la existencia actual del producto en la sucursal
                                    $existencia = DB::table('inventarios')
                                        ->where('producto', $productoSeleccionado)
                                        ->where('sucursal', $sucursal->id)
                                        ->first();

                                    // Determinar el color según las reglas
                                    if ($ventasTotales == 0) {
                                        $color = '#d1d1cf'; // GRIS: Nunca ha tenido ventas
                                    } elseif ($existencia && $existencia->existencia == 0) {
                                        if ($ventasUltimos90 > 0) {
                                            $color = '#b0d4f1'; // VERDE: Sin stock pero ventas en los últimos 90 días
                                        } elseif ($ventasUltimos365 > 0) {
                                            $color = '#a8e6a3'; // CELESTE: Sin stock pero ventas hace más de 90 días
                                        } else {
                                            $color = ''; // Sin color si no cumple
                                        }
                                    } elseif ($existencia->existencia >= $ventasUltimos30) {
                                        $color = '#ffffff'; // BLANCO: Stock para 30 días
                                    } elseif ($existencia->existencia < $ventasUltimos30) {
                                        $color = '#ffffb5'; // AMARILLO: Menos de lo que puede vender en 30 días
                                    } elseif (
                                        $existencia->existencia > 0 &&
                                        $ventasUltimos180 == 0 &&
                                        $ventasUltimos365 > 0
                                    ) {
                                        $color = '#ffd699'; // NARANJA: Stock sin ventas en 91-180 días
                                    } elseif ($existencia->existencia > 0 && $ventasUltimos365 == 0) {
                                        $color = '#f7b58a'; // OCRE: Stock sin ventas en 181-365 días
                                    } elseif ($existencia->existencia > $ventasUltimos90 * 3) {
                                        $color = '#ff9292'; // ROJO: Más de lo que puede vender en 90 días
                                    } else {
                                        $color = ''; // Sin color si no cumple
                                    }
                                @endphp

                                <!-- Celda para Ventas en los últimos 30 días -->
                                <td class="text-center" style="background-color: {{ $color }};">
                                    <h6>{{ $ventasUltimos30 ?: 0 }}</h6>
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>V90</td>
                            @foreach ($sucursales as $sucursal)
                                @php

                                    // Obtener ventas en los últimos 90, 180 y 365 días
                                    $ventasUltimos90 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(90)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasUltimos180 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(180)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasUltimos365 = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->whereBetween('v.created_at', [
                                            Carbon::now()->subDays(365)->toDateTimeString(),
                                            Carbon::now()->toDateTimeString(),
                                        ])
                                        ->sum('vd.cantidad');

                                    $ventasTotales = DB::table('ventas_detalles as vd')
                                        ->join('ventas as v', 'vd.venta', '=', 'v.id')
                                        ->where('vd.producto', $productoSeleccionado)
                                        ->where('v.sucursal', $sucursal->id)
                                        ->sum('vd.cantidad');

                                    // Obtener la existencia actual del producto en la sucursal
                                    $existencia = DB::table('inventarios')
                                        ->where('producto', $productoSeleccionado)
                                        ->where('sucursal', $sucursal->id)
                                        ->first();

                                    // Determinar el color según las reglas
                                    if ($ventasTotales == 0) {
                                        $color = '#d1d1cf'; // GRIS: Nunca ha tenido ventas
                                    } elseif ($existencia && $existencia->existencia == 0) {
                                        if ($ventasUltimos90 > 0) {
                                            $color = '#b0d4f1'; // VERDE: Sin stock pero ventas en los últimos 90 días
                                        } elseif ($ventasUltimos365 > 0) {
                                            $color = '#a8e6a3'; // CELESTE: Sin stock pero ventas hace más de 90 días
                                        } else {
                                            $color = ''; // Sin color si no cumple
                                        }
                                    } elseif ($existencia->existencia >= $ventasUltimos90) {
                                        $color = '#ffffff'; // BLANCO: Stock para 30 días
                                    } elseif ($existencia->existencia < $ventasUltimos90) {
                                        $color = '#ffffb5'; // AMARILLO: Menos de lo que puede vender en 30 días
                                    } elseif (
                                        $existencia->existencia > 0 &&
                                        $ventasUltimos180 == 0 &&
                                        $ventasUltimos365 > 0
                                    ) {
                                        $color = '#ffd699'; // NARANJA: Stock sin ventas en 91-180 días
                                    } elseif ($existencia->existencia > 0 && $ventasUltimos365 == 0) {
                                        $color = '#f7b58a'; // OCRE: Stock sin ventas en 181-365 días
                                    } elseif ($existencia->existencia > $ventasUltimos90 * 3) {
                                        $color = '#ff9292'; // ROJO: Más de lo que puede vender en 90 días
                                    } else {
                                        $color = ''; // Sin color si no cumple
                                    }
                                @endphp

                                <!-- Celda para Ventas en los últimos 30 días -->
                                <td class="text-center" style="background-color: {{ $color }};">
                                    <h6>{{ $ventasUltimos90 ?: 0 }}</h6>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="custom_tr">
                            <td>
                                C
                            </td>
                            <td style="background-color: #d5d5d5;">Nunca ha tenido ventas</td> <!-- Gris suave -->
                            <td style="background-color: #a8e6a3;">Sin stock pero ventas en los últimos 90 días</td>
                            <!-- Verde pastel suave -->
                            <td style="background-color: #b0d4f1;">Sin stock pero ventas hace más de 90 días</td>
                            <!-- Azul pastel suave -->
                            <td style="background-color: #ffffff;">Stock para 30 días</td> <!-- Blanco -->
                            <td style="background-color: #ffffb5;">Menos de lo que puede vender en 30 días</td>
                            <!-- Amarillo muy suave -->
                            <td style="background-color: #ffd699;">Stock sin ventas en 91-180</td>
                            <!-- Naranja suave -->
                            <td style="background-color: #f7b58a;">Stock sin ventas en 181-365 días</td>
                            <!-- Naranja pastel -->
                            <td style="background-color: #ff9292;">Más de lo que puede vender en 90 días</td>
                            <!-- Rojo pastel -->


                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<style>
    .modal_detalle {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 80px;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
    }

    .modal_detalle .modal-content {
        position: relative;
        background-color: #fefefe;
        margin: 10% auto;
        padding: 10px 0px;
        width: 50%;
        max-width: 50rem;
        min-width: 50rem;
        border-radius: 5px;
        text-align: center;
        box-shadow: 0px 0px 8px 0px #e5e5e5;
        border: 1px solid #b0d4f1;
    }

    .modal_detalle .modal-content .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;

        position: absolute;
        left: 18px;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    }

    .product-cell h6:hover {
        color: rgb(92, 97, 255) !important;
    }

    .remove_padding_table tr td {
        padding: 0.2rem 0.5rem !important;
        padding-bottom: 0 !important;
    }


    .remove_padding_table tr td h6 {
        font-size: 0.7rem
    }

    .custom_tr td {
        color: rgb(57, 57, 57) !important;
    }

    .table-bordered tr td h6 {
        color: rgb(57, 57, 57) !important;
    }
</style>

<script>
    function mostrarModalSiEstabaAbierto() {
        const modal = document.getElementById('inputModal');
        if (localStorage.getItem('modalOpen') === 'true') {
            modal.style.display = 'block';
        }
    }

    document.addEventListener('livewire:load', () => {
        const modal = document.getElementById('inputModal');
        const closeModalBtn = document.getElementById('closeModal');

        mostrarModalSiEstabaAbierto();

        // Evento para mostrar modal
        window.addEventListener('mostrar-modal-producto', () => {
            modal.style.display = 'block';
            localStorage.setItem('modalOpen', 'true');
            Livewire.emit('cargarExistenciasProducto');
        });

        // Cierre solo con botón X
        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            localStorage.setItem('modalOpen', 'false');
        });

        // Bloquea cierre por clic fuera del modal
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                e.stopPropagation(); // No hacer nada
            }
        });

        // Reabre el modal si Livewire actualiza el DOM
        document.addEventListener('livewire:update', () => {
            mostrarModalSiEstabaAbierto();
        });

        // Detectar recarga manual y limpiar el estado del modal
        window.addEventListener('beforeunload', () => {
            localStorage.removeItem('modalOpen');
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('focus-input', function(event) {
            setTimeout(() => {
                const input = document.getElementById('can-' + event.id);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 300); // espera para que el DOM esté listo
        });
    });
</script>
 --}}
