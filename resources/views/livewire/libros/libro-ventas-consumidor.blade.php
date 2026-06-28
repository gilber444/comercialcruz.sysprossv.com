<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> Libro de Ventas Consumidor Final</b></h5>
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
                <label for="">Hasta</label>
                <input type="date" wire:model="hasta" id="hasta" class="form-control">
                @error('hasta')
                    <span class="text-danger er">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-sm-2 mt-3">
                <label for="">&nbsp;</label>
                <button class="btn btn-label-primary btn-sm" wire:click="processSales">
                    <i class="fa-solid fa-download"></i> Generar
                </button>
            </div>
            @if (count($this->sales) > 0)
                @can('GenerarAnexoConsumidor_CSV')
                    <div class="col-sm-3 mt-3">
                        <label for="">&nbsp;</label>
                        <button class="btn btn-label-success btn-sm" wire:click="exportCSV">
                            <i class="fa-solid fa-file-excel"></i> Generar Anexo
                        </button>
                    </div>
                @endcan
                @can('GenerarLibroConsumidor_PDF')
                    <div class="col-sm-3 mt-3">
                        <a href="{{ url('pdf/libroConsumidor' . '/' . $empresa . '/' . $sucursal . '/' . $caja . '/' . $desde . '/' . $hasta) }}"
                            class="btn btn-label-google-plus btn-sm" target="_blank"> <i class="fa-solid fa-file-pdf"></i>
                            Generar PDF </a>
                    </div>
                @endcan
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm table-bordered">
            <thead>
                <th class="text-center">FECHA EMISION</th>
                <th class="text-center">CLASE DOCUMENTO</th>
                <th class="text-center">TIPO DOCUMENTO</th>
                <th class="text-center">NUMERO RESOLUCION</th>
                <th class="text-center">SERIE</th>
                <th class="text-center">NUMERO DE CONTROL DEL</th>
                <th class="text-center">NUMERO DE CONTROL AL</th>
                <th class="text-center">NUMERO DOCUMENTO DEL</th>
                <th class="text-center">NUMERO DOCUMENTO AL</th>
                <th class="text-center">CAJA</th>
                <th class="text-center">VENTAS EXENTAS</th>
                <th class="text-center">VENTAS INTERNAS</th>
                <th class="text-center">VENTAS NO SUJETAS</th>
                <th class="text-center">VENTAS GRAVADAS</th>
                <th class="text-center">EXPORTACIONES DENTRO</th>
                <th class="text-center">EXPORTACIONES FUERA</th>
                <th class="text-center">EXPORTACIONES SERVICIO</th>
                <th class="text-center">VENTAS ZONA FRANCA</th>
                <th class="text-center">VENTAS CUENTA TERCEROS</th>
                <th class="text-center">TOTAL VENTAS</th>
                <th class="text-center">TIPO OPERACION</th>
                <th class="text-center">TIPO INGRESO</th>
                <th class="text-center">ANEXO</th>
            </thead>
            <tbody>
                @foreach ($this->sales as $sale)
                    <tr>
                        <td class="text-center">
                            {{ $sale->fecha ? \Carbon\Carbon::parse($sale->fecha)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-center">
                            @if ($sale->tipo == 'DTE')
                                4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)
                            @else
                                @if ($sale->facturador == 1)
                                    1. IMPRESO POR IMPRENTA O TIQUETES
                                @else
                                    3. FORMULARIO ÚNICO
                                @endif
                            @endif
                        </td>
                        <td class="text-center">
                            01.FACTURA DE CONSUMIDOR FINAL
                        </td>
                        <td class="text-center">
                            @if ($sale->tipo == 'Fisico')
                                {{ $sale->facturador == 1 ? $sale->Rcaja->tresolucion ?? 'SIN RESOLUCION' : $sale->Rcaja->conresolucion ?? 'SIN RESOLUCION' }}
                            @else
                                {{ str_replace('-', '', $sale->primer_codigo ?? '') }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($sale->tipo == 'Fisico')
                                {{ $sale->facturador == 1 ? $sale->Rcaja->tserie ?? 'SIN SERIE' : $sale->Rcaja->conserie ?? 'SIN SERIE' }}
                            @else
                                {{ str_replace('-', '', $sale->ultimo_codigo ?? '') }}
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $sale->tipo == 'Fisico' ? $sale->primer_correlativo ?? '' : str_replace('-', '', $sale->primer_codigo ?? '') }}
                        </td>
                        <td class="text-center">
                            {{ $sale->tipo == 'Fisico' ? $sale->ultimo_correlativo ?? '' : str_replace('-', '', $sale->ultimo_codigo ?? '') }}
                        </td>
                        <td class="text-center">
                            {{ $sale->tipo == 'Fisico' ? $sale->primer_correlativo ?? '' : str_replace('-', '', $sale->primer_numero ?? '') }}
                        </td>
                        <td class="text-center">
                            {{ $sale->tipo == 'Fisico' ? $sale->ultimo_correlativo ?? '' : str_replace('-', '', $sale->ultimo_numero ?? '') }}
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
                        <td class="text-center">01</td>
                        <td class="text-center">03</td>
                        <td class="text-center">2</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('common.notis')
</div>
