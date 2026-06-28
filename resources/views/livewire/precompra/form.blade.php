@include('common.modalHead')
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <div class="input-group input-group-merge">
            <span class="input-group-text" id="archivo"><i class='fa-solid fa-file-upload'></i></span>
            <input type="file" wire:model.lazy='archivo' class="form-control" accept=".json">
        </div>

        @error('archivo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-sm-12 col-md-12 mb-3">
        <label for="">Tipos de Json a procesar</label>
        <div class="input-group input-group-merge">
            <select class="form-select form-control" wire:model.lazy='tipo'>
                <option value="">Elegir.....</option>
                <option value="Ingreso">Json de Ingresos</option>
                <option value="Gasto">Json de Gastos</option>
            </select>
        </div>

        @error('tipo')
            <span class="text-danger er">{{ $message }}</span>
        @enderror
    </div>
</div>
@include('common.modalFooter')
