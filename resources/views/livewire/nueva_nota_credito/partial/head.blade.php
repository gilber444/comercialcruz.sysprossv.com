<div class="row">
    <div class="col-sm-12 col-md-8 mb-2">
        <label for="">Credito Fiscal</label>
        <div class="input-group">
            <select class="form-select" wire:model='fiscal' wire:change="CargarTempNotas()">
                <option selected="">Elegir Credito Fiscal...</option>
                @if ($creditosFiscales)
                    @foreach ($creditosFiscales as $c)
                        <option value="{{ $c->id }}">Numero: {{$c->numero }}, Fecha: {{$c->fecha }} $ {{ $c->total}}</option>
                    @endforeach
                @endif
            </select>
        </div>
        @error('fiscal') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Correlativo</label>
        <div class="input-group">
            <span class="input-group-text" id="basic-addon11"><i class="fa-solid fa-hashtag"></i></span>
            <input type="text" wire:model.lazy='correlativo' class="form-control" readonly>
        </div>
        @error('correlativo') <span class="text-danger er">{{$message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-2 mb-2">
        <label for="">Fecha</label>
        <div class="input-group">
            <input id="fechaInput" type="datetime-local" class="form-control" placeholder="0000" wire:model='fecha'>
        </div>
        @error('fecha') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-4 mb-2">
        <label for="">Total Venta:</label>
        <div class="input-group">
            <span class="input-group-text" id="basic-addon11"><i class="fa-solid fa-dollar-sign"></i></span>
            <input type="text" wire:model.lazy='totalVenta' class="form-control" readonly>
        </div>
        @error('totalVenta') <span class="text-danger er">{{$message}}</span>@enderror
    </div>
</div>
{{-- <div class="row">
    <div class="col-sm-12 col-md-12 mb-2" wire:ignore>
        <label for="">Detalle</label>
        <div class="form-group">
            <input type="text" wire:model='detalle' placeholder="Detalle de la nota de credito" class="form-control">
        </div>
        @error('detalle') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div> --}}
