<div wire:ignore.self class="modal fade" id="modalNewCliente" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal"> <i class="fas fa-plus"></i> Nuevo Cliente
                </h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label class="form-label">Nombre del Cliente</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="nombreCliente"><i class='fa-solid fa-user'></i></span>
                            <input type="text" wire:model.lazy='nombreCliente' class="form-control"
                                placeholder="Nombre del Cliente">
                        </div>
                        @error('nombreCliente')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label class="form-label">Tipo Persona</label>
                        <div class="input-group">
                            <select class="form-select" wire:model.lazy='tipoPersona'>
                                <option selected="">Elegir Tipo de Persona</option>
                                @foreach ($personas as $p)
                                    <option value="{{ $p->id }}">{{ $p->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('tipoPersona')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label class="form-label">Actividad Economica</label>
                        <div class="input-group" wire:ignore>
                            <select class="form-select select2" wire:model.lazy='actividadCliente' id='actividadCliente'>
                                <option selected="">Elegir Actividad Economica</option>
                                @foreach ($actividades as $a)
                                    <option value="{{ $a->id }}">{{ $a->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <span>{{ $clienteAddSelectName }}</span>
                        @error('clienteAddSelectId')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label class="form-label">Departamento</label>
                        <div class="input-group">
                            <select class="form-select" wire:model.lazy='departamento' wire:click='updateDepto'
                                wire:change="updateDepto()">
                                <option selected="">Elegir Departamento</option>
                                @foreach ($departamentos as $d)
                                    <option value="{{ $d->id }}">{{ $d->departamento }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('departamento')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label class="form-label">Municipio</label>
                        <div class="input-group">
                            <select class="form-select" wire:model.lazy='municipio' wire:click='updateMuni'
                                wire:change="updateMuni()">
                                <option selected="">Elegir Municipio</option>
                                @foreach ($municipios as $m)
                                    <option value="{{ $m->id }}">{{ $m->municipio }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('municipio')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label class="form-label">Distrito</label>
                        <div class="input-group">
                            <select class="form-select" wire:model.lazy='distrito'>
                                <option selected="">Elegir Distrito</option>
                                @foreach ($distritos as $d)
                                    <option value="{{ $d->id }}">{{ $d->distrito }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('distrito')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label class="form-label">DUI</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="dui"><i class="fa-solid fa-id-card"></i></span>
                            <input type="text" wire:model.lazy='dui' class="form-control" placeholder="000000000">
                        </div>
                        @error('dui')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label class="form-label">Homologado</label>
                        <div class="input-group">
                            <select wire:model.lazy='homologado' class="form-select" id="inputGroupSelect01">
                                <option value="">Elegir</option>
                                <option value="SI" selected="SI">SI</option>
                                <option value="NO">NO</option>
                            </select>
                        </div>
                        @error('homologado')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label class="form-label">NIT</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="nit"><i class="fa-solid fa-id-card"></i></span>
                            <input type="text" wire:model.lazy='nit' class="form-control"
                                placeholder="00000000000000">
                        </div>
                        @error('nit')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label class="form-label">Registro</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="registro"><i class='fa-solid fa-hashtag'></i></span>
                            <input type="text" wire:model.lazy='registro' class="form-control" placeholder="000">
                        </div>
                        @error('registro')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label class="form-label">Giro</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="giro"><i class='bx bx-edit'></i></span>
                            <input type="text" wire:model.lazy='giro' class="form-control">
                        </div>
                        @error('giro')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class='row'>
                    <div class="col-sm-12 col-md-2 mb-3">
                        <label class="form-label">Identificacion Receptor</label>
                        <div class="input-group input-group-merge">
                            <select class='form-control' wire:model.lazy='idenReceptor'>
                                <option value="">Elegir tipo de Identificacion</option>
                                @foreach ($idenreceptors as $iden)
                                    <option value='{{ $iden->id }}'>{{ $iden->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('idenReceptor')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label class="form-label">Telefono</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="telefono"><i class='fa-solid fa-phone'></i></span>
                            <input type="text" wire:model.lazy='telefono' class="form-control"
                                placeholder="00000000">
                        </div>
                        @error('telefono')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label class="form-label">Celular</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="telefono"><i class='fa-solid fa-mobile'></i></span>
                            <input type="text" wire:model.lazy='celular' class="form-control"
                                placeholder="00000000">
                        </div>
                        @error('celular')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label class="form-label">Correo</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="telefono"><i class='fa-solid fa-envelope'></i></span>
                            <input type="text" wire:model.lazy='correo' class="form-control"
                                placeholder="cliente@comercialidalia.com">
                        </div>
                        @error('cliente')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label class="form-label">Direccion</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="direccion"><i class='fa-solid fa-location'></i></span>
                            <input type="text" wire:model.lazy='direccion' class="form-control">
                        </div>
                        @error('direccion')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" wire:click.prevent='resetUICliente()' class="btn btn-label-secondary"
                    data-bs-dismiss="modal">Cerrar</button>

                <button type="button" id='editar' wire:click.prevent="SaveClientes()" class="btn btn-primary"><i
                        class='bx bx-revision'></i> Guardar Datos</button>
            </div>
        </div>
    </div>
</div>
