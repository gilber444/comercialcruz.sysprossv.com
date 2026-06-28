<div wire:ignore.self class="modal fade" id="myModals" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Metodo de pago</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">Metodo</label>
                        <div class="input-group input-group-merge">
                            <select wire:model='metodo' class='form-control'>
                                <option value="Elegir">Elegir</option>
                                {{--@foreach ($formas as $forma)--}}
                                <option value="{{--$forma->id--}}">{{--$forma->forma--}}</option>

                                {{--@endforeach--}}
                            </select>
                        </div>
                        @error('metodo') <span class="text-danger er">{{ $message}}</span>@enderror
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for=""># Comprobante</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" wire:model='comprobante' id="modal-search-input" placeholder="0000" class="form-control">
                        </div>
                        @error('comprobante') <span class="text-danger er">{{ $message}}</span>@enderror
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-6 fw-normal">Total</dt>
                        <dd class="col-sm-6 text-success text-end">${{--$total--}}</dd>

                        <dt class="col-sm-6 fw-normal">Efectivo</dt>
                        <dd class="col-sm-6 text-end"><input type="text" wire:model="efectivo" wire:keyup="Cash()" class="form-control text-end"></dd>
                        <dt class="col-sm-6 fw-normal">Cambio</dt>
                        <dd class="col-sm-6 text-danger text-end">{{--number_format($cambio, 2)--}}</dd>
                    </dl>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="saveVentas()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Efectuar</button>
                <div wire:loading wire:target="saveVentas">Guardando la venta...</div>
            </div>
        </div>
    </div>
</div>
