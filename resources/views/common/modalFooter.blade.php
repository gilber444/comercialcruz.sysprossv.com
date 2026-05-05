</div>
    <div class="modal-footer">
        <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
        @if ($selected_id < 1)
        <button type="button" id="guardar" wire:click.prevent="Store()" wire:keydown.alt.s.away='Store()' class="btn btn-primary"><i class='bx bxs-save' ></i> Alt+s Guardar Datos</button>
        @else
        <button type="button" id='editar' wire:click.prevent="Update()" wire:keydown.alt.s.away='Update()' class="btn btn-primary"><i class='bx bx-revision'></i> Alt+s Actualizar Datos</button>
        @endif
    </div>
</div>
</div>
</div>
