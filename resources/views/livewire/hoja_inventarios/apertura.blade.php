<div wire:ignore.self class="modal fade" id="modalAperturaInventario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Iniciar inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="col-sm-12 col-md-12 mb-3">
                    <label class="form-label">Elegir Sucursal</label>
                    <select wire:model='sucursal' class="form-select">
                        <option value="0">Elegir Sucursal</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                    @error('sucursal')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
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

                <div class="mb-2">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" rows="3" wire:model.defer="observacionApertura"></textarea>
                    @error('observacionApertura') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

                <button type="button" class="btn btn-success"
                    wire:click="Validar"
                    wire:loading.attr="disabled"
                    wire:target="Validar">
                    <span wire:loading.remove wire:target="Validar">
                        <i class="fa-solid fa-check"></i> Abrir inventario
                    </span>
                    <span wire:loading wire:target="Validar">
                        <i class="fa-solid fa-spinner fa-spin"></i> Abriendo...
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>