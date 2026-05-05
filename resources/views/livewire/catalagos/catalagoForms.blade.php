@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-4 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="codogio"><i class='fa-solid fa-hashtag'></i></span>
            <input type="text" wire:model.lazy='codigo' class="form-control" placeholder="CAT-000">
        </div>
        @error('codigo') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-8 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="catalago"><i class='fa-solid fa-edit'></i></span>
            <input type="text" wire:model.lazy='catalago' class="form-control" placeholder="Nombre del catalago">
        </div>
        @error('catalago') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="descripcion"><i class='fa-solid fa-edit'></i></span>
            <textarea wire:model.lazy='descripcion' class="form-control" placeholder="Descripcion del catalago" cols="30" rows="5"></textarea>
        </div>
        @error('descripcion') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>

@include('common.modalFooter')
