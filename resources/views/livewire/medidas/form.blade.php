@include('common.modalHead')
<div class="row">
    <div class="col">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="medida"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='medida' class="form-control" placeholder="Nombre de la Unidad de Medida">
            <br>

        </div>
        @error('medida') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
    <div class="col-4">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="simbolo"><i class='bx bx-edit'></i></span>
            <input type="text" wire:model.lazy='simbolo' class="form-control" placeholder="simbolo de la Unidad de Medida">
            <br>

        </div>
        @error('simbolo') <span class="text-danger er">{{ $message}}</span>@enderror
    </div>
</div>
@include('common.modalFooter')
