<div wire:ignore.self class="modal fade" id="detalleAjusteModal" tabindex="-1" style="display: none;" aria-hidden="true">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">Detalles del Ajuste</h5>

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

                                    <th class="text-center">TOTAL</th>

                                    <th></th>

                                </thead>

                                <tbody class="">

                                    @foreach ($detalleAjustes as $det)

                                        <tr>

                                            <td class="text-center">{{ number_format($det->cantidad, 0) }}</td>

                                            <td class="">{{ $det->producto }}</td>

                                            <td class="">{{ $det->medida }}</td>

                                            <td class="text-end"> $

                                                {{ number_format($det->total, 2) }}

                                            </td>

                                            <td>

                                                @if ($det->status_ajustes !== 'Anulado' && $det->status_ajustes !== 'Realizado')

                                                <a href="javascript:void(0)" wire:click="DestroyP({{ $det->id }})" class="btn btn-danger btn-rounded mb-2">

                                                    <i class="fa-solid fa-trash"></i>

                                                </a>

                                                @endif

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

