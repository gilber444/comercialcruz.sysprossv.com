<div class="row">
    <div class="col-sm-12 col-md-6 mb-2">
        <label for="">Proveedor o Empresa</label>
        <div class="input-group" wire:ignore>
            <select class="form-select select2 w-50" id="proveedor" wire:model='proveedorCompras' style="width: 100%" wire:change='CalcularPercicionProveedor'>
                <option selected="">Elegir un Proveedor para esta factura... </option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">{{$proveedor->nombre }}</option>
                @endforeach
            </select>
            @can('Proveedor_Compra')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#ProveedorCompra"> <i class="fa-solid fa-plus"></i></a>
            @endcan
        </div>
        @error('proveedorCompras') <span class="text-danger er">{{ $message}}</span>@enderror
        @error('proveedorSelectId') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Tipo de Factura</label>
        <div class="input-group">
            <select class="form-select" wire:model='factura'">
                <option selected="">Elegir...</option>
                @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}">{{$tipo->tipo }}</option>
                @endforeach
            </select>
        </div>
        @error('factura') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Numero</label>
        <div class="input-group">
            <span class="input-group-text" id="basic-addon11"><i class="fa-solid fa-hashtag"></i></span>
            <input type="text" wire:model.lazy='correlativo' class="form-control">
        </div>
        @error('correlativo') <span class="text-danger er"> {{$message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Serie</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="fa-solid fa-hashtag"></i>
            </span>
            <input type="text" class="form-control" wire:model.lazy='serie'>
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
            <input id="fechaInput" type="date" class="form-control" placeholder="0000" wire:model='fecha'>
        </div>
        @error('fecha') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-2">
        <label for="">Condicion de Pago</label>
        <div class="input-group">
            <select class="form-control" wire:model.lazy='condiPago'>
                <option value="Elegir">Elegir</option>
                <option value="Credito">Credito a 30 dias</option>
                <option value="Contado">Contado</option>
            </select>
        </div>
        @error('condiPago') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-2">
        <label for="">Nombre del Vendedor</label>
        <div class="input-group">
            <input id="vendedor" type="text" class="form-control" placeholder="Nombre del vendedor" wire:model='vendedor'>
        </div>
        @error('vendedor') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-3 mb-2">
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
