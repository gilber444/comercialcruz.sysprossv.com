<div wire:ignore.self class="modal fade" @if ($itemsQuantity > 0) id="modalSaveConsumidor" @endif tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModals">Metodo de pago | Consumidor Final</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-4">
                        <label for="">DUI</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" class="form-control" wire:model.lazy='duiC' wire:keydown.enter='SearchClientDui()'>
                        </div>
                        <input type="hidden" class="form-control" wire:model.lazy='idC'>
                        @error('idC')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-12 col-md-8">
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
                        </div> --}}
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
                                <dd class="col-sm-6 text-success text-end">${{ number_format($total, 2) }}</dd>

                                <dt class="col-sm-6 fw-normal">Efectivo</dt>
                                <dd class="col-sm-6 text-end"><input type="text" wire:model.lazy="efectivo" wire:blur="Cash()" wire:keydown.enter='SaveConsumidor()' class="form-control text-end"></dd>
                                <dt class="col-sm-6 fw-normal">Cambio</dt>
                                <dd class="col-sm-6 text-danger text-end">{{ number_format($cambio,2)}}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" wire:click.prevent="SaveConsumidor()" id="guardar" class="btn btn-primary"><i class='bx bxs-save' ></i> Efectuar</button>
                <div wire:loading wire:target="SaveConsumidor">Guardando la venta...</div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById('modalSaveConsumidor');

            if (modal && modal.classList.contains('show')) { // Verificar si el modal está abierto
                let focusableElements = modal.querySelectorAll('input, select, textarea');
                let focusable = Array.from(focusableElements).filter(el => !el.disabled && !el.readOnly);
                let currentIndex = focusable.findIndex(el => el === document.activeElement);

                // Permitir navegación normal dentro de los input de texto (izquierda/derecha para mover cursor)
                if (document.activeElement.tagName === "INPUT" && document.activeElement.type === "text") {
                    if (event.keyCode === 37 || event.keyCode === 39) { // Izquierda ⬅️ o Derecha ➡️
                        return; // No interferir, dejar que el cursor se mueva dentro del input
                    }
                }

                // Flecha ABAJO ⬇️ (Moverse al siguiente campo)
                if (event.keyCode === 40) {
                    event.preventDefault();
                    let nextIndex = (currentIndex + 1) % focusable.length;
                    focusable[nextIndex].focus();
                }
                // Flecha ARRIBA ⬆️ (Moverse al campo anterior)
                else if (event.keyCode === 38) {
                    event.preventDefault();
                    let prevIndex = (currentIndex - 1 + focusable.length) % focusable.length;
                    focusable[prevIndex].focus();
                }
                // Flecha DERECHA ➡️ (Moverse al siguiente campo si es un select)
                else if (event.keyCode === 39 && document.activeElement.tagName === "SELECT") {
                    event.preventDefault();
                    let nextIndex = (currentIndex + 1) % focusable.length;
                    focusable[nextIndex].focus();
                }
                // Flecha IZQUIERDA ⬅️ (Moverse al campo anterior si es un select)
                else if (event.keyCode === 37 && document.activeElement.tagName === "SELECT") {
                    event.preventDefault();
                    let prevIndex = (currentIndex - 1 + focusable.length) % focusable.length;
                    focusable[prevIndex].focus();
                }
            }
        });

        Livewire.on('modal-opened', () => {
            const modal = document.getElementById('modalSaveConsumidor');
            let firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });
    });
</script>



