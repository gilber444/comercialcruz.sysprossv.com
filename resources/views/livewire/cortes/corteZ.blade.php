<div wire:ignore.self class="modal fade" id="modalCorteZ" tabindex="-1" aria-labelledby="modalCorteZLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Corte z y Cierre de Caja</h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="row">
                        <div class="col">
                            <p>Ventas con Efectivo $ {{ number_format($totalVentas, 2) }}</p>
                            <p>Ventas con Tarjetas de Credito / Debito $ {{ number_format($totalTarjetas, 2) }}</p>
                            <p>Ventas con Cheque $ {{ number_format($totalCheque, 2) }}</p>
                            <hr>
                            <h6>
                                Total Sumas $ {{ number_format($totalSumas, 2) }}
                            </h6>
                            <hr>
                            <p>Creditos $ -{{ number_format($totalCreditos, 2) }}</p>
                            <p>Remesas $ -{{ number_format($totalRemesas, 2) }} </p>
                            <p>Cortes X / Arqueos $ -{{ number_format($arqueos, 2) }} </p>
                            <p>Devoluciones $ -{{ number_format($totalDevoluciones, 2) }} </p>
                            <p>Anulaciones $ -{{ number_format($totalAnulaciones, 2) }} </p>
                            <hr>
                            <h6>Total Operacion $ {{ number_format($totalSumaResta, 2)  }} + Caja Chica $ {{ number_format($cajaChica, 2) }} = {{ number_format($totalSumaResta + $cajaChica, 2)  }} </h6>
                            <hr>
                            <h6>Total Efectivo en Caja $ <input type="text" wire:model.lazy='totalEfectivo' class="form-control" wire:change='CorteZ({{ $selected_id }})' wire:keydown.enter='CorteZ({{ $selected_id }})'></h6>
                            <h5>Diferencia $ <span class="badge @if($totalDiferencia > 0) bg-label-success @else bg-label-danger @endif text-uppercase">
                                {{ number_format($totalDiferencia, 2) }}
                            </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="ProcesarCorteZ()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Efectuar</button>
                <div wire:loading wire:target="SaveTicket">Procesando Datos...</div>
            </div>
        </div>
    </div>
</div>
