<div wire:ignore.self  class="modal fade" id="modalArqueo" tabindex="-1" aria-labelledby="modalCorteZLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Corte X y Cierre de Caja</h5>
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
                                    <input type="text" wire:model.lazy="b100" wire:keydown.enter="focoX('b100', 'b50')" class="form-control" id="b100" placeholder="100" @if (!empty($disabledInputs['b100'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 50 /{{ $b50R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b50" wire:keydown.enter="focoX('b50', 'b20')" class="form-control" id="b50" placeholder="50" @if (!empty($disabledInputs['b50'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 20 /{{ $b20R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b20" wire:keydown.enter="focoX('b20', 'b10')" class="form-control" id="b20" placeholder="20" @if (!empty($disabledInputs['b20'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 10 /{{ $b10R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b10" wire:keydown.enter="focoX('b10', 'b5')" class="form-control" id="b10" placeholder="10" @if (!empty($disabledInputs['b10'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 5 /{{ $b5R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b5" wire:keydown.enter="focoX('b5', 'b1')" class="form-control" id="b5" placeholder="5" @if (!empty($disabledInputs['b5'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 1 /{{ $b1R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-money-bill"></i></span>
                                    <input type="text" wire:model.lazy="b1" wire:keydown.enter="focoX('b1', 'bd1')" class="form-control" id="b1" placeholder="1" @if (!empty($disabledInputs['b1'])) disabled @endif>
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
                                    <input type="text" wire:model.lazy="bd1" wire:keydown.enter="focoX('bd1', 'b025')" class="form-control" id="bd1" placeholder="1" @if (!empty($disabledInputs['bd1'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.25 /{{ $b025R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b025" wire:keydown.enter="focoX('b025', 'b010')" class="form-control" id="b025" placeholder="0.25" @if (!empty($disabledInputs['b025'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.10 /{{ $b010R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b010" wire:keydown.enter="focoX('b010', 'b005')" class="form-control" id="b010" placeholder="0.10" @if (!empty($disabledInputs['b010'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.05 /{{ $b005R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b005" wire:keydown.enter="focoX('b005', 'b001')" class="form-control" id="b005" placeholder="0.05" @if (!empty($disabledInputs['b005'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.01 /{{ $b001R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="fa-solid fa-coins"></i></span>
                                    <input type="text" wire:model.lazy="b001" wire:keydown.enter="focoX('b001', 'totalEfectivo')" class="form-control" id="b001" placeholder="0.01" @if (!empty($disabledInputs['b001'])) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">Otros Ingresos</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy="otrosIngresos" wire:keydown.enter="focoX('otrosIngresos', 'totalEfectivo')" class="form-control" id="otrosIngresos" placeholder="0.01" @if (!empty($disabledInputs['otrosIngresos'])) disabled @endif>
                                </div>
                                @error('otrosIngresosx') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            {{--
                            <p>Ventas con Efectivo $ {{ number_format($totalVentasx, 2) }}</p>
                            <p>Ventas con Tarjetas de Credito / Debito $ {{ number_format($totalTarjetasx, 2) }}</p>
                            <p>Ventas con Cheque $ {{ number_format($totalChequex, 2) }}</p>
                            <hr>
                            <h6>
                                Total Sumas $ {{ number_format($totalSumasx, 2) }}
                            </h6>
                            <hr>
                            <p>Creditos $ -{{ number_format($totalCreditosx, 2) }}</p>
                            <p>Remesas $ -{{ number_format($totalRemesasx, 2) }} </p>
                            <p>Cortes X / Arqueos $ -{{ number_format($cortesx, 2) }} </p>
                            <p>Devoluciones $ -{{ number_format($totalDevolucionesx, 2) }} </p>
                            <p>Anulaciones $ -{{ number_format($totalAnulacionesx, 2) }} </p>
                            <hr>
                            <h6>Total Operacion $ {{ number_format($totalSumaRestax, 2)  }} + Caja Chica $ {{ number_format($aperturas->inicio,2) }} = {{ number_format($totalSumaRestax + $aperturas->inicio, 2)  }} </h6>
                            <hr>
                            <h6>Total Efectivo en Caja $ <input type="text" wire:model.defer="totalEfectivox" wire:change='CuadrarEfectivoX()' wire:keypress.enter='CuadrarEfectivoX()' class="form-control"></h6>--}}
                            <h6>Total Efectivo en Caja $ {{ number_format($totalEfectivox, 2)}}
                            </h6>
                            {{--  <h5>Diferencia $ <span class="badge @if($totalDiferenciax > 0) bg-label-success @else bg-label-danger @endif text-uppercase">
                                {{ number_format($totalDiferenciax, 2) }}
                            </span>
                            </h5>--}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="CorteX()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Efectuar</button>
                <div wire:loading wire:target="CorteX">Procesando Datos...</div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        Livewire.on('focusInputx', function (inputId) {
            setTimeout(() => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 200);
        });
    });
</script>
