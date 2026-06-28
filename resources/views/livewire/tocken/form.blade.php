@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="tocken"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='tocken' class="form-control" placeholder="Tocken">
        </div>
        @error('tocken')
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
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="json"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model='json' class="form-control"
                placeholder="Json">
        </div>
        @error('json')
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
