<div wire:ignore.self class="modal fade" id="Detelles" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="Detelles">
                </h5>
                <h6 class="text-center"> Detalles de DTE</h6>
            </div>
            <div class="modal-body">
                <table class="table table-hover table-sm">
                    <thead>
                        <th class="text-center"> #</th>
                        <th class="text-center">Codigo Control</th>
                        <th class="text-center">Cdigo Generacion</th>
                        <th class="text-center">Fecha Emision</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center"></th>
                    </thead>
                    <tbody>
                        @if ($detalle && $detalle->isNotEmpty())
                        @foreach ( $detalle as $dt )
                        <tr>
                            <td class="text-center">
                                {{ $dt->noItem }}
                            </td>
                            <td class="text-center">
                                {{ $dt->numeroControl }}
                            </td>
                            <td class="text-center">
                                {{ $dt->codigoGeneracion }}
                            </td>
                            <td class="text-center">
                                {{ $dt->fecEmi }}
                            </td>
                            <td class="text-center">
                                {{ $dt->estado }}
                            </td>
                            <td class="text-center">
                                @if ($dt->estado <> 'Prosecado')
                                    Recepcion DTE
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @else
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary"
                    data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
