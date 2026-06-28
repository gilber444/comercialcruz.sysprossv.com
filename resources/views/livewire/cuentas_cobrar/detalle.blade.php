<div wire:ignore.self class="modal fade" id="detalleAjusteModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Productos del Credito</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                    <div class='row'>
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <th class="text-center">CANTIDAD</th>
                                    <th class="text-center">PRODUCTO</th>
                                    <th class="text-center">MEDIDA</th>
                                    <th class="text-center">PRECIO</th>
                                    <th class="text-center">TOTAL</th>
                                </thead>
                                <tbody class="">
                                    @foreach ($detalle_productos as $det)
                                        <tr>
                                            <td class="text-center">{{ number_format($det->cantidad, 0) }}</td>
                                            <td class="">{{ $det->Rproductos->nombreProducto }}</td>
                                            <td class="text-center">{{ $det->unidad }}</td>
                                            <td class="text-end">${{ number_format($det->precio, 2) }}
                                            </td>
                                            <td class="text-end">${{ number_format($det->total, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary"
                    data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
