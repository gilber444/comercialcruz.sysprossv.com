<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
            <a href="{{ route('evento_retorno.nuevo') }}" class="btn btn-primary btn-rounded mb-2">
                <i class="fa-solid fa-plus"></i> Nuevo retorno
            </a>
        </div>
        <hr class="my-2">
        <input type="text" class="form-control" wire:model.debounce.400ms="search"
            placeholder="Buscar por codigo de generacion, sello o correlativo de la factura">
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Codigo de generacion</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($eventos as $e)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($e->fecEmi)->format('d/m/Y') }}</td>
                        <td><b>{{ $e->Rventa?->correlativo }}</b></td>
                        <td>{{ $e->Rcliente?->nombreCliente ?? 'Consumidor final' }}</td>
                        <td><small><code>{{ $e->codigoGeneracion }}</code></small></td>
                        <td class="text-center">{{ $e->Rdetalles?->count() ?? 0 }}</td>
                        <td class="text-end">{{ number_format($e->montoTotalOperacion, 2) }}</td>
                        <td class="text-center">
                            @if ($e->estado === 'PROCESADO')
                                <span class="badge bg-success">PROCESADO</span>
                            @elseif ($e->estado === 'RECHAZADO')
                                <span class="badge bg-danger">RECHAZADO</span>
                            @elseif (in_array($e->estado, ['Creado', 'Generado', 'Firmado']))
                                <span class="badge bg-warning text-dark">{{ $e->estado }}</span>
                            @else
                                <span class="badge bg-secondary">{{ $e->estado }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if (!$e->selloRecibido)
                                <button class="btn btn-sm btn-outline-success"
                                    wire:click="Procesar({{ $e->id }})" wire:loading.attr="disabled"
                                    onclick="confirm('Se transmitira el evento a Hacienda. Continuar?') || event.stopImmediatePropagation()">
                                    <span wire:loading.remove wire:target="Procesar({{ $e->id }})"><i class="fa-solid fa-paper-plane"></i></span>
                                    <span wire:loading wire:target="Procesar({{ $e->id }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    Transmitir
                                </button>
                            @endif
                            {{-- Sin sello el PDF sale marcado PENDIENTE DE TRANSMISION. --}}
                            <a href="{{ route('evento_retorno.pdf', $e->id) }}" target="_blank"
                                class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </a>
                            <a href="{{ route('evento_retorno.json.show', $e->id) }}" target="_blank"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-code"></i> JSON
                            </a>
                        </td>
                    </tr>
                    @if ($e->descripcionMsg && $e->estado !== 'PROCESADO')
                        <tr>
                            <td colspan="8" class="pt-0">
                                <small class="text-danger">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $e->descripcionMsg }}
                                </small>
                            </td>
                        </tr>
                    @endif
                    @if ($e->selloRecibido)
                        <tr>
                            <td colspan="8" class="pt-0">
                                <small class="text-muted">Sello: <code>{{ $e->selloRecibido }}</code></small>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Todavia no hay eventos de retorno.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $eventos->links() }}
    </div>

    @include('common.notis')
</div>
