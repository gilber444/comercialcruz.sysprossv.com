<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="row">
            <div class="col-sm-12 col-md-4">
                <label for="">DUI</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fa-solid fa-hashtag"></i>
                    </span>
                    <input type="text" class="form-control" wire:model.lazy='duiC'
                        wire:keydown.enter='SearchClientDui()'>
                </div>
                <input type="hidden" class="form-control" wire:model.lazy='idC'>
                @error('idC')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-12 col-md-8">
                <label for="">Nombre del Cliente</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" class="form-control" wire:model.lazy='nombreC'
                        wire:keydown.enter='SearchClientName()'>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <label for="">Dirección:</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fa-solid fa-location"></i>
                    </span>
                    <input type="text" class="form-control" wire:model.lazy='direccionC' readonly>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <label for="">Telefono:</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fa-solid fa-mobile"></i>
                    </span>
                    <input type="text" class="form-control" wire:model.lazy='telefonoC' readonly>
                </div>
            </div>
            <div class="col-sm-12 col-md-6 mb-3">
                <label for="">Correo Electronico:</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="text" class="form-control" wire:model.lazy='correoC' readonly>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-3 mb-2">
        <label for="">Fecha Cotización</label>
        <div class="input-group">
            <input id="fechaInput" type="date" class="form-control" placeholder="0000" wire:model='fechaC'>
        </div>
        @error('fecha')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-3">
        <label for="">Tipo de Factura</label>
        <div class="input-group input-group-merge">
            <select wire:model='facturador' class='form-control'>
                <option value="Elegir">Elegir</option>
                @foreach ($facturadores as $f)
                    <option value="{{ $f->id }}">{{ $f->facturador }}</option>
                @endforeach
            </select>
        </div>
        @error('facturador')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <label class="form-label">Detalles adicionales</label>
        <div class="input-group input-group-merge">
            <textarea class="form-control" wire:model.lazy='detalles' name="detalle" id="detalle" cols="30" rows="1"></textarea>
        </div>
        @error('detalle')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>
