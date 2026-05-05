<div wire:ignore.self class="modal fade" id="modalAutenticate2" tabindex="-1" aria-labelledby="modalAutenticate2Label" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Validar Credenciales</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="username2" class="form-label">Usuario</label>
                    <input wire:model.lazy="username2" type="text" class="form-control" id="username2" required>
                </div>
                <div class="mb-3">
                    <label for="password2" class="form-label">Contraseña</label>
                    <input wire:model.lazy="password2" type="password" class="form-control" id="password2" required wire:keydown.enter="openAuthenticatedModal2()" >
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="openAuthenticatedModal2()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Validar</button>
                <div wire:loading wire:target="openAuthenticatedModal2">Procesando datos...</div>
            </div>
        </div>
    </div>
</div>
