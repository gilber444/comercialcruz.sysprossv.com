<div>

    {{-- HEADER + FILTROS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">
                            Inventario filtrado por sucursal y categoría de producto
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
                <div class="col-sm-6 col-md-5">
                    <label class="form-label fw-semibold small mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model="sucursal">
                        <option value="0">Todas las sucursales</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-5">
                    <label class="form-label fw-semibold small mb-1">Categoría</label>
                    <select class="form-select form-select-sm" wire:model="categoria">
                        <option value="0">Todas las categorías</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->categoria }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <button class="btn btn-sm btn-primary rounded-pill px-4" wire:click="reporteGenerar"
                    wire:loading.attr="disabled" wire:target="reporteGenerar">
                    <span wire:loading.remove wire:target="reporteGenerar">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                    </span>
                    <span wire:loading wire:target="reporteGenerar">
                        <span class="spinner-border spinner-border-sm me-1"></span> Cargando...
                    </span>
                </button>
                @if(count($data) > 0)
                    <a href="{{ url('report/pdfInventarioCategoria/' . $sucursal . '/' . $categoria) }}"
                        class="btn btn-sm btn-label-danger rounded-pill px-3" target="_blank">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ url('report/excelInventarioCategoria/' . $sucursal . '/' . $categoria) }}"
                        class="btn btn-sm btn-label-success rounded-pill px-3" target="_blank">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="card shadow-sm">
        <div class="card-header border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
            <span class="fw-bold small"><i class="fa-solid fa-list me-2 text-primary"></i>Inventario por categoría</span>
            <span class="badge bg-label-primary rounded-pill">{{ count($data) }} registros</span>
        </div>
        <div class="table-responsive" style="max-height:520px; overflow-y:auto;">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead style="position:sticky; top:0; z-index:10;">
                    <tr style="background:#233446; color:#fff;">
                        <th class="text-center ps-3" style="width:50px;">#</th>
                        <th class="text-center" style="width:120px;">Código</th>
                        <th class="text-center" style="width:130px;">Categoría</th>
                        <th>Descripción</th>
                        <th class="text-center" style="width:80px;">Medida</th>
                        <th class="text-center" style="width:130px;">Sucursal</th>
                        <th class="text-end pe-3" style="width:100px;">Existencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $i => $r)
                        <tr>
                            <td class="text-center ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td class="text-center">
                                <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:#233446;">{{ $r->codebar3 }}</code>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-secondary rounded-pill" style="font-size:10px;">{{ $r->categoria }}</span>
                            </td>
                            <td class="fw-semibold small">{{ $r->nombreProducto }}</td>
                            <td class="text-center small">{{ $r->medida }}</td>
                            <td class="text-center small">{{ $r->sucursal }}</td>
                            <td class="text-end pe-3 fw-semibold">{{ number_format($r->existencia, 2) }}</td>
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
            </table>
        </div>
    </div>

</div>
