@include('common.modalHead')
<div class="input-group input-group-merge">
    <span class="input-group-text" id="roleName"><i class='bx bx-edit'></i></span>
    <input type="text" wire:model.lazy='roleName' class="form-control" placeholder="Nombre del rol" wire:keydown.alt.s.away="{{ $selected_id > 0 ? 'Update()' : 'Store()' }}">
</div>
@error('roleName') <span class="text-danger er">{{ $message}}</span>@enderror
@include('common.modalFooter')
