<div wire:ignore.self class="modal fade" id="myModalCon" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalCon">{{ $componentName }} |
                    {{ $selected_id > 0 ? 'Editar' : 'Nuevo' }}
                </h5>
                <button type="button" id="guardar" wire:click.prevent="Store()" wire:keydown.alt.s.away='Store()' class="btn btn-primary"><i class='bx bxs-save'></i>Realizar Contingencia</button>
                <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-4 mb-3" wire:ignore.self>
                        <label for="">Tipo de Contingencia</label>
                        <select class="form-control" wire:model.lazy='tipo' >
                            <option value="">Elegir</option>
                            @foreach ($tipos as $t)
                                <option value="{{ $t->id }}">{{ $t->valor }}</option>
                            @endforeach
                        </select>
                        @error('tipo')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-8 mb-3">
                        <label for="">Otro Motivo</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="cant"><i class='bx bx-edit'></i></span>
                            <input type="text" wire:model.lazy='otro' class="form-control"
                                placeholder="Digitar en caso que  haya otro motivo para la contingencia">
                        </div>
                        @error('otro')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label for="">F. I. Contingencia</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="cant"><i class='bx bx-edit'></i></span>
                            <input type="date" wire:model.lazy='fInicio' class="form-control">
                        </div>
                        @error('fInicio')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label for="">H. I. Contingencia</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="cant"><i class='bx bx-edit'></i></span>
                            <input type="time" wire:model.lazy='hInicio' class="form-control">
                        </div>
                        @error('hInicio')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label for="">Fecha Fin Contingencia</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="cant"><i class='bx bx-edit'></i></span>
                            <input type="date" wire:model.lazy='fFin' class="form-control">
                        </div>
                        @error('fFin')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label for="">Hora Fin Contingencia</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="cant"><i class='bx bx-edit'></i></span>
                            <input type="time" wire:model.lazy='hFin' class="form-control">
                        </div>
                        @error('hFin')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <hr>
                <h6 class="text-center">DTE a Reportar en Contingencia</h6>
                <hr>
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <table class="table table-responsive table-hover table-sm">
                            <thead>
                                <th class="text-center">
                                    <input type="checkbox" wire:model="selectAll" id="select-all" class='form-check-input'>
                                    <label for="select-all">Todos</label>
                                </th>
                                <th class="text-center">Numero Control</th>
                                <th class="text-center">Codigo Generacion</th>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Estado</th>
                            </thead>
                            <tbody>
                                @if ($dtes->count() > 0)
                                    @foreach ($dtes as $d)
                                        <tr>
                                            <td wire:ignore.self>
                                                <input type="checkbox" wire:model="selectedDtes" class='form-check-input' value="{{ $d->id }}">
                                            </td>
                                            <td>{{ $d->numeroControl }}</td>
                                            <td>{{ $d->codigoGeneracion }}</td>
                                            <td>{{ $d->fecEmi }}</td>
                                            <td>{{ $d->estado }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center">No hay DTE para procesar en contingencia
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @error('selectedDtes')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
