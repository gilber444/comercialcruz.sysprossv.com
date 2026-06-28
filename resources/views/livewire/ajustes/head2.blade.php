<div class="row">
    <div class="col-sm-12 col-md-6 mb-3">
        <label class="form-label">Elegir Sucursal</label>
        <select wire:model='sucursal' class="form-select"
            data-allow-clear="true" wire:change='cargaSucursal'>
            <option value="Elegir">Elegir Sucursal</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
            @endforeach
        </select>
        @error('sucursal')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-3">
        <label class="form-label">Tipo Ajuste</label>
        <select wire:model='tipo' class="form-select"
            data-allow-clear="true">
            <option value="">Elegir...</option>
            <option value="Ingreso">Ingreso</option>
            @if(!auth()->user()->hasRole('Cajeros'))
                <option value="Egreso">Egreso</option>
            @endif
        </select>
        @error('tipo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-2">
        <label for="">Fecha Ajuste</label>
        <div class="input-group">
            <input id="fechaInput" type="date" class="form-control" placeholder="0000" wire:model='fecha'>
        </div>
        @error('fecha') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <label class="form-label">Detalle</label>
        <div class="input-group input-group-merge">
            <textarea class="form-control"  wire:model.lazy='detalle' name="detalle" id="detalle" cols="30" rows="3"></textarea>
        </div>
        @error('detalle')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>

</div>
