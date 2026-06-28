<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal"> <i class="fas fa-plus"></i> Invalidaciones DTE
                </h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-2 col-md-2 mb-3">
                        <label class="form-label">Numero</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="numero"><i class='fa-solid fa-hashtag'></i></span>
                            <input type="text" wire:model.lazy='numero' class="form-control" readonly>
                        </div>
                        @error('nombreCliente')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label class="form-label">Tipo Invalidación</label>
                        <div class="input-group" wire:ignore>
                            <select class="form-select" wire:model='tipo'>
                                <option value="">Elegir</option>
                                <option value="Anulacion">Anulacion</option>
                                <option value="Devolucion">Devolucion</option>
                            </select>
                        </div>
                        @error('tipo')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <label class="form-label">Motivo de Invalidación</label>
                        <div class="input-group" wire:ignore>
                            <select class="form-select" wire:model='motivo'>
                                <option value="">Elegir</option>
                                @foreach ($motivos as $motivo)
                                    <option value="{{ $motivo->id }}">{{ $motivo->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('tipo')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label class="form-label">Otro Movito</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="desmotivo"><i class='fa-solid fa-edit'></i></span>
                            <input type="text" wire:model.lazy='desmotivo' class="form-control"
                                placeholder="Detalle otro motivo de invalidación">
                        </div>
                        @error('desmotivo')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-6 mb-3">
                        <label class="form-label">Código de Generación a Invalidar</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="dteinva"><i class='fa-solid fa-file-code'></i></span>
                            <input type="text" wire:model.lazy='dteinva' class="form-control"
                                placeholder="Código de Generación" wire:keydown.enter="cargarDatos">
                        </div>
                        <input type="hidden" class="form-control" wire:model='dteid'>
                        @error('dteinva')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                        @error('dteid')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <label class="form-label">Codigo de Generación a Reemplazar</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="dterem"><i class='fa-solid fa-file-code'></i></span>
                            <input type="text" class="form-control" placeholder="Código de Generación"
                                wire:model.lazy='dterem'>
                        </div>
                        @error('dterem')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                @if ($selected_id > 0)
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <span>Estado: {{ $estado }}</span>
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <span>Detalle: {{ $detalle }}</span>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" wire:click.prevent='ResetUI()' class="btn btn-label-secondary"
                    data-bs-dismiss="modal">Cerrar</button>

                <button type="button" id='editar' wire:click.prevent="Save()" class="btn btn-primary"><i class='bx bx-revision'></i> Relizar Invalidación</button>
                
            </div>
        </div>
    </div>
</div>
