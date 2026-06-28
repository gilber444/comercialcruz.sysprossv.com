<!-- Modal Detalle de Precios -->
<div wire:ignore.self class="modal fade" id="detallePrecios" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $productoName }}</h5>
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
            <div class="modal-body">
                <table class="table table-hover table-sm" id="detallePreciosTable">
                    <thead>
                        <tr>
                            <th>Presentación</th>
                            <th>Cantidad</th>
                            <th>P.Unitario</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody> 
                        <tr>
                            <td colspan="4" class="text-center fw-bold">PRECIOS</td>
                        </tr>
                        @forelse ($detallePrecios as $p)
                            <tr class="selectable-row" data-id="{{ $p->id }}">
                                <td class="text-center">{{ $p->presentacion }}</td>
                                <td class="text-center">{{ $p->cantidad }}</td>
                                <td class="text-end"> $ {{ number_format($p->pventasiva, 2) }}</td>
                                <td class="text-end"> $ {{ number_format($p->pvventa, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No hay precios disponibles</td>
                            </tr>
                        @endforelse

                        <tr class="table-info">
                            <td colspan="4" class="text-center fw-bold">ESCALAS</td>
                        </tr>
                        @forelse ($detalleEscalas as $e)
                            <tr class="not-selectable">
                                <td class="text-center">{{ $e->presentacion }}</td>
                                <td class="text-center">{{ $e->cantidad }}</td>
                                <td class="text-end"> $ {{ number_format($e->pventasiva, 2) }}</td>
                                <td class="text-end"> $ {{ number_format($e->pvventa, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No hay Escalas disponibles</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

