<div wire:ignore.self class="modal fade" id="detalleSolicitudModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Solicitud</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class='row'>
                    <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm table-fixed">
                            <thead class="bg-light sticky-top">
                                <th class="text-center">CODEBAR</th>
                                <th class="text-center">PRODUCTO</th>
                                <th class="text-center">MEDIDA</th>
                                <th class="text-center">CANTIDAD</th>
                                <th class="text-right">COSTO</th>
                                <th class="text-right">TOTAL</th>
                            </thead>
                            <tbody>
                                @foreach ($detalleSolicitud as $det)
                                    <tr>
                                        <td class="">{{ $det->codebar3 }}</td>
                                        <td class="">{{ $det->producto }}</td>
                                        <td class="">{{ $det->medida }}</td>
                                        <td class="text-center">{{ number_format($det->cantidad, 0) }}</td>
                                        <td class="text-end">$ {{ number_format($det->costo, 2) }}</td>
                                        <td class="text-end">$ {{ number_format($det->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light sticky-bottom">
                                <tr>
                                    <td colspan="5" class="text-end">
                                        <b>TOTAL</b>
                                    </td>
                                    <td class="text-end">
                                        $ {{ number_format($totalSolicitud, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<style>
    .table-fixed thead th,
    .table-fixed tfoot td {
        position: sticky;
        z-index: 10;
        background-color: #00000000;
    }

    .table-fixed thead th {
        top: 0;
    }

    .table-fixed tfoot td {
        bottom: 0;
    }
</style>
