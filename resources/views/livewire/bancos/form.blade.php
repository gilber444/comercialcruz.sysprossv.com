@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="nombre"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='nombre' class="form-control" placeholder="Nombre del Banco" wire:keydown.alt.s.away="{{ $selected_id > 0 ? 'Update()' : 'Store()' }}">
        </div>
        @error('nombre') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="cuenta"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='cuenta' class="form-control" placeholder="Cuenta del Banco">
        </div>
        @error('cuenta') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="correlativo"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='correlativo' class="form-control" placeholder="Correlativo">
        </div>
        @error('correlativo') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>

@include('common.modalFooter')
