<div class="card text-center" wire:keydown.window.f10="Update">
    <div class="card-header">
        <ul class="nav nav-pills card-header-pills" role="tablist">
            <li class="nav-item">
                <button type="button" class="nav-link {{ $activeTab == 'navs-pills-within-card-producto' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                    data-bs-target="#navs-pills-within-card-producto" aria-controls="navs-pills-within-card-producto"
                    aria-selected="true" wire:click="setActiveTab('navs-pills-within-card-producto')">
                    <i class="fa-brands fa-product-hunt"></i> Información del Producto
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link {{ $activeTab == 'navs-pills-within-card-precios' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                    data-bs-target="#navs-pills-within-card-precios" aria-controls="navs-pills-within-card-precios"
                    aria-selected="false" wire:click="setActiveTab('navs-pills-within-card-precios')">
                    <i class="fa-solid fa-barcode"></i> Admin de Precios
                </button>
            </li>
            <li class="nav-item">
                <button ype="button" class="nav-link {{ $activeTab == 'navs-pills-within-card-descuento' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                    data-bs-target="#navs-pills-within-card-descuento"
                    aria-controls="navs-pills-within-card-descuento" aria-selected="false" wire:click="setActiveTab('navs-pills-within-card-descuento')">
                    <i class="fa-solid fa-percent"></i> Admin de Descuentos
                </button>
            </li>
            <li class="nav-tiem">
                <button type="button" id="editar"
                        wire:click.prevent="Update()"
                        wire:keydown.f10="Update"
                        class="btn btn-primary">
                    <i class='bx bx-revision'></i> F10 Actualizar Datos
                </button>

                <a class="btn btn-info"
   href="{{ route('productoAdmin', [
       'search' => $search,
       'page' => $page
   ]) }}">
   <i class="fa-solid fa-arrow-up"></i> Volver
</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content p-0">
            <div class="tab-pane fade {{ $activeTab == 'navs-pills-within-card-producto' ? 'show active' : '' }}" id="navs-pills-within-card-producto" role="tabpanel">
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
                            @foreach ($medidas as $medida)
                                <option value="{{ $medida->id }}">{{ $medida->unidad }}</option>
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
                            @foreach ($categorias as $cate)
                                <option value="{{ $cate->id }}">{{ $cate->categoria }}</option>
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
                            @foreach ($familias as $fami)
                                <option value="{{ $fami->id }}">{{ $fami->familia }}</option>
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
                            @foreach ($medidasMH as $medi)
                                <option value="{{ $medi->id }}">{{ $medi->valor }}</option>
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
                </div>
                <hr>
                <h4 class="card-title">Administración de Precios</h4>
                @include('livewire.productos.precios')
            </div>
            {{--<div class="tab-pane fade {{ $activeTab == 'navs-pills-within-card-precios' ? 'show active' : '' }}" id="navs-pills-within-card-precios" role="tabpanel">
                <h4 class="card-title">Administración de Precios</h4>
                @include('livewire.productos.precios')
            </div>--}}
            <div class="tab-pane fade {{ $activeTab == 'navs-pills-within-card-descuento' ? 'show active' : '' }}" id="navs-pills-within-card-descuento" role="tabpanel">
                <h4 class="card-title">Administración de Descuentos</h4>
                @include('livewire.productos.descuentos')
            </div>
        </div>
    </div>
</div>
@include('common.notis')
<script>
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            Livewire.emit('volverAListaConSeleccion');
        }
    });
</script>
