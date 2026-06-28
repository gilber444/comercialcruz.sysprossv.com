<div class="card">
    <div class="card-header pb-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title m-0"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-secondary fs-6">{{ number_format($totalRegistros) }} registro(s)</span>
                @can('Ajustes_Create')
                    <a href="{{ route('nuevo-ajuste') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Agregar Ajuste
                    </a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        {{-- FILTROS --}}
        <div class="row g-2 align-items-end">
            {{-- BÃºsqueda --}}
            <div class="col-12 col-md-3">
                <label class="form-label mb-1 small text-muted">Buscar</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" wire:model.lazy="search"
                        placeholder="ID, detalle, sucursal...">
                    @if ($search)
                        <button class="btn btn-outline-secondary" wire:click="$set('search','')" title="Limpiar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>
            {{-- Fecha desde --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Desde</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="fechaDesde">
            </div>
            {{-- Fecha hasta --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Hasta</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="fechaHasta">
            </div>
            {{-- Status --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Estado</label>
                <select class="form-select form-select-sm" wire:model="filterStatus">
                    <option value="">Todos</option>
                    <option value="Ingresado">Ingresado</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Anulado">Anulado</option>
                </select>
            </div>
            {{-- Tipo --}}
            <div class="col-6 col-md-1">
                <label class="form-label mb-1 small text-muted">Tipo</label>
                <select class="form-select form-select-sm" wire:model="filterTipo">
                    <option value="">Todos</option>
                    <option value="Ingreso">Ingreso</option>
                    <option value="Egreso">Egreso</option>
                </select>
            </div>
            {{-- Sucursal --}}
            <div class="col-12 col-md-2">
                <label class="form-label mb-1 small text-muted">Sucursal</label>
                <select class="form-select form-select-sm" wire:model="filterSucursal">
                    <option value="">Todas</option>
                    @foreach ($sucursalesList as $suc)
                        <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- RANGOS RÃPIDOS + POR PÃGINA + LIMPIAR --}}
        <div class="d-flex align-items-center flex-wrap gap-1 mt-2">
            <small class="text-muted me-1">Rango:</small>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('hoy')">Hoy</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('semana')">Esta semana</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('mes')">Este mes</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('anio')">Este aÃ±o</button>
            <button class="btn btn-xs btn-outline-warning" wire:click="setRango('todo')" title="Sin filtro de fechas">
                <i class="fa-solid fa-globe"></i> Todo
            </button>
            <div class="ms-auto">
                <select class="form-select form-select-sm d-inline-block w-auto" wire:model="perPage">
                    <option value="15">15 / pÃ¡g</option>
                    <option value="20">20 / pÃ¡g</option>
                    <option value="50">50 / pÃ¡g</option>
                    <option value="100">100 / pÃ¡g</option>
                </select>
                <button class="btn btn-sm btn-outline-danger ms-1" wire:click="limpiarFiltros" title="Limpiar filtros">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
                </button>
            </div>
        </div>
        @if (!$fechaDesde && !$fechaHasta)
            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Mostrando <b>todos los registros</b> sin filtro de fecha.
            </div>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Detalle</th>
                <th class="text-center">Sucursal</th>
                <th class="text-center">Creado por / Perfil</th>
                <th class="text-center">Producto</th>
                <th class="text-center">Total</th>
                <th class="text-center">Tipo</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($ajustes as $ajuste)
                    <tr>
                        <td class="text-center">{{ $ajuste->id }}</td>
                        <td class="">{{ $ajuste->fecha }}</td>
                        <td class="">{{ $ajuste->detalle }}</td>
                        <td class="">{{ $ajuste->sucursal_nombre }}</td>
                        <td class="">{{ $ajuste->Ruser->name }} / {{ $ajuste->Ruser->profile }}</td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="text-black"
                                wire:click="cargarDetallesAjustes('{{ $ajuste->id }}')">
                                @php
                                    $totalProductos = DB::table('ajustes_detalles')
                                        ->where('ajuste', $ajuste->id)
                                        ->whereNull('ajustes_detalles.deleted_at')
                                        ->sum('cantidad');
                                    echo $totalProductos;
                                @endphp
                            </a>
                        </td>
                        <td class="text-end">
                            $ @php
                                $total = DB::table('ajustes_detalles')
                                    ->where('ajuste', $ajuste->id)
                                    ->whereNull('ajustes_detalles.deleted_at')
                                    ->sum('total');
                                echo number_format($total, 2);
                            @endphp
                        </td>
                        <td class="text-center">{{ $ajuste->tipo }}</td>
                        <td class="text-center">
                            <span
                                class="badge
                                        @if ($ajuste->status == 'Realizado') bg-label-success
                                        @elseif($ajuste->status == 'Pendiente')
                                            bg-label-warning
                                        @elseif($ajuste->status == 'Ingresado')
                                            bg-label-info
                                        @else
                                            bg-label-secondary @endif
                                text-uppercase">
                                {{ $ajuste->status }}
                            </span>
                        </td>
                        <td class="text-center">
                            @can('Print_Ajuste')
                                <a class="btn btn-icon btn-primary" href="javascript:void(0);"
                                    wire:click="Print('{{ $ajuste->id }}')"><i class="fa-solid fa-print"></i></a>
                            @endcan
                            @can('Ajustes_Update')
                                <a class="btn btn-icon btn-warning"
                                    href="{{ route('editar-ajuste', ['id' => $ajuste->id]) }}"><i
                                        class="fa-solid fa-pen-to-square"></i></a>
                            @endcan
                            @if ($ajuste->status == 'Ingresado')
                                @php
                                    $perfilCreador = $ajuste->Ruser->profile;
                                    $perfilAprobador = $UsuarioReversor->profile;
                                @endphp
                                {{-- Auditor crea â†’ aprueba Super, Administrador o Gerente General --}}
                                @if ($perfilCreador === 'Auditor' && in_array($perfilAprobador, ['Super', 'Administrador', 'Gerente General']))
                                    @can('AutorizaAjuste_Aply_Admin')
                                        <button type="button" class="btn btn-icon btn-success me-1" data-aplicar-btn
                                            onclick="confirmAplicar('{{ $ajuste->id }}')" title="Aplicar">
                                            <span class="tf-icons fa-solid fa-check"></span>
                                        </button>
                                    @endcan
                                    {{-- Auxiliar de Auditor crea â†’ aprueba Auditor, Super, Administrador o Gerente General --}}
                                @elseif (
                                    $perfilCreador === 'Auxiliar de Auditor' &&
                                        in_array($perfilAprobador, ['Auditor', 'Super', 'Administrador', 'Gerente General']))
                                    @can('AutorizaAjuste_Aply')
                                        <button type="button" class="btn btn-icon btn-success me-1" data-aplicar-btn
                                            onclick="confirmAplicar('{{ $ajuste->id }}')" title="Aplicar">
                                            <span class="tf-icons fa-solid fa-check"></span>
                                        </button>
                                    @endcan
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @include('livewire.ajustes.detAjuste')
        @include('common.notis')
    </div>
    {{ $ajustes->links() }}
    <script>
        let _aplicarEnProceso = false;

        function confirmAplicar(id) {
            if (_aplicarEnProceso) return;
            setTimeout(() => {
                Swal.fire({
                    title: 'Confirmar AplicaciÃ³n',
                    html: `
                        <input type="text" id="swal-user" class="swal2-input" placeholder="Usuario">
                        <input type="password" id="swal-pass" class="swal2-input" placeholder="ContraseÃ±a">
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Aplicar',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        document.getElementById('swal-user').focus();
                    },
                    preConfirm: () => {
                        const user = document.getElementById('swal-user').value;
                        const pass = document.getElementById('swal-pass').value;
                        if (!user || !pass) {
                            Swal.showValidationMessage('Debe ingresar usuario y contraseÃ±a');
                            return false;
                        }
                        return {
                            user,
                            pass
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        _aplicarEnProceso = true;
                        // Deshabilitar todos los botones de aplicar y mostrar spinner
                        document.querySelectorAll('[data-aplicar-btn]').forEach(btn => {
                            btn.disabled = true;
                            btn.innerHTML =
                            '<span class="spinner-border spinner-border-sm"></span>';
                        });
                        Livewire.emit('aplicarConCredenciales', id, result.value.user, result.value.pass);
                        // Re-habilitar tras 5s por si Livewire no re-renderiza (error de red, etc.)
                        setTimeout(() => {
                            _aplicarEnProceso = false;
                        }, 5000);
                    }
                });
            }, 300);
        }
        // Resetear flag cuando Livewire termine de actualizar el DOM
        document.addEventListener('livewire:load', () => {
            Livewire.hook('message.processed', () => {
                _aplicarEnProceso = false;
            });
        });
    </script>
    <script>
        window.addEventListener('swal-error', event => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: event.detail.message
            });
        });
    </script>

</div>
