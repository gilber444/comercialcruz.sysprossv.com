<div class="card">
    <div class="card-header">
        <div class="row">

            {{-- Selector de Sucursal --}}
            <div class="col-sm-12 col-md-5 mb-3">
                <label class="form-label">Elegir Sucursal</label>
                <input type="text" class="form-control" wire:model="sucursalNombre" disabled>
                <input type="hidden" class="form-control" wire:model="sucursal">
                @error('sucursal')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>

            {{-- Fecha Inventario --}}
            <div class="col-sm-12 col-md-3 mb-3">
                <label class="form-label">Fecha Inventario</label>
                <input id="fechaInput" type="date" class="form-control" wire:model="fecha">

                @error('fecha')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>

            <div class="col-sm-12 col-md-2 mb-3">
                <label class="form-label">Correlativo</label>
                <input id="correlativo" type="text" class="form-control" wire:model="correlativo"
                    placeholder="1, 1-10">

                @error('correlativo')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="col-md-2 mb-3 d-grid">
                <a href="{{ route('hoja_inventarios') }}" class="btn btn-danger btn-rounded mb-2">
                    Regresar
                </a>

                <button type="button" class="btn btn-primary btn-rounded mb-2" wire:click="preValidar"
                    data-bs-toggle="modal" data-bs-target="#validarModal">
                    Validar datos
                </button>
            </div>

        </div>
    </div>

    @include('livewire.hoja_inventarios.validar')
</div>

@include('common.notis')
