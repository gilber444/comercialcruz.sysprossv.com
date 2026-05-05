@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="nombre"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='nombre' class="form-control" placeholder="Nombre">
        </div>
        @error('nombre')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="url"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='url' class="form-control"
                placeholder="Url">
        </div>
        @error('url')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select wire:model.lazy='metodo' class="form-control">
            <option value="">Elegir Metodo...</option>
            <option value="POST">POST</option>
            <option value="GET">GET</option>
            <option value="PUT">PUT</option>
            <option value="DELETE">DELETE</option>
            <option value="PATCH">PATCH</option>
            <option value="HEAD">HEAD</option>
            <option value="OPTIONS">OPTIONS</option>
            <option value="TRACE">TRACE</option>
        </select>
        @error('metodo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select wire:model.lazy='tipo' class="form-control">
            <option value="">Elegir Tipo...</option>
            <option value="Prueba">Prueba</option>
            <option value="Produccion">Produccion</option>
        </select>
        @error('tipo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select wire:model.lazy='estado' class="form-control">
            <option value="">Elegir Estado...</option>
            <option value="Activo">Activo</option>
            <option value="Desactivado">Desactivado</option>
        </select>
        @error('estado')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>
@include('common.modalFooter')
