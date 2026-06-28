{{-- [2026-03-29] Rediseño completo — grupos, clone, stats --}}
<div>

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="bx bx-shield-quarter me-2 text-primary"></i>Asignar Permisos</h4>
            <small class="text-muted">Gestiona los permisos por rol. Los cambios se guardan al instante.</small>
        </div>
    </div>

    <div class="row g-4">

        {{-- ══ PANEL IZQUIERDO ═══════════════════════════════════════════ --}}
        <div class="col-lg-3">

            {{-- Selector de rol --}}
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold text-muted text-uppercase" style="font-size:11px;letter-spacing:.5px">Rol Activo</label>
                    <select wire:model="role" class="form-select">
                        <option value="Elegir">— Seleccionar rol —</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Stats del rol seleccionado --}}
            @if($rolActual)
            <div class="card mb-3 border-primary">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <p class="mb-0 fw-bold fs-5 text-primary">{{ $rolStats['count'] }}</p>
                            <small class="text-muted">de {{ $rolStats['total'] }} permisos</small>
                        </div>
                        <span class="badge bg-label-primary fs-6">{{ $rolStats['pct'] }}%</span>
                    </div>
                    <div class="progress mb-0" style="height:6px;border-radius:3px">
                        <div class="progress-bar bg-primary" style="width:{{ $rolStats['pct'] }}%"></div>
                    </div>
                    <p class="mt-2 mb-0 fw-semibold text-truncate">{{ $rolActual->name }}</p>
                </div>
            </div>
            @endif

            {{-- Acciones rápidas --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <small class="fw-semibold text-muted text-uppercase" style="font-size:11px;letter-spacing:.5px">Acciones Rápidas</small>
                </div>
                <div class="card-body p-2 d-grid gap-2">
                    <button wire:click="SyncAll"
                            wire:loading.attr="disabled" wire:target="SyncAll"
                            class="btn btn-sm btn-outline-success text-start"
                            @if($role === 'Elegir') disabled @endif>
                        <i class="bx bx-check-double me-2"></i>Asignar Todos
                    </button>
                    <button onclick="confirmarRevocar()"
                            class="btn btn-sm btn-outline-danger text-start"
                            @if($role === 'Elegir') disabled @endif>
                        <i class="bx bx-x-circle me-2"></i>Revocar Todos
                    </button>
                    <hr class="my-1">
                    <button onclick="abrirModalClonar()"
                            class="btn btn-sm btn-outline-warning text-start"
                            @if($role === 'Elegir') disabled @endif>
                        <i class="bx bx-copy me-2"></i>Clonar desde otro Rol
                    </button>
                </div>
            </div>

            {{-- Lista de todos los roles --}}
            <div class="card">
                <div class="card-header py-2">
                    <small class="fw-semibold text-muted text-uppercase" style="font-size:11px;letter-spacing:.5px">Todos los Roles</small>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($rolesStats as $rs)
                    <li class="list-group-item px-3 py-2 d-flex justify-content-between align-items-center {{ $role == $rs['id'] ? 'active' : '' }}"
                        style="cursor:pointer"
                        wire:click="$set('role', {{ $rs['id'] }})">
                        <span style="font-size:13px" class="fw-semibold">{{ $rs['name'] }}</span>
                        <span class="badge {{ $role == $rs['id'] ? 'bg-white text-primary' : 'bg-label-secondary' }}" style="font-size:11px">
                            {{ $rs['count'] }}
                        </span>
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>

        {{-- ══ PANEL DERECHO: Permisos agrupados ══════════════════════════ --}}
        <div class="col-lg-9">

            {{-- Barra de búsqueda --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search text-muted"></i></span>
                        <input type="text"
                               wire:model.debounce.300ms="search"
                               class="form-control"
                               placeholder="Buscar permiso o módulo...">
                        @if($search)
                        <button wire:click="$set('search', '')" class="btn btn-outline-secondary">
                            <i class="bx bx-x"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            @if($role === 'Elegir')
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bx bx-shield" style="font-size:3rem"></i>
                        <p class="mt-3">Selecciona un rol para gestionar sus permisos.</p>
                    </div>
                </div>
            @elseif($grupos->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-4 text-muted">
                        <i class="bx bx-search-alt-2 me-2"></i>No se encontraron permisos con «{{ $search }}»
                    </div>
                </div>
            @else

            <div class="accordion" id="acordeonPermisos">
                @foreach($grupos as $modulo => $permisos)
                @php
                    $grupoActivos  = collect($permisos)->whereIn('id', $permisosDelRol)->count();
                    $grupoTotal    = count($permisos);
                    $grupoCompleto = $grupoActivos === $grupoTotal;
                    $grupoParcial  = $grupoActivos > 0 && !$grupoCompleto;
                    $accordionId   = 'grp_' . preg_replace('/[^a-zA-Z0-9]/', '_', $modulo);
                @endphp
                <div class="accordion-item mb-2">
                    <div class="accordion-header d-flex align-items-center px-3 py-2">

                        {{-- Switch para activar/desactivar todo el grupo --}}
                        <div class="form-check form-switch me-3 mb-0">
                            <input class="form-check-input" type="checkbox"
                                   style="width:2.2em;height:1.1em;cursor:pointer"
                                   {{ $grupoCompleto ? 'checked' : '' }}
                                   wire:change="SyncGrupo('{{ $modulo }}', $event.target.checked)">
                        </div>

                        {{-- Botón expandir --}}
                        <button class="accordion-button collapsed p-0 shadow-none border-0 flex-grow-1"
                                style="background:transparent"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $accordionId }}">
                            <span class="fw-semibold" style="font-size:14px">{{ $modulo }}</span>
                            <span class="ms-3 badge {{ $grupoCompleto ? 'bg-success' : ($grupoParcial ? 'bg-warning text-dark' : 'bg-label-secondary') }}"
                                  style="font-size:11px">
                                {{ $grupoActivos }} / {{ $grupoTotal }}
                            </span>
                        </button>
                    </div>

                    <div id="{{ $accordionId }}" class="accordion-collapse collapse">
                        <div class="accordion-body pt-0 pb-2">
                            <div class="row g-2">
                                @foreach($permisos as $permiso)
                                @php $tienePermiso = in_array($permiso->id, $permisosDelRol); @endphp
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex align-items-center gap-2 px-2 py-2 rounded {{ $tienePermiso ? 'bg-label-success' : 'bg-label-secondary' }}"
                                         style="font-size:13px">
                                        <input class="form-check-input mt-0" type="checkbox"
                                               id="p{{ $permiso->id }}"
                                               {{ $tienePermiso ? 'checked' : '' }}
                                               wire:change="SyncPermiso($event.target.checked, '{{ $permiso->name }}')">
                                        <label class="mb-0 text-truncate" for="p{{ $permiso->id }}"
                                               style="cursor:pointer;max-width:160px"
                                               title="{{ $permiso->name }}">
                                            {{ last(explode('_', $permiso->name)) }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @endif
        </div>
    </div>

</div>

{{-- ══ Modal Clonar ════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalClonar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bx bx-copy me-2 text-warning"></i>Clonar Permisos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 mb-3" style="font-size:13px">
                    <i class="bx bx-info-circle me-1"></i>
                    Los permisos del rol origen <strong>reemplazarán</strong> los del rol destino.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Rol Destino <span class="text-muted fw-normal">(receptor)</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-target-lock text-primary"></i></span>
                        <input type="text" class="form-control" readonly
                               value="{{ $rolActual ? $rolActual->name : '— ninguno —' }}">
                    </div>
                </div>

                <div>
                    <label class="form-label fw-semibold">Rol Origen <span class="text-muted fw-normal">(fuente)</span></label>
                    {{-- [2026-03-29] defer: evita re-render de Livewire al seleccionar (destruía el modal) --}}
                    <select wire:model.defer="roleClonar" id="selectRolClonar" class="form-select"
                            onchange="document.getElementById('btnClonar').disabled = (this.value === 'Elegir')">
                        <option value="Elegir">— Seleccionar rol origen —</option>
                        @foreach($roles as $r)
                            @if($r->id != $role)
                            <option value="{{ $r->id }}">
                                {{ $r->name }}
                                ({{ $rolesStats->firstWhere('id', $r->id)['count'] ?? 0 }} permisos)
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button id="btnClonar"
                        wire:click="ClonarPermisos"
                        wire:loading.attr="disabled"
                        class="btn btn-warning"
                        disabled>
                    <span wire:loading.remove wire:target="ClonarPermisos">
                        <i class="bx bx-copy me-1"></i>Clonar
                    </span>
                    <span wire:loading wire:target="ClonarPermisos">
                        <i class="bx bx-loader-alt bx-spin me-1"></i>Clonando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@include('common.notis')

<script>
    function abrirModalClonar() {
        // Resetear select y botón cada vez que se abre
        document.getElementById('selectRolClonar').value = 'Elegir';
        document.getElementById('btnClonar').disabled = true;
        new bootstrap.Modal(document.getElementById('modalClonar')).show();
    }

    function confirmarRevocar() {
        Swal.fire({
            title: '¿Revocar todos los permisos?',
            text: 'Se quitarán todos los permisos del rol seleccionado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, revocar',
        }).then(result => {
            if (result.isConfirmed) @this.call('RemoveAll');
        });
    }

    document.addEventListener('livewire:load', () => {
        Livewire.on('close-modal-clonar', () => {
            bootstrap.Modal.getInstance(document.getElementById('modalClonar'))?.hide();
        });
    });
</script>

</div>
