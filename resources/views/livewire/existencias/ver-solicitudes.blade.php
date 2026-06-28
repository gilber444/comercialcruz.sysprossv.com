<div class="card">
    <div class="card-header pb-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title m-0"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-secondary fs-6">{{ number_format($totalRegistros) }} registro(s)</span>
            </div>
        </div>
        <hr class="my-2"> 

        {{-- FILTROS --}}
        <div class="row g-2 align-items-end">

            {{-- Búsqueda --}}
            <div class="col-12 col-md-3">
                <label class="form-label mb-1 small text-muted">Buscar</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" wire:model.lazy="search"
                        placeholder="N° solicitud, sucursal, solicitante...">
                    @if($search)
                        <button class="btn btn-outline-secondary" wire:click="$set('search','')" title="Limpiar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Desde --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Desde</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="fechaDesde">
            </div>

            {{-- Hasta --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Hasta</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="fechaHasta">
            </div>

            {{-- Estado --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Estado</label>
                <select class="form-select form-select-sm" wire:model="filterEstado">
                    <option value="">Todos</option>
                    <option value="Solicitado">Solicitado</option>
                    <option value="Autorizado">Autorizado</option>
                    <option value="Despachado">Despachado</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Anulado">Anulado</option>
                </select>
            </div>

            {{-- Origen --}}
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small text-muted">Tienda entrega</label>
                <select class="form-select form-select-sm" wire:model="filterOrigen">
                    <option value="">Todas</option>
                    @foreach($sucursalesList as $suc)
                        <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Destino --}}
            <div class="col-6 col-md-1">
                <label class="form-label mb-1 small text-muted">Recibe</label>
                <select class="form-select form-select-sm" wire:model="filterDestino">
                    <option value="">Todas</option>
                    @foreach($sucursalesList as $suc)
                        <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- RANGOS RÁPIDOS --}}
        <div class="d-flex align-items-center flex-wrap gap-1 mt-2">
            <small class="text-muted me-1">Rango:</small>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('hoy')">Hoy</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('semana')">Esta semana</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('mes')">Este mes</button>
            <button class="btn btn-xs btn-outline-secondary" wire:click="setRango('anio')">Este año</button>
            <button class="btn btn-xs btn-outline-warning" wire:click="setRango('todo')">
                <i class="fa-solid fa-globe"></i> Todo
            </button>
            <div class="ms-auto">
                <select class="form-select form-select-sm d-inline-block w-auto" wire:model="perPage">
                    <option value="15">15 / pág</option>
                    <option value="20">20 / pág</option>
                    <option value="50">50 / pág</option>
                    <option value="100">100 / pág</option>
                </select>
                <button class="btn btn-sm btn-outline-danger ms-1" wire:click="limpiarFiltros">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
                </button>
            </div>
        </div>

        @if(!$fechaDesde && !$fechaHasta)
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
                <th class="text-center">Fecha y Hora</th>
                <th class="text-center">Tienda Entrega</th>
                <th class="text-center">Tienda Recibe</th>
                <th class="text-center">Realizado Por</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Estado</th>
                <th class="text-center"></th>
            </thead>
            <tbody>
                @foreach ($data as $dat)
                    <tr>
                        <td class="text-center"><b>{{ $dat->numero }}</b></td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($dat->fecha)->format('d/m/Y H:i:s') }}</td>
                        <td class="text-center">{{ $dat->Rorigen->nombre }}</td>
                        <td class="text-center">{{ $dat->Rdestino->nombre }}</td>
                        <td class="text-center">{{ $dat->Rsolicitante->name }}</td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="text-black"
                                wire:click="cargarDetallesSolicitud('{{ $dat->id }}')">
                                @php
                                    $totalProductos = DB::table('solicitudes_detalles')
                                        ->where('solicitud', $dat->id)
                                        ->sum('cantidad');
                                    echo number_format($totalProductos, 0);
                                @endphp
                            </a>
                        </td>
                        <td class="text-center">
                            <span
                                class="badge
                            @if ($dat->estado == 'Solicitado') bg-label-primary text-uppercase
                            @elseif($dat->estado == 'Finalizado')
                                bg-success text-uppercase
                            @elseif($dat->estado == 'Autorizado')
                                bg-label-info text-uppercase
                            @elseif($dat->estado == 'Despachado')
                                bg-warning text-uppercase @endif">
                                {{ $dat->estado }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if ($dat->estado === 'Solicitado')
                                @can('Autorizar_Index')
                                    <a class="btn btn-label-warning btn-sm" href="javascript:void(0);"
                                        wire:click="Autorizar('{{ $dat->id }}')">
                                        <i class="fa-solid fa-unlock"></i> Autorizar
                                    </a>
                                @endcan
                            @elseif ($dat->estado === 'Autorizado')
                                @can('Despacho_Index')
                                    <a class="btn btn-info" href="javascript:void(0);"
                                        wire:click="Despacho('{{ $dat->id }}')">
                                        <i class="fa-solid fa-clipboard-list"></i> Despachar
                                    </a>
                                @endcan
                            @elseif ($dat->estado === 'Despachado')
                                @can('Ingreso_Index')
                                    @if (!\App\Helpers\SistemaHelper::operacionBloqueada((int) $dat->destino))
                                        <button class="btn btn-success" wire:click="Ingreso({{ $dat->id }})">
                                            <i class="fa-solid fa-file-import"></i> Ingresar
                                        </button>
                                    @endif
                                @endcan
                            @endif
                            @can('Solicitudes_Print')
                                <a href="{{ url('existencias/pdf' . '/' . $dat->id) }}"
                                    class="btn btn-label-secondary btn-sm" target="_blank">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            @endcan
                            @if ($dat->estado === 'Solicitado')
                                @can('Solicitudes_Update')
                                    <a class="btn btn-warning"
                                        href="{{ route('editar-solicitud', ['id' => $dat->id]) }}"><i
                                            class="bx bx-edit-alt"></i>Editar</a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $data->links() }}
    @include('livewire.existencias.detSolicitud')
    @include('livewire.existencias.autorizar')
</div>
@include('common.notis')
