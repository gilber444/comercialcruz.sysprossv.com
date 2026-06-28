<div>

    {{-- HEADER + FILTROS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">
                            Resumen de utilidad por caja, sucursal y período
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
                    <select class="form-select form-select-sm" wire:model="sucursal" wire:change="getCaja"
                        @if(auth()->user()->profile === 'Gerente') disabled @endif>
                        <option value="" disabled selected>Elegir</option>
                        <option value="0">Todas</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Caja</label>
                    <select class="form-select form-select-sm" wire:model="caja">
                        <option value="" disabled selected>Elegir</option>
                        <option value="0">Todas</option>
                        @foreach ($cajas as $c)
                            <option value="{{ $c->id }}">{{ $c->caja }}</option>
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
                <div class="col-sm-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">Fecha desde</label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm"
                        @if($reporteType == 0) disabled @endif>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label fw-semibold small mb-1">Fecha hasta</label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm"
                        @if($reporteType == 0) disabled @endif>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <button class="btn btn-sm btn-primary rounded-pill px-4" wire:click="SalesByDate"
                    wire:loading.attr="disabled" wire:target="SalesByDate">
                    <span wire:loading.remove wire:target="SalesByDate">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                    </span>
                    <span wire:loading wire:target="SalesByDate">
                        <span class="spinner-border spinner-border-sm me-1"></span> Cargando...
                    </span>
                </button>
                @php
                    $f1 = ($reporteType == 1 && $dateFrom) ? $dateFrom : now()->format('Y-m-d');
                    $f2 = ($reporteType == 1 && $dateTo)   ? $dateTo   : now()->format('Y-m-d');
                @endphp
                <a href="{{ url('report/pdfUtilidadSin/' . ($sucursal ?: 0) . '/' . ($caja ?: 0) . '/' . ($reporteType ?: 0) . '/' . $f1 . '/' . $f2) }}"
                    class="btn btn-sm btn-label-primary rounded-pill px-3" target="_blank">
                    <i class="fa-solid fa-eye me-1"></i> Ver
                </a>
                <a href="{{ url('report/excelUtilidadSin/' . ($sucursal ?: 0) . '/' . ($caja ?: 0) . '/' . ($reporteType ?: 0) . '/' . $f1 . '/' . $f2) }}"
                    class="btn btn-sm btn-label-success rounded-pill px-3" target="_blank">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </a>
            </div>
        </div>
    </div>

    {{-- PROGRESO --}}
    <div wire:loading wire:target="SalesByDate" class="w-100 mt-3">
        <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2" style="border-radius:8px;">
            <span class="spinner-border spinner-border-sm"></span>
            <span>Buscando…</span>
        </div>
    </div>

    {{-- KPIs --}}
    @if(count($data) > 0)
    @php
        $utilidadKpi = $totalSales - $totalCosto;
        $margenKpi   = $totalCosto > 0 ? round(($utilidadKpi / $totalCosto) * 100, 2) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #28a745;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Total Ventas</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#28a745;">$ {{ number_format($totalSales, 2) }}</div>
                        </div>
                        <div style="background:#e8f5e9; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#28a745; font-size:16px;">
                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('UtilidadSin_Costo')
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100" style="border-left:4px solid #233446;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Total Costo</div>
                            <div class="fw-bold mt-1" style="font-size:18px;">$ {{ number_format($totalCosto, 2) }}</div>
                        </div>
                        <div style="background:rgba(35,52,70,.1); border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#233446; font-size:16px;">
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
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Utilidad</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#4a90d9;">$ {{ number_format($utilidadKpi, 2) }}</div>
                        </div>
                        <div style="background:#e8f2fd; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#4a90d9; font-size:16px;">
                            <i class="fa-solid fa-chart-line"></i>
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
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size:10px; letter-spacing:.5px;">Margen</div>
                            <div class="fw-bold mt-1" style="font-size:18px; color:#fd7e14;">{{ $margenKpi }} %</div>
                        </div>
                        <div style="background:#fff3e0; border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; color:#fd7e14; font-size:16px;">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>
    @endif

    {{-- TABLA --}}
    <div class="card shadow-sm">
        <div class="card-header border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
            <span class="fw-bold small"><i class="fa-solid fa-list me-2 text-primary"></i>Resumen por caja</span>
            <span class="badge bg-label-primary rounded-pill">{{ count($data) }} registros</span>
        </div>
        <div class="table-responsive" style="max-height:520px; overflow-y:auto;">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead style="position:sticky; top:0; z-index:10;">
                    <tr style="background:#233446; color:#fff;">
                        <th class="text-center ps-3" style="width:50px;">#</th>
                        <th>Sucursal</th>
                        <th class="text-center" style="width:120px;">Caja</th>
                        <th class="text-end" style="width:120px;">Total Ventas</th>
                        @can('UtilidadSin_Costo')
                        <th class="text-end" style="width:120px;">Total Costo</th>
                        @endcan
                        <th class="text-end" style="width:120px;">Utilidad</th>
                        <th class="text-end pe-3" style="width:100px;">% Utilidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $i => $row)
                        @php
                            $venta    = $row->total_venta;
                            $costo    = $row->total_costo;
                            $utilidad = $venta - $costo;
                            $porcentaje = $costo > 0 ? round(($utilidad / $costo) * 100, 2) : 0;
                        @endphp
                        <tr>
                            <td class="text-center ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td class="fw-semibold small">{{ $row->nombre_sucursal }}</td>
                            <td class="text-center small">{{ $row->caja }}</td>
                            <td class="text-end small">$ {{ number_format($venta, 2) }}</td>
                            @can('UtilidadSin_Costo')
                            <td class="text-end small">$ {{ number_format($costo, 2) }}</td>
                            @endcan
                            <td class="text-end small">$ {{ number_format($utilidad, 2) }}</td>
                            <td class="text-end pe-3 fw-semibold">{{ $porcentaje }} %</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                Sin resultados. Ajusta los filtros y presiona Consultar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($data) > 0)
                <tfoot>
                    <tr style="background:#233446; color:#fff; font-weight:700; font-size:.8rem;">
                        <td colspan="3" class="text-end ps-3 pe-3" style="letter-spacing:.4px;">TOTALES</td>
                        <td class="text-end">$ {{ number_format($totalSales, 2) }}</td>
                        @can('UtilidadSin_Costo')
                        <td class="text-end">$ {{ number_format($totalCosto, 2) }}</td>
                        @endcan
                        <td class="text-end">$ {{ number_format($totalSales - $totalCosto, 2) }}</td>
                        <td class="text-end pe-3">
                            @php
                                $margenTotal = $totalCosto > 0 ? round((($totalSales - $totalCosto) / $totalCosto) * 100, 2) : 0;
                                echo $margenTotal . ' %';
                            @endphp
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
