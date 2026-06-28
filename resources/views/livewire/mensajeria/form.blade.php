@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="lema"><i class='fa-solid fa-hashtag'></i></span>
            <input type="text" wire:model.lazy='lema' class="form-control" placeholder="lema">
        </div>
        @error('lema') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="mensaje"><i class='fa-solid fa-edit'></i></span>
            <input type="text" wire:model.lazy='mensaje' class="form-control" placeholder="mensaje">
        </div>
        @error('mensaje') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="aviso"><i class="fa-solid fa-hashtag"></i></span>
            <input type="text" wire:model.lazy='aviso' class="form-control" placeholder="aviso">
        </div>
        @error('aviso') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <input type="text" wire:model.lazy='notificacion' class="form-control" placeholder="notificacion">
        @error('notificacion') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="input-group">
        <label class="input-group-text" for="inputGroupSelect01">Empresa</label>
        <select class="form-select" id="inputGroupSelect01" wire:model.lazy='empresa'>
            <option selected="">Elegir...</option>
            @foreach ($empresas as $empresa )
            <option value="{{$empresa->id}}">{{$empresa->empresa}}</option>
            @endforeach
        </select>
      </div>
</div>
@include('common.modalFooter')
