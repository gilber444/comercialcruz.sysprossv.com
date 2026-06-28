<div wire:ignore.self class="modal fade" id="detallesHojas" tabindex="-1" style="display: none;" aria-hidden="true">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">Detalles de la de hojas de inventarios</h5>

                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>

            </div>

            <div class="modal-body">

                <div class='row'>

                    <div class="table-responsive">

                        <table class="table table-hover table-sm">

                            <thead>

                                <th class="text-center">codebar</th>

                                <th class="text-center">PRODUCTO</th>

                                <th class="text-center">MEDIDA</th>

                                <th class="text-center">Cantidad anterior</th>

                                <th class="text-center">Conteo</th>

                                <th class="text-center">Diferencia</th>

                                <th class="text-right">COSTO</th>

                                <th class="text-right">TOTAL</th>

                                <th class="text-center">Responsable</th>

                                <th></th>

                            </thead>

                            <tbody class="">

                                @foreach ($detalles as $det)
                                    <tr class="table-sm" style="font-size: 80%">

                                        <td class="text-center">{{ number_format($det->codebar, 0) }}</td>

                                        <td class="">{{ $det->producto }} - {{ $det->nombre }}</td>

                                        <td class="">{{ $det->medida }}</td>

                                        <td class="">{{ $det->cantidadAnterior }}</td>

                                        <td class="">{{ $det->cantidadActual }}</td>

                                        <td class="">{{ $det->diferencia }}</td>

                                        <td class="text-right">$ {{ number_format($det->costo, 2) }}</td>

                                        <td class="text-right">$ {{ number_format($det->total, 2) }}</td>

                                        <td class="">{{ $det->responsable }}</td>

                                    </tr>
                                @endforeach

                            </tbody>
                            

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
