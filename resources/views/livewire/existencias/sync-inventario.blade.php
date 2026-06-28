<div>
    {{-- Botón de sincronización --}}
    <div class="mb-3">
        <button 
            wire:click="sincronizar" 
            wire:loading.attr="disabled"
            class="btn btn-primary"
            @if($sincronizando) disabled @endif>
            <span wire:loading.remove wire:target="sincronizar">
                <i class="bx bx-sync me-1"></i> Sincronizar al VPS
            </span>
            <span wire:loading wire:target="sincronizar">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Sincronizando...
            </span>
        </button>
        
        @if($usarApi)
            <small class="text-muted ms-2">
                <i class="bx bx-link me-1"></i>Vía API
            </small>
        @else
            <small class="text-muted ms-2">
                <i class="bx bx-data me-1"></i>Vía Conexión Directa
            </small>
        @endif
    </div>

    {{-- Barra de progreso --}}
    @if($estado !== 'idle')
        <div class="card mb-3">
            <div class="card-body">
                {{-- Estado actual --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        @if($estado === 'sincronizando')
                            <span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span>
                            <span class="text-primary">Sincronizando...</span>
                        @elseif($estado === 'completado')
                            <i class="bx bx-check-circle text-success me-2"></i>
                            <span class="text-success">Completado</span>
                        @elseif($estado === 'error')
                            <i class="bx bx-x-circle text-danger me-2"></i>
                            <span class="text-danger">Error</span>
                        @endif
                    </h6>
                    <span class="text-muted">{{ $mensajeEstado }}</span>
                </div>

                {{-- Barra de progreso --}}
                <div class="progress mb-2" style="height: 25px;">
                    <div 
                        class="progress-bar {{ $estado === 'completado' ? 'bg-success' : ($estado === 'error' ? 'bg-danger' : 'bg-primary') }}" 
                        role="progressbar" 
                        style="width: {{ $porcentaje }}%;" 
                        aria-valuenow="{{ $porcentaje }}" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        {{ $porcentaje }}%
                    </div>
                </div>

                {{-- Detalles de progreso --}}
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-2">
                        <small class="text-muted">Procesados</small>
                        <div class="fw-bold">{{ $procesados }} / {{ $total }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <small class="text-muted">Exitosos</small>
                        <div class="fw-bold text-success">{{ $exitosos }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <small class="text-muted">Fallidos</small>
                        <div class="fw-bold text-danger">{{ $fallidos }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <small class="text-muted">Tiempo restante</small>
                        <div class="fw-bold">{{ $tiempoRestante ?? '--' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Resumen de errores --}}
    @if(count($errores) > 0)
        <div class="card mb-3 border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">
                    <i class="bx bx-error me-1"></i> 
                    Productos que fallaron ({{ count($errores) }})
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Sucursal</th>
                                <th>Existencia</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($errores as $index => $error)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $error['producto'] }}</td>
                                    <td>{{ $error['sucursal'] }}</td>
                                    <td><code>{{ $error['existencia'] ?? 'N/A' }}</code></td>
                                    <td><code class="text-danger">{{ $error['error'] }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Resumen final --}}
    @if($estado === 'completado')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h6 class="alert-heading">
                <i class="bx bx-check-circle me-1"></i> Sincronización Finalizada
            </h6>
            <hr>
            <p class="mb-1">
                <strong>Total de productos:</strong> {{ $total }}<br>
                <strong>Sincronizados exitosamente:</strong> <span class="text-success">{{ $exitosos }}</span><br>
                <strong>Error al sincronizar:</strong> <span class="text-danger">{{ $fallidos }}</span>
            </p>
            @if($fallidos > 0)
                <p class="mb-0 text-danger">
                    <small>Revisa la tabla de errores arriba para más detalles.</small>
                </p>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        {{-- Botón para reiniciar --}}
        <div class="mb-3">
            <button wire:click="resetearEstado" class="btn btn-secondary">
                <i class="bx bx-refresh me-1"></i> Nueva Sincronización
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
    Livewire.on('syncCompleted', function() {
        // Scroll al resumen de errores si los hay
        @if(count($errores) > 0)
            setTimeout(function() {
                const errorCard = document.querySelector('.border-danger');
                if (errorCard) {
                    errorCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 500);
        @endif
    });
</script>
@endpush
