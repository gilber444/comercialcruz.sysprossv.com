<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="myModal">{{$componentName2}} | {{ $selected_id > 0 ? 'Editar' : 'Nuevo' }}</h5>

                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>

            </div>

            <div class="modal-body">

                <div class="row mb-3">

                    <div class="col-sm-12 col-md-3 mb-3">

                        <label for="">DUI</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="dui"><i class="fa-solid fa-address-card"></i></span>

                            <input type="text" wire:model.lazy='dui' class="form-control" placeholder="00000000-0">

                        </div>

                        @error('dui') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                    <div class="col-sm-12 col-md-9 mb-3">

                        <label for="">Nombre del Proveedor</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="nombreCliente"><i class="fa-solid fa-user"></i></span>

                            <input type="text" wire:model.lazy='nombreCliente' class="form-control" placeholder="Nombre del Cliente">

                        </div>

                        @error('nombreCliente') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-sm-12 col-md-3 mb-3">

                        <label for="">Telefono</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="telefono"><i class="fa-solid fa-phone"></i></span>

                            <input type="text" wire:model.lazy='telefono' class="form-control" placeholder="00000000">

                        </div>

                        @error('telefono') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                    <div class="col-sm-12 col-md-9 mb-3">

                        <label for="">Dirección</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="direccion"><i class="fa-solid fa-location-dot"></i></span>

                            <input type="text" wire:model.lazy='direccion' class="form-control" placeholder="Direccion del Cliente">

                        </div>

                        @error('dirrecion') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-sm-12 col-md-4 mb-3">

                        <label for="">NIT</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="nit"><i class="fa-solid fa-passport"></i></span>

                            <input type="text" wire:model.lazy='nit' class="form-control" placeholder="0000-000000-000-0">

                        </div>

                        @error('nit') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                    <div class="col-sm-12 col-md-4 mb-3">

                        <label for="">NCR</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="ncr"><i class="fa-solid fa-hashtag"></i></span>

                            <input type="text" wire:model.lazy='ncr' class="form-control" placeholder="">

                        </div>

                        @error('ncr') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                    <div class="col-sm-12 col-md-4 mb-3">

                        <label for="">Giro</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="giro"><i class="fa-solid fa-pen-to-square"></i></span>

                            <input type="text" wire:model.lazy='giro' class="form-control" placeholder="giro">

                        </div>

                        @error('giro') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-sm-12 col-md-6 mb-3">

                        <label for="">Identificacion</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="identificacion"><i class="fa-solid fa-user"></i></span>

                            <input type="text" wire:model.lazy='identificacion' class="form-control" placeholder="Pasaporte o Carnet de Residente">

                        </div>

                        @error('identificacion') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                    <div class="col-sm-12 col-md-6 mb-3">

                        <label for="">Numero</label>

                        <div class="input-group input-group-merge">

                            <span class="input-group-text" id="numero"><i class="fa-solid fa-hashtag"></i></span>

                            <input type="text" wire:model.lazy='numero' class="form-control" placeholder="000000000">

                        </div>

                        @error('numero') <span class="text-danger er">{{ $message}}</span>@enderror

                    </div>

                </div>



            </div>

            <div class="modal-footer">

                <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>

                <button type="button" wire:click.prevent="nuewCliente()" class="btn btn-primary"><i class='bx bxs-save' ></i> Guardar Datos</button>

            </div>

        </div>

    </div>

</div>

