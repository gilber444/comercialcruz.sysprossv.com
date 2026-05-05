<div wire:ignore.self class="modal fade" @if ($itemsQuantity > 0) id="modalSaveCFiscal" @endif tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Metodo de pago | Crédito Fiscal</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-3">
                        <label for="">NRC</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='nrcC' wire:keydown.enter='SearchClientNrc()'>
                        </div>
                        <input type="hidden" class="form-control" wire:model.lazy='idC'>
                        @error('idC')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label for="">DUI / NIT</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='duiC' wire:keydown.enter='SearchClientDui()'>
                        </div>
                        @if ($tipoC == 2)
                            <span class="text-danger">No se puede Crear Factura de Credito Fiscal a este Cliente porque no Tiene numero de Registro o NRC</span>
                        @endif
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <label for="">Nombre del Cliente</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='nombreC' wire:keydown.enter='SearchClientName()'>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12">
                        <label for="">Dirección:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-location"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='direccionC' readonly>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12">
                        <label for="">Actividad Ecomomica:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-file"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='ActividadC' readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <label for="">Telefono:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-mobile"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='telefonoC' readonly>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 mb-3">
                        <label for="">Correo Electronico:</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='correoC' readonly>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-6">
                        {{--<div class="row">
                            <div class="col-sm-12 col-md-12 mb-3">
                                <label for="">Fecha</label>
                                <div class="input-group input-group-merge">
                                    <input type="date" wire:model.lazy='fecha' class="form-control">
                                </div>
                                @error('fecha') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-12 mb-3">
                                <label for=""># Correlativo</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-hashtag"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='correlativo' placeholder="0000" class="form-control">
                                </div>
                                @error('correlativo') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                        </div>--}}
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="row mb-3">
                            <div class="col-sm-12 col-md-12 mb-3">
                                <label for="">Metodo</label>
                                <div class="input-group input-group-merge">
                                    <select wire:model.lazy='metodo' class='form-control'>
                                        <option value="Elegir">Elegir</option>
                                        @foreach ($formas as $forma)
                                        <option value="{{$forma->id}}">{{$forma->forma}}</option>

                                        @endforeach
                                    </select>
                                </div>
                                @error('metodo') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <div class="col-sm-12 col-md-12 mb-3">
                                <label for=""># Comprobante</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-hashtag"></i>
                                    </span>
                                    <input type="text" wire:model.lazy='comprobante' id="modal-search-input" placeholder="0000" class="form-control">
                                </div>
                                @error('comprobante') <span class="text-danger er">{{ $message}}</span>@enderror
                            </div>
                            <dl class="row mb-0">
                                <dt class="col-sm-6 fw-normal">Total</dt>
                                <dd class="col-sm-6 text-success text-end">${{ number_format($total, 2)}}</dd>

                                <dt class="col-sm-6 fw-normal">Efectivo</dt>
                                <dd class="col-sm-6 text-end"><input type="text" wire:model.lazy="efectivo" wire:blur="Cash()" wire:keydown.enter="SaveFiscal" class="form-control text-end"> </dd>
                                <dt class="col-sm-6 fw-normal">Cambio</dt>
                                <dd class="col-sm-6 text-danger text-end">{{ number_format($cambio,2)}}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="SaveFiscal()" id="guardar" class="btn btn-primary @if ($tipoC <> 1) disabled @endif">
                    <i class='bx bxs-save'></i> Efectuar
                </button>
                <div wire:loading wire:target="SaveFiscal">Guardando la venta...</div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById('modalSaveCFiscal');

            if (modal && modal.classList.contains('show')) { // Verificar si el modal está abierto
                let focusableElements = modal.querySelectorAll('input, select, textarea');
                let focusable = Array.from(focusableElements).filter(el => !el.disabled && !el.readOnly);
                let currentIndex = focusable.findIndex(el => el === document.activeElement);

                // Permitir navegación normal dentro de los input de texto
                if (document.activeElement.tagName === "INPUT" && document.activeElement.type === "text") {
                    if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
                        return; // Permitir el movimiento del cursor dentro del input
                    }
                }

                // Flecha ABAJO ⬇️ (Moverse al siguiente campo)
                if (event.key === "ArrowDown") {
                    event.preventDefault();
                    let nextIndex = (currentIndex + 1) % focusable.length;
                    focusable[nextIndex].focus();
                }
                // Flecha ARRIBA ⬆️ (Moverse al campo anterior)
                else if (event.key === "ArrowUp") {
                    event.preventDefault();
                    let prevIndex = (currentIndex - 1 + focusable.length) % focusable.length;
                    focusable[prevIndex].focus();
                }
                // Flecha DERECHA ➡️ (Permitir en inputs y moverse en selects)
                else if (event.key === "ArrowRight") {
                    if (document.activeElement.tagName === "SELECT") {
                        event.preventDefault();
                        let nextIndex = (currentIndex + 1) % focusable.length;
                        focusable[nextIndex].focus();
                    }
                }
                // Flecha IZQUIERDA ⬅️ (Permitir en inputs y moverse en selects)
                else if (event.key === "ArrowLeft") {
                    if (document.activeElement.tagName === "SELECT") {
                        event.preventDefault();
                        let prevIndex = (currentIndex - 1 + focusable.length) % focusable.length;
                        focusable[prevIndex].focus();
                    }
                }
            }
        });

        Livewire.on('modal-opened', () => {
            const modal = document.getElementById('modalSaveCFiscal');
            let firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });
    });
</script>
