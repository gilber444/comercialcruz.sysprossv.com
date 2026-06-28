<!-- Modal Detalle de Precios -->
<div wire:ignore.self class="modal fade" id="modalAnulacionesDetalle" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $productoName }}</h5>
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
            <div class="modal-body">
                <div class="row justify-content-between">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="basic-addon-search31"><i class="bx bx-search"></i></span>
                            <input type="text" wire:model.lazy="search2" class="form-control" placeholder="Buscar..." aria-label="Buscar..." aria-describedby="basic-addon-search31" id="search-input">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="detallePreciosTable">
                        <thead>
                            <tr>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Correlativo</th>
                                <th class="text-center">Facturación</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($detalleAnulaciones as $d)
                                <tr class="selectable-row" data-id="{{ $d->id }}">
                                    <td class="text-center">{{ $d->fecha }}</td>
                                    <td class="text-center">
                                         <a href="javascript:void(0)" class="btn rounded-pill btn-outline-dark btn-sm" wire:click="AnulaDevo({{ $d->id }})" title="Imprimir">
                                            {{ $d->numero && $d->numero !== '' ? $d->numero : $d->correlativo }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $d->Rfacturadores->facturador }}</td>
                                    <td class="text-end"> $ {{ number_format($d->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No hay registros disponibles</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalAnulacionesDetalle');
        const searchInput = modal.querySelector('#search-input');
        let productRows = [];
        let selectedIndex = -1;

        function updateSelection() {
            productRows.forEach(row => row.classList.remove("table-primary"));

            if (selectedIndex >= 0 && selectedIndex < productRows.length) {
                productRows[selectedIndex].classList.add("table-primary");
                productRows[selectedIndex].scrollIntoView({ block: "nearest", behavior: "smooth" });
            }
        }

        function moveSelection(direction) {
            if (productRows.length === 0) return;

            if (direction === "down") {
                if (selectedIndex < productRows.length - 1) {
                    selectedIndex++;
                }
            } else if (direction === "up") {
                if (selectedIndex > 0) {
                    selectedIndex--;
                } else {
                    searchInput.focus();
                    selectedIndex = -1;
                    return;
                }
            }

            updateSelection();
        }

        $('#modalAnulacionesDetalle').on('shown.bs.modal', function() {
            setTimeout(() => {
                searchInput.focus();
                productRows = Array.from(modal.querySelectorAll('tbody .selectable-row'));
                selectedIndex = -1;
            }, 50);
        });

        modal.addEventListener('keydown', function(event) {
            let focusedElement = document.activeElement;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (focusedElement === searchInput) {
                    if (productRows.length > 0) {
                        selectedIndex = 0;
                        updateSelection();
                    }
                } else {
                    moveSelection("down");
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveSelection("up");
            } else if (event.key === 'Enter' && selectedIndex !== -1) {
                event.preventDefault();
                let id = productRows[selectedIndex].getAttribute('data-id');
                if (id) {
                    Livewire.emit('AnulaDevo', id);
                    $('#modalAnulacionesDetalle').modal('hide');
                }
            }
        });

        document.addEventListener('livewire:update', function() {
            productRows = Array.from(modal.querySelectorAll('tbody .selectable-row'));
            selectedIndex = -1;
        });
    });
</script>


