<div wire:ignore.self class="modal fade" id="modalSearchProduct" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" wire:model='search' id="modal-search-input"
                        placeholder="Buscar por nombre, código de barras o categorías" class="form-control">
                </div>
            </div>
            <div class="modal-body">
                <div class="row p-2">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover table-sm">
                            <thead>
                                <th class="text-center">CÓDIGO BARRA</th>
                                <th class="text-center">DESCRIPCIÓN</th>
                                <th class="text-center">MEDIDA</th>
                                <th class="text-center">CANTIDAD</th>
                                <th class="text-center">COSTO</th>
                                @can('AjusteExistencia_Index')
                                    <th class="text-center">EXISTENCIA</th>
                                @endcan
                                <th class="text-center"></th>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr tabindex="0"> <!-- Asegúrate de que cada fila sea enfocable -->
                                        <td class="text-center">
                                            <h6>{{ $product->codebar }}</h6>
                                        </td>
                                        <td>
                                            <h6><b>{{ $product->Rproductos->nombreProducto }}</b></h6>
                                        </td>
                                        <td class="text-center">
                                            <h6>{{ $product->presentacion }}</h6>
                                        </td>
                                        <td class="text-center">
                                            <h6>{{ $product->cantidad }}</h6>
                                        </td>
                                        <td class="text-end">
                                            <h6>$ {{ number_format($product->costosiva, 2) }}</h6>
                                        </td>
                                        @can('AjusteExistencia_Index')
                                            <td class="text-center">
                                                @if (empty($sucursal))
                                                    Seleccione una sucursal para ver la existencia
                                                @else
                                                    @php
                                                        $existencia = DB::table('inventarios')
                                                            ->where('producto', $product->Rproductos->id)
                                                            ->where('sucursal', $sucursal)
                                                            ->value('existencia');

                                                    @endphp
                                                    {{ number_format($existencia, 2) }}
                                                @endif
                                            </td>
                                        @endcan
                                        <td class="text-center">
                                            @if ($sucursal)
                                                <button wire:click.prevent="Add2({{ $product->id }})"
                                                    class="btn btn-primary">
                                                    <i class="fas fa-cart-arrow-down mr-1"></i> Agregar
                                                </button>
                                            @endif
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
            <div class="modal-footer">
                <button type="button" class="btn btn-label-primary" data-bs-dismiss="modal">Cerrar Ventana</button>
            </div>
        </div>
    </div>
</div>
<style>
    tr:focus {
        outline: 2px solid #969cff;
        background-color: #e7e7ff;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalSearchProduct');
        const searchInput = modal.querySelector('#modal-search-input');
        let productRows = modal.querySelectorAll('tbody tr');

        // Enfocar automáticamente el input al cargar la página
        searchInput.focus();

        // Función para enfocar una fila específica
        function focusRow(index) {
            if (index >= 0 && index < productRows.length) {
                productRows[index].focus();
            }
        }

        // Escuchar eventos de teclado dentro del modal
        modal.addEventListener('keydown', function(event) {
            const focusedElement = document.activeElement;
            const index = Array.from(productRows).indexOf(focusedElement);

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (index === -1) {
                    focusRow(0);
                } else if (index < productRows.length - 1) {
                    focusRow(index + 1);
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index > 0) {
                    focusRow(index - 1);
                } else if (index === 0) {
                    searchInput.focus();
                }
            } else if (event.key === 'Enter' && index !== -1) {
                event.preventDefault();
                const addButton = productRows[index].querySelector('button.btn-primary');
                if (addButton) {
                    addButton.click();
                    setTimeout(() => {
                        $('#modalSearchProduct').modal(
                        'hide'); // Cerrar modal después de agregar producto
                    }, 500);
                }
            }
        });

        // Cerrar modal al hacer clic en "Agregar"
        document.addEventListener('click', function(event) {
            if (event.target.closest('.btn-primary')) {
                setTimeout(() => {
                    $('#modalSearchProduct').modal(
                    'hide'); // Cerrar modal después de agregar producto
                }, 500);
            }
        });

        // Actualizar las filas después de una actualización de Livewire
        document.addEventListener('livewire:update', function() {
            productRows = modal.querySelectorAll('tbody tr');
        });

        // Enfocar el input de búsqueda al mostrar el modal
        $('#modalSearchProduct').on('shown.bs.modal', function() {
            searchInput.focus();
            searchInput.select();
        });
    });
</script>
