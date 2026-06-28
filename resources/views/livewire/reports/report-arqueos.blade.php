<div>

    {{-- HEADER + FILTROS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">
                            Control de arqueos de caja por período, cajero y sucursal
                        </div>
                    </div>
                </div>
                <div style="color:#fff; text-align:right;">
                    <div style="font-size:10px; opacity:.75; letter-spacing:.4px; text-transform:uppercase;">Registros</div>
                    <div style="font-size:20px; font-weight:700;">{{ count($data) }}</div>
                </div>
            </div>
        </div>
        <div class="card-body py-3 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label fw-semibold small mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model="sucursal">
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
                    <label class="form-label fw-semibold small mb-1">Cajero</label>
                    <select class="form-select form-select-sm" wire:model="user">
                        <option value="0">Todos</option>
                        @foreach ($cajeros as $cs)
                            <option value="{{ $cs->id }}">{{ $cs->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Fecha desde</label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label fw-semibold small mb-1">Fecha hasta</label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm">
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <button class="btn btn-sm btn-primary rounded-pill px-4" wire:click="generarReporte"
                    wire:loading.attr="disabled" wire:target="generarReporte">
                    <span wire:loading.remove wire:target="generarReporte">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                    </span>
                    <span wire:loading wire:target="generarReporte">
                        <span class="spinner-border spinner-border-sm me-1"></span> Cargando...
                    </span>
                </button>
                @if(count($data) > 0)
                    @php
                        $f1 = $dateFrom ?? now()->format('Y-m-d');
                        $f2 = $dateTo   ?? now()->format('Y-m-d');
                    @endphp
                    <a href="{{ url('report/pdfArqueos/' . ($sucursal ?? 0) . '/' . ($caja ?? 0) . '/' . ($user ?? 0) . '/' . $f1 . '/' . $f2) }}"
                        class="btn btn-sm btn-label-danger rounded-pill px-3" target="_blank">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ url('report/excelArqueos/' . ($sucursal ?? 0) . '/' . ($caja ?? 0) . '/' . ($user ?? 0) . '/' . $f1 . '/' . $f2) }}"
                        class="btn btn-sm btn-label-success rounded-pill px-3" target="_blank">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    @if(count($data) > 0)
    @php
        $kpiVentas     = collect($data)->sum('totalVentas');
        $kpiEfectivo   = collect($data)->sum('efectivo');
        $kpiTarjeta    = collect($data)->sum('tarjeta');
        $kpiDiferencia = collect($data)->sum('diferencia');
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #28a745;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Venta Total</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#28a745;">$ {{ number_format($kpiVentas, 2) }}</div>
                        </div>
                        <div style="background:#e8f5e9; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#28a745; font-size:16px;">
                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #233446;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Efectivo</div>
                            <div class="fw-bold mt-1" style="font-size:18px;">$ {{ number_format($kpiEfectivo, 2) }}</div>
                        </div>
                        <div style="background:rgba(35,52,70,.1); border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#233446; font-size:16px;">
                            <i class="fa-solid fa-money-bill"></i>
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
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Tarjetas</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#4a90d9;">$ {{ number_format($kpiTarjeta, 2) }}</div>
                        </div>
                        <div style="background:#e8f2fd; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#4a90d9; font-size:16px;">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid {{ $kpiDiferencia < 0 ? '#dc3545' : '#28a745' }};">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Diferencia</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:{{ $kpiDiferencia < 0 ? '#dc3545' : '#28a745' }};">$ {{ number_format($kpiDiferencia, 2) }}</div>
                        </div>
                        <div style="background:{{ $kpiDiferencia < 0 ? '#fde8e8' : '#e8f5e9' }}; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:{{ $kpiDiferencia < 0 ? '#dc3545' : '#28a745' }}; font-size:16px;">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TABLA --}}
    <div class="card shadow-sm">
        <div class="card-header border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
            <span class="fw-bold small"><i class="fa-solid fa-list me-2 text-primary"></i>Detalle de arqueos</span>
            <span class="badge bg-label-primary rounded-pill">{{ count($data) }} registros</span>
        </div>
        <div class="table-responsive" style="max-height:520px; overflow-y:auto;">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead style="position:sticky; top:0; z-index:10;">
                    <tr style="background:#233446; color:#fff;">
                        <th class="text-center ps-3" style="width:70px;">N°</th>
                        <th class="text-center" style="width:95px;">Fecha</th>
                        <th class="text-center" style="width:95px;">Hora</th>
                        <th style="width:110px;">Caja</th>
                        <th>Cajero</th>
                        <th class="text-end" style="width:100px;">V. Total</th>
                        <th class="text-end" style="width:100px;">Efectivo</th>
                        <th class="text-end pe-3" style="width:100px;">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $i => $r)
                        <tr>
                            <td class="text-center ps-3">
                                <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:#233446;">{{ $r->numero }}</code>
                            </td>
                            <td class="text-center small">{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}</td>
                            <td class="text-center small">{{ \Carbon\Carbon::parse($r->hora)->format('g:i:s A') }}</td>
                            <td class="small">{{ $r->caja }}</td>
                            <td class="fw-semibold small">{{ $r->cajero }}</td>
                            <td class="text-end small">$ {{ number_format($r->totalGlobal, 2) }}</td>
                            <td class="text-end small">$ {{ number_format($r->totalEfectivo + $r->remesas, 2) }}</td>
                            <td class="text-end pe-3 fw-semibold" style="color:{{ ($r->diferencia + $r->remesas) < 0 ? '#dc3545' : 'inherit' }};">
                                $ {{ number_format($r->diferencia + $r->remesas, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                Sin resultados. Ajusta los filtros y presiona Consultar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
