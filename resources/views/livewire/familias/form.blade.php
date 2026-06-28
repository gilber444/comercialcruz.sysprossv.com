@include('common.modalHead')
<div class="input-group input-group-merge">
    <span class="input-group-text" id="familia"><i class='bx bx-edit'></i></span>
    <input type="text" wire:model.lazy='familia' class="form-control" placeholder="Nombre de la Familia">
</div>
@error('familia') <span class="text-danger er">{{ $message}}</span>@enderror
@include('common.modalFooter')
