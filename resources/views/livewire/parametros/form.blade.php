<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">{{ $componentName }} | {{ $selected_id > 0 ? 'Editar' : 'Nuevo' }}</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label for="">Empresa</label>
                        <select class="form-select" wire:model.lazy='empresa' wire:click='updateEmpresa' wire:change="updateEmpresa()">
                            <option selected="">Elegir Empresa...</option>
                            @foreach ($empresas as $em )
                            <option value="{{$em->id}}">{{$em->razon}}</option>
                            @endforeach
                        </select>
                        @error('empresa') <span class="text-danger er">{{ $message}}</span>@enderror
                    </div>
                    <div class="col-sm-12 col-md-3 mb-3">
                        <label for="name">Sucursal</label>
                        <select class="form-control" wire:model.lazy='sucursal'>
                            <option value="Elegir">Elegir</option>
                            @foreach ($sucursales as $su)
                                <option value="{{ $su->id }}">{{ $su->nombre }}</option>
                            @endforeach
                        </select>
                        @error('sucursal') <span class="text-danger er">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-sm-12 col-md-1 mb-3">
                        <label for="">No. Caja</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="cajas"><i class="fa-solid fa-cart-shopping"></i></span>
                            <input type="text" wire:model.lazy='caja' class="form-control" placeholder="00">
                        </div>
                        @error('caja') <span class="text-danger er">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-sm-12 col-md-1 mb-3">
                        <label for="">Token</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text" id="token"><i class="fa-solid fa-computer"></i></span>
                            <input type="text" wire:model.lazy='token' class="form-control">
                        </div>
                        @error('token') <span class="text-danger er">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-sm-12 col-md-2 mb-3">
                        <label for="">Formato de Impresión</label> <br>
                        <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="full" id="formato-full" value="Si" wire:model.lazy='full'>
                            <label class="form-check-label" for="formato-full">Full</label>
                        </div>
                        <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="slip" id="formato-slip" value="Si" wire:model.lazy='slip'>
                            <label class="form-check-label" for="formato-slip">Slip</label>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-2 mb">
                        <label for="">Sistema de Caja</label><br>
                        <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="sistema" id="sistema-multiple" value="Si" wire:model.lazy='multiple'>
                            <label class="form-check-label" for="sistema-multiple">Multiple</label>
                        </div>
                        <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="sistema" id="sistema-centralizada" value="Si" wire:model.lazy='centralizada'>
                            <label class="form-check-label" for="sistema-centralizada">Centralizada</label>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <div class="row">
                            <div class="col-md">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customCheckboxIcon1">
                                        <span class="custom-option-body">
                                            <i class="fa-solid fa-ticket"></i>
                                            <span class="custom-option-title"> Tiquet </span>
                                        </span>
                                        <input class="form-check-input" type="checkbox" wire:model.lazy='tiquet'>
                                        <input class="form-check-input" type="checkbox" wire:model.lazy='tiquedte' title="DTE">
                                        <input type="text" class="form-control" wire:model.lazy='tcorrelativo' value="0" placeholder="000">
                                        <input type="text" class="form-control" wire:model.lazy='tresolucion' placeholder="Resolución">
                                        <input type="text" class="form-control" wire:model.lazy='tserie' placeholder="Serie">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customCheckboxIcon2">
                                        <span class="custom-option-body">
                                            <i class="fa-solid fa-file-invoice"></i>
                                            <span class="custom-option-title"> Consumidor Final </span>
                                        </span>
                                        <input class="form-check-input" type="checkbox" value="Si" wire:model.lazy='consumidor'>
                                        <input type="text" class="form-control" wire:model.lazy='concorrelativo' value="0">
                                        <input type="text" class="form-control" wire:model.lazy='conresolucion' placeholder="Resolución">
                                        <input type="text" class="form-control" wire:model.lazy='conserie' placeholder="Serie">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customCheckboxIcon3">
                                        <span class="custom-option-body">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                            <span class="custom-option-title"> Crédito Fiscal </span>
                                        </span>
                                        <input class="form-check-input" type="checkbox" value="Si" wire:model.lazy='credito'>
                                        <input type="text" class="form-control" wire:model.lazy='crecorrelativo' value="0">
                                        <input type="text" class="form-control" wire:model.lazy='creresolucion' placeholder="Resolución">
                                        <input type="text" class="form-control" wire:model.lazy='creserie' placeholder="Serie">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customCheckboxIcon3">
                                        <span class="custom-option-body">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                            <span class="custom-option-title"> Nota de Crédito </span>
                                        </span>
                                        <input class="form-check-input" type="checkbox" value="Si" wire:model.lazy='notaCredito'>
                                        <input type="text" class="form-control" wire:model.lazy='ncCorrelativo' value="0">
                                        <input type="text" class="form-control" wire:model.lazy='ncResolucion' placeholder="Resolución">
                                        <input type="text" class="form-control" wire:model.lazy='ncSerie' placeholder="Serie">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customCheckboxIcon3">
                                        <span class="custom-option-body">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                            <span class="custom-option-title"> Nota de Débito </span>
                                        </span>
                                        <input class="form-check-input" type="checkbox" value="Si" wire:model.lazy='notaDebito'>
                                        <input type="text" class="form-control" wire:model.lazy='ndCorrelativo' value="0">
                                        <input type="text" class="form-control" wire:model.lazy='ndResolucion' placeholder="Resolución">
                                        <input type="text" class="form-control" wire:model.lazy='ndSerie' placeholder="Serie">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customCheckboxIcon3">
                                        <span class="custom-option-body">
                                            <i class="fa-solid fa-file-lines"></i>
                                            <span class="custom-option-title"> Cotización </span>
                                        </span>
                                        <input class="form-check-input" type="checkbox" value="Si" wire:model.lazy='cotizacion'>
                                        <input type="text" class="form-control" wire:model.lazy='cocorrelativo' value="0">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-4 mb-3">
                        <div class="card">
                            <h5 class="card-header">Parametros Percepción IVA</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <label for="">Tasa</label>
                                        <input type="text" class="form-control" wire:model.lazy='tasa'>
                                    </div>
                                    <div class="col">
                                        <label for="">Venta Mínima</label>
                                        <input type="text" class="form-control" wire:model.lazy='ventamin'>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <label for="">Envio</label>
                                        <input type="text" class="form-control" wire:model.lazy='envio'>
                                    </div>
                                    <div class="col">
                                        <label for="">Descuento</label>
                                        <input type="text" class="form-control" wire:model.lazy='descuento'>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <div class="input-group">
                            <label class="input-group-text" for="inputGroupSelect01">Estado</label>
                            <select class="form-select" id="inputGroupSelect01" wire:model.lazy='estado'>
                                <option selected="">Elegir...</option>
                                <option value="Activa">Activa</option>
                                <option value="Cerrada">Cerrada</option>
                            </select>
                        </div>
                        @error('estado') <span class="text-danger er">{{ $message }}</span> @enderror
                        <br>
                        <div class="input-group">
                            <label class="input-group-text" for="inputGroupSelect01">DTE</label>
                            <select class="form-select" id="inputGroupSelect01" wire:model.lazy='dte'>
                                <option selected="">Elegir...</option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        @error('dte') <span class="text-danger er">{{ $message }}</span> @enderror
                        <br>
                        <div class="input-group">
                            <label class="input-group-text" for="inputGroupSelect01">DTE Automatico</label>
                            <select class="form-select" id="inputGroupSelect01" wire:model.lazy='dteAutomatico'>
                                <option selected="">Elegir...</option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        @error('dteAutomatico') <span class="text-danger er">{{ $message }}</span> @enderror
                    </div>
                </div>
                @include('common.modalFooter')

