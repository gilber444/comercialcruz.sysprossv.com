<div>

    {{-- ══════════════════════════════════════════════════════
         HEADER + FILTROS (unificados)
    ══════════════════════════════════════════════════════ --}}
    <div class="card shadow-sm mb-4">
        {{-- Encabezado degradado --}}
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-cart-flatbed"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">
                            Consulta y análisis de compras por proveedor, sucursal y período
                        </div>
                    </div>
                </div>
                <div style="color:#fff; text-align:right;">
                    <div style="font-size:10px; opacity:.75; letter-spacing:.4px; text-transform:uppercase;">Registros</div>
                    <div style="font-size:20px; font-weight:700;">{{ count($data) }}</div>
                </div>
            </div>
        </div>
        {{-- Filtros --}}
        <div class="card-body py-3 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model="sucursal">
                        <option value="0">Todas</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Proveedor</label>
                    <select class="form-select form-select-sm" wire:model="proveedor">
                        <option value="0">Todos</option>
                        @foreach ($proveedores as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Tipo de factura</label>
                    <select class="form-select form-select-sm" wire:model="facturador">
                        <option value="0">Todos</option>
                        @foreach ($facturadores as $f)
                            <option value="{{ $f->id }}">{{ $f->tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Tipo de reporte</label>
                    <select class="form-select form-select-sm" wire:model="reporteType">
                        <option value="0">Compras del día</option>
                        <option value="1">Rango de fechas</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Fecha desde</label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm"
                        @if($reporteType == 0) disabled @endif>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Fecha hasta</label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm"
                        @if($reporteType == 0) disabled @endif>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <button class="btn btn-sm btn-primary rounded-pill px-4" wire:click="ComprasByDate"
                    wire:loading.attr="disabled" wire:target="ComprasByDate">
                    <span wire:loading.remove wire:target="ComprasByDate">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                    </span>
                    <span wire:loading wire:target="ComprasByDate">
                        <span class="spinner-border spinner-border-sm me-1"></span> Cargando...
                    </span>
                </button>
                @if(count($data) > 0)
                    @php
                        $proveedorParam = $proveedor ?? '0';
                        $f1 = ($reporteType == 1 && $dateFrom) ? $dateFrom : now()->format('Y-m-d');
                        $f2 = ($reporteType == 1 && $dateTo)   ? $dateTo   : now()->format('Y-m-d');
                    @endphp
                    <a href="{{ url('report/pdfCompras/' . $proveedorParam . '/' . $reporteType . '/' . $f1 . '/' . $f2) }}"
                        class="btn btn-sm btn-label-danger rounded-pill px-3" target="_blank">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ url('report/excelCompras/' . $proveedorParam . '/' . $reporteType . '/' . $f1 . '/' . $f2) }}"
                        class="btn btn-sm btn-label-success rounded-pill px-3" target="_blank">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         KPIs
    ══════════════════════════════════════════════════════ --}}
    @if(count($data) > 0)
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #233446;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Subtotal</div>
                            <div class="fw-bold mt-1" style="font-size:18px;">$ {{ number_format($totalSubtotal, 2) }}</div>
                        </div>
                        <div class="text-primary" style="background:rgba(74,144,217,.12); border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; font-size:16px;">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #4a90d9;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">IVA (13%)</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#4a90d9;">$ {{ number_format($totalIva, 2) }}</div>
                        </div>
                        <div style="background:#e8f2fd; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#4a90d9; font-size:16px;">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #fd7e14;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Percepción</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#fd7e14;">$ {{ number_format($totalPercepcion, 2) }}</div>
                        </div>
                        <div style="background:#fff3e0; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#fd7e14; font-size:16px;">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #28a745;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Total general</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#28a745;">$ {{ number_format($totalGeneral, 2) }}</div>
                        </div>
                        <div style="background:#e8f5e9; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#28a745; font-size:16px;">
                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         TABLA
    ══════════════════════════════════════════════════════ --}}
    <div class="card shadow-sm">
        <div class="card-header border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
            <span class="fw-bold small"><i class="fa-solid fa-list me-2 text-primary"></i>Detalle de compras</span>
            <span class="badge bg-label-primary rounded-pill">{{ count($data) }} registros</span>
        </div>
        <div class="table-responsive" style="max-height:520px; overflow-y:auto;">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead style="position:sticky; top:0; z-index:10;">
                    <tr style="background:#233446; color:#fff;">
                        <th class="text-center ps-3" style="width:50px;">#</th>
                        <th class="text-center" style="width:130px;">N° Control</th>
                        <th class="text-center" style="width:130px;">Cód. Generación</th>
                        <th class="text-center" style="width:100px;">Fecha</th>
                        <th class="text-center" style="width:110px;">Tipo</th>
                        <th>Proveedor</th>
                        <th class="text-center" style="width:70px;">Items</th>
                        <th class="text-end" style="width:110px;">Subtotal</th>
                        <th class="text-end" style="width:100px;">IVA</th>
                        <th class="text-end" style="width:100px;">Percepción</th>
                        <th class="text-end pe-3" style="width:110px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $i => $r)
                        <tr>
                            <td class="text-center ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td class="text-center">
                                <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:#233446;">
                                    {{ $r->correlativo }}
                                </code>
                            </td>
                            <td class="text-center">
                                <span class="text-muted small">{{ $r->serie ?? '—' }}</span>
                            </td>
                            <td class="text-center small">
                                {{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-secondary rounded-pill" style="font-size:10px;">
                                    {{ $r->facturadors }}
                                </span>
                            </td>
                            <td class="fw-semibold small">{{ $r->nombre }}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-label-primary rounded-pill px-2"
                                    style="font-size:11px;"
                                    wire:click.prevent="getDetails({{ $r->id }})"
                                    data-bs-toggle="modal" data-bs-target="#modalDetailsCompras">
                                    <i class="fa-solid fa-eye me-1"></i>{{ number_format($r->items, 0) }}
                                </button>
                            </td>
                            <td class="text-end small">$ {{ number_format($r->subtotal, 2) }}</td>
                            <td class="text-end small">$ {{ number_format($r->iva, 2) }}</td>
                            <td class="text-end small text-muted">$ {{ number_format($r->percepcion, 2) }}</td>
                            <td class="text-end pe-3 fw-semibold">$ {{ number_format($r->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                Sin resultados. Ajusta los filtros y presiona Consultar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($data) > 0)
                <tfoot>
                    <tr style="background:#233446; color:#fff; font-weight:700; font-size:.8rem;">
                        <td colspan="7" class="text-end ps-3 pe-3" style="letter-spacing:.4px;">TOTALES</td>
                        <td class="text-end">$ {{ number_format($totalSubtotal, 2) }}</td>
                        <td class="text-end">$ {{ number_format($totalIva, 2) }}</td>
                        <td class="text-end">$ {{ number_format($totalPercepcion, 2) }}</td>
                        <td class="text-end pe-3">$ {{ number_format($totalGeneral, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @include('livewire.reports.compras-detalles')
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.livewire.on('show-modal', function () {
            var modal = new bootstrap.Modal(document.getElementById('modalDetailsCompras'));
            modal.show();
        });
    });
</script>
