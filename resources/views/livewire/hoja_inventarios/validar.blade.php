<div wire:ignore.self class="modal fade" id="validarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="validateCredentialsModalLabel">Validar Credenciales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" class="form-control" wire:model.defer="username" required>
                    @error('username') <span class="text-danger er">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" class="form-control" wire:model.defer="password" required>
                    @error('password') <span class="text-danger er">{{ $message }}</span> @enderror
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

                <button type="button"
                        class="btn btn-primary"
                        wire:click.prevent="Validar">
                    <i class='bx bxs-save'></i> Guardar Datos
                </button>
            </div>

        </div>
    </div>
</div>
