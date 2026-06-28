<div wire:ignore.self class="modal fade"  id="modalRemesas" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Remesar</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">Disponile</label>
                        <h4>$ {{ number_format($disponible, 2) }}</h4>
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">$ Monto de Envio</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-dollar"></i>
                            </span>
                            <input type="text" wire:model.lazy="montoEnvio" placeholder="{{ number_format($disponible, 2) }}" class="form-control" id='montoEnvio' onfocus="this.select()" wire:loading.attr="disabled">
                        </div>
                        @error('montoEnvio') <span class="text-danger er">{{ $message}}</span>@enderror
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">Motivo de la Remesa</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                            </span>
                            <input type="text" wire:model.lazy="concepto" class="form-control" id="concepto" wire:loading.attr="disabled" wire:keydown.enter="SaveRemesa">
                        </div>
                        @error('montoEnvio') <span class="text-danger er">{{ $message}}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button"
                    wire:click.prevent="SaveRemesa"
                    wire:loading.attr="disabled"
                    wire:target="SaveRemesa"
                    id="guardar"
                    class="btn btn-primary">

                    <span wire:loading.remove wire:target="SaveRemesa">
                        <i class='bx bxs-save'></i> Remesar
                    </span>

                    <span wire:loading wire:target="SaveRemesa">
                        Procesando Remesa, por favor espere...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
