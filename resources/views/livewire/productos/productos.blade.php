<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title">Admin Producto</h5>
        @if ($selected_id < 1)
        <button type="button" id="guardar" wire:click.prevent="Store()" wire:keydown.alt.s.away='Store()' class="btn btn-primary"><i class='bx bxs-save' ></i> F10 Guardar Datos</button>
        @else
        <button type="button" id='editar' wire:click.prevent="Update()" wire:keydown.alt.s.away='Update()' class="btn btn-primary"><i class='bx bx-revision'></i> Alt+s Actualizar Datos</button>
        @endif
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="defaultFormControlInput" class="form-label">Codigo Barra Producto</label>
                <input type="text" class="form-control" placeholder="000000000000" wire:model.lazy='codebar3'>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('codebar3')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="defaultFormControlInput" class="form-label">Codigo Barra Interno</label>
                <input type="text" class="form-control" placeholder="000000000000" wire:model.lazy='codealternativo'>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('codealternativo')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="defaultFormControlInput" class="form-label">Codigo Barra Paquete</label>
                <input type="text" class="form-control" placeholder="000000000000" wire:model.lazy='codebar2'>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('codebar2')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="defaultFormControlInput" class="form-label">Codigo Barra Caja</label>
                <input type="text" class="form-control" placeholder="000000000000" wire:model.lazy='codebar1'>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('codebar1')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-9 mb-3">
                <label for="">Nombre del Producto</label>
                <input type="text" wire:model.lazy='nombreProducto' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('nombreProducto')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="">Medida</label>
                <select class="form-control" wire:model.lazy='medida'>
                    <option value="">Elegir</option>
                    @foreach ($medidas as $medida )
                    <option value="{{$medida->id}}">{{$medida->unidad}}</option>
                    @endforeach
                </select>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('medida')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-4 mb-3">
                <label for="">Categoria</label>
                <select class="form-control" wire:model.lazy='categoria'>
                    <option value="">Elegir</option>
                    @foreach ($categorias as $cate )
                    <option value="{{$cate->id}}">{{$cate->categoria}}</option>
                    @endforeach
                </select>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('categoria')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-4 mb-3">
                <label for="">Familia</label>
                <select class="form-control" wire:model.lazy='familia'>
                    <option value="">Elegir</option>
                    @foreach ($familias as $fami )
                    <option value="{{$fami->id}}">{{$fami->familia}}</option>
                    @endforeach
                </select>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('familia')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-4 mb-3">
                <label for="">Unidad de Medidad MH</label>
                <select class="form-control" wire:model.lazy='medidaMH'>
                    <option value="">Elegir</option>
                    @foreach ($medidasMH as $medi )
                    <option value="{{$medi->id}}">{{$medi->valor}}</option>
                    @endforeach
                </select>
                <div id="defaultFormControlHelp" class="form-text">
                    @error('medidaMH')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-4 mb-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" wire:model.lazy='activo'>
                    <label class="form-check-label" for="flexSwitchCheckDefault">Activo</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" wire:model.lazy='exento'>
                    <label class="form-check-label" for="flexSwitchCheckDefault">Exento</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" wire:model.lazy='caja'>
                    <label class="form-check-label" for="flexSwitchCheckDefault">Ver en Caja</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" wire:model.lazy='fraccionario'>
                    <label class="form-check-label" for="flexSwitchCheckDefault">Fraccionario</label>
                </div>
            </div>
            <div class="col-sm-12 col-md-2 mb-3">
                <label for="">Contenedor</label>
                <input type="text" wire:model.lazy='contenedor' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('contenedor')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-2 mb-3">
                <label for="">Maximo</label>
                <input type="text" wire:model.lazy='maximo' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('maximo')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="">Minimo</label>
                <input type="text" wire:model.lazy='minimo' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('minimo')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            {{-- <div class="col-sm-12 col-md-8 mb-2">
                <input type="file" wire:model.lazy="imagenes" class="form-control" accept="image/png, image/gif, image/jpeg, image/jpg" multiple>
                <div class="d-flex align-items-center avatar-group">
                    @if (is_array($imagenes))
                        @foreach ($imagenes as $imagen)
                        <div class="avatar pull-up" data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="" data-bs-original-title="{{ $imagen->getClientOriginalName() }}">
                            <img src="{{ $imagen->temporaryUrl() }}" alt="{{ $imagen->getClientOriginalName() }}" class="rounded-circle" width="38" height="38">
                          </div>
                        @endforeach
                    @else
                        <p>Sin imágenes</p>
                    @endif
                </div>
                <label class="custom-file-label">Imagen {{ is_array($imagenes) ? implode(', ', $imagenes) : 'sin imágenes' }}</label>
            </div>--}}
            <!--
            <hr>
            <h5 class="card-title">Descuentos y ofertas</h5>
            <hr>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="">Inicio</label>
                <input type="date" wire:model.lazy='inicio' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('inicio')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="">Fin</label>
                <input type="date" wire:model.lazy='fin' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('fin')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-12 col-md-2 mb-3">
                <label for="">% descuento</label>
                <input type="text" wire:model.lazy='descuento' class="form-control">
                <div id="defaultFormControlHelp" class="form-text">
                    @error('descuento')
                        <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>
            </div> -->
        </div>
    </div>
</div>
