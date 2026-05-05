@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <label class="form-label">Nombre del Proveedor</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="nombre"><i class="fa-solid fa-users"></i></span>
            <input type="text" wire:model.lazy='nombre' class="form-control" placeholder="Nombre del Proveedor">
        </div>
        @error('nombre')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <label class="form-label">Tipo de Persona</label>
        <div class="input-group">
            <select class="form-select" wire:model='tipoPersona'>
                <option selected="">Elegir Tipo de Persona</option>
                @foreach ($personas as $p)
                    <option value="{{ $p->id }}">{{ $p->valor }}</option>
                @endforeach
            </select>
        </div>
        @error('tipoPersona')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <label class="form-label">Categoria de la Empresa</label>
        <div class="input-group">
            <select class="form-select" wire:model='categoria'>
                <option selected="">Elegir Categoriaa</option>
                <option value="GRANDE">GRANDE</option>
                <option value="MEDIANO">MEDIANO</option>
                <option value="PEQUEÑO">PEQUEÑO</option>
                <option value="OTRO">OTRO</option>
            </select>
        </div>
        @error('categoria')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <label class="form-label">Actividad Economica</label>
        <div class="input-group" wire:ignore>
            <select class="form-select select2" wire:model='actividad' id="ActiProveedor">
                <option selected="">Elegir Actividad Economica</option>
                @foreach ($actividades as $a)
                    <option value="{{ $a->id }}">{{ $a->valor }}</option>
                @endforeach
            </select>
        </div>
        <span>{{ $proveedorAddSelectName }}</span>
        @error('actividad')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Departamento</label>
        <div class="input-group">
            <select class="form-select" wire:model='departamento' wire:click='updateDepto' wire:change="updateDepto()">
                <option selected="">Elegir Departamento</option>
                @foreach ($departamentos as $d)
                    <option value="{{ $d->id }}">{{ $d->departamento }}</option>
                @endforeach
            </select>
        </div>
        @error('departamento')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Municipio</label>
        <div class="input-group">
            <select class="form-select" wire:model='municipio' wire:click='updateMuni' wire:change="updateMuni()">
                <option selected="">Elegir Municipio</option>
                @foreach ($municipios as $m)
                    <option value="{{ $m->id }}">{{ $m->municipio }}</option>
                @endforeach
            </select>
        </div>
        @error('municipio')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Distrito</label>
        <div class="input-group">
            <select class="form-select" wire:model='distrito'>
                <option selected="">Elegir Distrito</option>
                @foreach ($distritos as $d)
                    <option value="{{ $d->id }}">{{ $d->distrito }}</option>
                @endforeach
            </select>
        </div>
        @error('distrito')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <label class="form-label">Direccion</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="direccion"><i class="fa-solid fa-location-pin"></i></span>
            <input type="text" wire:model.lazy='direccion' class="form-control">
        </div>
        @error('direccion')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Telefono</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="telefono"><i class='fa-solid fa-phone'></i></span>
            <input type="text" wire:model.lazy='telefono' class="form-control" placeholder="00000000">
        </div>
        @error('telefono')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-8 mb-3">
        <label class="form-label">Correo</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="correo"><i class="fa-solid fa-envelope"></i></span>
            <input type="text" wire:model.lazy='correo' class="form-control" placeholder="prueba@gmail">
        </div>
        @error('correo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Registro</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="registro"><i class="fa-solid fa-hashtag"></i></span>
            <input type="text" wire:model.lazy='registro' class="form-control" placeholder="0000">
        </div>
        @error('registro')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-8 mb-3">
        <label class="form-label">NIT/DUI</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="nit"><i class="fa-solid fa-id-card"></i></span>
            <input type="text" wire:model.lazy='nit' class="form-control" placeholder="0000000000">
        </div>
        @error('nit')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <label class="form-label">Giro</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="giro"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='giro' class="form-control" placeholder="Giro">
        </div>
        @error('giro')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>

@include('common.modalFooter')
