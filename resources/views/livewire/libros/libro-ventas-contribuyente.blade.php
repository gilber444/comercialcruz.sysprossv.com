<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> Libro de Ventas Contribuyente </b></h5>
        </div>
        <hr class="my-2">
        <div class="row">
            <div class="col-sm-3">
                <label for="">Empresa:</label>
                <select wire:model='empresa' class="form-control" wire:change="updateSucursal()">
                    <option value="">Elegir Empresa</option>
                    @foreach ($empresas as $e)
                        <option value="{{ $e->id }}">{{ $e->empresa }}</option>
                    @endforeach
                </select>
                @error('empresa') <span class="text-danger er">{{ $message}}</span>@enderror
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
                @error('sucursal') <span class="text-danger er">{{ $message}}</span>@enderror
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
                @error('caja') <span class="text-danger er">{{ $message}}</span>@enderror
            </div>
            <div class="col-sm-2">
                <label for="">Desde:</label>
                <input type="date" wire:model="desde" id="desde" class="form-control">
                @error('desde') <span class="text-danger er">{{ $message}}</span>@enderror
            </div>
            <div class="col-sm-2">
                <label for="">Hasta</label>
                <input type="date" wire:model="hasta" id="hasta" class="form-control">
                @error('hasta') <span class="text-danger er">{{ $message}}</span>@enderror
            </div>
            <div class="col-sm-2 mt-3">
                <label for="">&nbsp;</label>
                <button class="btn btn-label-primary btn-sm" wire:click="processSales"> <i class="fa-solid fa-download"></i> Generar</button>
            </div>
            @if(count($this->sales) > 0)
                @can('GenerarAnexoConsumidor_CSV')
                    <div class="col-sm-3 mt-3">
                        <label for="">&nbsp;</label>
                        <button class="btn btn-label-success btn-sm" wire:click="exportCSV"> <i class="fa-solid fa-file-excel"></i> Generar Anexo</button>
                    </div>
                @endcan
                @can('GenerarLibroConsumidor_PDF')
                    <div class="col-sm-3 mt-3">
                        <a href="{{ url('pdf/libroConsumidor' . '/' . $empresa .  '/' . $sucursal . '/' . $caja . '/' . $desde . '/' . $hasta) }}" class="btn btn-label-google-plus btn-sm" target="_blank"> <i class="fa-solid fa-file-pdf"></i> Generar PDF </a>
                    </div>
                @endcan

            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm table-bordered">
            <thead>
                <th class="text-center">FECHA</th>
                <th class="text-center">CLASE</th>
                <th class="text-center">TIPO</th>
                <th class="text-center">RESOLUCION</th>
                <th class="text-center">SERIE</th>
                <th class="text-center">NUMERO</th>
                <th class="text-center">NUMERO CONTROL</th>
                <th class="text-center">NIT O NRC</th>
                <th class="text-center">RAZON SOCIAL</th>
                <th class="text-center">VENTAS EXENTAS</th>
                <th class="text-center">VENTAS NO SUJETAS</th>
                <th class="text-center">VENTAS GRAVADAS</th>
                <th class="text-center">DEBITO FISCAL</th>
                <th class="text-center">VENTAS TERCEROS</th>
                <th class="text-center">DEBITO FISCAL TERCEROS</th>
                <th class="text-center">TOTAL VENTAS</th>
                <th class="text-center">DUI CLIENTE</th>
                <th class="text-center">ANEXO</th>
            </thead>
            <tbody>
                @foreach($this->sales as $sale)
                    <tr>
                        <td class="text-center">
                            {{ $sale->fecha ? \Carbon\Carbon::parse($sale->fecha)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo) ? '1. IMPRESO POR IMPRRENTA O TIQUETES' : '4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)' }}
                        </td>
                        <td class="text-center">
                            {{ $sale->facturador == 3 ? '03 COMPROBANTE DE CREDITO FISCAL' : '' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo)
                                ? ($sale->facturador == 3
                                    ? ($sale->Rcaja->creresolucion ?? 'SIN RESOLUCION')
                                    : (str_replace('-', '', $sale->numero ?? 'SIN NUMERO')))
                                : '' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo)
                                ? ($sale->facturador == 3
                                    ? ($sale->Rcaja->creserie ?? 'SIN SERIE')
                                    : ($sale->sello ?? 'SIN NUMERO'))
                                : '' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo) ? ($sale->correlativo ?? '') : $sale->codgio ?? '' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo) ? ($sale->correlativo ?? '') : $sale->codgio ?? '' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo) ? ($sale->primer_correlativo ?? '') : $sale->codgio ?? '' }}
                        </td>
                        <td class="text-center">
                            {{ is_null($sale->codigo) ? ($sale->ultimo_correlativo ?? '') : $sale->codgio ?? '' }}
                        </td>
                        <td class="text-center">
                            {{ $sale->Rcaja->caja ?? '' }}
                        </td>
                        <td class="text-end">
                            {{ !empty($sale->ventasExenta) ? '$ ' . number_format($sale->ventasExenta, 2) : '' }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->ventasInternaExenta ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->ventaNoSujera ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->ventaGravada ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->exportacionesDentro ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->exportacionesFuera ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ !empty($sale->exportacionesServicios) ? '$ ' . number_format($sale->exportacionesServicios, 2) : '' }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->ventasZonaFranca ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->ventaCuentaTerceros ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->ventaGravada ?? 0, 2) }}
                        </td>
                        <td class="text-center">2</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('common.notis')
</div>
