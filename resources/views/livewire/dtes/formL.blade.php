@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-4 mb-3">
       <span class="text-dark">DTE disponibles: {{ $disponibles}}</span>
    </div>
    <div class="col-sm-12 col-md-8 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="cant"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='cant' class="form-control" placeholder="Cantidad de DTE a enviar">
        </div>
        @error('cant') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
@include('common.modalFooter')
