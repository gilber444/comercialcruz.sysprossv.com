<div class="card-body border rounded p-3 mb-3">
    <div class="row">
        <div class="col-sm-12 col-md-3">
            <label for="">Fecha de Apertura</label>
            <div class="input-group input-group-merge">
                <span class="input-group-text" id="fechaApertura"><i class="fa-solid fa-calendar"></i></span>
                <input type="date" wire:model.lazy='fechaApertura' class="form-control" readonly>
            </div>
            @error('fechaApertura')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-sm-12 col-md-3">
            <label for="">Hora de Apertura</label>
            <div class="input-group input-group-merge">
                <span class="input-group-text" id="horaApertura"><i class="fa-solid fa-calendar"></i></span>
                <input type="timezone" wire:model.lazy='horaApertura' class="form-control" readonly>
            </div>
            @error('horaApertura')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-sm-12 col-md-3">
            <label for="">Monto Inicial de Apertura</label>
            <div class="input-group input-group-merge">
                <span class="input-group-text" id="montoApertura"><i class="fa-solid fa-dollar"></i></span>
                <input type="text" wire:model.lazy='montoApertura' wire:keydown.enter="Aperturar()" class="form-control" placeholder="0.00">
            </div>
            @error('montoApertura')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-sm-12 col-md-3 text-center">
            <label for="">&nbsp;</label>
            <a class="btn btn-primary" href="javascript:void(0);" wire:click="Aperturar()"><i class="fa-solid fa-save"></i> Aperturar Caja</a>
        </div>
    </div>
</div>
