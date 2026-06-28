<div class="card">

    {{-- HEADER --}}
    <div class="card-header pb-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title m-0"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
            <span class="badge bg-label-secondary fs-6">
                {{ number_format($totalRegistros) }} registro(s) encontrado(s)
            </span>
        </div>
        <hr class="my-2">

        {{-- FILTROS --}}
        <div class="row g-2 align-items-end">

            {{-- Búsqueda --}}
            <div class="col-12 col-md-4">
                <label class="form-label mb-1 small text-muted">Buscar</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text"
                        class="form-control"
                        wire:model.lazy="search"
                        placeholder="N° control, código, cliente...">
                    @if($search)
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

            {{-- Estado --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Estado</label>
                <select class="form-select form-select-sm" wire:model="filterEstado">
                    <option value="">Todos</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}">{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tipo DTE --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Tipo DTE</label>
                <select class="form-select form-select-sm" wire:model="filterTipo">
                    <option value="">Todos</option>
                    @foreach($tiposDte as $tipo)
                        <option value="{{ $tipo }}">{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sucursal (solo para Super/Admin) --}}
            @if($canViewAll)
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Sucursal</label>
                <select class="form-select form-select-sm" wire:model="filterSucursal">
                    <option value="0">Todas</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>

        {{-- RANGOS RÁPIDOS + LIMPIAR --}}
        <div class="d-flex align-items-center flex-wrap gap-1 mt-2">
            <small class="text-muted me-1">Rango:</small>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('hoy')">Hoy</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('semana')">Esta semana</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('mes')">Este mes</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('anio')">Este año</button>
            <button class="btn btn-xs btn-outline-warning" wire:click="setRango('todo')" title="Sin filtro de fechas">
                <i class="fa-solid fa-globe"></i> Todo
            </button>
            <div class="ms-auto">
                <select class="form-select form-select-sm d-inline-block w-auto" wire:model="perPage">
                    <option value="15">15 / pág</option>
                    <option value="20">20 / pág</option>
                    <option value="50">50 / pág</option>
                    <option value="100">100 / pág</option>
                </select>
                <button class="btn btn-sm btn-outline-danger ms-1" wire:click="limpiarFiltros" title="Limpiar filtros">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
                </button>
            </div>
        </div>

        @if(!$fechaDesde && !$fechaHasta)
            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Mostrando <b>todos los registros</b> sin filtro de fecha. Puede tardar más con más de 1M de registros.
            </div>
        @endif
    </div>

    {{-- TABLA --}}
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center">N° Control</th>
                    <th class="text-center">Código Generación</th>
                    <th>Cliente</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dtes as $dte)
                    <tr>
                        <td class="text-center">
                            <small class="font-monospace">{{ $dte->numeroControl }}</small>
                        </td>
                        <td class="text-center">
                            <small class="font-monospace text-muted">
                                {{ substr($dte->codigoGeneracion, 0, 16) }}…
                            </small>
                        </td>
                        <td>{{ $dte->nombreCliente }}</td>
                        <td class="text-center">
                            @php
                                $t = strtoupper($dte->tipo ?? '');
                                if ($t === 'FACTURA') $tipoBadge = 'bg-label-primary';
                                elseif ($t === 'CCF') $tipoBadge = 'bg-label-info';
                                elseif (strpos($t, 'NOTA') !== false) $tipoBadge = 'bg-label-warning';
                                else $tipoBadge = 'bg-label-secondary';
                            @endphp
                            <span class="badge {{ $tipoBadge }}">{{ $dte->tipo }}</span>
                        </td>
                        <td class="text-center">{{ $dte->fecEmi }}</td>
                        {{-- [2026-03-17] totalPagar viene del JOIN — eliminado N+1 por fila --}}
                        <td class="text-end fw-semibold">$ {{ number_format($dte->totalPagar, 2) }}</td>
                        <td class="text-center">
                            @php
                                if ($dte->estado === 'PROCESADO') $estadoBadge = 'bg-label-success';
                                elseif ($dte->estado === 'RECHAZADO') $estadoBadge = 'bg-label-danger';
                                elseif ($dte->estado === 'ANULADO') $estadoBadge = 'bg-label-secondary';
                                elseif ($dte->estado === 'Firmado') $estadoBadge = 'bg-label-warning';
                                elseif ($dte->estado === 'Creado') $estadoBadge = 'bg-label-info';
                                else $estadoBadge = 'bg-label-secondary';
                            @endphp
                            @if($dte->estado === 'RECHAZADO')
                                <a href="javascript:void(0);" wire:click="Detalle('{{ $dte->id }}')">
                                    <span class="badge {{ $estadoBadge }}">{{ $dte->estado }}</span>
                                </a>
                            @else
                                <span class="badge {{ $estadoBadge }}">{{ $dte->estado }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                @can('DTE_Print')
                                    <a class="btn btn-icon btn-sm btn-label-info"
                                       href="{{ url('report/pdf/' . $dte->id) }}"
                                       title="Ver PDF" target="_blank">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                @endcan

                                @if($dte->estado === 'PROCESADO')
                                    @can('DTE_ReenviarCorreo')
                                        <button type="button"
                                            class="btn btn-icon btn-sm btn-label-primary"
                                            wire:click.prevent="enviarCorreo({{ $dte->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="enviarCorreo({{ $dte->id }})"
                                            title="Reenviar correo">
                                            <span wire:loading.remove wire:target="enviarCorreo({{ $dte->id }})">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </span>
                                            <span wire:loading wire:target="enviarCorreo({{ $dte->id }})">
                                                <i class="fa-solid fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                    @endcan
                                    <button class="btn btn-icon btn-sm btn-label-dark"
                                        onclick="handleJson({{ $dte->id }}, '{{ $dte->codigoGeneracion }}')"
                                        title="Ver / Descargar JSON">
                                        <i class="fa-solid fa-file-code"></i>
                                    </button>

                                @elseif(in_array($dte->estado, ['Creado', 'Firmado', 'RECHAZADO']))
                                    <button class="btn btn-icon btn-sm btn-label-warning"
                                        title="Firmar y Procesar DTE"
                                        onclick="startProcessing({{ $dte->id }})">
                                        <i class="fa-solid fa-file-signature"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                            No se encontraron DTE con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-3 py-2">
        {{ $dtes->links() }}
    </div>

    @include('livewire.dtes.recepcion')
</div>

@include('common.notis')

<script>
    function handleJson(id, codigo) {
        Swal.fire({
            title: 'Acción requerida',
            text: `¿Qué deseas hacer con "${codigo}.json"?`,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-regular fa-eye"></i> Ver',
            denyButtonText: '<i class="fa-solid fa-download"></i> Descargar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(`/json/${id}`, '_blank');
            } else if (result.isDenied) {
                window.location.href = `/json/download/${id}`;
            }
        });
    }
</script>

<style>
    .btn-xs { padding: 0.15rem 0.45rem; font-size: 0.75rem; }
</style>
