
@include('common.modalHead')

<div class="row">
    <div class="col-sm-12 col-md-6">
        <p class="">Nombre</p>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="fas fa-edit"></i></span>
            <input type="text" wire:model.lazy="name" class="form-control" placeholder="ej: Nombre del Usuario" autofocus>
        </div>
        @error('name') <span class="text-danger er">{{ $message }}</span> @enderror
    </div>
    <div class="col-sm-12 col-md-3">
        <p class="">Usuario</p>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="fas fa-edit"></i></span>
            <input type="text" wire:model.lazy="usuario" class="form-control" placeholder="ej: user" autofocus>
        </div>
        @error('usuario') <span class="text-danger er">{{ $message }}</span> @enderror
    </div>
    <div class="col-sm-12 col-md-3">
        <p class="">Telefono</p>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
            <input type="text" wire:model.lazy="phone" class="form-control" placeholder="ej: 00000000">
        </div>
        @error('phone') <span class="text-danger er">{{ $message }}</span> @enderror
    </div>
</div>
<div class="row mt-3">
    <div class="col-sm-6 col-md-6">
        <p class="">Email</p>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="text" wire:model.lazy="email" class="form-control" placeholder="ej: laravel@dmin.com">
        </div>
        @error('email') <span class="text-danger er">{{ $message }}</span> @enderror
    </div>
    <div class="col-sm-6 col-md-6">
        <div>
            <label for="password">Contraseña</label>
            <div class="input-group input-group-merger">
                <span class="input-group-text" id="password"><i class="fa-solid fa-key"></i></span>
                @if ($showPassword)
                    <input type="text" wire:model="password" id="password" class="form-control">
                @else
                    <input type="password" wire:model="password" id="password" class="form-control">
                @endif
            </div>
            @error('password') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
        <div>
            <input type="checkbox" wire:click="togglePasswordVisibility" class="form-check-input">
            <label for="show_password">Mostrar contraseña</label>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-sm-6 col-md-6">
        <div class="form-group">
            <label>Estatus</label>
            <select wire:model='status' class="form-control">
                <option value="Elegir" selected>Elegir</option>
                <option value="ACTIVE" selected>Activo</option>
                <option value="LOCKED" selected>Bloqueado</option>
            </select>
            @error('status') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-sm-6 col-md-6">
        <div class="form-group">
            <label>Asignar Rol</label>
            <select wire:model='profile' class="form-control">
                <option value="Elegir" selected>Elegir</option>
                @foreach ( $roles as $role )
                <option value="{{ $role->name}}">{{ $role->name}}</option>
                @endforeach
            </select>
            @error('profile') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-12">
        <div class="form-group">
            <label>Empresa</label>
            <select wire:model='empresa' class="form-control">
                <option value="Elegir" selected>Elegir</option>
                @foreach ( $empresas as $e )
                <option value="{{ $e->id}}">{{ $e->razon}}</option>
                @endforeach
            </select>
            @error('empresa') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-12">
        <div class="form-group">
            <label>Sucursal/Tienda</label>
            <select wire:model='sucursal' class="form-control">
                <option value="Elegir" selected>Elegir</option>
                @foreach ( $sucursales as $s )
                <option value="{{ $s->id}}">{{ $s->nombre}}</option>
                @endforeach
            </select>
            @error('sucursal') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-sm-12 mt-12">
        <p class="">Imagen del Perfil</p>
        <div class="form-group custom-file">
            <input type="file" class="form-control custom-file-input" wire:model="image" accept="image/x-png, image/x-gif, image/x-jpeg">
            <label class="custom-file-label">Imagen {{ $image }}</label>
            @error('image') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
@include('common.modalFooter')
