<div wire:ignore.self class="modal fade" @if ($itemsProd > 0) id="modalSaveTikect" @endif tabindex="-1" style="display: none;" aria-hidden="true">
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
                            <select wire:model.lazy='metodo' class='form-control' id='metodot' disabled>
                                <option value="Elegir">Elegir</option>
                                @foreach ($formas as $forma)
                                <option value="{{$forma->id}}">{{$forma->forma}}</option>
                                @endforeach
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
                            <input type="text" wire:model.lazy='comprobante' id="comprobantet" placeholder="0000" class="form-control" disabled>
                        </div>
                        @error('comprobante') <span class="text-danger er">{{ $message}}</span>@enderror
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-6 fw-normal">Total</dt>
                        <dd class="col-sm-6 text-success text-end">$ {{number_format($total, 2)}}</dd>

                        <dt class="col-sm-6 fw-normal">Efectivo</dt>
                        <dd class="col-sm-6 text-end">
                            <input type="text" wire:model.defer="efectivo"  wire:model.lazy="efectivo" wire:blur="Cash()" wire:keydown.enter='SaveTicket()' id="efectivot" class="form-control text-end">
                            @error('efectivo') <span class="text-danger er">{{ $message}}</span>@enderror
                        </dd>
                        <dt class="col-sm-6 fw-normal">Cambio</dt>
                        <dd class="col-sm-6 text-danger text-end">{{ number_format($cambio,2)}}</dd>
                    </dl>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="SaveTicket()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Efectuar</button>
                <div wire:loading wire:target="SaveTicket">Guardando la venta...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById('modalSaveTikect');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function () {
            const efectivoInput = document.getElementById('efectivo');
            if (efectivoInput) {
                setTimeout(() => {
                    efectivoInput.focus();
                    efectivoInput.select();
                }, 100);
            }
        });
    }
});
</script>

