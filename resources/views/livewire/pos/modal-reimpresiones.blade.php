<div wire:ignore.self class="modal fade" id="modalReimpresion" tabindex="-1" aria-labelledby="modalReimpresionlabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Reimpresiones</h5>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-header">
                        @include('common.searchbox')
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover table-sm">
                            <thead>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Hora</th>
                                <th class="text-center">Caja</th>
                                <th class="text-center">Corte</th>
                                <th class="text-center">Ticket No.</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Cajero</th>
                            </thead>
                            <tbody>
                                 @foreach ( $ventas as $r )
                                <tr>
                                    <td class="text-center">{{ $r->fecha }}</td>
                                    <td class="text-center">{{ $r->hora }}</td>
                                    <td class="text-center">{{ $r->Rcajas->caja }}</td>
                                    <td class="text-center">{{ $r->Rcortes->corte }}</td>
                                    <td class="text-center">
                                        <a href="javascript:void(0)" class="btn rounded-pill btn-outline-dark btn-sm" wire:click="ImprimirReimpresion({{ $r->id }})" title="Imprimir">
                                            {{ $r->numero && $r->numero !== '' ? $r->numero : $r->correlativo }}
                                        </a>
                                    </td>
                                    <td class="text-end"> ${{ number_format($r->total, 2)  }}
                                    </td>
                                    <td class="text-center">{{ $r->Rcajeros->name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $ventas->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
