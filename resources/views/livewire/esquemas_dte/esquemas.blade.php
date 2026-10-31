<div class="card">
    <div class="card-header">
        <h5 class="card-title m-0 me-2"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
        <hr class="my-2">

        <div class="row align-items-end">
            <div class="col-md-7">
                <label class="form-label">ZIP "svfe-json-schemas" de Hacienda (o un .json suelto)</label>
                <input type="file" class="form-control @error('archivo') is-invalid @enderror"
                    wire:model="archivo" accept=".zip,.json">
                @error('archivo') <span class="text-danger small">{{ $message }}</span> @enderror
                <div class="form-text">
                    La version se lee del propio esquema (<code>identificacion.version</code>).
                    No hay que digitarla.
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <button class="btn btn-primary" wire:click="Analizar" wire:loading.attr="disabled">
                    <i class="fa-solid fa-magnifying-glass"></i> Analizar
                </button>
                @if (count($comparacion))
                    <button class="btn btn-outline-secondary" wire:click="Cancelar">Descartar</button>
                @endif
                <span wire:loading wire:target="Analizar,Aplicar,archivo" class="ms-2 text-muted">
                    <i class="fa-solid fa-spinner fa-spin"></i> Procesando...
                </span>
            </div>
        </div>
    </div>

    <div class="card-body">
        @if (count($comparacion))
            <div class="alert alert-info py-2">
                Archivo: <b>{{ $nombreArchivo }}</b> &mdash; {{ count($comparacion) }} esquemas leidos.
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Tipo</th>
                            <th>Documento</th>
                            <th>Archivo</th>
                            <th class="text-center">Version del esquema</th>
                            <th class="text-center">Guardada</th>
                            <th class="text-center">Usa el sistema</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparacion as $c)
                            @php
                                $desalineado = $c['versionSistema'] !== null
                                    && $c['versionSistema'] !== $c['versionArchivo'];
                            @endphp
                            <tr class="{{ $desalineado ? 'table-danger' : '' }}">
                                <td>
                                    @if ($c['accion'] !== 'igual')
                                        <input type="checkbox" class="form-check-input"
                                            value="{{ $c['tipoDte'] }}" wire:model="seleccion">
                                    @endif
                                </td>
                                <td><b>{{ $c['tipoDte'] }}</b></td>
                                <td><small>{{ $c['nombre'] }}</small></td>
                                <td><code>{{ $c['archivo'] }}</code></td>
                                <td class="text-center"><b>{{ $c['versionArchivo'] }}</b></td>
                                <td class="text-center">{{ $c['versionGuardada'] ?? '-' }}</td>
                                <td class="text-center">
                                    @if ($c['versionSistema'] === null)
                                        <span class="text-muted" title="El generador no emite este tipo">n/a</span>
                                    @elseif ($desalineado)
                                        <span class="badge bg-danger">{{ $c['versionSistema'] }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $c['versionSistema'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($c['accion'])
                                        @case('nuevo')
                                            <span class="badge bg-primary">Nuevo</span>
                                            @break
                                        @case('cambia-version')
                                            <span class="badge bg-warning text-dark">
                                                Cambia {{ $c['versionGuardada'] }} &rarr; {{ $c['versionArchivo'] }}
                                            </span>
                                            @break
                                        @case('cambia-contenido')
                                            <span class="badge bg-info text-dark"
                                                title="Misma version pero el archivo cambio">
                                                Contenido modificado
                                            </span>
                                            @break
                                        @default
                                            <span class="text-muted">Sin cambios</span>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
                $alertas = collect($comparacion)->filter(fn($c) =>
                    $c['versionSistema'] !== null && $c['versionSistema'] !== $c['versionArchivo']);
            @endphp

            @if ($alertas->count())
                <div class="alert alert-danger">
                    <b><i class="fa-solid fa-triangle-exclamation"></i> Version desalineada</b>
                    <p class="mb-1 mt-2">
                        El sistema esta emitiendo con una version distinta a la que declara el esquema
                        oficial. Hacienda va a rechazar esos documentos:
                    </p>
                    <ul class="mb-0">
                        @foreach ($alertas as $a)
                            <li>
                                <b>{{ $a['tipoDte'] }}</b> {{ $a['nombre'] }}:
                                el sistema usa <b>{{ $a['versionSistema'] }}</b>,
                                el esquema exige <b>{{ $a['versionArchivo'] }}</b>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Guardar el esquema no cambia la version con la que se emite.
                    "Usa el sistema" es la version del ultimo DTE emitido de ese tipo.
                </small>
                <button class="btn btn-success" wire:click="Aplicar" wire:loading.attr="disabled"
                    @if (empty($seleccion)) disabled @endif>
                    <i class="fa-solid fa-check"></i> Guardar {{ count($seleccion) }} esquema(s)
                </button>
            </div>

            @if ($aplicado)
                <div class="alert alert-success mt-3 mb-0">{{ $aplicado }} esquema(s) guardado(s).</div>
            @endif
        @endif

        <h6 class="mt-4"><b>Carga manual de un esquema</b></h6>
        <div class="border rounded p-3 mb-4 bg-light">
            <div class="row g-3 align-items-start">
                <div class="col-md-4">
                    <label class="form-label">Tipo de documento</label>
                    <select class="form-select @error('manualTipo') is-invalid @enderror"
                        wire:model="manualTipo">
                        <option value="">Elegir...</option>
                        @foreach ($tipos as $codigo => $etiqueta)
                            <option value="{{ $codigo }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('manualTipo') <span class="text-danger small">{{ $message }}</span> @enderror
                    <div class="form-text">Sale del catalogo CAT-002.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Archivo .json</label>
                    <input type="file" class="form-control @error('manualArchivo') is-invalid @enderror"
                        wire:model="manualArchivo" accept=".json">
                    @error('manualArchivo') <span class="text-danger small">{{ $message }}</span> @enderror
                    <span wire:loading wire:target="manualArchivo" class="text-muted small">
                        <i class="fa-solid fa-spinner fa-spin"></i> Leyendo...
                    </span>
                    @if ($manualNombreDetectado)
                        <div class="form-text text-success">{{ $manualNombreDetectado }}</div>
                    @endif
                </div>

                <div class="col-md-2">
                    <label class="form-label">Version</label>
                    <input type="number" min="1" max="99"
                        class="form-control @error('manualVersion') is-invalid @enderror"
                        wire:model="manualVersion">
                    @error('manualVersion') <span class="text-danger small">{{ $message }}</span> @enderror
                    @if ($manualVersionDetectada !== null)
                        @if ((int) $manualVersion === $manualVersionDetectada)
                            <div class="form-text text-success">
                                <i class="fa-solid fa-check"></i> Coincide con el archivo
                            </div>
                        @else
                            <div class="form-text text-danger">
                                El archivo declara <b>{{ $manualVersionDetectada }}</b>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="col-md-2">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button class="btn btn-primary w-100" wire:click="GuardarManual"
                        wire:loading.attr="disabled">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </div>

            <small class="text-muted d-block mt-2">
                La version se precarga desde el propio archivo. Si la cambia, se guarda
                la que usted indique y queda registrada la diferencia.
            </small>
        </div>

        <h6 class="mt-4"><b>Esquemas guardados</b></h6>
        <div class="table-responsive text-nowrap">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Documento</th>
                        <th>Archivo</th>
                        <th class="text-center">Version</th>
                        <th class="text-center">Estado</th>
                        <th>Cargado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guardados as $g)
                        <tr class="{{ $g->status === 'Activo' ? '' : 'text-muted' }}">
                            <td><b>{{ $g->tipoDte }}</b></td>
                            <td><small>{{ $g->nombre }}</small></td>
                            <td><code>{{ $g->archivo }}</code></td>
                            <td class="text-center">{{ $g->version }}</td>
                            <td class="text-center">
                                <span class="badge {{ $g->status === 'Activo' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $g->status }}
                                </span>
                            </td>
                            <td><small>{{ $g->created_at?->format('d/m/Y H:i') }}</small></td>
                            <td class="text-end">
                                @if ($g->status === 'Activo')
                                    <button class="btn btn-sm btn-outline-warning"
                                        wire:click="Desactivar({{ $g->id }})"
                                        onclick="return confirm('El tipo {{ $g->tipoDte }} dejara de tener vigente el esquema version {{ $g->version }}. Continuar?')">
                                        <i class="fa-solid fa-ban"></i> Desactivar
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-success"
                                        wire:click="Activar({{ $g->id }})"
                                        onclick="return confirm('El tipo {{ $g->tipoDte }} quedara con el esquema version {{ $g->version }} como vigente. Continuar?')">
                                        <i class="fa-solid fa-check"></i> Usar esta
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Todavia no hay esquemas cargados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
