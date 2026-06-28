<div class="row">
    <div class="col-sm-12 col-md-6 mb-1">
        <label class="form-label">Sucursal</label>

        {{--  @if (auth()->user()->profile == 'Super' || auth()->user()->profile == 'Administrador') --}}
        <select wire:model='sucursal' class="form-select from-control-sm" wire:change='cargaSucursal'>
            <option value="Elegir">Elegir Sucursal</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
            @endforeach
        </select>
        {{--  --
        @else
            <input type="text" class="form-control" value="{{ $sucursales->first()->nombre }}" readonly>
        @endif --}}

        @error('sucursal')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-sm-12 col-md-3 mb-1">
        <label class="form-label">Tipo Ajuste</label>
        <select wire:model='tipo' class="form-select from-control-sm" data-allow-clear="true">
            <option value="">Elegir...</option>
            @can('IngresoComercial_Index')
                <option value="Ingreso Fac. Comercial">Ingreso Fac. Comercial</option>
                <option value="Ingreso por Traslado">Ingreso por Traslado</option>
                @can('EgresosAjustes_Config')
                    <option value="Gastos">Gastos</option>
                    <option value="Producto Dañado">Producto Dañado</option>
                    <option value="Producto Vencido">Producto Vencido</option>
                @endcan

            @endcan
            @if (!auth()->user()->hasRole('Cajeros'))
                <option value="Ingreso">Ingreso</option>
                <option value="Egreso">Egreso</option>
            @endif
        </select>
        @error('tipo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-1">
        <label for="">Fecha Ajuste</label>
        <div class="input-group">
            <input id="fechaInput" type="date" class="form-control from-control-sm" placeholder="0000"
                wire:model='fecha'>
        </div>
        @error('fecha')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-1">
        <label class="form-label">Detalle</label>
        <div class="input-group input-group-merge">
            <textarea class="form-control from-control-sm" wire:model.lazy='detalle' name="detalle" id="detalle" cols="30"
                rows="1"></textarea>
        </div>
        @error('detalle')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>

</div>
