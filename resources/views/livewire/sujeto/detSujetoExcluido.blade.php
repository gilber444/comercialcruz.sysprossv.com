<div wire:ignore.self class="modal fade" id="modalDetalleSujetoExcluido" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle Sujeto Excluido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <th class="text-center">#</th>
                            <th class="text-center">Producto</th>
                            <th class="text-center">Unidad</th>
                            <th class="text-center">Descripción</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Precio Unitario</th>
                            <th class="text-center">Total</th>
                        </thead>
                        <tbody>
                             @foreach ($detalleSujetoExcluido as $det)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-center">{{ $det->producto ?? '-' }}</td>
                                    <td class="text-center">{{ $det->unidad ?? '-' }}</td>
                                    <td class="text-center">{{ $det->name ?? '-' }}</td>
                                    <td class="text-center">{{ number_format($det->cantidad, 0) }}</td>
                                    <td class="text-center">{{ number_format($det->costo ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($det->total ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                            @if(count($cart) == 0)
                                <tr>
                                    <td colspan="7" class="text-center">Sin detalles</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>