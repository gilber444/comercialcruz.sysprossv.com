<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">{{ $componentName }} | {{ $correlativo }}</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="cliente">Nombre del Cliente:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="codigo">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" id="proveedor" wire:model="cliente" readonly class="form-control">
                        </div>
                        @error('cliente')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <label for="proveedor">Saldo:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="saldo">
                                <i class="fa-solid fa-dollar"></i>
                            </span>
                            <input type="text" id="saldo" wire:model="saldo" readonly class="form-control">
                        </div>
                        @error('saldo')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <label for="proveedor">Monto a Cancelar:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="monto">
                                <i class="fa-solid fa-dollar"></i>
                            </span>
                            <input type="text" id="monto" wire:model="monto" class="form-control">
                        </div>
                        @error('monto')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="proveedor">Metodo de Pago</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="metodo">
                                <i class="fa-solid fa-credit-card"></i>
                            </span>
                            <select id="metodo" wire:model="metodo" class="form-control">
                                <option value="">Seleccione un método</option>
                                @foreach ($tipos as $t)
                                    <option value="{{ $t->id }}">{{ $t->forma }}</option>)

                                @endforeach
                            </select>
                        </div>
                        @error('metodo')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary"
                    data-bs-dismiss="modal">Cerrar</button>

                <button type="button" id="guardar" wire:click.prevent="Procesar()"
                    wire:keydown.alt.s.away='Procesar()' class="btn btn-primary"><i class='bx bxs-save'></i> Procesar
                    Pago</button>
            </div>
        </div>
    </div>
</div>
