@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;
@endphp
<div wire:ignore.self class="modal fade" id="modalSearchProduct" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" wire:model="search" id="modal-search-input" wire:click="liveSearch()"
                        placeholder="Buscar por nombre, código de barras o categorías" class="form-control">
                </div>
            </div>
            <div class="modal-body">
                <div class="row p-2">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm">
                            <thead>
                                <th class="text-center">CÓDIGO BARRA</th>
                                <th class="text-center">DESCRIPCIÓN</th>
                                <th class="text-center">MEDIDA</th>
                                <th class="text-center">CANTIDAD</th>
                                @can('Solicitud_Costo')
                                    <th class="text-center">COSTO</th>
                                @endcan
                                <th class="text-center">EXISTENCIA</th>
                                <th class="text-center"></th>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr  class="product-row" data-product-id="{{ $product->id }}" tabindex="-1">
                                        <td class="text-center">
                                            <h6>{{ $product->codebar3 }}</h6>
                                        </td>
                                        <td style="cursor: pointer">
                                            <h6><b>{{ $product->nombreProducto }}</b></h6>
                                        </td>
                                        <td class="text-center">
                                            <h6>{{ $product->presentacion }}</h6>
                                        </td>
                                        <td class="text-center">
                                            <h6>{{ $product->cantidad }}</h6>
                                        </td>
                                        @can('Solicitud_Costo')
                                            <td class="text-end">
                                                <h6>$ {{ number_format($product->costosiva, 2) }}</h6>
                                            </td>
                                        @endcan
                                        <td class="text-center">
                                            {{ number_format($product->existencia, 2) }}
                                        </td>
                                            <td class="text-center">
                                                <button wire:click.prevent="Add({{ $product->id }})"
                                                    class="btn btn-primary add-product btn-sm"
                                                    @if($product->existencia == 0) disabled @endif
                                                    <i class="fas fa-cart-arrow-down mr-1"></i> Agregar
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
            </div>
            {{--
            <div class="modal-footer d-flex justify-content-between align-items-center">
                @if (count($products) > 0)
                    {{-- <table class="table table-borderless table-sm text-center"
                        style="width: auto; font-size: 12px; table-layout: fixed;" id="table-detalleexistencia">
                        <thead>
                            @foreach ($sucursales as $sucursal)
                                <th class="text-center" style="padding: 2px; min-width: 50px;">
                                    {{ $sucursal->numero }}
                                </th>
                            @endforeach
                        </thead>
                        <tbody>
                            <tr>
                                @foreach ($sucursales as $sucursal)
                                    <td class="text-center">
                                        @php
                                            $existencia = DB::table('inventarios')
                                                ->where('producto', $productoSeleccionado)
                                                ->where('sucursal', $sucursal->id)
                                                ->first();
                                        @endphp
                                        <h6>
                                            {{ $existencia ? $existencia->existencia : 0 }}
                                        </h6>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($sucursales as $sucursal)
                                    <td class="text-center">
                                        @php
                                            $ventas3 = DB::table('ventas_detalles')
                                                ->where('producto', $productoSeleccionado)
                                                ->where('sucursal', $sucursal->id)
                                                ->where('created_at', '>=', now()->subDays(30))
                                                ->first();
                                        @endphp
                                        <h6>
                                            {{ $ventas3 ? $ventas3->cantidad : 0 }}
                                        </h6>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table> --}
                    <!-- Tabla de existencias -->
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
                        <tbody>
                            <tr wire:mouseenter="loadExistencias({{ $productoSeleccionado }})"
                                wire:mouseleave="loadExistencias()">
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
                            echo '#ff0000'; // Rojo (si tiene más existencias que lo que puede vender en 90 días)
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
                        echo '#46ed2f'; // Verde
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

                                        // Obtener ventas en los últimos 30, 90, 180 y 365 días
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
                                                $color = '#46ed2f'; // VERDE: Sin stock pero ventas en los últimos 90 días
                                            } elseif ($ventasUltimos365 > 0) {
                                                $color = '#74d1e9'; // CELESTE: Sin stock pero ventas hace más de 90 días
                                            } else {
                                                $color = ''; // Sin color si no cumple
                                            }
                                        } elseif ($existencia->existencia >= $ventasUltimos30) {
                                            $color = '#ffffff'; // BLANCO: Stock para 30 días
                                        } elseif ($existencia->existencia < $ventasUltimos30) {
                                            $color = '#ffff00'; // AMARILLO: Menos de lo que puede vender en 30 días
                                        } elseif (
                                            $existencia->existencia > 0 &&
                                            $ventasUltimos180 == 0 &&
                                            $ventasUltimos365 > 0
                                        ) {
                                            $color = '#ff9900'; // NARANJA: Stock sin ventas en 91-180 días
                                        } elseif ($existencia->existencia > 0 && $ventasUltimos365 == 0) {
                                            $color = '#cc8400'; // OCRE: Stock sin ventas en 181-365 días
                                        } elseif ($existencia->existencia > $ventasUltimos90 * 3) {
                                            $color = '#ff0000'; // ROJO: Más de lo que puede vender en 90 días
                                        } else {
                                            $color = ''; // Sin color si no cumple
                                        }
                                    @endphp

                                    <!-- Celda para Ventas en los últimos 30 días -->
                                    <td class="text-center" style="background-color: {{ $color }};">
                                        <h6>{{ $ventasUltimos30 ?: 0 }}</h6>
                                    </td>
                                @endforeach
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
                                                $color = '#46ed2f'; // VERDE: Sin stock pero ventas en los últimos 90 días
                                            } elseif ($ventasUltimos365 > 0) {
                                                $color = '#74d1e9'; // CELESTE: Sin stock pero ventas hace más de 90 días
                                            } else {
                                                $color = ''; // Sin color si no cumple
                                            }
                                        } elseif ($existencia->existencia >= $ventasUltimos90) {
                                            $color = '#ffffff'; // BLANCO: Stock para 30 días
                                        } elseif ($existencia->existencia < $ventasUltimos90) {
                                            $color = '#ffff00'; // AMARILLO: Menos de lo que puede vender en 30 días
                                        } elseif (
                                            $existencia->existencia > 0 &&
                                            $ventasUltimos180 == 0 &&
                                            $ventasUltimos365 > 0
                                        ) {
                                            $color = '#ff9900'; // NARANJA: Stock sin ventas en 91-180 días
                                        } elseif ($existencia->existencia > 0 && $ventasUltimos365 == 0) {
                                            $color = '#cc8400'; // OCRE: Stock sin ventas en 181-365 días
                                        } elseif ($existencia->existencia > $ventasUltimos90 * 3) {
                                            $color = '#ff0000'; // ROJO: Más de lo que puede vender en 90 días
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
                            <tr>
                                <td>
                                    C
                                </td>
                                <td style="background-color: #d1d1cf;">Nunca ha tenido ventas</td>
                                <td style="background-color: #46ed2f;">Sin stock pero ventas en los últimos 90 días</td>
                                <td style="background-color: #74d1e9;">Sin stock pero ventas hace más de 90 días</td>
                                <td style="background-color: #ffffff;">Stock para 30 días</td>
                                <td style="background-color: #ffff00;">Menos de lo que puede vender en 30 días</td>
                                <td style="background-color: #ff9900;">Stock sin ventas en 91-180</td>
                                <td style="background-color: #cc8400;">Stock sin ventas en 181-365 días</td>
                                <td style="background-color: #ff0000;">Más de lo que puede vender en 90 días</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
                <button type="button" class="btn btn-label-primary" data-bs-dismiss="modal">Cerrar Ventana</button>
            </div> --}}
        </div>
    </div>
