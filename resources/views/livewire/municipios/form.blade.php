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
            <span class="input-group-text" id="municipio"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='municipio' class="form-control"
                placeholder="Nombre del municipio">
        </div>
        @error('municipio')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select wire:model.lazy='departamento' class="form-control">
            <option value="">Elegir Departamento...</option>
            @foreach ($departamentos as $departamento)
            <option value="{{ $departamento->id }}">{{ $departamento->departamento }}</option>
            @endforeach
        </select>
        @error('departamento')
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
