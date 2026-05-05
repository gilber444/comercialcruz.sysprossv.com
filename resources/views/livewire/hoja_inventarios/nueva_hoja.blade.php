<div class="card">
    <div class="card-header">
        <div class="row align-items-end justify-content-end">
            <!-- Botón Guardar -->
            @if (!empty($this->sucursal))
                <div class="col-md-4 mb-3">
                    <a href="{{ route('hoja_inventarios') }}" class="btn btn-danger btn-rounded mb-2">Regresar</a>
                    <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal"
                        data-bs-target="#validarModal">Validar datos</a>
                </div>
            @endif
        </div>
        <hr class="my-2">
        <div class="row">
            <div class="col-xl-12 mb-3 mb-xl-12">
                @include('livewire.ajustes.product')
            </div>
        </div>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th class="text-center">CODIGO</th>
                    <th class="text-center">DESCRIPCION</th>
                    <th class="text-center">MEDIDA</th>
                    <th class="text-center">CONTENEDOR</th>
                    <th class="text-center">Existencia</th>
                    <th class="text-center">Conteo Fisico</th>
                    <th class="text-center">Diferencia</th>
                    <th class="text-center">Costo</th>
                    <th class="text-center">Total</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @if ($inventarios && $inventarios->count())
                    @foreach ($inventarios as $item)
                        <tr class="product-row" data-id="{{ $item->id }}">
                            <td class="text-center">
                                <h6>{{ $item->codebar }}</h6>
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>
                                @php
                                    $medida = DB::table('medidas')->where('id', $item->medida)->value('unidad');
                                    echo $medida;
                                @endphp
                            </td>
                            <td>
                                {{ $item->limit }}
                            </td>
                            <td>{{ $item->existencia }}</td>
                            <td>
                                <input id="can-{{ $item->id }}" type="text"
                                    class="form-control form-control-sm text-center conteo-input"
                                    placeholder="{{ $item->conteoFisico }}" wire:model.lazy="conteo.{{ $item->id }}"
                                    wire:keydown.enter="$refresh" inputmode="decimal"
                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')"
                                    style="height: 50px; width: 100px; font-size: 20px;">
                            </td>
                            <td>
                                <span
                                    class="badge {{ $item->diferencia > 0 ? 'bg-warning' : ($item->diferencia < 0 ? 'bg-danger' : 'bg-success') }}">
                                    {{ $item->diferencia }}
                                </span>
                            </td>
                            <td>
                                {{ $item->costo }}
                            </td>
                            <td>
                                <span
                                    class="badge {{ $item->total > 0 ? 'bg-warning' : ($item->total < 0 ? 'bg-danger' : 'bg-success') }}">
                                    {{ $item->total }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" class="text-center">SIN RESULTADOS</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@include('common.notis')
@include('livewire.hoja_inventarios.scripts.events')
@include('livewire.hoja_inventarios.scripts.shortcuts')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');

        window.addEventListener('focus-after-update', function(event) {
            // Retraso para asegurar que Livewire ha terminado de actualizar el DOM
            setTimeout(() => {
                const input = document.getElementById('can-' + event.detail.id);
                if (input) {
                    input.focus(); // Foco en el campo de entrada actualizado
                    input.select(); // Seleccionamos el valor para facilitar el cambio
                    resaltarFila(input); // Resaltamos la fila correspondiente
                }
            }, 150); // Retraso para que Livewire termine de procesar
        });

        document.addEventListener('keydown', function(event) {
            const rows = document.querySelectorAll('tbody tr');
            const focused = document.querySelector('tr.tr-focus');
            let index = Array.from(rows).indexOf(focused);

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (index < rows.length - 1) {
                    focusRow(rows, index + 1);
                } else if (index === -1 && rows.length > 0) {
                    focusRow(rows, 0);
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index > 0) {
                    focusRow(rows, index - 1);
                } else if (index === 0 && searchInput) {
                    rows[index].classList.remove('tr-focus');
                    searchInput.focus();
                }
            } else if (event.key === 'Enter' && index !== -1) {
                event.preventDefault();
                const id = rows[index].getAttribute('data-id');
                if (id) {
                    window.livewire.emit('updateQtyEnter', {
                        id: id
                    });

                    // Evitar que se pierda el foco y agregar un retraso para asegurar que el valor se haya actualizado correctamente
                    setTimeout(() => {
                        const nextInput = rows[index].querySelector('input');
                        if (nextInput) {
                            nextInput
                        .focus(); // Aseguramos que el siguiente input reciba el foco
                            nextInput.select();
                        }
                    }, 100); // Retraso después de que se actualice el valor
                }
            }
        });

        function focusRow(rows, index) {
            rows.forEach(r => r.classList.remove('tr-focus'));
            rows[index].classList.add('tr-focus');
            rows[index].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            const input = rows[index].querySelector('input');
            if (input) {
                input.focus();
                input.select();
            }
        }

        // Cuando se enfoca un input manualmente
        document.addEventListener('focusin', function(event) {
            if (event.target.matches('input[id^="can-"]')) {
                resaltarFila(event.target);
            }
        });

        function resaltarFila(input) {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(r => r.classList.remove('tr-focus'));
            const tr = input.closest('tr');
            if (tr) {
                tr.classList.add('tr-focus');
            }
        }
    });
</script>

<style>
    /* resaltar input activo */
    input[id^="can-"]:focus {
        background-color: #e0e0e0 !important;
    }

    .tr-focus {
        outline: 2px solid #969cff;
        background-color: #e7e7ff;
    }
</style>
