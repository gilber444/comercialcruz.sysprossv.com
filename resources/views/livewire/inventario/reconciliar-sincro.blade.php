<div class="row">
    <div class="col-12">
        <div class="card shadow-none border border-primary mb-3">
            <div class="card-body">
                <h4 class="card-title text-center"><b>{{ $componentName }}</b></h4>
                <p class="text-center text-muted small mb-3">
                    Detecta inventarios donde el <code>sincro_id</code> local difiere del VPS (mismo producto/sucursal) y permite actualizarlo sin tocar la existencia.
                </p>

                <div class="row g-3 align-items-end">
                    <div class="col-sm-12 col-md-4">
                        <label class="form-label">Sucursal</label>
                        <select wire:model.defer="sucursal" class="form-select">
                            @if(in_array(Auth::user()->profile, ['Super', 'Administrador', 'Gerente']))
                                <option value="0">Todas</option>
                            @endif
                            @foreach ($sucursales as $s)
                                <option value="{{ $s['id'] }}">{{ $s['nombre'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-12 col-md-4">
                        <button wire:click="consultar" wire:loading.attr="disabled" class="btn btn-primary w-100">
                            <span wire:loading wire:target="consultar" class="spinner-border spinner-border-sm me-1"></span>
                            Buscar diferencias
                        </button>
                    </div>

                    @if(!empty($rows))
                        @can('ReconciliarSincroId_Actualizar')
                            <div class="col-sm-12 col-md-4">
                                <button wire:click="actualizarTodos" wire:loading.attr="disabled"
                                    wire:confirm="¿Actualizar TODOS los sincro_id locales al valor del VPS? No se modifica la existencia."
                                    class="btn btn-warning w-100">
                                    <span wire:loading wire:target="actualizarTodos" class="spinner-border spinner-border-sm me-1"></span>
                                    Actualizar todos ({{ count($rows) }})
                                </button>
                            </div>
                        @endcan
                    @endif
                </div>

                @if($mensaje)
                    <div class="alert alert-{{ $tipoMensaje }} mt-3 mb-0 py-2">{{ $mensaje }}</div>
                @endif
            </div>
        </div>

        @if(!empty($rows))
            <div class="card shadow-none border">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="text-end">Exist. Local</th>
                                    <th class="text-end">Exist. VPS</th>
                                    <th>Sincro ID Local</th>
                                    <th>Sincro ID VPS</th>
                                    @can('ReconciliarSincroId_Actualizar')
                                        <th class="text-center">Acción</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td class="text-nowrap">
                                            {{ $row['codebar3'] }}
                                            <div class="text-muted" style="font-size:0.75rem;">
                                                Local ID: {{ $row['local_inv_id'] }}<br>
                                                VPS ID: {{ $row['vps_inv_id'] }}
                                            </div>
                                        </td>
                                        <td>{{ $row['nombreProducto'] }}</td>
                                        <td class="text-end">{{ number_format($row['existencia_local'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['existencia_vps'], 2) }}</td>
                                        <td class="text-danger small text-break" style="max-width:200px;">
                                            {{ $row['sincro_local'] }}
                                        </td>
                                        <td class="text-success small text-break" style="max-width:200px;">
                                            {{ $row['sincro_vps'] }}
                                        </td>
                                        @can('ReconciliarSincroId_Actualizar')
                                            <td class="text-center">
                                                <button
                                                    wire:click="actualizar({{ $row['local_inv_id'] }})"
                                                    wire:loading.attr="disabled"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <span wire:loading wire:target="actualizar({{ $row['local_inv_id'] }})" class="spinner-border spinner-border-sm"></span>
                                                    Actualizar
                                                </button>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
