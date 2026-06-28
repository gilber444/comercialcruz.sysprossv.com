<div class="card">

    <div class="card-header">

        <div class="d-flex align-items-center justify-content-between">

            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>



            @if ($rol == 'Super' or $rol == 'Administrador' or $rol == 'GERENTE')

                <div class="dropdown">

                    <select wire:model="sucursalSeleccionada" class="form-control">

                        <option value="Global">Global, Todas las Sucursales</option>

                        @foreach ($sucursales as $sucursal)

                            <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>

                        @endforeach

                    </select>

                </div>

            @endif

        </div>

        <hr class="my-2">



        {{-- Botón de sincronización al VPS --}}

        @can('Inventario_Index')

            <div class="mb-3">

                <button

                    type="button"

                    class="btn btn-primary btn-sm"

                    data-bs-toggle="modal"

                    data-bs-target="#modalSyncInventario">

                    <i class="bx bx-sync me-1"></i> Sincronizar al VPS

                </button>

            </div>

        @endcan



        @include('common.searchbox')

    </div>

    <div class="table-responsive text-nowrap">

        <table class="table table-hover table-sm">

            <thead>

                <th class="text-center">Código de barras</th>

                <th class="text-center">Nombre del producto</th>

                <th class="text-center">Medida</th>

                <th class="text-center">Existencia</th>

                <th class="text-center">Lugar</th>

            </thead>

            <tbody>

                @foreach ($data as $dat)

                    <tr>

                        <td class="text-center"><b>{{ $dat->Rproductos->codebar3 }}</b></td>

                        <td>

                            @can('Productos_Update')

                                <a class="btn" href="{{ route('editarProduct', ['id' => $dat->Rproductos->id]) }}">

                                    {{ $dat->Rproductos->nombreProducto }}

                                </a>

                            @else

                                {{ $dat->Rproductos->nombreProducto }}

                            @endcan



                        </td>

                        <td class="text-center">{{ $dat->Rproductos->Rmedidas->unidad }}</td>

                        <td class="text-center">

                            @php

                                if ($dat->existencia != 0) {

                                    $media = ($dat->Rproductos->maximo + $dat->Rproductos->minimo) / 2;

                                    //$existencia = $dat->existencia / $dat->Rproductos->contenedor;

                                    $existencia = $dat->existencia;

                                } else {

                                    $existencia = 0;

                                }

                            @endphp



                            @if ($existencia <= $dat->Rproductos->minimo)

                                <span class="text-danger">{{ $existencia }}</span>

                            @elseif($existencia > $dat->Rproductos->minimo && $existencia <= $media)

                                <span class="text-warning">{{ $existencia }}</span>

                            @else

                                <span class="text-success">{{ $existencia }}</span>

                            @endif

                        </td>

                        <td>{{ $dat->Rsucursales->nombre }}</td>

                    </tr>

                @endforeach

            </tbody>

        </table>



    </div>

    {{ $data->links() }}



    {{-- Modal de Sincronización al VPS --}}

    @can('Inventario_Index')

        <div class="modal fade" id="modalSyncInventario" tabindex="-1" aria-labelledby="modalSyncInventarioLabel" aria-hidden="true" wire:ignore.self>

            <div class="modal-dialog modal-lg modal-dialog-scrollable">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title" id="modalSyncInventarioLabel">

                            <i class="bx bx-sync me-2"></i>Sincronizar Existencias al VPS

                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    </div>

                    <div class="modal-body">
                        {{--
                        @livewire('sync-inventario-controller')--}}
                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

                    </div>

                </div>

            </div>

        </div>

    @endcan



    @include('livewire.medidas.form')

</div>

@include('common.notis')

<style>

    .selected {

        color: #696cff;

        background-color: rgba(105, 108, 255, 0.16) !important

            /* Color de fondo para la fila seleccionada */

    }

</style>

<script>

    document.addEventListener('DOMContentLoaded', function() {

        // Foco automático en el input de búsqueda

        const searchInput = document.getElementById('search-input');

        searchInput.focus();



        let selectedRow = null;



        // Escuchar las teclas en el input de búsqueda

        searchInput.addEventListener('keydown', function(e) {

            const rows = document.querySelectorAll('tbody tr');

            if (rows.length === 0) return;



            // Flecha abajo

            if (e.key === 'ArrowDown') {

                e.preventDefault();

                if (selectedRow === null) {

                    selectedRow = 0;

                } else if (selectedRow < rows.length - 1) {

                    selectedRow++;

                }

                highlightRow(rows, selectedRow);

            }



            // Flecha arriba

            if (e.key === 'ArrowUp') {

                e.preventDefault();

                if (selectedRow > 0) {

                    selectedRow--;

                }

                highlightRow(rows, selectedRow);

            }



            // Enter para editar (si tiene permiso)

            if (e.key === 'Enter' && selectedRow !== null) {

                e.preventDefault();

                const row = rows[selectedRow];

                const editButton = row.querySelector('.btn-label-warning'); // Botón de edición

                if (editButton) {

                    // Si existe el botón de edición, redirigir al enlace

                    window.location.href = editButton.href;

                } else {

                    // Mostrar SweetAlert si no tiene permiso

                    Swal.fire({

                        icon: 'warning',

                        title: 'Acción no permitida',

                        text: 'No tienes permiso para editar este producto.',

                        confirmButtonText: 'Entendido',

                        timer: 3000

                    });

                }

            }

        });



        // Resaltar la fila seleccionada

        function highlightRow(rows, index) {

            rows.forEach((row, i) => {

                if (i === index) {

                    row.classList.add('table-primary');

                    row.scrollIntoView({

                        behavior: 'smooth',

                        block: 'center'

                    });

                } else {

                    row.classList.remove('table-primary');

                }

            });

        }

    });

</script>

