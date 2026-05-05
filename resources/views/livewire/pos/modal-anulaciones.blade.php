<div wire:ignore.self class="modal fade" id="modalAnulaciones" tabindex="-1" aria-labelledby="modalAnulaciones" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Anulaciones y Devoluciones</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="" class="form-label">Tipo de Factura</label>
                    <select wire:model.lazy='fo' class="form-control">
                        <option value="">Elegir</option>
                        @foreach ($formasPagos as $f )
                            <option value="{{ $f->id }}">{{ $f->facturador }}</option>
                        @endforeach
                    </select>
                    @error('fo') <span class="text-danger er">{{ $message}}</span>@enderror
                </div>
                <div class="mb-3">
                    <label for="ticket" class="form-label">Ticket o Factura</label>
                    <input wire:model.lazy="numeroDoc" type="text" class="form-control" id="tikect" required>
                    @error('numeroDoc') <span class="text-danger er">{{ $message}}</span>@enderror
                </div>
                <div class="mb-3">
                    <label for="" class="form-label">Tipo de Transaccion</label>
                    <select wire:model.lazy='tipoTran' class="form-control">
                        <option value="">Elegir</option>
                        <option value="1">Anulacion</option>
                        <option value="2">Devolucion</option>
                    </select>
                    @error('tipoTran') <span class="text-danger er">{{ $message}}</span>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña de Autorizacion</label>
                    <input wire:model.lazy="passwordd" type="password" class="form-control" id="password" required wire:keydown.enter="AnulaDevo()" >
                    @error('passwordd') <span class="text-danger er">{{ $message}}</span>@enderror
                </div>
                <div class="mb-3 text-center">
                    <label for="password" class="form-label text-danger">{{ $error }}</label>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="AnulaDevo()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Realizar Transaccion</button>
                <div wire:loading wire:target="AnulaDevo">Guardando la venta...</div>
            </div>
        </div>
    </div>
</div>
