<div class="card">
    <div class="card-header pb-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title m-0"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-secondary fs-6">
                    {{ number_format($totalRegistros) }} registro(s)
                </span>
                @can('Compras_Create')
                    <a href="{{ route('nueva-comra') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Agregar
                    </a>
                @endcan
            </div>
        </div>

        <hr class="my-2">

        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1 small text-muted">Buscar</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" wire:model.lazy="search" placeholder="Factura, proveedor, usuario...">
                    @if($search)
                        <button class="btn btn-outline-secondary" wire:click="$set('search','')" title="Limpiar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Desde</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="fechaDesde">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Hasta</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="fechaHasta">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Estado</label>
                <select class="form-select form-select-sm" wire:model="filterEstado">
                    <option value="">Todos</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}">{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Condicion</label>
                <select class="form-select form-select-sm" wire:model="filterCondiPago">
                    <option value="">Todas</option>
                    @foreach($condiciones as $condicion)
                        <option value="{{ $condicion }}">{{ $condicion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Tipo de factura</label>
                <select class="form-select form-select-sm" wire:model="filterTipo">
                    <option value="">Todos</option>
                    @foreach($tiposCompra as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->tipo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label mb-1 small text-muted">Sucursal</label>
                <select class="form-select form-select-sm" wire:model="filterSucursal" @if(!$puedeVerTodasSucursales) disabled @endif>
                    @if($puedeVerTodasSucursales)
                        <option value="0">Todas las sucursales</option>
                    @endif
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-1 mt-2">
            <small class="text-muted me-1">Rango:</small>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('hoy')">Hoy</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('semana')">Esta semana</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('mes')">Este mes</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('anio')">Este ano</button>
            <button class="btn btn-xs btn-outline-warning" wire:click="setRango('todo')">
                <i class="fa-solid fa-globe"></i> Todo
            </button>

            <div class="ms-auto d-flex align-items-center gap-1">
                <select class="form-select form-select-sm d-inline-block w-auto" wire:model="perPage">
                    <option value="15">15 / pag</option>
                    <option value="20">20 / pag</option>
                    <option value="50">50 / pag</option>
                    <option value="100">100 / pag</option>
                </select>
                <button class="btn btn-sm btn-outline-danger" wire:click="limpiarFiltros">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
                </button>
            </div>
        </div>

        @if(!$fechaDesde && !$fechaHasta)
            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Mostrando todos los registros sin filtro de fecha.
            </div>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Proveedor</th>
                    <th class="text-center">Sucursal</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-center">Condicion</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Productos</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Ingresado por</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($compras as $compra)
                    @php
                        $totalProductos = (float) ($compra->total_productos ?? 0);
                        $totalCompra = (float) ($compra->total_detalle ?? 0);
                        $estado = (string) $compra->estado;
                        $fechaLimite = \Carbon\Carbon::parse($compra->fecha)->addDays(13);
                        $puedeAnular = $estado !== 'Anulado'
                            && $totalProductos > 0
                            && \Carbon\Carbon::now()->lte($fechaLimite)
                            && !\App\Helpers\SistemaHelper::operacionBloqueada((int) $compra->sucursal);
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">{{ optional($compra->RtipoCompra)->tipo ?? 'Factura' }} # {{ $compra->correlativo }}</div>
                            @if($tieneGeneracion && !empty($compra->generacion))
                                <small class="text-muted font-monospace">{{ \Illuminate\Support\Str::limit($compra->generacion, 22, '...') }}</small>
                            @endif
                        </td>
                        <td>{{ optional($compra->Rproveedores)->nombre }}</td>
                        <td class="text-center">{{ optional($compra->Rsucursal)->nombre }}</td>
                        <td class="text-center">{{ $compra->fecha }}</td>
                        <td class="text-center">
                            <span class="badge {{ $compra->condi_pago === 'Credito' ? 'bg-label-warning' : 'bg-label-success' }}">
                                {{ $compra->condi_pago }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php
                                $tipoColor = match ((int) $compra->tipo) {
                                    1 => 'bg-label-primary',
                                    2 => 'bg-label-success',
                                    3 => 'bg-label-info',
                                    4, 5 => 'bg-label-warning',
                                    default => 'bg-label-secondary',
                                };
                            @endphp
                            <span class="badge {{ $tipoColor }}">{{ optional($compra->RtipoCompra)->tipo ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge
                                {{ in_array($estado, ['Pagada', 'Pagado']) ? 'bg-label-success' : '' }}
                                {{ $estado === 'Pendiente' ? 'bg-label-warning' : '' }}
                                {{ $estado === 'Anulado' ? 'bg-label-dark' : '' }}
                                {{ !in_array($estado, ['Pagada', 'Pagado', 'Pendiente', 'Anulado']) ? 'bg-label-secondary' : '' }}">
                                {{ $estado }}
                            </span>
                        </td>
                        <td class="text-center fw-semibold">
                            <a href="javascript:void(0)" class="text-body" wire:click="cargarDetallesCompra('{{ $compra->id }}')">
                                {{ number_format($totalProductos) }}
                            </a>
                        </td>
                        <td class="text-end fw-semibold">
                            <div>$ {{ number_format($totalCompra, 2) }}</div>
                            @if(stripos(optional($compra->RtipoCompra)->tipo ?? '', 'credito fiscal') !== false)
                                <div class="small text-muted">$ {{ number_format($totalCompra * 1.13, 2) }} c/IVA</div>
                            @endif
                        </td>
                        <td class="text-center">{{ optional($compra->Rusers)->name }}</td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                <button type="button" class="btn btn-icon btn-sm btn-label-info" wire:click="cargarDetallesCompra('{{ $compra->id }}')" title="Ver detalle">
                                    <i class="fa-solid fa-list"></i>
                                </button>

                                @if($puedeAnular)
                                    <a href="javascript:void(0)" class="btn btn-icon btn-sm btn-label-primary" onclick="ConfirmA('{{ $compra->id }}')" title="Anular">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </a>
                                @endif

                                @if($estado !== 'Anulado' && $estado !== 'Realizado' && $totalProductos == 0)
                                    @can('Compras_Destroy')
                                        <a class="btn btn-icon btn-sm btn-label-danger" href="javascript:void(0);" onclick="Confirm('{{ $compra->id }}')" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                            No se encontraron compras con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-3 py-2">
        {{ $compras->links() }}
    </div>

    @include('livewire.compras.detCompra')
</div>

@include('common.notis')

<style>
    .btn-xs { padding: 0.15rem 0.45rem; font-size: 0.75rem; }
</style>
