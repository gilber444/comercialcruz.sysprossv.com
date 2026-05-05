@include('common.modalHead')
<div class="input-group input-group-merge">
    <span class="input-group-text" id="tipo"><i class='bx bx-edit'></i></span>
    <input type="text" wire:model.lazy='tipo' class="form-control" placeholder="Nombre de la Forma de Pago" wire:keydown.enter='Store()'>
</div>
@error('tipo') <span class="text-danger er">{{ $message}}</span>@enderror
@include('common.modalFooter')
