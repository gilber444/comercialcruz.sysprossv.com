<div wire:poll.30s>
    <div class="card shadow-none border border-primary mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="card-title mb-1"><b>Monitor de sincronización por API</b></h4>
                    <p class="text-muted small mb-0">Se actualiza solo cada 30 segundos · última lectura {{ $actualizado }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <select wire:model="sucursal" class="form-select form-select-sm" style="min-width: 180px">
                        <option value="0">Todas las sucursales</option>
                        @foreach ($sucursales as $id => $nombre)
                            <option value="{{ $id }}">{{ $id }} · {{ $nombre }}</option>
                        @endforeach
                    </select>
                    <select wire:model="horas" class="form-select form-select-sm" style="min-width: 150px">
                        <option value="1">Última hora</option>
                        <option value="6">Últimas 6 horas</option>
                        <option value="24">Últimas 24 horas</option>
                        <option value="168">Últimos 7 días</option>
                    </select>
                </div>
            </div>

            @if (!$esVps)
                <div class="alert alert-warning mt-3 mb-0 py-2">Esta pantalla muestra lo que recibe el VPS. En una caja local no hay datos: ábrela en el VPS.</div>
            @elseif (!$hayTablas)
                <div class="alert alert-warning mt-3 mb-0 py-2">Faltan las tablas del monitor. En el VPS ejecute <code>php artisan migrate</code>.</div>
            @endif
        </div>
    </div>

    {{-- Resumen --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-none border h-100"><div class="card-body py-3">
                <div class="text-muted small">Cajas al día</div>
                <div class="fs-4 fw-bold">{{ $cajasAlDia }} <span class="fs-6 text-muted">/ {{ $latidos->count() }}</span></div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-none border h-100"><div class="card-body py-3">
                <div class="text-muted small">Peticiones recibidas</div>
                <div class="fs-4 fw-bold">{{ number_format($totales['peticiones']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-none border h-100"><div class="card-body py-3">
                <div class="text-muted small">Filas guardadas</div>
                <div class="fs-4 fw-bold">{{ number_format($totales['guardados']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-none border h-100 {{ ($totales['errores'] + $totales['fallidas']) > 0 ? 'border-danger' : '' }}"><div class="card-body py-3">
                <div class="text-muted small">Filas con error · peticiones fallidas</div>
                <div class="fs-4 fw-bold {{ ($totales['errores'] + $totales['fallidas']) > 0 ? 'text-danger' : '' }}">{{ number_format($totales['errores']) }} · {{ number_format($totales['fallidas']) }}</div>
            </div></div>
        </div>
    </div>

    {{-- Estado de cada caja --}}
    <h5 class="mb-2">Estado de cada caja</h5>
    @if ($latidos->isEmpty())
        <div class="card shadow-none border mb-3"><div class="card-body text-muted">
            Ninguna caja ha reportado todavía. Las cajas reportan al terminar cada comando de sync por API (necesitan la versión 1.0.58 o mayor).
        </div></div>
    @endif
    <div class="row g-3 mb-4">
        @foreach ($latidos as $suc => $comandos)
            @php
                $peor = $comandos->contains('color', 'danger') ? 'danger' : ($comandos->contains('color', 'warning') ? 'warning' : 'success');
                $primero = $comandos->first();
            @endphp
            <div class="col-12 col-lg-6">
                <div class="card shadow-none border border-{{ $peor }} h-100">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <b>Sucursal {{ $suc }} · {{ $sucursales[$suc] ?? '' }}</b>
                        <small class="text-muted">{{ $primero->equipo }} · v{{ $primero->version }}</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <tbody>
                                @foreach ($comandos as $l)
                                    <tr>
                                        <td style="width: 40%">
                                            <span class="badge bg-{{ $l->color }} me-1">{{ $l->estado }}</span>
                                            {{ $l->nombre }}
                                        </td>
                                        <td class="small text-muted text-nowrap">{{ $l->hace }}</td>
                                        <td class="text-end">
                                            <a href="javascript:void(0)" class="small" data-bs-toggle="collapse" data-bs-target="#res-{{ $l->id }}">detalle</a>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="res-{{ $l->id }}" wire:ignore.self>
                                        <td colspan="3" class="bg-light">
                                            <pre class="small mb-1" style="white-space: pre-wrap; max-height: 240px; overflow: auto">{{ $l->resumen ?: 'Sin salida.' }}</pre>
                                            <small class="text-muted">Último sin error: {{ $l->ultimo_ok_at ? \Carbon\Carbon::parse($l->ultimo_ok_at)->format('d/m/Y H:i:s') : 'nunca' }} · {{ $l->comando }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Actividad --}}
    <h5 class="mb-2">Lo que recibió el VPS</h5>
    <div class="card shadow-none border mb-4">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Sucursal</th>
                        <th>Servicio</th>
                        <th>Tabla</th>
                        <th class="text-end">Peticiones</th>
                        <th class="text-end">Filas enviadas</th>
                        <th class="text-end">Guardadas</th>
                        <th class="text-end">Esperando padre</th>
                        <th class="text-end">Con error</th>
                        <th class="text-end">Fallidas</th>
                        <th class="text-end">ms prom.</th>
                        <th>Última</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividad as $a)
                        <tr class="{{ ($a->errores > 0 || $a->fallidas > 0) ? 'table-danger' : '' }}">
                            <td>{{ $a->sucursal ?: '—' }} {{ $a->sucursal ? ($sucursales[$a->sucursal] ?? '') : '' }}</td>
                            <td>{{ \App\Http\Livewire\MonitorSyncApiController::nombreServicio($a->servicio) }}</td>
                            <td>{{ $a->tabla ?: '—' }}</td>
                            <td class="text-end">{{ number_format($a->peticiones) }}</td>
                            <td class="text-end">{{ number_format($a->recibidas) }}</td>
                            <td class="text-end">{{ number_format($a->guardados) }}</td>
                            <td class="text-end">{{ number_format($a->pendientes) }}</td>
                            <td class="text-end">{{ number_format($a->errores) }}</td>
                            <td class="text-end">{{ number_format($a->fallidas) }}</td>
                            <td class="text-end">{{ number_format($a->ms) }}</td>
                            <td class="text-nowrap small">{{ \Carbon\Carbon::parse($a->ultima)->format('d/m H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-3">Sin peticiones en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Errores --}}
    <h5 class="mb-2">Últimos errores</h5>
    <div class="card shadow-none border mb-3">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-dark">
                    <tr><th>Fecha</th><th>Sucursal</th><th>Servicio</th><th>Tabla</th><th>HTTP</th><th>Detalle</th></tr>
                </thead>
                <tbody>
                    @forelse ($errores as $e)
                        <tr>
                            <td class="text-nowrap small">{{ \Carbon\Carbon::parse($e->created_at)->format('d/m H:i:s') }}</td>
                            <td>{{ $e->sucursal ?: '—' }}</td>
                            <td>{{ \App\Http\Livewire\MonitorSyncApiController::nombreServicio($e->servicio) }}</td>
                            <td>{{ $e->tabla ?: '—' }}</td>
                            <td><span class="badge bg-{{ $e->http >= 400 ? 'danger' : 'warning' }}">{{ $e->http }}</span></td>
                            <td class="small" style="white-space: pre-wrap">{{ $e->mensaje }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Sin errores en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
