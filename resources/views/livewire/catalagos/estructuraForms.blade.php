@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-4 mb-3">
        <label for="">Codigo</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="codogio"><i class='fa-solid fa-hashtag'></i></span>
            <input type="text" wire:model.lazy='codigo' class="form-control" placeholder="00000">
        </div>
        @error('codigo') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-8 mb-3">
        <label for="">Valor</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="valores"><i class='fa-solid fa-edit'></i></span>
            <input type="text" wire:model.lazy='valores' class="form-control" placeholder="Nombre del valor">
        </div>
        @error('valor') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <label for="">Catalago</label>
        <select wire:model.lazy='catalago' class="form-control">
            <option value="">Elegir</option>
            @foreach ($catalagos as $cata )
            <option value="{{ $cata->id }}">{{ $cata->codigo }} >{{ $cata->catalago }}</option>

            @endforeach
        </select>
        @error('catalago') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <label for="">Referencia</label>
        <select wire:model.lazy='dependencia' class="form-control">
            <option value="">Elegir</option>
            <option value="0">0</option>
            @foreach ( $referencias as $ref )
            <option value="{{ $ref->id }}">{{ $ref->valores }}</option>

            @endforeach
        </select>
        @error('dependencia') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <label for="">Estado</label>
        <select wire:model.lazy='estado' class="form-control">
            <option value="">Elegir</option>
            <option value="Si">Si</option>
            <option value="No">No</option>
        </select>
        @error('estado') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
@include('common.modalFooter')
