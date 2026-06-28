<div class="row">
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Elegir Emisor</label>
        <select wire:model="emisor" class="form-select form-select-lg" data-allow-clear="true">
            <option value="">Elegir Emisor</option>
            @if(isset($emisores) && count($emisores) > 0)
                @foreach ($emisores as $e)
                    <option value="{{ $e->id }}">{{ $e->razon }}</option>
                @endforeach
            @endif
        </select>
        @error('emisor')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Elegir Cliente</label>
        <select wire:model="cliente" class="form-select form-select-lg" data-allow-clear="true">
            <option value="">Elegir Cliente</option>
            @if(isset($clientes) && count($clientes) > 0)
                @foreach ($clientes as $clienteItem)
                    <option value="{{ $clienteItem->id }}">{{ $clienteItem->nombreCliente }}</option>
                @endforeach 
            @endif   
        </select>
        @error('cliente') 
            <span class="text-danger er">{{ $message }}</span>
        @enderror 
    </div>
     <div class="col-sm-12 col-md-4 mb-3">
        <label class="form-label">Elegir Tipo</label>
        <select wire:model="tipo" class="form-select form-select-lg" data-allow-clear="true">
            <option value="">Elegir Tipo</option>
            <option value="1">13% IVA</option>
            <option value="2">10% Renta</option>
        </select>
        @error('tipo') 
            <span class="text-danger er">{{ $message }}</span>
        @enderror 
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <label class="form-label">Observaciones</label>
        <input type="text" wire:model="observaciones" class="form-control form-control-lg" placeholder="Observaciones...">
        @error('observaciones')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>