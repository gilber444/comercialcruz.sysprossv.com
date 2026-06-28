<div>

    {{-- HEADER + FILTROS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">
                            Análisis de utilidad detallado por producto, sucursal y período
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body py-3 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label fw-semibold small mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model="sucursal" wire:change="getCaja">
                        <option value="0">Todas</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Caja</label>
                    <select class="form-select form-select-sm" wire:model="caja">
                        <option value="0">Todas</option>
                        @foreach ($cajas as $c)
                            <option value="{{ $c->id }}">{{ $c->caja }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Facturador</label>
                    <select class="form-select form-select-sm" wire:model="facturador">
                        <option value="0">Todos</option>
                        @foreach ($facturadores as $f)
                            <option value="{{ $f->id }}">{{ $f->facturador }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Tipo de reporte</label>
                    <select class="form-select form-select-sm" wire:model="reporteType">
                        <option value="0">Ventas del día</option>
                        <option value="1">Rango de fechas</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-1">
                    <label class="form-label fw-semibold small mb-1">Desde</label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm"
                        @if($reporteType == 0) disabled @endif>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Hasta</label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm"
                        @if($reporteType == 0) disabled @endif>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2" style="border-radius:8px;">
                    <i class="fa-solid fa-circle-info"></i>
                    Selecciona los filtros y genera el reporte directamente en PDF.
                </div>
                @php
                    $f1 = ($reporteType == 1 && $dateFrom) ? $dateFrom : now()->format('Y-m-d');
                    $f2 = ($reporteType == 1 && $dateTo)   ? $dateTo   : now()->format('Y-m-d');
                @endphp
                <a href="{{ url('report/pdfUtilidad/' . ($sucursal ?? 0) . '/' . ($caja ?? 0) . '/' . ($reporteType ?? 0) . '/' . ($facturador ?? 0) . '/' . $f1 . '/' . $f2) }}"
                    class="btn btn-sm btn-label-danger rounded-pill px-3" target="_blank">
                    <i class="fa-solid fa-file-pdf me-1"></i> Generar PDF
                </a>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.livewire.on('show-modal', function () {
            var modal = new bootstrap.Modal(document.getElementById('modalDetails'));
            modal.show();
        });
    });
</script>
