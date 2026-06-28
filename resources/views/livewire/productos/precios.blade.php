<style>
@media (max-width: 768px) {
    .table-responsive table {
        font-size: 12px;
    }

    .table-responsive input,
    .table-responsive select {
        font-size: 12px !important;
        padding: 4px;
        min-width: 60px;
    }

    .table-responsive button i {
        font-size: 12px !important;
    }
}

</style>
<div class="row">
    <div class="col-sm-4 col-md-2">
        <label for="codigbarra">Codigo de Barra</label>
        <select class="form-control" wire:model.lazy="codebarP" style="font-size: 12px;">
            <option value="">Elegir Codigo</option>
             @if (!empty($codebar1) && $codebar1 != '0')
                <option value="{{ $codebar1 }}">{{ $codebar1 }}</option>
            @endif
            @if (!empty($codebar2) && $codebar2 != '0')
                <option value="{{ $codebar2 }}">{{ $codebar2 }}</option>
            @endif
            @if (!empty($codebar3) && $codebar3 != '0')
                <option value="{{ $codebar3 }}">{{ $codebar3 }}</option>
            @endif
            @if (!empty($codealternativo) && $codealternativo != '0')
                <option value="{{ $codealternativo }}">{{ $codealternativo }}</option>
            @endif
        </select>
        <div id="defaultFormControlHelp" class="form-text">
            @error('codebarP')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-4 col-md-2">
        <label for="codigbarra">Presentacion</label>
        <select class="form-control" wire:model.lazy='unidadP' style="font-size: 12px;" wire:change="actualizarUnidad">
            <option value="">Elegir Unidad</option>
            @foreach ($medidas as $m )
            <option value="{{ $m->id }}">{{ $m->unidad }}</option>
            @endforeach
        </select>
        <div id="defaultFormControlHelp" class="form-text">
            @error('unidadP')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="defaultFormControlInput" class="form-label">Cantidad</label>
        <input type="text" class="form-control" placeholder="00.00" wire:model.lazy='cantidadP' wire:change="actualizarCantidad" onfocus="this.select()" style="font-size: 12px;">
        <div id="defaultFormControlHelp" class="form-text">
            @error('cantidadP')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="defaultFormControlInput" class="form-label">C.S/IVA</label>
        <input type="text" class="form-control" placeholder="00.0000" wire:model.lazy='costoS' wire:change="actualizarCostoConIva" onfocus="this.select()">
        <div id="defaultFormControlHelp" class="form-text">
            @error('costoS')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
            @error('mensajeError')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="defaultFormControlInput" class="form-label">C/IVA</label>
        <input type="text" class="form-control" placeholder="00.0000" wire:model.lazy='costoIVA' wire:change="actualizarCostoSinIva" onfocus="this.select()" style="font-size: 12px;">
        <div id="defaultFormControlHelp" class="form-text">
            @error('costoIVA')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="defaultFormControlInput" class="form-label">%</label>
        <input type="text" class="form-control" placeholder="00.0000" wire:model.lazy='utilidad' wire:change="actualizarPrecioVenta" onfocus="this.select()" style="font-size: 12px;">
        <div id="defaultFormControlHelp" class="form-text">
            @error('utilidad')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="defaultFormControlInput" class="form-label">P.U. Venta</label>
        <input type="text" class="form-control" placeholder="00.0000" wire:model.lazy='precioVentaUni' wire:change="actualizarUtilidad" onfocus="this.select()" style="font-size: 12px;">
        <div id="defaultFormControlHelp" class="form-text">
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="defaultFormControlInput" class="form-label">P. Presen.</label>
        <input type="text" class="form-control" placeholder="00.0000" wire:model.lazy='precioVenta' wire:change="actualizarUtilidad2" onfocus="this.select()" style="font-size: 12px;" wire:keydown.enter="StorePrecios">
        <div id="defaultFormControlHelp" class="form-text">
        </div>
    </div>
    <div class="col-sm-4 col-md-1">
        <label for="">Agregar</label>
        <a href="javascript:void(0);" wire:click="StorePrecios()" class="btn btn-success rounded-pill"><i class="fa-solid fa-check"></i></a>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-sm-12 col-md">
        <table class="table table-sm table-responsive table-hover">
            <thead>
                <!--<th class="text-center">Codigo de Barra</th>-->
                <th class="text-center">Presentacion</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Costo S. IVA</th>
                <th class="text-center">Costo IVA</th>
                <th class="text-center">Utilidad</th>
                <th class="text-center">PV IVA</th>
                <th class="text-center">PV</th>
                <th class="text-center">Es.</th>
                <th class="text-center">S.</th>
                <th class="text-center"></th>
            </thead>
            <tbody style="font-size: 12px;">
                @foreach ($precios as $p )
                <tr>
                    {{-- <td wire:ignore.self>
                        <select class="form-control w-100" wire:model.lazy='codebarPU.{{ $p->id }}' wire:change='UpdateCodebar({{ $p->id }})' style="font-size: 12px;">
                            @if ($codebar1 && $codebar1 != '0')
                                <option value="{{ $codebar1 }}">{{ $codebar1 }}</option>
                            @endif
                            @if ($codebar2 && $codebar2 != '0')
                                <option value="{{ $codebar2 }}">{{ $codebar2 }}</option>
                            @endif
                            @if ($codebar3 && $codebar3 != '0')
                                <option value="{{ $codebar3 }}">{{ $codebar3 }}</option>
                            @endif
                            @if ($codealternativo && $codealternativo != '0')
                                <option value="{{ $codealternativo }}">{{ $codealternativo }}</option>
                            @endif
                        </select>
                    </td>--}}
                    <td>
                        <select class="form-control w-100" wire:model.lazy='unidadPU.{{ $p->id }}' wire:change='UpdateUnidad({{ $p->id }})' style="font-size: 12px;">
                            @foreach ($medidas as $m )
                            <option value="{{ $m->id }}">{{ $m->unidad }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control w-100" placeholder="0.0000" wire:model.lazy='cantidadPU.{{ $p->id }}'
                        wire:keydown.enter="actualizarCantidadU({{ $p->id }})" onfocus="this.select()" style="font-size: 12px;">
                    </td>
                    <td>
                        <input type="text" class="form-control w-100" placeholder="0.0000" wire:model.lazy='costoSU.{{ $p->id }}' wire:change="actualizarCostoConIvaU({{ $p->id }})" onfocus="this.select()" style="font-size: 12px;">
                    </td>
                    <td>
                        <input type="text" class="form-control w-100" placeholder="0.0000" wire:model.lazy='costoIVAU.{{ $p->id }}' wire:change="actualizarCostoSinIvaU({{ $p->id }})" onfocus="this.select()" style="font-size: 12px;">
                    </td>
                    <td>
                        <input type="text" class="form-control w-100" placeholder="0.0000" wire:model.lazy='utilidadU.{{ $p->id }}'
                        wire:change="actualizarPrecioVentaU({{ $p->id }})" onfocus="this.select()" style="font-size: 12px;">
                    </td>
                    <td>
                        <input type="text" class="form-control w-100" placeholder="0.0000" wire:model.lazy='precioVentaUniU.{{ $p->id }}' wire:change="actualizarUtilidadU({{ $p->id }})" onfocus="this.select()" style="font-size: 12px;">
                    </td>
                    <td>
                        <input type="text" class="form-control w-100" placeholder="0.0000" wire:model.lazy='precioVentaU.{{ $p->id }}' wire:change="actualizarUtilidad2U({{ $p->id }})" onfocus="this.select()" style="font-size: 12px;">
                    </td>
                    <td>
                        <div class="input-group-merge form-check form-switch">
                            <input class="form-check-input"
                            type="checkbox"
                            wire:model.lazy="escalaU.{{ $p->id }}"
                            wire:change="updateEscala({{ $p->id }})"
                            onfocus="this.select()"
                            style="font-size: 12px;">
                        </div>
                    </td>
                    <td>
                        {{-- <button type="button" class="btn btn-label-facebook" wire:click='CargaSucursales({{ $p->id }})'><i class="fa-regular fa-building fa-sm"></i></button> --}}
                    </td>
                    <td>
                        <a href="javascript:void(0);" wire:click="UpdatePrecio({{ $p->id }})" class="btn btn-label-primary rounded-pill"><i class="fa-solid fa-arrows-rotate fa-sm"></i></a>
                        <a href="javascript:void(0);" wire:click="ElimPreU({{ $p->id }})" class="btn btn-label-danger rounded-pill"><i class="fa-solid fa-trash fa-sm"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{-- @include('livewire.productos.sucursal_precios') --}}
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let inputs = document.querySelectorAll('input, select'); // Captura todos los inputs y selects

        // Obtener el índice de la columna actual basado en la posición del input
        function getColumnIndex(element) {
            let parentRow = element.closest('tr'); // Encuentra la fila
            if (!parentRow) return -1;
            let cells = [...parentRow.children]; // Obtiene todas las celdas de la fila
            let index = cells.findIndex(cell => cell.contains(element)); // Encuentra la posición del input
            return index;
        }

        function focusNextInput(currentIndex) {
            if (currentIndex < inputs.length - 1) {
                inputs[currentIndex + 1].focus();
            }
        }

        function focusPrevInput(currentIndex) {
            if (currentIndex > 0) {
                inputs[currentIndex - 1].focus();
            }
        }

        function focusVerticalInput(currentIndex, direction) {
            let currentElement = inputs[currentIndex];
            let currentColumn = getColumnIndex(currentElement);
            if (currentColumn === -1) return;

            let currentRect = currentElement.getBoundingClientRect();
            let closestElement = null;
            let minDistance = Number.MAX_VALUE;

            inputs.forEach((element, index) => {
                if (index !== currentIndex) {
                    let rect = element.getBoundingClientRect();
                    let elementColumn = getColumnIndex(element);

                    if (elementColumn === currentColumn) { // Solo mover dentro de la misma columna
                        let distance = direction === 'down' ? rect.top - currentRect.top : currentRect.top - rect.top;

                        if (distance > 0 && distance < minDistance) {
                            minDistance = distance;
                            closestElement = element;
                        }
                    }
                }
            });

            if (closestElement) {
                closestElement.focus();
            }
        }

        inputs.forEach((element, index) => {
            element.addEventListener('focus', function () {
                this.select();
            });

            element.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowRight') {
                    focusNextInput(index);
                    event.preventDefault();
                } else if (event.key === 'ArrowLeft') {
                    focusPrevInput(index);
                    event.preventDefault();
                } else if (event.key === 'ArrowDown') {
                    focusVerticalInput(index, 'down');
                    event.preventDefault();
                } else if (event.key === 'ArrowUp') {
                    focusVerticalInput(index, 'up');
                    event.preventDefault();
                } else if (event.key === 'Enter') {
                    focusNextInput(index);
                    event.preventDefault();
                }
            });

            // Mantener el valor seleccionado en los selects al moverse entre campos
            if (element.tagName === 'SELECT') {
                element.addEventListener('change', function () {
                    this.setAttribute('data-selected', this.value);
                });

                element.addEventListener('focus', function () {
                    let selectedValue = this.getAttribute('data-selected');
                    if (selectedValue) {
                        this.value = selectedValue;
                    }
                });
            }
        });
    });
</script>
