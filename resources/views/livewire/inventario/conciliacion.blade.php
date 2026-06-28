<div class="row">
    <div class="col-12">
        <div class="card shadow-none border border-primary mb-3">
            <div class="card-body">
                <h4 class="card-title text-center"><b>{{ $componentName }}</b></h4>

                <div class="row g-3">
                    <div class="col-sm-12 col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" wire:model.defer="dateFrom" class="form-control">
                        @error('dateFrom')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-sm-12 col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" wire:model.defer="dateTo" class="form-control">
                        @error('dateTo')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-sm-12 col-md-3">
                        <label class="form-label">Sucursal</label>
                        <select wire:model.defer="sucursal" class="form-select">
                            @if(in_array(Auth::user()->profile, ['Super', 'Administrador', 'Gerente']))
                                <option value="0">Todas</option>
                            @endif
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-12 col-md-5">
                        <label class="form-label">Movimientos a comparar</label>
                        <div class="d-flex flex-wrap gap-3 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="incluirAjustes" id="f-ajustes">
                                <label class="form-check-label" for="f-ajustes">Ajustes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="incluirCompras" id="f-compras">
                                <label class="form-check-label" for="f-compras">Compras</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="incluirSolicitudes" id="f-solicitudes">
                                <label class="form-check-label" for="f-solicitudes">Solicitudes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="incluirVentas" id="f-ventas">
                                <label class="form-check-label" for="f-ventas">Ventas</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" wire:model="soloEnCero" id="solo-cero">
                            <label class="form-check-label" for="solo-cero">Solo inventarios en 0</label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" wire:model="soloDiferencias" id="solo-diff">
                            <label class="form-check-label" for="solo-diff">Solo diferencias</label>
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-9">
                        <div class="d-flex flex-wrap gap-2 pt-4">
                            @can('ConciliacionInventario_Consultar')
                                <button class="btn btn-primary" wire:click="consultar" wire:loading.attr="disabled" wire:target="consultar">
                                    <span wire:loading.remove wire:target="consultar">
                                        <i class="fa-solid fa-magnifying-glass"></i> Consultar
                                    </span>
                                    <span wire:loading wire:target="consultar">
                                        <i class="fa-solid fa-spinner fa-spin"></i> Consultando...
                                    </span>
                                </button>
                            @endcan
                            @can('ConciliacionInventario_AplicarFuentes')
                                <button class="btn btn-success {{ count($rows) ? '' : 'disabled' }}" wire:click="aplicarDesdeFuentes">
                                    <i class="fa-solid fa-plus"></i> Aplicar Faltantes Desde Fuentes
                                </button>
                            @endcan
                            @can('ConciliacionInventario_IgualarKardex')
                                <button class="btn btn-warning {{ count($rows) ? '' : 'disabled' }}" wire:click="igualarAlUltimoKardex">
                                    <i class="fa-solid fa-scale-balanced"></i> Igualar Al Ultimo Kardex
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>

                @if (!empty($summary))
                    <div class="row mt-4">
                        <div class="col-sm-12 col-md-3">
                            <div class="alert alert-primary mb-2">
                                <strong>Total filas:</strong> {{ $summary['total'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="alert alert-success mb-2">
                                <strong>Faltantes desde fuentes:</strong> {{ $summary['con_faltante_fuentes'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="alert alert-warning mb-2">
                                <strong>Dif. inventario vs kardex:</strong> {{ $summary['con_diferencia_kardex'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="alert alert-dark mb-2">
                                <strong>Neto fuentes-kardex:</strong> {{ number_format($summary['monto_fuentes'] ?? 0, 2) }}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="table-responsive mt-3" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Sucursal</th>
                                <th>Codigo</th>
                                <th>Producto</th>
                                <th class="text-end">Existencia</th>
                                <th class="text-center">Compras</th>
                                <th class="text-center">Aj. In</th>
                                <th class="text-center">Aj. Eg</th>
                                <th class="text-center">Sol. In</th>
                                <th class="text-center">Sol. Out</th>
                                <th class="text-center">Ventas</th>
                                <th class="text-end">Fuentes Neto</th>
                                <th class="text-end">Kardex Neto</th>
                                <th class="text-end">Faltante</th>
                                <th class="text-end">Sug. Fuentes</th>
                                <th class="text-end">Ult. Kardex</th>
                                <th class="text-end">Dif. Inv/Kdx</th>
                                <th class="text-center">Manual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row['sucursal'] }}</td>
                                    <td>{{ $row['codebar3'] }}</td>
                                    <td>{{ $row['nombreProducto'] }}</td>
                                    <td class="text-end">{{ number_format($row['existencia_actual'], 2) }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ number_format($row['compras'], 2) }}</span>
                                            @can('ConciliacionInventario_MovimientoMas')
                                                <button class="btn btn-sm btn-outline-success"
                                                    wire:click="aplicarMovimiento('{{ $row['key'] }}', 'compras')">+</button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ number_format($row['ajustes_ingreso'], 2) }}</span>
                                            @can('ConciliacionInventario_MovimientoMas')
                                                <button class="btn btn-sm btn-outline-success"
                                                    wire:click="aplicarMovimiento('{{ $row['key'] }}', 'ajustes_ingreso')">+</button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ number_format($row['ajustes_egreso'], 2) }}</span>
                                            @can('ConciliacionInventario_MovimientoMenos')
                                                <button class="btn btn-sm btn-outline-danger"
                                                    wire:click="aplicarMovimiento('{{ $row['key'] }}', 'ajustes_egreso')">-</button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ number_format($row['solicitudes_in'], 2) }}</span>
                                            @can('ConciliacionInventario_MovimientoMas')
                                                <button class="btn btn-sm btn-outline-success"
                                                    wire:click="aplicarMovimiento('{{ $row['key'] }}', 'solicitudes_in')">+</button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ number_format($row['solicitudes_out'], 2) }}</span>
                                            @can('ConciliacionInventario_MovimientoMenos')
                                                <button class="btn btn-sm btn-outline-danger"
                                                    wire:click="aplicarMovimiento('{{ $row['key'] }}', 'solicitudes_out')">-</button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ number_format($row['ventas'], 2) }}</span>
                                            @can('ConciliacionInventario_MovimientoMenos')
                                                <button class="btn btn-sm btn-outline-danger"
                                                    wire:click="aplicarMovimiento('{{ $row['key'] }}', 'ventas')">-</button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td class="text-end">{{ number_format($row['fuentes_neto'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['kardex_neto'], 2) }}</td>
                                    <td class="text-end {{ abs($row['diferencia_fuentes_kardex']) > 0.0001 ? 'text-success fw-bold' : '' }}">
                                        {{ number_format($row['diferencia_fuentes_kardex'], 2) }}
                                    </td>
                                    <td class="text-end">{{ number_format($row['existencia_sugerida_fuentes'], 2) }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span>{{ $row['ultimo_saldo_kardex'] !== null ? number_format($row['ultimo_saldo_kardex'], 2) : '-' }}</span>
                                            @if ($row['ultimo_saldo_kardex'] !== null)
                                                @can('ConciliacionInventario_AplicarSaldoKardex')
                                                    <button class="btn btn-sm btn-outline-warning"
                                                        wire:click="aplicarSaldoKardex('{{ $row['key'] }}')">Kdx</button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end {{ abs($row['diferencia_inventario_kardex'] ?? 0) > 0.0001 ? 'text-warning fw-bold' : '' }}">
                                        {{ $row['diferencia_inventario_kardex'] !== null ? number_format($row['diferencia_inventario_kardex'], 2) : '-' }}
                                    </td>
                                    <td style="min-width: 190px;">
                                        <div class="d-flex gap-2">
                                            @can('ConciliacionInventario_RepararManual')
                                                <input type="number" step="0.01"
                                                    wire:model.defer="manualExistencia.{{ $row['key'] }}"
                                                    class="form-control form-control-sm text-end">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    wire:click="actualizarExistenciaManual('{{ $row['key'] }}')">
                                                    Reparar
                                                </button>
                                            @else
                                                <span class="text-muted small ms-auto">Sin permiso</span>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="17" class="text-center py-4">Sin resultados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        `Faltante` = movimientos fuente seleccionados menos lo que ya aparece aplicado en kardex dentro del rango.
                        `Dif. Inv/Kdx` = ultimo saldo del kardex menos la existencia actual del inventario.
                    </small>
                </div>
            </div>
        </div>
    </div>

    @include('common.notis')
</div>


