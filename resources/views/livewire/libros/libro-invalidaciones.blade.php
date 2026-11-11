<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>Libro de Invalidaciones</b></h5>
            <small class="text-muted">DTE invalidados con sello, del ambiente de la empresa</small>
        </div>
        <hr class="my-2">
        <div class="row g-2">
            <div class="col-sm-3">
                <label for="">Empresa:</label>
                <select wire:model='empresa' class="form-control" wire:change="updateSucursal()">
                    <option value="">Elegir Empresa</option>
                    @foreach ($empresas as $e)
                        <option value="{{ $e->id }}">{{ $e->empresa }}</option>
                    @endforeach
                </select>
                @error('empresa')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-3">
                <label for="">Tienda/Sucursal:</label>
                <select wire:model='sucursal' class="form-control">
                    <option value="">Elegir Sucursal</option>
                    <option value="0">Todas las Sucursales</option>
                    @foreach ($sucursales as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </select>
                @error('sucursal')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-2">
                <label for="">Tipo de documento:</label>
                <select wire:model='facturador' class="form-control">
                    <option value="">Elegir</option>
                    <option value="0">Todos</option>
                    <option value="1">Facturas</option>
                    <option value="2">Credito Fiscal</option>
                </select>
                @error('facturador')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-2">
                <label for="">Desde:</label>
                <input type="date" wire:model="desde" class="form-control">
                @error('desde')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-2">
                <label for="">Hasta:</label>
                <input type="date" wire:model="hasta" class="form-control">
                @error('hasta')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-label-primary btn-sm" wire:click="processSales" wire:loading.attr="disabled" wire:target="processSales">
                <span wire:loading wire:target="processSales" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                <i class="fa-solid fa-magnifying-glass"></i> Consultar
            </button>
            @if (count($sales) > 0)
                @can('GenerarLibroInvalidacion_PDF')
                    <a href="{{ url('pdf/libroInvalidaciones' . '/' . $empresa . '/' . $sucursal . '/' . $facturador . '/' . $desde . '/' . $hasta) }}"
                        class="btn btn-label-google-plus btn-sm" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i> Libro PDF
                    </a>
                @endcan
                @can('GenerarAnexoInvalidacion_CSV')
                    <button class="btn btn-label-success btn-sm" wire:click="exportCSV" wire:loading.attr="disabled" wire:target="exportCSV">
                        <span wire:loading wire:target="exportCSV" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <i class="fa-solid fa-file-csv"></i> Archivo de invalidados (CSV)
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="card-body pt-0">
        @if (count($sales) < 1)
            <div class="alert alert-secondary text-center mt-3 mb-0" role="alert">
                <i class="fa-regular fa-folder-open me-1"></i>
                {{ $consultado ? 'No hay datos en las fechas seleccionadas.' : 'Seleccione los filtros y presione Consultar.' }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">N.</th>
                            <th class="text-center">FECHA EMISION</th>
                            <th class="text-center">TIPO DE DOCUMENTO</th>
                            <th class="text-center">NUMERO DE CONTROL</th>
                            <th class="text-center">CODIGO DE GENERACION</th>
                            <th class="text-center">SELLO DE RECEPCION</th>
                            <th class="text-center">CORRELATIVO</th>
                            <th class="text-center">MONTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $i => $sale)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($sale['fecha'])->format('d/m/Y') }}</td>
                                <td class="text-center">{{ trim($sale['tipo_codigo'] . ' ' . $sale['tipo_nombre']) }}</td>
                                <td class="small"><code>{{ $sale['numero_control'] }}</code></td>
                                <td class="small"><code>{{ \App\Support\AnexosF07::sinGuiones($sale['codigo']) }}</code></td>
                                <td class="small"><code>{{ $sale['sello'] }}</code></td>
                                <td class="text-center">{{ $sale['correlativo'] }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="7" class="text-end">TOTAL INVALIDADO ({{ count($sales) }} documentos)</td>
                            <td class="text-end">$ {{ number_format((float) $totales['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
    @include('common.notis')
</div>
