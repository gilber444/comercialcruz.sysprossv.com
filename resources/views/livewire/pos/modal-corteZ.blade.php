<div wire:ignore.self class="modal fade" id="modalCorteZ" tabindex="-1" aria-labelledby="modalCorteZLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Corte z y Cierre de Caja</h5>
            </div>
            <div class="modal-body">
                <div class="row">

                    <div class="col-sm-12 col-md-6 mb-3">
                        <h4 class="text-center">Billetes</h4>
                        <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 100 /{{ $b100R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b100" wire:keydown.enter="foco('b100', 'b50')" class="form-control" id="b100" placeholder="100" @if (!empty($disabledInputs['b100'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 50 /{{ $b50R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b50" wire:keydown.enter="foco('b50', 'b20')" class="form-control" id="b50" placeholder="50" @if (!empty($disabledInputs['b50'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 20 /{{ $b20R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b20" wire:keydown.enter="foco('b20', 'b10')" class="form-control" id="b20" placeholder="20" @if (!empty($disabledInputs['b20'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 10 /{{ $b10R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b10" wire:keydown.enter="foco('b10', 'b5')" class="form-control" id="b10" placeholder="10" @if (!empty($disabledInputs['b10'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 5 /{{ $b5R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b5" wire:keydown.enter="foco('b5', 'b1')" class="form-control" id="b5" placeholder="5" @if (!empty($disabledInputs['b5'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 1 /{{ $b1R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b1" wire:keydown.enter="foco('b1', 'bd1')" class="form-control" id="b1" placeholder="1" @if (!empty($disabledInputs['b1'])) disabled @endif>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <h4 class="text-center">Monedas</h4>
                        <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 1 /{{ $bd1R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="bd1" wire:keydown.enter="foco('bd1', 'b025')" class="form-control" id="bd1" placeholder="1" @if (!empty($disabledInputs['bd1'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.25 /{{ $b025R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b025" wire:keydown.enter="foco('b025', 'b010')" class="form-control" id="b025" placeholder="0.25" @if (!empty($disabledInputs['b025'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.10 /{{ $b010R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b010" wire:keydown.enter="foco('b010', 'b005')" class="form-control" id="b010" placeholder="0.10" @if (!empty($disabledInputs['b010'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.05 /{{ $b005R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b005" wire:keydown.enter="foco('b005', 'b001')" class="form-control" id="b005" placeholder="0.05" @if (!empty($disabledInputs['b005'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.01 /{{ $b001R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b001" wire:keydown.enter="foco('b001', 'totalEfectivo')" class="form-control" id="b001" placeholder="0.01" @if (!empty($disabledInputs['b001'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">Otros Ingresos</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy="otrosIngresos" wire:keydown.enter="foco('otrosIngresos', 'totalEfectivo')" class="form-control" id="otrosIngresos" placeholder="0.01" @if (!empty($disabledInputs['otrosIngresos'])) disabled @endif>
                                </div>
                                @error('otrosIngresosx') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            {{--  <p>Ventas con Efectivo $ {{ number_format($totalVentas, 2) }}</p>--}}
                            {{--  <p>Ventas con Tarjetas de Credito / Debito $ {{ number_format($totalTarjetas, 2) }}</p>--}}
                            {{--  <p>Ventas con Cheque $ {{ number_format($totalCheque, 2) }}</p>--}}
                            <hr>
                            <h6>
                                Total Sumas $ {{ number_format($totalSumas, 2) }}
                            </h6>
                            <hr>
                            <p>Creditos $ -{{ number_format($totalCreditos, 2) }}</p>
                            <p>Remesas $ -{{ number_format($totalRemesas, 2) }} </p>
                            <p>Cortes X / Arqueos $ -{{ number_format($cortes, 2) }} </p>
                            <p>Devoluciones $ -{{ number_format($totalDevoluciones, 2) }} </p>
                            <p>Anulaciones $ -{{ number_format($totalAnulaciones, 2) }} </p>
                            <hr>
                            <h6>Total Operacion $ {{ number_format($totalSumaResta, 2)  }} + Caja Chica $ {{ number_format($aperturas->inicio,2) }} = {{ number_format($totalSumaResta + $aperturas->inicio, 2)  }} </h6>
                            <hr>
                            <h6>Total Efectivo en Caja $ <input type="text" wire:model.lazy='totalEfectivo' wire:keypress.enter='CuadrarEfectivo()' wire:change='CuadrarEfectivo()' class="form-control" readonly>
                            </h6>
                            <h5>Diferencia $ <span class="badge @if($totalDiferencia > 0) bg-label-success @else bg-label-danger @endif text-uppercase">
                                {{ number_format($totalDiferencia, 2) }}
                            </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
               <button type="button"
                    wire:click.prevent="CorteZ"
                    wire:loading.attr="disabled"
                    wire:target="CorteZ"
                    id="guardar"
                    class="btn btn-primary">

                    <span wire:loading.remove wire:target="CorteZ">
                        <i class='bx bxs-save'></i> Efectuar
                    </span>

                    <span wire:loading wire:target="CorteZ">
                        Procesando Corte Z, por favor espere...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
