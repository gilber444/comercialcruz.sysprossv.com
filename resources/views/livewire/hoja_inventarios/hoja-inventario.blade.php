<div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── HEADER ── --}}
    <div class="mb-4">
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-boxes-stacking me-2 text-primary"></i>{{ $componentName }}
        </h5>
    </div>

    {{-- ── BANNER APERTURA ACTIVA ── --}}
    @if($aperturaActiva)
    <div class="alert alert-danger d-flex align-items-center gap-3 mb-4 py-2 px-3" style="border-radius:10px;">
        <i class="fa-solid fa-circle-dot fa-beat" style="color:#dc3545;"></i>
        <div class="small">
            <strong>Inventario en curso</strong> —
            Apertura <strong>#{{ $aperturaActiva->id }}</strong> |
            Iniciado: {{ $aperturaActiva->fecha_apertura }} {{ $aperturaActiva->hora_apertura }} |
            Sucursal: <strong>{{ optional($aperturaActiva->Rsucursales)->nombre ?? '—' }}</strong>
        </div>
    </div>
    @endif

    {{-- ── CARD TABLA ── --}}
    <div class="card shadow-sm">
        <div class="card-header border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2 text-primary"></i>Aperturas de Inventario</h6>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($aperturaActiva)
                        @can('AperturaInventario_Close')
                            <button type="button" class="btn btn-sm btn-danger rounded-pill"
                                wire:click="confirmCerrarApertura"
                                wire:loading.attr="disabled" wire:target="confirmCerrarApertura">
                                <span wire:loading wire:target="confirmCerrarApertura">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                </span>
                                <i class="fa-solid fa-lock me-1"></i> Cerrar Inventario
                            </button>
                        @endcan
                        @can('hojaInventario_Print')
                            <a href="{{ route('hoja_inventarios.vistaHojaCompleta', $aperturaActiva->id) }}"
                               class="btn btn-sm btn-label-info rounded-pill" target="_blank">
                                <i class="fa-solid fa-eye me-1"></i> Vista previa
                            </a>
                            <a href="{{ route('hoja_inventarios.noContados', $aperturaActiva->id) }}"
                               class="btn btn-sm btn-label-warning rounded-pill" target="_blank">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> No contados
                            </a>
                        @endcan
                        @can('hojaInventario_Create')
                            <button type="button" class="btn btn-sm btn-primary rounded-pill"
                                wire:click="abrirModalNuevaHoja">
                                <i class="fa-solid fa-plus me-1"></i> Agregar hoja
                            </button>
                        @endcan
                    @else
                        @can('AperturaInventario_Create')
                            <button type="button" class="btn btn-sm btn-success rounded-pill"
                                data-bs-toggle="modal" data-bs-target="#modalAperturaInventario">
                                <i class="fa-solid fa-play me-1"></i> Iniciar Inventario
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
            <div class="input-group input-group-sm" style="max-width:320px;">
                <span class="input-group-text bg-white"><i class="bx bx-search text-muted"></i></span>
                <input type="text" wire:model="search" class="form-control" placeholder="Buscar apertura...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center ps-3" style="width:60px;">#</th>
                        <th class="text-center">Apertura</th>
                        <th class="text-center">Cierre</th>
                        <th class="text-center">Responsable</th>
                        <th class="text-center">Sucursal</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventarios as $in)
                        @php
                            $usuario      = auth()->user();
                            $puedeGestionar = $in->user === $usuario->id || $in->responsable === $usuario->id || $usuario->can('hojaInventario_Update');
                            $estado       = $in->estado;
                            $esActiva     = $estado === 'Activa' || $estado === 'Pendiente';
                            $esCerrada    = $estado === 'Cerrada' || $estado === 'Cerrado' || $estado === 'Realizado';
                            $badgeClass   = $esActiva ? 'bg-label-warning' : 'bg-label-success';
                            $badgeIcon    = $esActiva ? 'fa-circle-dot' : 'fa-circle-check';
                        @endphp
                        <tr>
                            <td class="text-center ps-3">
                                <span class="fw-bold text-muted">#{{ $in->id }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold small">{{ $in->fecha_apertura }}</div>
                                <div class="text-muted" style="font-size:.7rem;">{{ $in->hora_apertura }}</div>
                            </td>
                            <td>
                                @if($in->fecha_cierre || $in->hora_cierre)
                                    <div class="fw-semibold small">{{ $in->fecha_cierre }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">{{ $in->hora_cierre }}</div>
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
                                    {{-- Ver hojas --}}
                                    @canany('hojaInventario_Create','hojaInventario_Update')
                                    <button type="button"
                                        class="btn btn-sm btn-label-primary rounded-pill"
                                        wire:click="verHojas({{ $in->id }})"
                                        data-bs-toggle="modal" data-bs-target="#modalHojasInventario"
                                        title="Ver hojas">
                                        <i class="fa-solid fa-layer-group me-1"></i> Hojas
                                    </button>
                                    @endcanany

                                    @if($esCerrada)
                                        @can('hojaInventario_Print')
                                            <a href="{{ route('hoja_inventarios.vistaHojaCompleta', $in->id) }}"
                                               class="btn btn-sm btn-label-info rounded-pill" target="_blank" title="Vista hoja completa">
                                                <i class="fa-solid fa-file-lines me-1"></i> Ver
                                            </a>
                                            <a href="{{ route('hoja_inventarios.excelApertura', $in->id) }}"
                                               class="btn btn-sm btn-label-success rounded-pill" title="Excel">
                                                <i class="fa-solid fa-file-excel me-1"></i> Excel
                                            </a>
                                            <a href="{{ route('hoja_inventarios.noContados', $in->id) }}"
                                               class="btn btn-sm btn-label-warning rounded-pill" target="_blank" title="No contados">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
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
                                No hay aperturas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($inventarios->hasPages())
        <div class="card-footer border-top py-2">
            {{ $inventarios->links() }}
        </div>
        @endif
    </div>

    {{-- ── MODAL: Productos no contados (inline) ── --}}
    <div wire:ignore.self class="modal fade" id="modalNoContados" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#233446,#2d4a63); color:#fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-triangle-exclamation me-2" style="color:#ffc107;"></i>
                        {{ $tituloModalNoContados }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    @if(count($productosNoContados) === 0)
                        <div class="p-4 text-center">
                            <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                            <p class="fw-semibold text-success mb-0">¡Todos los productos han sido contados!</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center ps-3" style="width:40px;">#</th>
                                        <th class="text-center" style="width:140px;">Código</th>
                                        <th>Descripción</th>
                                        <th class="text-end pe-3" style="width:130px;">Existencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productosNoContados as $i => $nc)
                                    <tr style="{{ $nc->existencia > 0 ? 'background:#fff8f8;' : '' }}">
                                        <td class="text-center ps-3 text-muted small">{{ $i + 1 }}</td>
                                        <td class="text-center">
                                            <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:#233446;">
                                                {{ $nc->codebar ?? '—' }}
                                            </code>
                                        </td>
                                        <td class="fw-semibold small">{{ $nc->nombre }}</td>
                                        <td class="text-end pe-3 fw-bold {{ $nc->existencia > 0 ? 'text-danger' : 'text-muted' }}">
                                            @if($nc->existencia > 0)
                                                <i class="fa-solid fa-circle-exclamation me-1" style="font-size:11px;"></i>
                                            @endif
                                            {{ number_format($nc->existencia, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-label-secondary rounded-pill" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Nueva Hoja ── --}}
    <div wire:ignore.self class="modal fade" id="modalNuevaHoja" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#233446,#2d4a63); color:#fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-plus me-2"></i> Nueva Hoja de Inventario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Sucursal</label>
                        <input type="text" class="form-control form-control-sm" wire:model="hojaSucursalNombre" disabled>
                    </div>
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-semibold small">Fecha</label>
                            <input type="text" class="form-control form-control-sm" wire:model="hojaFecha" disabled>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-semibold small">Correlativo</label>
                            <input type="text" class="form-control form-control-sm" wire:model.defer="hojaCorrelativo" placeholder="Ej: 1-A">
                            @error('hojaCorrelativo') <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-label-secondary rounded-pill" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3"
                        wire:click="crearHoja"
                        wire:loading.attr="disabled" wire:target="crearHoja">
                        <span wire:loading wire:target="crearHoja">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-check me-1"></i> Crear Hoja
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.hoja_inventarios.apertura')
    @include('livewire.hoja_inventarios.modalhojas')

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('swal:confirm', function (e) {
            Swal.fire({
                title: e.detail.title || 'Confirmar',
                text: e.detail.text || '',
                icon: e.detail.icon || 'warning',
                showCancelButton: true,
                confirmButtonText: e.detail.confirmButtonText || 'Confirmar',
                cancelButtonText: e.detail.cancelButtonText || 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) Livewire.emit(e.detail.event);
            });
        });

        window.addEventListener('open-no-contados-tab', (e) => window.open(e.detail.url, '_blank'));

        window.addEventListener('open-nueva-hoja-modal', () => {
            new bootstrap.Modal(document.getElementById('modalNuevaHoja')).show();
        });
        window.addEventListener('close-nueva-hoja-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('modalNuevaHoja'))?.hide();
        });
    });

    function confirmarReabrir(hojaId) {
        Swal.fire({
            title: '¿Reabrir esta hoja?',
            text: 'Se revertirán los cambios al inventario y al kardex generados por esta hoja.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, reabrir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ffc107',
        }).then((result) => {
            if (result.isConfirmed) Livewire.emit('reabrirHoja', hojaId);
        });
    }
</script>

@include('common.notis')
