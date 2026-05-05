@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="codigo"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='codigo' class="form-control" placeholder="Codigo">
        </div>
        @error('codigo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="valor"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='valor' class="form-control"
                placeholder="Nombre del INCOTERMS">
        </div>
        @error('valor')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select wire:model.lazy='status' class="form-control">
            <option value="">Elegir...</option>
            <option value="Activo">Activo</option>
            <option value="Desactivado">Desactivado</option>
        </select>
        @error('status')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>

</div>

@include('common.modalFooter')
