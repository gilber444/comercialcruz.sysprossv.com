<div>

    {{-- HEADER + FILTROS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-boxes-stacking"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">
                            Reporte de inventario por sucursal y tipo de existencia
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body py-3 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-5">
                    <label class="form-label fw-semibold small mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model="sucursal">
                        <option value="0">Todas las sucursales</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label fw-semibold small mb-1">Tipo de reporte</label>
                    <select class="form-select form-select-sm" wire:model="tipo">
                        <option value="0">Productos con existencia</option>
                        <option value="1">Productos a cero</option>
                        <option value="2">Productos en negativo</option>
                    </select>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2" style="border-radius:8px;">
                    <i class="fa-solid fa-circle-info"></i>
                    Selecciona los filtros y genera el reporte directamente en PDF o Excel.
                </div>
                <a href="{{ url('report/pdfInventario/' . $sucursal . '/' . ($tipo ?? 0)) }}"
                    class="btn btn-sm btn-label-danger rounded-pill px-3" target="_blank">
                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                </a>
                <a href="{{ url('report/excelInventario/' . $sucursal . '/' . ($tipo ?? 0)) }}"
                    class="btn btn-sm btn-label-success rounded-pill px-3" target="_blank">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </a>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('livewire:load', function() {
        Livewire.hook('message.sent', (message, component) => {
            if (message.updateQueue[0].method === 'reporteGenerar') {
                Swal.fire({
                    title: 'Generando reporte...',
                    text: 'Por favor espere.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }
        });
        Livewire.on('reporte-generado', () => {
            Swal.close();
            Swal.fire({ icon: 'success', title: 'Reporte listo', text: 'Los datos se han generado correctamente.', timer: 2000, showConfirmButton: false });
        });
    });
</script>
