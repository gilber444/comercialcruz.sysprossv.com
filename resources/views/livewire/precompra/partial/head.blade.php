<div class="row">
    <div class="col-sm-12 col-md-5 mb-2">
        <label for="">Proveedor o Empresa</label>
        <input type="text" readonly class="form-control" wire:model.lazy='proveedor'>
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Telefono</label>
        <input type="text" readonly class="form-control" wire:model.lazy='Telproveedor'>
    </div>
    <div class="col-sm-12 col-md-5 mb-2">
        <label for="">Direccion</label>
        <input type="text" readonly class="form-control" wire:model.lazy='Dirproveedor'>
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Tipo de Factura</label>
        <div class="input-group">
            <input type="hidden" readonly class="form-control" wire:model.lazy='factura'>
            <input type="text" readonly class="form-control" wire:model.lazy='tipos'>
        </div>
        @error('factura') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-5 mb-2">
        <label for="">Numero de Control</label>
        <div class="input-group">
            <span class="input-group-text" id="basic-addon11"><i class="fa-solid fa-hashtag"></i></span>
            <input type="text" wire:model.lazy='correlativo' class="form-control" readonly>
        </div>
        @error('correlativo') <span class="text-danger er"> {{$message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-5 mb-2">
        <label for="">Codigo de Generacion</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="fa-solid fa-hashtag"></i>
            </span>
            <input type="text" class="form-control" wire:model.lazy='serie' readonly>
        </div>
        @error('serie')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-3 mb-2">
        <label for="">Fecha</label>
        <div class="input-group">
            <input id="fechaInput" type="datetime-local" class="form-control" placeholder="0000" wire:model='fecha' readonly>
        </div>
        @error('fecha') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-2">
        <label for="">Condicion de Pago</label>
        <div class="input-group">
            <select class="form-control" wire:model.lazy='condiPago'>
                <option value="Elegir">Elegir</option>
                <option value="Credito">Credito</option>
                <option value="Contado">Contado</option>
            </select>
        </div>
        @error('condiPago') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-6 mb-2">
        <label for="">Ubicacion de la Compra</label>
        <div class="input-group" style="width: 100%">
            <select class="form-control" wire:model='sucursal' style="width: 100%">
                <option value="">Elegir</option>
                @foreach ($sucursales as  $s)
                    <option value="{{ $s->id }}">{{ $s->Rempresa->empresa }} - {{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>
        @error('sucursal') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
