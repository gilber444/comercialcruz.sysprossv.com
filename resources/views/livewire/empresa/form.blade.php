@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-8 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="empresa"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='empresa' class="form-control" placeholder="Nombre de la empresa">
        </div>
        @error('empresa') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="telefono"><i class='fa-solid fa-phone'></i></span>
            <input type="text" wire:model.lazy='telefono' class="form-control" placeholder="Telefono">
        </div>
        @error('telefono') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="telefono"><i class='fa-solid fa-edit'></i></span>
            <input type="text" wire:model.lazy='razon' class="form-control" placeholder="Razon Social de la empresa">
        </div>
        @error('razon') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <textarea wire:model='direccion' cols="30" rows="3" class="form-control" placeholder="Direccion de la empresa"></textarea>
        </div>
        @error('direccion') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="nit"><i class="fa-solid fa-pen-to-square"></i></span>
            <input type="text" wire:model.lazy='nit' class="form-control" placeholder="NIT">
        </div>
        @error('nit') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="registro"><i class="fa-solid fa-pen-to-square"></i></span>
            <input type="text" wire:model.lazy='registro' class="form-control" placeholder="Regisrro">
        </div>
        @error('registro') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="responsable"><i class="fa-solid fa-pen-to-square"></i></span>
            <input type="text" wire:model.lazy='responsable' class="form-control" placeholder="Responsable">
        </div>
        @error('reposable') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="giro"><i class="fa-solid fa-pen-to-square"></i></span>
            <input type="text" wire:model.lazy='giro' class="form-control" placeholder="Giro">
        </div>
        @error('giro') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="tipoContribuyente"><i class="fa-solid fa-pen-to-square"></i></span>
            <input type="text" wire:model.lazy='tipoContribuyente' class="form-control" placeholder="Tipo de Contribuyente">
        </div>
        @error('tipoContribuyente') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="correo"><i class="fa-solid fa-envelope"></i></span>
            <input type="text" wire:model.lazy='correo' class="form-control" placeholder="Correo Electronico">
        </div>
        @error('correo') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-9 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="apiPassword"><i class="fa-solid fa-envelope"></i></span>
            <input type="password" wire:model.lazy='apiPassword' class="form-control" placeholder="Contraseña de Api de Hacienda">
        </div>
        @error('apiPassword') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="plan"><i class="fa-solid fa-hashtag"></i></span>
            <input type="text" wire:model.lazy='plan' class="form-control" placeholder="plan" @if (!$isSuper) readonly @endif>
        </div>
        @error('plan') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group" wire:ignore>
            <label class="input-group-text" for="inputGroupSelect01">Actividad Economica</label>
            <select class="select2 form-select form-select-lg text-uppercase" id="select2-Actiempresa" wire:model.lazy='actividad'>
                <option selected="">Elegir...</option>
                @foreach ($actividades as $actividad )
                <option value="{{$actividad->id}}">{{$actividad->codigo}} {{$actividad->valor}}</option>
                @endforeach
            </select>
          </div>
          <span>{{$actividadSelectName}}</span>
        @error('actividad') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <select class="form-select" wire:model.lazy='depto' wire:click='updateDepto' wire:change="updateDepto()">
            <option selected="">Elegir Departamento...</option>
            @foreach ($departamentos as $depto )
            <option value="{{$depto->id}}">{{$depto->departamento}}</option>
            @endforeach
        </select>
        @error('depto') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <select class="form-select" wire:model.lazy='muni' wire:click='updateMuni' wire:change="updateMuni()">
            <option selected="">Elegir Municipio...</option>
            @foreach ($municipios as $muni )
            <option value="{{$muni->id}}">{{$muni->municipio}}</option>
            @endforeach
        </select>
        @error('muni') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <select class="form-select" wire:model.lazy='distrito'>
            <option selected="">Elegir Distrito...</option>
            @foreach ($distritos as $dis )
            <option value="{{$dis->id}}">{{$dis->distrito}}</option>
            @endforeach
        </select>
        @error('distrito') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <select class="form-select" wire:model.lazy='ambiente'>
            <option selected="">Elegir Ambiente...</option>
            @foreach ($ambientes as $a )
            <option value="{{$a->id}}">{{$a->valor}}</option>
            @endforeach
        </select>
        @error('ambiente') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <input type="file" class="form-control custom-file-input" wire:model="image" accept="image/x-png, image/x-gif, image/x-jpeg">
        </div>
        <label class="custom-file-label">Imagen {{ $image }}</label>
        @error('image') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <input type="file" class="form-control custom-file-input" wire:model="certificado" accept=".crt">
        </div>
        <label class="custom-file-label">Subir Certificado (.crt) {{ $certificado }} </label>
        @error('certificado') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>

@include('common.modalFooter')
