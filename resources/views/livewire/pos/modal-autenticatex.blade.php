<div wire:ignore.self class="modal fade" id="modalAutenticatex" tabindex="-1" aria-labelledby="modalAutenticatexLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Validar Credenciales</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="usernamex" class="form-label">Usuario</label>
                    <input wire:model.lazy="usernamex" type="text" class="form-control" id="usernamex" autofocus required>
                </div>
                <div class="mb-3">
                    <label for="passwordx" class="form-label">Contraseña</label>
                    <input wire:model.lazy="passwordx" type="password" class="form-control" id="passwordx" required wire:keydown.enter="openAuthenticatedModal3()" >
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="openAuthenticatedModal3()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Validar</button>
                <div wire:loading wire:target="openAuthenticatedModal3">Procesando datos...</div>
            </div>
        </div>
    </div>
</div>
