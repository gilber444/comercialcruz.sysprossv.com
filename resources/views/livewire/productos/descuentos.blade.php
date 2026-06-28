<div class="row">
    <div class="col-sm-12 col-md-3 mb-3">
        <label for="">Precio a Descuento</label>
        <select wire:model='precioDes' class="form-control">
            <option value="">Elegir Precio..</option>
            @foreach ($preciosDescuento as $p)
                <option value="{{ $p->id }}">{{ $p->presentacion }} / $ {{ number_format($p->pvventa, 2) }}
                </option>
            @endforeach
        </select>
        <div id="defaultFormControlHelp" class="form-text">
            @error('precioDes')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-2 mb-3">
        <label for="">Inicio</label>
        <input type="date" wire:model.lazy='inicio' class="form-control">
        <div id="defaultFormControlHelp" class="form-text">
            @error('inicio')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-2 mb-3">
        <label for="">Fin</label>
        <input type="date" wire:model.lazy='fin' class="form-control">
        <div id="defaultFormControlHelp" class="form-text">
            @error('fin')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-1 mb-3">
        <label for="">Descuento</label>
        <input type="text" wire:model.lazy='descuento' class="form-control" wire:change="calcularNuevoPrecio" wire:blur="calcularNuevoPrecio">
        <div id="defaultFormControlHelp" class="form-text">
            @error('descuento')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-1 mb-3">
        <label for="">Valor</label>
        <input type="text" wire:model.lazy='valor' class="form-control" wire:blur="calcularDescuento" wire:change="calcularDescuento">
        <div id="defaultFormControlHelp" class="form-text">
            @error('valor')
                <span class="text-danger er">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-sm-12 col-md-1 mb-3">
        <label for="">Familia</label>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model.lazy='familiaD'>
            <label class="form-check-label" for="flexSwitchCheckDefault"></label>
        </div>
    </div>
    <div class="col-sm-12 col-md-2">
        <a class="btn btn-primary" href="javascript:void(0);" wire:click="CrearDes()"><i class="fa-solid fa-save"></i> Agregar</a>
    </div>
</div>
<div class="row">
    <div class="col-sm-12 col-md-12 mb-3">
        <table class="table table-responsive table-hover table-sm">
            <thead>
                <th>Presentacion</th>
                <th>Descuento</th>
                <th>Valor</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Status</th>
                <th></th>
            </thead>
            <tbody>
                @foreach ($descuentos as $d)
                    <tr>
                        <td>
                            <select wire:model='precioDesUp.{{ $d->id }}' class="form-control">
                                <option value="">Elegir Precio..</option>
                                @foreach ($preciosDescuento as $p)
                                    <option value="{{ $p->id }}">{{ $p->presentacion }} / $
                                        {{ number_format($p->pvventa, 2) }}</option>
                                @endforeach
                            </select>
                            @error('precioDesUp.{{ $d->id }}')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </td>
                        <td>
                            <input type="text" wire:model.lazy='descuentoU.{{ $d->id }}'
                                class="form-control" wire:change="calcularNuevoPrecioU({{ $d->id }})" wire:blur="calcularNuevoPrecio({{ $d->id }})">
                            @error('descuentoU.{{ $d->id }}')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </td>
                        <td>
                            <input type="text" wire:model.lazy='valorU.{{ $d->id }}' class="form-control" wire:blur="calcularDescuentoU({{ $d->id }})" wire:change="calcularDescuentoU({{ $d->id }})">
                        </td>
                        <td>
                            <input type="date" wire:model.lazy='inicioU.{{ $d->id }}' class="form-control">
                            @error('inicioU.{{ $d->id }}')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </td>
                        <td>
                            <input type="date" wire:model.lazy='finU.{{ $d->id }}' class="form-control">
                            @error('finU. $d->id')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </td>
                        <td>
                            @php
                                $fechaInicio = \Carbon\Carbon::parse($d->inicio);
                                $fechaFin = \Carbon\Carbon::parse($d->fin);
                                $hoy = \Carbon\Carbon::today();

                                if ($hoy->isBefore($fechaInicio)) {
                                    $estado = 'Sin Iniciar';
                                    $estadoClass = 'text-info';
                                } elseif ($hoy->isSameDay($fechaFin)) {
                                    $estado = 'Vence Hoy';
                                    $estadoClass = 'text-warning';
                                } elseif ($hoy->between($fechaInicio, $fechaFin->subDay())) {
                                    $estado = 'Activo';
                                    $estadoClass = 'text-success';
                                } else {
                                    $estado = 'Vencido';
                                    $estadoClass = 'text-danger';
                                }
                            @endphp
                            <span class="{{ $estadoClass }}">{{ $estado }}</span>

                        </td>
                        <td>
                            <a class="btn btn-primary" href="javascript:void(0);"
                                wire:click="UpdateDes({{ $d->id }})"> <i class="fa-solid fa-refresh"></i> </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
