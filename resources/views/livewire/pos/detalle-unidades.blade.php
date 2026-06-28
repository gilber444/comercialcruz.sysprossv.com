<!-- Modal Detalle de Precios -->
<div wire:ignore.self class="modal fade" id="detalleUnidades" tabindex="-1" role="dialog">
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
                        @forelse ($detallePrecios as $p)
                            <tr class="selectable-row {{ $p->escala === 'Si' ? 'table-info' : '' }}"
                                data-id="{{ $p->id }}"
                                data-tmpventas-id="{{ $tmpventasId ?? '' }}"> <!-- Se guarda el tmpventasId -->
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
        let tmpventasId = null; // Variable para almacenar el ID del tmpVentas actual

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
                selectedIndex = 0;
                updateSelection();
            }
        }

        document.getElementById("detalleUnidades").addEventListener("shown.bs.modal", function() {
            setTimeout(setupNavigation, 200);
        });

        document.addEventListener("keydown", function(event) {
            if (event.key === "F6") {
                event.preventDefault();
                let focusedElement = document.activeElement;

                if (focusedElement && focusedElement.hasAttribute("wire:model")) {
                    let itemId = focusedElement.getAttribute("wire:model").split(".")[1]; // Obtiene el ID del tmpVentas
                    tmpventasId = itemId; // Guarda el ID para usarlo después
                    Livewire.emit("updateUni", itemId); // Llama a updateUni con el ID
                }
            }

            if (!document.getElementById("detalleUnidades").classList.contains("show")) return;

            rows = Array.from(document.querySelectorAll("#detallePreciosTable .selectable-row"));

            if (rows.length === 0) return;

            if (event.key === "ArrowDown") {
                event.preventDefault();
                selectedIndex = (selectedIndex + 1) % rows.length;
                updateSelection();
            } else if (event.key === "ArrowUp") {
                event.preventDefault();
                selectedIndex = (selectedIndex - 1 + rows.length) % rows.length;
                updateSelection();
            } else if (event.key === "Enter") {
                event.preventDefault();
                if (selectedIndex !== -1 && rows[selectedIndex]) {
                    let precioId = rows[selectedIndex].getAttribute("data-id");

                    if (precioId && tmpventasId) {
                        console.log("Cambiando tmpVentas:", tmpventasId, "Nuevo precio:", precioId);
                        Livewire.emit("CantiUpdate2", precioId, tmpventasId);
                        $("#detalleUnidades").modal("hide");
                    } else {
                        console.error("⚠️ No se encontró 'data-id' o 'tmpventasId'.");
                    }
                }
            }
        });
    });
</script>
