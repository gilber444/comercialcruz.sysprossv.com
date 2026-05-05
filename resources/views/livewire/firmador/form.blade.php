@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="firmador"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='firmador' class="form-control" placeholder="Firmador">
        </div>
        @error('firmador')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="fecha"><i class='bx bx-edit'></i></span>
            <input type="date" wire:model.lazy='fecha' class="form-control"
                placeholder="Fecha">
        </div>
        @error('fecha')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select wire:model.lazy='estado' class="form-control">
            <option value="">Elegir...</option>
            <option value="Activo">Activo</option>
            <option value="Desactivado">Desactivado</option>
        </select>
        @error('estado')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>

</div>

@include('common.modalFooter')