</div>
<style>

#modalSearchProduct.hide-cursor {
    cursor: none !important;  /* Ocultar el cursor en todo el modal */
}

#modalSearchProduct.hide-cursor * {
    cursor: none !important;  /* Asegurar que todos los elementos dentro del modal no muestren el cursor */
}

#modalSearchProduct.hide-cursor .table-responsive {
    pointer-events: none !important; /* Desactiva interacciones en áreas con scroll */
}
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalSearchProduct');
        let searchInput = modal.querySelector('#modal-search-input');

        // Función para actualizar la lista de productos dinámicamente
        function updateProductRows() {
            return modal.querySelectorAll('tbody tr[data-product-id]');
        }

        function focusRow(index, productRows) {
            if (index >= 0 && index < productRows.length) {
                // Eliminar estilo de focus de todas las filas
                productRows.forEach(row => {
                    row.style.outline = '';
                    row.style.backgroundColor = '';
                });

                const row = productRows[index];
                row.focus({ preventScroll: true });

                // Aplicamos el estilo al row enfocado
                row.style.outline = '2px solid #969cff';
                row.style.backgroundColor = '#e7e7ff';

                const container = modal.querySelector('.table-responsive'); // Usamos .table-responsive
                if (container) {
                    const rowRect = row.getBoundingClientRect();
                    const containerRect = container.getBoundingClientRect();

                    // Activar scroll suave
                    container.style.scrollBehavior = 'smooth';

                    // Si la fila está por debajo del área visible
                    if (rowRect.bottom > containerRect.bottom) {
                        const scrollAmount = rowRect.bottom - containerRect.bottom;
                        container.scrollTop += scrollAmount;
                    }

                    // Si la fila está por encima del área visible
                    else if (rowRect.top < containerRect.top) {
                        const scrollAmount = containerRect.top - rowRect.top;
                        container.scrollTop -= scrollAmount;
                    }
                }
            }
        }

        // Re-aplicar el estilo de focus después de la actualización de Livewire
        document.addEventListener('livewire:update', function() {
            const focusedRow = document.activeElement;
            if (focusedRow && focusedRow.tagName === 'TR' && focusedRow.hasAttribute('data-product-id')) {
                focusedRow.style.outline = '2px solid #969cff';
                focusedRow.style.backgroundColor = '#e7e7ff';
            }
        });


        modal.addEventListener('keydown', function(event) {
            let productRows = updateProductRows();
            const focusedElement = document.activeElement;
            let index = Array.from(productRows).indexOf(focusedElement);
            let cursorTimeout;
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (index === -1) {
                    focusRow(0, productRows);
                    emitExistencias(productRows[0]); // Emitir al enfocar la primera fila
                } else if (index < productRows.length - 1) {
                    focusRow(index + 1, productRows);
                    emitExistencias(productRows[index + 1]);
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index > 0) {
                    focusRow(index - 1, productRows);
                    emitExistencias(productRows[index - 1]);
                } else if (index === 0) {
                    searchInput.focus();
                }
            } else if (event.key === 'Enter' && index !== -1) {
                event.preventDefault();
                const addButton = productRows[index].querySelector('button.btn-primary');
                if (addButton) {
                    addButton.click();
                    $('#modalSearchProduct').modal('hide');
                }
            }

        });


        // Al abrir el modal
        $('#modalSearchProduct').on('shown.bs.modal', function () {
            document.getElementById('modalSearchProduct').classList.add('hide-cursor');
        });

        // Al cerrar el modal
        $('#modalSearchProduct').on('hidden.bs.modal', function () {
            document.getElementById('modalSearchProduct').classList.remove('hide-cursor');
        });




        //function emitExistencias(row) {
            //if (!row) return;
           // const productId = row.getAttribute('data-product-id');
           // if (productId) {
               // Livewire.emit('loadExistencias', productId);
            //}
        //}

        // // Evento para cargar existencias al pasar el mouse sobre una fila
        // modal.addEventListener('click', function(event) {
        //     const row = event.target.closest('tr[data-product-id]');
        //     if (row) {
        //         const productId = row.getAttribute('data-product-id');
        //         Livewire.emit('loadExistencias', productId);
        //     }
        // });

        // Cerrar modal al hacer clic en Agregar
        document.addEventListener('click', function(event) {
            if (event.target.closest('.add-product')) {
                $('#modalSearchProduct').modal('hide');
            }
        });

        // // Actualizar las filas después de que Livewire actualice la lista de productos
        document.addEventListener('livewire:update', function() {
            let productRows = updateProductRows();
        });

        // Foco en el campo de búsqueda al abrir el modal
        $('#modalSearchProduct').on('shown.bs.modal', function() {
            searchInput.focus();
            searchInput.select();
        });
    });
</script>
