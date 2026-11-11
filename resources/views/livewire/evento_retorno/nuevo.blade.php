<div class="card">
    <div class="card-header">
        <h5 class="card-title m-0 me-2"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
        <hr class="my-2">

        @if (count($motivos))
            <div class="alert alert-danger">
                <b><i class="fa-solid fa-triangle-exclamation"></i> No se puede aplicar el retorno</b>
                <ul class="mb-0 mt-2">
                    @foreach ($motivos as $m)
                        <li>{{ $m }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$venta)
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Buscar la factura a la que se aplica el retorno</label>
                    <input type="text" class="form-control" wire:model.debounce.400ms="search"
                        placeholder="Correlativo, codigo de generacion o cliente">
                    <div class="form-text">
                        Solo aparecen documentos con sello de recepcion. El Credito Fiscal no admite
                        retorno: para ese caso se emite una Nota de Credito.
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label">Fecha del evento</label>
                    <input type="date" class="form-control" wire:model="fecha">
                </div>
            </div>
        @endif
    </div>

    <div class="card-body">
        @if (!$venta)
            <div class="table-responsive text-nowrap">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Correlativo</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Codigo de generacion</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($facturas as $f)
                            <tr>
                                <td>
                                    <span class="badge bg-label-primary">{{ $f->tipoCodigo }}</span>
                                    <small>{{ $f->tipoNombre }}</small>
                                </td>
                                <td><b>{{ $f->correlativo }}</b></td>
                                <td>{{ \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $f->cliente ?? 'Consumidor final' }}</td>
                                <td><small><code>{{ $f->codigoGeneracion }}</code></small></td>
                                <td class="text-end">{{ number_format($f->total, 2) }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary" wire:click="Seleccionar({{ $f->id }})" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="Seleccionar({{ $f->id }})"><i class="fa-solid fa-arrow-right"></i></span>
                                        <span wire:loading wire:target="Seleccionar({{ $f->id }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                        Elegir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No hay facturas que admitan retorno con ese criterio.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info d-flex justify-content-between align-items-center py-2">
                <div>
                    Factura <b>{{ $ventaSel?->correlativo }}</b>
                    del {{ \Carbon\Carbon::parse($ventaSel?->fecha)->format('d/m/Y') }}
                    &mdash; total {{ number_format($ventaSel?->total ?? 0, 2) }}
                    &mdash; fecha del evento <b>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</b>
                </div>
                <button class="btn btn-sm btn-outline-secondary" wire:click="Cancelar">Cambiar factura</button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Disponible</th>
                            <th style="width:150px" class="text-center">A retornar</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart as $item)
                            <tr class="{{ $item->cantidad > 0 ? 'table-warning' : '' }}">
                                <td>{{ $item->name }}</td>
                                <td class="text-end">{{ number_format($item->vendida, 2) }}</td>
                                <td>
                                    <input type="number" min="0" step="0.01" max="{{ $item->vendida }}"
                                        class="form-control form-control-sm text-end"
                                        wire:model.defer="cantidades.{{ $item->id }}"
                                        wire:change="updateCantidad({{ $item->id }})">
                                </td>
                                <td class="text-end">{{ number_format($item->precio, 2) }}</td>
                                <td class="text-end">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total a retornar</th>
                            <th class="text-end">{{ number_format($totalRetorno, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">
                    Los productos con cantidad mayor a cero vuelven al inventario y quedan
                    registrados en el Kardex al guardar.
                </small>
                <div>
                    <button class="btn btn-outline-secondary" wire:click="Cancelar" wire:loading.attr="disabled">Cancelar</button>
                    <button class="btn btn-success" wire:click="Guardar" wire:loading.attr="disabled"
                        @if ($totalRetorno <= 0) disabled @endif>
                        <span wire:loading.remove wire:target="Guardar"><i class="fa-solid fa-floppy-disk"></i></span>
                        <span wire:loading wire:target="Guardar"><i class="fa-solid fa-spinner fa-spin"></i></span>
                        Guardar evento
                    </button>
                </div>
            </div>
        @endif
    </div>

    @include('common.notis')
</div>
