<div class="card">
    <div class="card-header">
        <h5 class="card-title m-0 me-2"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
        <hr class="my-2">

        <div class="row align-items-end">
            <div class="col-md-7">
                <label class="form-label">Excel de catalogos publicado por el Ministerio de Hacienda</label>
                <input type="file" class="form-control @error('archivo') is-invalid @enderror"
                    wire:model="archivo" accept=".xlsx,.xls">
                @error('archivo') <span class="text-danger small">{{ $message }}</span> @enderror
                <div class="form-text">
                    No se modifica nada hasta que usted confirme. Primero se muestra que cambiaria.
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <button class="btn btn-primary" wire:click="Analizar" wire:loading.attr="disabled">
                    <i class="fa-solid fa-magnifying-glass"></i> Analizar
                </button>
                @if (count($resumen))
                    <button class="btn btn-outline-secondary" wire:click="Cancelar" wire:loading.attr="disabled">Descartar</button>
                @endif
                <span wire:loading wire:target="Analizar,Aplicar,ConfirmarAplicar,CancelarAplicar,VerDetalle,Cancelar,archivo" class="ms-2 text-muted">
                    <i class="fa-solid fa-spinner fa-spin"></i> Procesando...
                </span>
            </div>
        </div>
    </div>

    @if (count($resumen))
        <div class="card-body">
            <div class="alert alert-info py-2">
                Archivo: <b>{{ $nombreArchivo }}</b> &mdash; {{ count($resumen) }} catalogos encontrados.
                Marque los que desea aplicar.
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Catalogo</th>
                            <th>Destino</th>
                            <th class="text-center">En Excel</th>
                            <th class="text-center text-success">Nuevos</th>
                            <th class="text-center text-primary">Actualizar</th>
                            <th class="text-center text-warning">Retirar</th>
                            <th class="text-center text-muted">Sin cambio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resumen as $c)
                            @php
                                $esTerritorial = $c['tipo'] === 'territorial';
                                $hayReporte = $esTerritorial && ($c['sinEmparejar'] || $c['ambiguos'] || $c['noEnExcel']);
                                $hayCambios = $c['nuevos'] || $c['actualizar'] || $c['retirar'];
                            @endphp
                            <tr class="{{ !empty($c['error']) ? 'table-danger' : ($esTerritorial ? 'table-light' : '') }}">
                                <td>
                                    @if (empty($c['error']))
                                        <input type="checkbox" class="form-check-input"
                                            value="{{ $c['codigo'] }}" wire:model="seleccion">
                                    @endif
                                </td>
                                <td>
                                    <b>{{ $c['codigo'] }}</b> {{ $c['nombre'] }}
                                    @if ($c['catalogoNuevo'])
                                        <span class="badge bg-danger ms-1">CATALOGO NUEVO</span>
                                    @endif
                                    @if ($c['descartadas'])
                                        <span class="badge bg-secondary ms-1"
                                            title="Notas al pie y subtitulos que no son registros">
                                            {{ $c['descartadas'] }} filas omitidas
                                        </span>
                                    @endif
                                    @if ($c['duplicados'])
                                        <span class="badge bg-warning text-dark ms-1">
                                            {{ $c['duplicados'] }} codigos repetidos
                                        </span>
                                    @endif
                                    @if ($esTerritorial)
                                        @if ($c['sinEmparejar'])
                                            <span class="badge bg-danger ms-1" title="Filas del Excel que no se pudieron emparejar: no se tocan">
                                                {{ $c['sinEmparejar'] }} sin emparejar
                                            </span>
                                        @endif
                                        @if ($c['ambiguos'])
                                            <span class="badge bg-warning text-dark ms-1" title="Empatan con mas de un registro: no se tocan">
                                                {{ $c['ambiguos'] }} ambiguos
                                            </span>
                                        @endif
                                        @if ($c['noEnExcel'])
                                            <span class="badge bg-secondary ms-1" title="Registros de la base que no aparecen en el Excel: no se desactivan">
                                                {{ $c['noEnExcel'] }} no estan en el Excel
                                            </span>
                                        @endif
                                    @endif
                                    @if (!empty($c['error']))
                                        <div class="text-danger small text-wrap">{{ $c['error'] }}</div>
                                    @endif
                                </td>
                                <td><small>{{ $c['destino'] }}</small></td>
                                <td class="text-center">{{ $c['total'] }}</td>
                                <td class="text-center">{{ $c['nuevos'] ?: '-' }}</td>
                                <td class="text-center">{{ $c['actualizar'] ?: '-' }}</td>
                                <td class="text-center">{{ $c['retirar'] ?: '-' }}</td>
                                <td class="text-center">{{ $c['iguales'] ?: '-' }}</td>
                                <td>
                                    @if (empty($c['error']) && ($hayCambios || $hayReporte))
                                        <button class="btn btn-sm btn-outline-primary"
                                            wire:click="VerDetalle('{{ $c['codigo'] }}')">
                                            {{ $verDetalle === $c['codigo'] ? 'Ocultar' : 'Ver' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            @if ($verDetalle === $c['codigo'] && count($detalle))
                                <tr>
                                    <td colspan="9" class="p-0">
                                        <div class="p-3 bg-light" style="max-height:420px; overflow:auto;">
                                            @if ($esTerritorial)
                                                <div class="small text-muted mb-2 text-wrap">
                                                    Se empata por nombre dentro del departamento deducido del Excel.
                                                    Solo se actualizan codigo y nombre; nunca el id, el municipio o
                                                    departamento padre ni el estado. Lo que no empata se muestra aqui
                                                    y no se toca.
                                                </div>
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Accion</th>
                                                            <th>Departamento</th>
                                                            <th>Excel</th>
                                                            <th>Registro actual</th>
                                                            <th>Quedaria</th>
                                                            <th>Observacion</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($detalle as $d)
                                                            <tr>
                                                                <td>
                                                                    @if ($d['accion'] === 'actualizar')
                                                                        <span class="badge bg-primary">Actualizar</span>
                                                                    @elseif ($d['accion'] === 'sin_emparejar')
                                                                        <span class="badge bg-danger">Sin emparejar</span>
                                                                    @elseif ($d['accion'] === 'ambiguo')
                                                                        <span class="badge bg-warning text-dark">Ambiguo</span>
                                                                    @else
                                                                        <span class="badge bg-secondary">No esta en Excel</span>
                                                                    @endif
                                                                </td>
                                                                <td><small>{{ $d['departamento'] ?? '-' }}</small></td>
                                                                <td>
                                                                    @if ($d['codigo'] !== null)
                                                                        <code>{{ $d['codigo'] }}</code> <small>{{ $d['nombre'] }}</small>
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($d['id'])
                                                                        <small class="text-muted">#{{ $d['id'] }}</small>
                                                                        <code>{{ $d['codigoActual'] }}</code>
                                                                        <small>{{ $d['nombreActual'] }}</small>
                                                                        @if ($d['municipio'])
                                                                            <br><small class="text-muted">Municipio: {{ $d['municipio'] }}</small>
                                                                        @endif
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($d['accion'] === 'actualizar')
                                                                        <code>{{ $d['codigoNuevo'] }}</code> <small>{{ $d['nombreNuevo'] }}</small>
                                                                    @else
                                                                        <small class="text-muted">sin cambio</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-wrap" style="min-width:260px;"><small>{{ $d['motivo'] }}</small></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Accion</th>
                                                            <th>Codigo</th>
                                                            <th>Actual</th>
                                                            <th>Quedaria</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($detalle as $d)
                                                            <tr>
                                                                <td>
                                                                    @if ($d['accion'] === 'nuevo')
                                                                        <span class="badge bg-success">Nuevo</span>
                                                                    @elseif ($d['accion'] === 'actualizar')
                                                                        <span class="badge bg-primary">Actualizar</span>
                                                                    @else
                                                                        <span class="badge bg-warning text-dark">Retirar</span>
                                                                    @endif
                                                                </td>
                                                                <td><code>{{ $d['codigo'] }}</code></td>
                                                                <td><small>{{ $d['antes'] ?? '-' }}</small></td>
                                                                <td><small>{{ $d['despues'] ?? 'se marca Desactivado' }}</small></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted text-wrap me-3">
                    Los codigos que Hacienda retira se marcan <b>Desactivado</b>, nunca se borran:
                    los DTE ya emitidos apuntan a estos registros. Departamentos, municipios y
                    distritos solo se corrigen en sitio y no se desactivan.
                </small>
                @if (!$confirmando)
                    <button class="btn btn-success" wire:click="Aplicar" wire:loading.attr="disabled"
                        @if (empty($seleccion)) disabled @endif>
                        <i class="fa-solid fa-check"></i> Aplicar {{ count($seleccion) }} catalogo(s)
                    </button>
                @endif
            </div>

            @if ($confirmando)
                <div class="alert alert-warning mt-3 mb-0">
                    <b>Confirme antes de aplicar.</b>
                    Se modificaran estos catalogos en una sola transaccion:
                    <ul class="mb-2 mt-2">
                        @foreach ($resumen as $c)
                            @if (in_array($c['codigo'], $seleccion))
                                <li>
                                    <b>{{ $c['codigo'] }}</b> {{ $c['nombre'] }}:
                                    @if ($c['tipo'] === 'territorial')
                                        {{ $c['actualizar'] }} a corregir;
                                        {{ $c['sinEmparejar'] + $c['ambiguos'] }} sin emparejar o ambiguos (no se tocan)
                                    @else
                                        {{ $c['nuevos'] }} nuevos, {{ $c['actualizar'] }} a actualizar,
                                        {{ $c['retirar'] }} a desactivar
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                    <button class="btn btn-danger btn-sm" wire:click="ConfirmarAplicar" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="ConfirmarAplicar"><i class="fa-solid fa-check-double"></i></span>
                        <span wire:loading wire:target="ConfirmarAplicar"><i class="fa-solid fa-spinner fa-spin"></i></span>
                        Si, aplicar
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" wire:click="CancelarAplicar">Cancelar</button>
                </div>
            @endif

            @if (count($aplicado))
                <div class="alert alert-success mt-3 mb-0">
                    <b>Cambios aplicados</b>
                    <ul class="mb-0 mt-2">
                        @foreach ($aplicado as $cod => $n)
                            <li>
                                @if (!empty($n['error']))
                                    {{ $cod }}: <span class="text-danger">{{ $n['error'] }}</span>
                                @else
                                    {{ $cod }}: {{ $n['nuevos'] ?? 0 }} nuevos,
                                    {{ $n['actualizados'] ?? 0 }} actualizados,
                                    {{ $n['retirados'] ?? 0 }} retirados
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- Sin esto los avisos item-error / item-added del componente no se ven. --}}
    @include('common.notis')
</div>
