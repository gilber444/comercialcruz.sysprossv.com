<div>
    <div class="card shadow-sm">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#233446,#2d4a63); border-radius: calc(.375rem - 1px) calc(.375rem - 1px) 0 0;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,.15); border-radius:10px; width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff;">
                        <i class="fa-solid fa-code-branch"></i>
                    </div>
                    <div style="color:#fff;">
                        <div style="font-size:16px; font-weight:700; letter-spacing:.3px;">{{ $componentName }}</div>
                        <div style="font-size:11px; opacity:.8; margin-top:1px;">Gestión de versiones, pruebas y liberaciones</div>
                    </div>
                </div>
                <span class="badge bg-white text-primary px-3 py-2">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    Las versiones se generan automáticamente desde el código
                </span>
            </div>
        </div>

        <div class="card-body py-3 px-3">
            {{-- Buscador y filtros --}}
            <div class="row g-2 align-items-center mb-4">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" wire:model.debounce.300ms="search" class="form-control border-start-0" placeholder="Buscar versión o descripción...">
                        @if($search)
                            <button class="btn btn-outline-secondary" type="button" wire:click="$set('search','')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-md-7 col-lg-8">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <button wire:click="$set('filterStatus','all')" class="btn btn-sm {{ $filterStatus === 'all' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3">
                            Todas <span class="badge bg-white text-primary ms-1">{{ $counts['all'] }}</span>
                        </button>
                        <button wire:click="$set('filterStatus','active')" class="btn btn-sm {{ $filterStatus === 'active' ? 'btn-success' : 'btn-outline-success' }} rounded-pill px-3">
                            Activas <span class="badge bg-white text-success ms-1">{{ $counts['active'] }}</span>
                        </button>
                        <button wire:click="$set('filterStatus','inactive')" class="btn btn-sm {{ $filterStatus === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' }} rounded-pill px-3">
                            Inactivas <span class="badge bg-white text-secondary ms-1">{{ $counts['inactive'] }}</span>
                        </button>
                        <button wire:click="$set('filterStatus','tests')" class="btn btn-sm {{ $filterStatus === 'tests' ? 'btn-warning' : 'btn-outline-warning' }} rounded-pill px-3">
                            Pruebas <span class="badge bg-white text-warning ms-1">{{ $counts['tests'] }}</span>
                        </button>
                        <button wire:click="$set('filterStatus','released')" class="btn btn-sm {{ $filterStatus === 'released' ? 'btn-info' : 'btn-outline-info' }} rounded-pill px-3">
                            Liberadas <span class="badge bg-white text-info ms-1">{{ $counts['released'] }}</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Grid de tarjetas --}}
            <div class="row g-3">
                @forelse($features as $feature)
                    <div class="col-12">
                        <div class="card mb-0 shadow-sm" style="border-left:4px solid {{ $feature->activo ? '#28a745' : '#6c757d' }};">
                            <div class="card-body py-3 px-3">
                                <div class="row align-items-center g-3">
                                    {{-- Versión y fecha --}}
                                    <div class="col-md-2 col-lg-2">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-primary align-self-start" style="font-size:.85rem; letter-spacing:.5px;">V{{ $feature->version }}</span>
                                            <small class="text-muted" style="font-size:.7rem;">{{ $feature->created_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>

                                    {{-- Descripción --}}
                                    <div class="col-md-5 col-lg-6">
                                        <p class="mb-0 text-secondary" style="font-size:.9rem; line-height:1.4;">{{ $feature->descripcion }}</p>
                                    </div>

                                    {{-- Estados --}}
                                    <div class="col-md-2 col-lg-2">
                                        <div class="d-flex flex-wrap gap-2">
                                            @if($feature->produccion)
                                                <span class="badge bg-success"><i class="fa-solid fa-lock-open me-1"></i>LIBERADO</span>
                                            @else
                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-flask me-1"></i>PRUEBAS</span>
                                            @endif

                                            @if($feature->activo)
                                                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>ACTIVO</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fa-solid fa-ban me-1"></i>INACTIVO</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Acciones --}}
                                    <div class="col-md-3 col-lg-2">
                                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                            @if($feature->produccion)
                                                {{-- Versión liberada: bloqueada --}}
                                                <span class="badge bg-secondary"><i class="fa-solid fa-lock me-1"></i>Bloqueado</span>
                                            @else
                                                {{-- Toggle activo --}}
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        wire:click="toggleActivo({{ $feature->id }})"
                                                        {{ $feature->activo ? 'checked' : '' }}>
                                                </div>

                                                {{-- Liberar a producción --}}
                                                <button class="btn btn-sm btn-success rounded-pill px-3"
                                                    wire:click="toggleProduccion({{ $feature->id }})"
                                                    title="Liberar a producción">
                                                    <i class="fa-solid fa-rocket"></i>
                                                    <span class="d-none d-lg-inline ms-1">Liberar</span>
                                                </button>

                                                {{-- Editar --}}
                                                <button class="btn btn-sm btn-info rounded-pill px-3" wire:click="Edit({{ $feature->id }})" data-bs-toggle="modal" data-bs-target="#modalFeature">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>

                                                {{-- Eliminar --}}
                                                <button class="btn btn-sm btn-danger rounded-pill px-3" onclick="confirmDelete({{ $feature->id }}, '{{ $feature->version }}')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0">No se encontraron versiones.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Paginación --}}
            <div class="d-flex justify-content-center mt-4">
                {{ $features->links() }}
            </div>
        </div>
    </div>

    {{-- Modal agregar/editar --}}
    <div wire:ignore.self class="modal fade" id="modalFeature" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#233446,#2d4a63); color:#fff;">
                    <h5 class="modal-title">
                        {{ $selected_id > 0 ? 'Editar versión' : 'Nueva versión' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="resetUI"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Versión</label>
                        <input type="text" wire:model="version" class="form-control" placeholder="Ej. 1.0.5">
                        @error('version') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Código de feature</label>
                        <input type="text" wire:model="codigo" class="form-control" placeholder="Ej. pos_cobro_tarjeta_saldo_cuenta">
                        <div class="form-text small">Identificador único para usar en el código con <code>Feature::isEnabled('codigo')</code>.</div>
                        @error('codigo') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea wire:model="descripcion" class="form-control" rows="4" placeholder="Resumen de cambios..."></textarea>
                        @error('descripcion') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click="resetUI">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="{{ $selected_id > 0 ? 'Update' : 'Store' }}">
                        <span wire:loading.remove wire:target="{{ $selected_id > 0 ? 'Update' : 'Store' }}">
                            <i class="fa-solid fa-save me-1"></i> Guardar
                        </span>
                        <span wire:loading wire:target="{{ $selected_id > 0 ? 'Update' : 'Store' }}">
                            <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('common.notis')
</div>

<script>
    function confirmDelete(id, version) {
        if (confirm('¿Eliminar la versión V' + version + '?')) {
            window.livewire.emit('destroy', id);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        window.livewire.on('show-modal', function (modalId) {
            var modalEl = document.getElementById(modalId);
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });

        window.livewire.on('close-modal', function (modalId) {
            var modalEl = document.getElementById(modalId);
            if (modalEl) {
                var modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
            }
        });
    });
</script>
