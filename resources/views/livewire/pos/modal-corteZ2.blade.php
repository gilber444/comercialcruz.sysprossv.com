<div wire:ignore.self class="modal fade" id="modalCorteZ2" tabindex="-1" aria-labelledby="modalCorteZ2Label" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Corte z y Cierre de Caja</h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{--<div class="col-sm-12 col-md-6 mb-3">
                        <h4 class="text-center">Billetes</h4>
                        <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 100 /{{ $b100R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b100' placeholder="100" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b100') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 50 /{{ $b50R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b50' placeholder="50" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b50') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 20 /{{ $b20R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b20' placeholder="20" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b20') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 10 /{{ $b10R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b10' placeholder="10" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b10') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 5 /{{ $b5R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b5' placeholder="5" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b5') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 1 /{{ $b1R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-money-bill"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b1' placeholder="1" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b1') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <h4 class="text-center">Monedas</h4>
                        <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 1 /{{ $bd1R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='bd1' placeholder="1" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('bd1') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.25 /{{ $b025R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b025' placeholder="0.25" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b025') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.10 /{{ $b010R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b010' placeholder="0.10" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b010') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.05 /{{ $b005R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b005' placeholder="0.05" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b005') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="">$ 0.01 /{{ $b001R }}</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-coins"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='b001' placeholder="0.01" class="form-control" wire:keyup="CuadrarEfectivo2()">
                                </div>
                                @error('b001') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                        </div>
                    </div>--}}
                    <div class="row">
                        <div class="col">
                            <p>Ventas con Efectivo $ {{ number_format($totalVentas2, 2) }}</p>
                            <p>Ventas con Tarjetas de Credito / Debito $ {{ number_format($totalTarjetas2, 2) }}</p>
                            <p>Ventas con Cheque $ {{ number_format($totalCheque2, 2) }}</p>
                            <hr>
                            <h6>
                                Total Sumas $ {{ number_format($totalSumas2, 2) }}
                            </h6>
                            <hr>
                            <p>Creditos $ -{{ number_format($totalCreditos2, 2) }}</p>
                            <p>Remesas $ -{{ number_format($totalRemesas2, 2) }} </p>
                            <p>Cortes X / Arqueos $ -{{ number_format($cortes2, 2) }} </p>
                            <p>Devoluciones $ -{{ number_format($totalDevoluciones2, 2) }} </p>
                            <p>Anulaciones $ -{{ number_format($totalAnulaciones2, 2) }} </p>
                            <hr>
                            <h6>Total Operacion $ {{ number_format($totalSumaResta2, 2)  }} + Caja Chica $ {{ number_format($aperturas2->inicio,2) }} = {{ number_format($totalSumaResta2 + $aperturas2->inicio, 2)  }} </h6>
                            <hr>
                            <h6>Total Efectivo en Caja $ <input type="text" wire:model.lazy='totalEfectivo2' wire:keypress.enter='CuadrarEfectivo2()' wire:change='CuadrarEfectivo2()' class="form-control"></h6>
                            <h5>Diferencia $ <span class="badge @if($totalDiferencia2 > 0) bg-label-success @else bg-label-danger @endif text-uppercase">
                                {{ number_format($totalDiferencia2, 2) }}
                            </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="CorteZ2({{ $aperturas2->id }})" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Efectuar</button>
                <div wire:loading wire:target="CorteZ">Guardando la venta...</div>
            </div>
        </div>
    </div>
</div>
