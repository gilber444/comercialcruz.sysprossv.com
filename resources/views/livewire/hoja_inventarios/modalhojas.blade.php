<div wire:ignore.self class="modal fade" id="modalHojasInventario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-label-primary rounded d-flex align-items-center justify-content-center"
                         style="width:38px; height:38px;">
                        <i class="fa-solid fa-layer-group text-primary" style="font-size:16px;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:15px;">Hojas de Inventario</div>
                        <div class="text-muted" style="font-size:11px;">Conteos registrados en esta apertura</div>
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center ps-3" style="width:90px;">Correlativo</th>
                                <th class="text-center">Inicio</th>
                                <th class="text-center">Cierre</th>
                                <th class="text-center">Responsable</th>
                                <th class="text-center">Sucursal</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hojas as $in)
                                @php
                                    $usuario        = auth()->user();
                                    $puedeGestionar = $in->user === $usuario->id
                                                   || $in->responsable === $usuario->id
                                                   || $usuario->can('hojaInventario_Update');
                                    $estado         = $in->estado;
                                    $esActiva       = $estado === 'Activa' || $estado === 'Pendiente';
                                    $esCerrada      = $estado === 'Cerrada' || $estado === 'Realizado';
                                    $badgeClass     = $esActiva ? 'bg-label-warning' : 'bg-label-success';
                                    $badgeIcon      = $esActiva ? 'fa-circle-dot' : 'fa-circle-check';
                                @endphp
                                <tr class="{{ $esActiva ? 'table-warning' : '' }}">
                                    <td class="text-center ps-3">
                                        <span class="badge bg-label-secondary fw-bold" style="font-size:12px;">
                                            {{ $in->correlativo }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-semibold small">{{ $in->fecha_inicio ?? $in->fecha }}</div>
                                        <div class="text-muted" style="font-size:.7rem;">{{ $in->hora_inicio ?? $in->hora }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($in->fecha_fin || $in->hora_fin)
                                            <div class="fw-semibold small">{{ $in->fecha_fin }}</div>
                                            <div class="text-muted" style="font-size:.7rem;">{{ $in->hora_fin }}</div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="avatar avatar-xs bg-label-primary rounded-circle">
                                                <span class="avatar-initial rounded-circle" style="font-size:10px;">
                                                    {{ strtoupper(substr(optional($in->Rusuarios)->name ?? '?', 0, 1)) }}
                                                </span>
                                            </div>
                                            <span class="small fw-semibold">{{ optional($in->Rusuarios)->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="small fw-semibold">{{ optional($in->Rsucursales)->nombre }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $badgeClass }} rounded-pill">
                                            <i class="fa-solid {{ $badgeIcon }} me-1" style="font-size:9px;"></i>
                                            {{ $estado }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">

                                            {{-- Activa: Contar + Cerrar --}}
                                            @if($esActiva && $puedeGestionar)
                                                @can('hojaInventario_Update')
                                                    <a class="btn btn-sm btn-label-warning rounded-pill"
                                                        href="{{ route('editar-hoja', ['hojaId' => $in->id]) }}">
                                                        <i class="bx bx-edit-alt me-1"></i> Contar
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-label-danger rounded-pill"
                                                        onclick="confirmCerrarHoja({{ $in->id }})">
                                                        <i class="fa-solid fa-lock me-1"></i> Cerrar
                                                    </button>
                                                @endcan
                                            @endif

                                            {{-- Cerrada + apertura abierta: Reabrir --}}
                                            @if($esCerrada && $aperturaActiva)
                                                @can('hojaInventario_Update')
                                                    <button type="button" class="btn btn-sm btn-label-warning rounded-pill"
                                                        onclick="confirmarReabrir({{ $in->id }})">
                                                        <i class="fa-solid fa-lock-open me-1"></i> Reabrir
                                                    </button>
                                                @endcan
                                            @endif

                                            {{-- Cerrada: Ver + Excel --}}
                                            @if($esCerrada)
                                                @can('hojaInventario_Print')
                                                    <a href="{{ route('hoja_inventarios.vistaHoja', $in->id) }}"
                                                        class="btn btn-sm btn-label-primary rounded-pill" target="_blank">
                                                        <i class="fa-solid fa-file-lines me-1"></i> Ver
                                                    </a>
                                                    <a href="{{ route('hoja_inventarios.excelHoja', $in->id) }}"
                                                        class="btn btn-sm btn-label-success rounded-pill">
                                                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                                                    </a>
                                                @endcan
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                        No hay hojas registradas en esta apertura.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="modal-footer py-2">
                <small class="text-muted me-auto">
                    <i class="fa-solid fa-layer-group me-1"></i>
                    {{ count($hojas ?? []) }} hoja(s) en esta apertura
                </small>
                <button type="button" class="btn btn-sm btn-label-secondary rounded-pill" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
