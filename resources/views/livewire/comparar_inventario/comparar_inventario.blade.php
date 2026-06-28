<div>
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="fa-solid fa-code-compare me-2 text-primary"></i>{{ $componentName }}
            </h4>
            <small class="text-muted">Compara existencias entre la PC local y el VPS. Lo local es la fuente de verdad.</small>
        </div>
    </div>

    {{-- Polling activo solo durante push masivo --}}
    @if($pushingAll)
        <div wire:poll.1000ms="procesarSiguiente"></div>
    @endif

    {{-- Barra de progreso push masivo --}}
    @if($pushingAll)
        @php
            $pct      = $pushTotal > 0 ? round(($pushDone / $pushTotal) * 100) : 0;
            $elapsed  = $pushStartedAt ? (now()->timestamp - $pushStartedAt) : 0;
            $mins     = intdiv($elapsed, 60);
            $secs     = $elapsed % 60;
            $tiempo   = $mins > 0 ? "{$mins}m {$secs}s" : "{$secs}s";
            $restantes = $pushTotal - $pushDone;
        @endphp
        <div class="card mb-3 border-primary">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-primary">
                        <i class="fa-solid fa-cloud-arrow-up fa-beat me-2"></i>
                        Subiendo al VPS... {{ $pushDone }} / {{ $pushTotal }} productos
                    </span>
                    <div class="d-flex align-items-center gap-3">
                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>{{ $tiempo }}</small>
                        <button class="btn btn-sm btn-outline-danger" wire:click="cancelarPushTodos">
                            <i class="fa-solid fa-stop me-1"></i>Cancelar
                        </button>
                    </div>
                </div>
                <div class="progress" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         role="progressbar"
                         style="width: {{ $pct }}%"
                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $pct }}%
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">Faltan {{ $restantes }} producto(s)</small>
                    @if($elapsed > 0 && $pushDone > 0)
                        @php $estimado = round(($elapsed / $pushDone) * $restantes); @endphp
                        <small class="text-muted">Tiempo estimado restante: ~{{ $estimado }}s</small>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Alerta de mensaje --}}
    @if($mensaje)
        <div class="alert alert-{{ $tipoMensaje }} alert-dismissible py-2 mb-3" role="alert">
            {!! $mensaje !!}
            <button type="button" class="btn-close" wire:click="$set('mensaje','')"></button>
        </div>
    @endif

    {{-- Panel de filtros --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">

                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm">Sucursal <span class="text-danger">*</span></label>
                    <select wire:model="sucursal_id" class="form-select form-select-sm">
                        <option value="0">— Seleccionar sucursal —</option>
                        @foreach($sucursales as $s)
                            <option value="{{ $s['id'] }}">{{ $s['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm">Mostrar</label>
                    <select wire:model="filtro" class="form-select form-select-sm">
                        <option value="diferencias">Solo diferencias</option>
                        <option value="todos">Todos los productos</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm">Buscar producto</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" wire:model.lazy="search" class="form-control" placeholder="Nombre o código...">
                    </div>
                </div>

                <div class="col-12 col-md-2">
                    <button class="btn btn-primary btn-sm w-100" wire:click="comparar"
                            wire:loading.attr="disabled" wire:target="comparar">
                        <span wire:loading.remove wire:target="comparar">
                            <i class="fa-solid fa-arrows-rotate me-1"></i>Comparar
                        </span>
                        <span wire:loading wire:target="comparar">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i>Consultando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Resumen --}}
    @if($comparado)
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="border rounded px-3 py-2 text-center bg-light">
                    <div class="text-muted small">Total productos</div>
                    <div class="fw-bold fs-5">{{ $total }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded px-3 py-2 text-center {{ $difCount > 0 ? 'bg-label-danger' : 'bg-label-success' }}">
                    <div class="small">Con diferencia</div>
                    <div class="fw-bold fs-5">{{ $difCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded px-3 py-2 text-center bg-label-success">
                    <div class="small">Iguales</div>
                    <div class="fw-bold fs-5">{{ $total - $difCount }}</div>
                </div>
            </div>
            @if($difCount > 0)
                <div class="col-12 col-md-3 d-flex align-items-center gap-2">
                    <div class="alert alert-warning py-1 px-2 mb-0 small flex-grow-1">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Hay <strong>{{ $difCount }}</strong> producto(s) diferentes.
                    </div>
                    @can('CompararInventario_Push')
                        @if(!$pushingAll)
                            <button class="btn btn-sm btn-danger text-nowrap"
                                    wire:click="iniciarPushTodos"
                                    wire:loading.attr="disabled" wire:target="iniciarPushTodos">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i>Subir Todos
                            </button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>

        {{-- Tabla --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Código</th>
                            <th class="text-end">Exist. Local</th>
                            <th class="text-end">Exist. VPS</th>
                            <th class="text-end">Diferencia</th>
                            <th class="text-center">Estado</th>
                            @can('CompararInventario_Push')
                                <th class="text-center">Acción</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($filas as $f)
                            <tr class="{{ $f['diferente'] ? 'table-warning' : '' }}">
                                <td class="fw-semibold small">{{ $f['nombreProducto'] }}</td>
                                <td><span class="font-monospace small text-muted">{{ $f['codebar1'] ?? '—' }}</span></td>
                                <td class="text-end fw-bold text-primary">{{ number_format($f['existencia_local'], 4) }}</td>
                                <td class="text-end {{ $f['sin_vps'] ? 'text-muted fst-italic' : '' }}">
                                    {{ $f['sin_vps'] ? 'No existe' : number_format($f['existencia_vps'], 4) }}
                                </td>
                                <td class="text-end fw-bold {{ $f['diferente'] ? 'text-danger' : 'text-success' }}">
                                    @if($f['diferencia'] !== null)
                                        {{ $f['diferencia'] > 0 ? '+' : '' }}{{ number_format($f['diferencia'], 4) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($f['sin_vps'])
                                        <span class="badge bg-label-danger">Sin registro VPS</span>
                                    @elseif($f['diferente'])
                                        <span class="badge bg-label-warning">Diferente</span>
                                    @else
                                        <span class="badge bg-label-success">Igual</span>
                                    @endif
                                </td>
                                @can('CompararInventario_Push')
                                    <td class="text-center">
                                        @if($f['diferente'])
                                            <button class="btn btn-icon btn-sm btn-label-primary"
                                                    wire:click="prepararPush({{ $f['producto_local_id'] }}, '{{ addslashes($f['nombreProducto']) }}')"
                                                    title="Subir existencia local al VPS">
                                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                            </button>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-circle-check fa-2x mb-2 d-block text-success"></i>
                                    @if($filtro === 'diferencias')
                                        No hay diferencias. El inventario local y VPS están sincronizados.
                                    @else
                                        No se encontraron productos con los filtros aplicados.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-code-compare fa-3x mb-3 d-block opacity-25"></i>
            Selecciona una sucursal y presiona <strong>Comparar</strong> para ver las diferencias.
        </div>
    @endif

    {{-- Modal confirmación push --}}
    <div wire:ignore.self class="modal fade" id="modalPushInventario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header bg-label-primary">
                    <h6 class="modal-title fw-bold">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>Subir al VPS
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="cancelarPush"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-triangle-exclamation fa-2x text-warning mb-3 d-block"></i>
                    <p class="mb-1 small">Se sobreescribirá la existencia y el <strong>kardex completo</strong> del producto en el VPS:</p>
                    <p class="fw-bold">{{ $pushNombre }}</p>
                    <p class="text-muted small mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"
                            wire:click="cancelarPush">Cancelar</button>
                    <button class="btn btn-primary btn-sm" wire:click="confirmarPush"
                            wire:loading.attr="disabled" wire:target="confirmarPush">
                        <span wire:loading.remove wire:target="confirmarPush">
                            <i class="fa-solid fa-cloud-arrow-up me-1"></i>Confirmar subida
                        </span>
                        <span wire:loading wire:target="confirmarPush">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i>Subiendo...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('common.notis')
</div>

<script>
window.addEventListener('open-modal-push', () => {
    new bootstrap.Modal(document.getElementById('modalPushInventario')).show();
});
window.addEventListener('close-modal-push', () => {
    const el = document.getElementById('modalPushInventario');
    if (el) bootstrap.Modal.getInstance(el)?.hide();
});
</script>
