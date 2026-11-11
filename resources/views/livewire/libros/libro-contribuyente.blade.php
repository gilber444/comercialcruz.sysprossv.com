<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>Libro de Ventas a Contribuyentes</b></h5>
            <small class="text-muted">Solo creditos fiscales DTE procesados y sellados, del ambiente de la empresa</small>
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
                @can('GenerarLibroContribuyente_PDF')
                    <a href="{{ url('pdf/libroContribuyente' . '/' . $empresa . '/' . $sucursal . '/' . $caja . '/' . $desde . '/' . $hasta) }}"
                        class="btn btn-label-google-plus btn-sm" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i> Libro PDF
                    </a>
                @endcan
                @can('GenerarAnexoContribuyente_CSV')
                    <button class="btn btn-label-success btn-sm" wire:click="exportCSV" wire:loading.attr="disabled" wire:target="exportCSV"
                        title="CSV para el portal de Hacienda: 20 columnas, separado por ';' y sin encabezados">
                        <span wire:loading wire:target="exportCSV" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <i class="fa-solid fa-file-csv"></i> Anexo 1 (CSV F07)
                    </button>
                    <button class="btn btn-label-info btn-sm" wire:click="exportAnexoRetencion" wire:loading.attr="disabled" wire:target="exportAnexoRetencion"
                        title="Retencion 1% IVA efectuada AL declarante por clientes grandes (casilla 162)">
                        <span wire:loading wire:target="exportAnexoRetencion" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <i class="fa-solid fa-file-csv"></i> Anexo 7 Retencion 1% (CSV F07)
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
                            <th class="text-center">N.</th>
                            <th class="text-center">FECHA</th>
                            <th class="text-center">NUMERO DE CONTROL</th>
                            <th class="text-center">CODIGO DE GENERACION</th>
                            <th class="text-center">NIT / NRC / DUI</th>
                            <th class="text-center">CLIENTE</th>
                            <th class="text-center">EXENTAS</th>
                            <th class="text-center">NO SUJETAS</th>
                            <th class="text-center">GRAVADAS</th>
                            <th class="text-center">DEBITO FISCAL</th>
                            <th class="text-center">TOTAL</th>
                            <th class="text-center">RETENCION 1%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $i => $sale)
                            @php
                                [$nitNrc, $dui] = \App\Support\AnexosF07::identificacion($sale['nit'], $sale['nrc'], $sale['dui']);
                            @endphp
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($sale['fecha'])->format('d/m/Y') }}</td>
                                <td class="small"><code>{{ \App\Support\AnexosF07::sinGuiones($sale['numero_control']) }}</code></td>
                                <td class="small"><code>{{ \App\Support\AnexosF07::documentoDte($sale['fecha'], $sale['codigo'], $sale['numero_control']) }}</code></td>
                                <td class="text-center">{{ $nitNrc !== '' ? $nitNrc : $dui }}</td>
                                <td>{{ $sale['cliente'] }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['exentas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['no_sujetas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['gravada'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['debito'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['total'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $sale['retencion'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">TOTALES ({{ count($sales) }} documentos)</td>
                            <td class="text-end">$ {{ number_format((float) $totales['exentas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['no_sujetas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['gravada'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['debito'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['total'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['retencion'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <small class="text-muted">
                Montos con IVA antes de la retencion del 1% del cliente grande. El anexo 7 incluye tambien facturas con retencion.
                El negocio no genera ventas exentas ni no sujetas: se informan en 0.00.
            </small>
        @endif
    </div>
    @include('common.notis')
</div>
