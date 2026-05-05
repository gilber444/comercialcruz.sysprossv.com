<!-- Modal Detalle de Precios -->
<div wire:ignore.self class="modal fade" id="detallePrecios" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-label-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:36px; height:36px;">
                        <i class="fa-solid fa-tags text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:15px;">{{ $productoName }}</div>
                        <div class="text-muted" style="font-size:11px;">Selecciona una presentación y presiona Enter</div>
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div> 

            {{-- BODY --}}
            <div class="modal-body p-0">
                <table class="table table-hover table-sm align-middle mb-0" id="detallePreciosTable" style="font-size:.83rem;">

                    {{-- SECCIÓN PRECIOS --}}
                    <thead class="table-light">
                        <tr>
                            <th class="text-center ps-3" style="width:35%;">
                                <i class="fa-solid fa-box me-1 text-primary opacity-75"></i>PRESENTACIÓN
                            </th>
                            <th class="text-center" style="width:15%;">CANTIDAD</th>
                            <th class="text-end"    style="width:25%;">P. UNITARIO</th>
                            <th class="text-end pe-3" style="width:25%;">TOTAL</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Divisor PRECIOS --}}
                        <tr class="table-light">
                            <td colspan="4" class="ps-3 py-1">
                                <span class="badge bg-label-primary rounded-pill" style="font-size:11px;">
                                    <i class="fa-solid fa-tag me-1"></i>PRECIOS
                                </span>
                            </td>
                        </tr>
                        @forelse ($detallePrecios as $p)
                            <tr class="selectable-row" data-id="{{ $p->id }}">
                                <td class="ps-3 fw-semibold">{{ $p->presentacion }}</td>
                                <td class="text-center">{{ $p->cantidad }}</td>
                                <td class="text-end">$ {{ number_format($p->pventasiva, 2) }}</td>
                                <td class="text-end pe-3">$ {{ number_format($p->pvventa, 2) }}</td>
                            </tr>
                        @empty
                            <tr class="not-selectable">
                                <td colspan="4" class="text-center text-muted py-3 small">
                                    <i class="fa-solid fa-circle-xmark me-1 opacity-50"></i>Sin precios disponibles
                                </td>
                            </tr>
                        @endforelse

                        {{-- Divisor ESCALAS --}}
                        <tr class="table-light">
                            <td colspan="4" class="ps-3 py-1">
                                <span class="badge bg-label-info rounded-pill" style="font-size:11px;">
                                    <i class="fa-solid fa-layer-group me-1"></i>ESCALAS
                                </span>
                            </td>
                        </tr>
                        @forelse ($detalleEscalas as $e)
                            <tr class="not-selectable">
                                <td class="ps-3 text-muted">{{ $e->presentacion }}</td>
                                <td class="text-center text-muted">{{ $e->cantidad }}</td>
                                <td class="text-end text-muted">$ {{ number_format($e->pventasiva, 2) }}</td>
                                <td class="text-end pe-3 text-muted">$ {{ number_format($e->pvventa, 2) }}</td>
                            </tr>
                        @empty
                            <tr class="not-selectable">
                                <td colspan="4" class="text-center text-muted py-3 small">
                                    <i class="fa-solid fa-circle-xmark me-1 opacity-50"></i>Sin escalas disponibles
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            {{-- FOOTER --}}
            <div class="modal-footer py-2">
                <small class="text-muted me-auto">
                    <i class="fa-solid fa-keyboard me-1"></i>
                    Usa <kbd>↑</kbd> <kbd>↓</kbd> para navegar y <kbd>Enter</kbd> para seleccionar
                </small>
                <button type="button" class="btn btn-sm btn-label-secondary rounded-pill" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    #detallePreciosTable .selectable-row { cursor: pointer; }
    #detallePreciosTable .selectable-row:focus {
        outline: 2px solid #696cff;
        background-color: #e7e7ff;
    }
    #detallePreciosTable .not-selectable { opacity: .75; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let selectedIndex = -1;
    let rows = [];

    function updateSelection() {
        rows.forEach(row => row.classList.remove("table-primary"));
        if (rows[selectedIndex]) {
            rows[selectedIndex].classList.add("table-primary");
            rows[selectedIndex].scrollIntoView({
                block: "nearest",
                behavior: "smooth"
            });
        }
    }

    function setupNavigation() {
        rows = Array.from(document.querySelectorAll("#detallePreciosTable .selectable-row"));
        if (rows.length > 0) {
            selectedIndex = 0; // Selecciona la primera fila
            updateSelection();
        }
    }

    // Cuando se abre el modal
    document.getElementById("detallePrecios").addEventListener("shown.bs.modal", function() {
        setTimeout(setupNavigation, 200);
    });

    // Navegación con teclado
    document.addEventListener("keydown", function(event) {
        if (!document.getElementById("detallePrecios").classList.contains("show")) return;

        rows = Array.from(document.querySelectorAll("#detallePreciosTable .selectable-row"));
        if (rows.length === 0) return;

        if (event.key === "ArrowDown") {
            event.preventDefault();
            if (selectedIndex < rows.length - 1) {
                selectedIndex++;
                updateSelection();
            }
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            if (selectedIndex > 0) {
                selectedIndex--;
                updateSelection();
            }
        } else if (event.key === "Enter") {
            event.preventDefault();
            if (selectedIndex !== -1 && rows[selectedIndex]) {
                let precioId = rows[selectedIndex].getAttribute("data-id");
                if (precioId) {
                    Livewire.emit("Add", precioId);
                    $("#detallePrecios").modal("hide");
                    setTimeout(() => {
                        Livewire.emit("focus-primer-cantidad");
                    }, 200);
                }
            }
        }
    });

    // Evitar clic en filas no seleccionables
    document.addEventListener("click", function(e) {
        const row = e.target.closest("tr");
        if (!row) return;

        if (row.classList.contains("not-selectable")) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }

        if (row.classList.contains("selectable-row")) {
            rows = Array.from(document.querySelectorAll("#detallePreciosTable .selectable-row"));
            selectedIndex = rows.indexOf(row);
            updateSelection();
        }
    });
});
</script>
