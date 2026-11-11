<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>Libro de Ventas a Consumidor Final</b></h5>
            <small class="text-muted">Solo DTE procesados y sellados, del ambiente de la empresa</small>
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
                <select wire:model='sucursal' class="form-control" wire:change="updateCaja()">
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
                <label for="">Caja:</label>
                <select wire:model='caja' class="form-control">
                    <option value="">Elegir Caja</option>
                    <option value="0">Todas las Cajas</option>
                    @foreach ($cajas as $c)
                        <option value="{{ $c->id }}">CAJA {{ $c->caja }}</option>
                    @endforeach
                </select>
                @error('caja')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-2">
                <label for="">Desde:</label>
                <input type="date" wire:model="desde" id="desde" class="form-control">
                @error('desde')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-2">
                <label for="">Hasta:</label>
                <input type="date" wire:model="hasta" id="hasta" class="form-control">
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
                @can('GenerarLibroConsumidor_PDF')
                    <a href="{{ url('pdf/libroConsumidor' . '/' . $empresa . '/' . $sucursal . '/' . $caja . '/' . $desde . '/' . $hasta) }}"
                        class="btn btn-label-google-plus btn-sm" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i> Libro PDF
                    </a>
                @endcan
                @can('GenerarAnexoConsumidor_CSV')
                    <button class="btn btn-label-success btn-sm" wire:click="exportCSV" wire:loading.attr="disabled" wire:target="exportCSV"
                        title="CSV para el portal de Hacienda: 23 columnas, separado por ';' y sin encabezados">
                        <span wire:loading wire:target="exportCSV" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <i class="fa-solid fa-file-csv"></i> Anexo 2 (CSV F07)
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="card-body pt-0">
        @if (count($sales) < 1)
            {{-- Estado vacio explicito: distingue "sin movimientos" de una consulta fallida. --}}
            <div class="alert alert-secondary text-center mt-3 mb-0" role="alert">
                <i class="fa-regular fa-folder-open me-1"></i>
                {{ $consultado ? 'No hay datos en las fechas seleccionadas.' : 'Seleccione los filtros y presione Consultar.' }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">FECHA</th>
                            <th class="text-center">SUCURSAL / CAJA</th>
                            <th class="text-center">DOCS.</th>
                            <th class="text-center">CLASE / TIPO</th>
                            <th class="text-center">DOCUMENTO DEL (COD. GENERACION)</th>
                            <th class="text-center">DOCUMENTO AL (COD. GENERACION)</th>
                            <th class="text-center">CORRELATIVO DEL - AL</th>
                            <th class="text-center">VENTAS EXENTAS</th>
                            <th class="text-center">VENTAS NO SUJETAS</th>
                            <th class="text-center">VENTAS GRAVADAS (IVA INCL.)</th>
                            <th class="text-center">TOTAL VENTAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td class="text-center">{{ \Carbon\Carbon::parse($sale['fecha'])->format('d/m/Y') }}</td>
                                <td>{{ $sale['sucursal'] }} / Caja {{ $sale['caja'] }}</td>
                                <td class="text-center">{{ $sale['documentos'] }}</td>
                                <td class="text-center">4 DTE / 01 Factura</td>
                                <td class="small"><code>{{ \App\Support\AnexosF07::documentoDte($sale['fecha'], $sale['del_codigo'], $sale['del_numero']) }}</code></td>
                                <td class="small"><code>{{ \App\Support\AnexosF07::documentoDte($sale['fecha'], $sale['al_codigo'], $sale['al_numero']) }}</code></td>
                                <td class="text-center">{{ $sale['del_correlativo'] }} - {{ $sale['al_correlativo'] }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['exentas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['no_sujetas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['gravadas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">TOTALES</td>
                            <td class="text-center">{{ collect($sales)->sum('documentos') }}</td>
                            <td colspan="4"></td>
                            <td class="text-end">$ {{ number_format((float) $totales['exentas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['no_sujetas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['gravadas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <small class="text-muted">
                {{ count($sales) }} rango(s) por dia y caja. El negocio no genera ventas exentas ni no sujetas: se informan en 0.00.
            </small>
        @endif
    </div>
    @include('common.notis')
</div>
