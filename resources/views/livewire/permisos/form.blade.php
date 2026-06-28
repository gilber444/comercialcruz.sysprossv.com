<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">{{ $componentName }} | {{ $selected_id > 0 ? 'Editar' : 'Nuevo' }}
                </h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="input-group input-group-merge">
                    <span class="input-group-text" id="permissionName"><i class='bx bx-edit'></i></span>
                    <input type="text" wire:model.lazy='permissionName' class="form-control"
                        placeholder="Nombre del permiso"
                        wire:keydown.alt.s.away="{{ $selected_id > 0 ? 'UpdatePermission()' : 'CreatePermission()' }}">
                </div>
                @error('permissionName')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="modal-footer">
                <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary"
                    data-bs-dismiss="modal">Cerrar</button>
                @if ($selected_id < 1)
                    <button type="button" id="guardar" wire:click.prevent="CreatePermission()" wire:keydown.alt.s.away='CreatePermission()'
                        class="btn btn-primary"><i class='bx bxs-save'></i> Alt+s Guardar Datos</button>
                @else
                    <button type="button" id='editar' wire:click.prevent="Update()"
                        wire:keydown.alt.s.away='UpdatePermission()' class="btn btn-primary"><i class='bx bx-revision'></i>
                        Alt+s Actualizar Datos</button>
                @endif
            </div>
        </div>
    </div>
</div>
