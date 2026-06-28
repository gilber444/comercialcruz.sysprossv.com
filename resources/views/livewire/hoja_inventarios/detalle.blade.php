<div wire:ignore.self class="modal fade" id="modalSearchProduct" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="bg-label-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:36px; height:36px;">
                        <i class="fa-solid fa-crosshairs text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" wire:model="search" id="modal-search-input"
                                class="form-control"
                                placeholder="Buscar por nombre, código de barras o categorías...">
                        </div>
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
                </div>
            </div> 

            {{-- BODY --}}
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: .82rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center ps-3" style="width:130px;">CÓDIGO BARRA</th>
                                <th>DESCRIPCIÓN</th>
                                <th class="text-center" style="width:100px;">MEDIDA</th>
                                <th class="text-center" style="width:80px;">CANTIDAD</th>
                                <th class="text-end"    style="width:90px;">COSTO</th>
                                <th class="text-center pe-3" style="width:100px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr tabindex="0">
                                    <td class="text-center ps-3">
                                        <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px;">
                                            {{ $product->codebar }}
                                        </code>
                                    </td>
                                    <td class="fw-semibold">{{ $product->Rproductos->nombreProducto }}</td>
                                    <td class="text-center text-muted">{{ $product->presentacion }}</td>
                                    <td class="text-center">{{ $product->cantidad }}</td>
                                    <td class="text-end">$ {{ number_format($product->costosiva, 2) }}</td>
                                    <td class="text-center pe-3">
                                        <button wire:click.prevent="Add({{ $product->producto }})"
                                            class="btn btn-sm btn-label-primary rounded-pill">
                                            <i class="fa-solid fa-plus me-1" style="font-size:10px;"></i> Agregar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-crosshairs fa-2x mb-2 d-block opacity-25"></i>
                                        Escribe para buscar un producto
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-label-secondary rounded-pill" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    tr:focus {
        outline: 2px solid #696cff;
        background-color: #e7e7ff;
        color: #1a1a1a;
        font-weight: 600;
    }
    tr:focus td { color: #1a1a1a; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalSearchProduct');
        const searchInput = modal.querySelector('#modal-search-input');
        let productRows = Array.from(modal.querySelectorAll('tbody tr'));
        let currentIndex = -1;
        let adding = false;

        function updateFocus() {
            productRows.forEach((row, i) => {
                if (i === currentIndex) {
                    row.focus();
                    row.classList.add('table-active');
                } else {
                    row.classList.remove('table-active');
                }
            });
        }

        $('#modalSearchProduct').on('shown.bs.modal', function() {
            searchInput.focus();
        });

        document.addEventListener('livewire:update', function() {
            productRows = Array.from(modal.querySelectorAll('tbody tr'));
            currentIndex = -1;
        });

        modal.addEventListener('keydown', function(event) {
            const key = event.key;
            if (key === 'ArrowDown' || key === 'ArrowUp' || key === 'Enter') {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
            if (key === 'ArrowDown') {
                if (currentIndex < productRows.length - 1) currentIndex++;
                else currentIndex = 0;
                updateFocus();
            } else if (key === 'ArrowUp') {
                if (currentIndex > 0) currentIndex--;
                else currentIndex = productRows.length - 1;
                updateFocus();
            } else if (key === 'Enter' && currentIndex !== -1) {
                if (adding) return;
                adding = true;
                const addButton = productRows[currentIndex].querySelector('button');
                if (addButton) {
                    addButton.click();
                    setTimeout(() => {
                        $('#modalSearchProduct').modal('hide');
                        adding = false;
                    }, 500);
                } else {
                    adding = false;
                }
            }
        });

        $('#modalSearchProduct').on('hidden.bs.modal', function() {
            currentIndex = -1;
            adding = false;
            productRows.forEach(r => r.classList.remove('table-active'));
        });
    });
</script>
