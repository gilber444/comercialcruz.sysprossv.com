<div wire:ignore.self class="modal fade" id="modalAutenticate" tabindex="-1" aria-labelledby="modalAutenticateLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Validar Credenciales</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input wire:model.lazy="username" type="text" class="form-control" id="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input wire:model.lazy="password" type="password" class="form-control" id="password" required wire:keydown.enter="openAuthenticatedModal()" >
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="openAuthenticatedModal()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Validar</button>
                <div wire:loading wire:target="openAuthenticatedModal">Procesando Datos...</div>
            </div>
        </div>
    </div>
</div>
