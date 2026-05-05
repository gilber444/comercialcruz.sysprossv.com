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
                <button class="btn btn-label-primary btn-sm" wire:click="processSales"> <i
                        class="fa-solid fa-download"></i> Generar</button>
            </div>
            @if (count($this->sales) > 0)
                @can('GenerarAnexoContribuyente_CSV')
                    <div class="col-sm-3 mt-3">
                        <label for="">&nbsp;</label>
                        <button class="btn btn-label-success btn-sm" wire:click="exportCSV">
                            <i class="fa-solid fa-file-excel"></i>
                            Generar Anexo<
                        </button>
                    </div>
                @endcan
                @can('GenerarLibroContribuyente_PDF')
                    <div class="col-sm-3 mt-3">
                        <a href="{{ url('pdf/libroContribuyente' . '/' . $empresa . '/' . $sucursal . '/' . $caja . '/' . $desde . '/' . $hasta) }}" class="btn btn-label-google-plus btn-sm" target="_blank">
                            <i class="fa-solid fa-file-pdf"></i>
                            Generar PDF
                        </a>
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
                <th class="text-center">NUMERO SERIE</th>
                <th class="text-center">NUMERO DOCUMENTO</th>
                <th class="text-center">NUMERO DE CONTROL INTERNO</th>
                <th class="text-center">NIT O NRC DEL CLIENTE</th>
                <th class="text-center">NOMBRE, RAZON SOCIAL</th>
                <th class="text-center">VENTAS EXENTAS</th>
                <th class="text-center">VENTAS NO SUJETAS</th>
                <th class="text-center">VENTAS GRAVADAS</th>
                <th class="text-center">DEBITO FISCAL</th>
                <th class="text-center">VENTAS CUENTA TERCEROS</th>
                <th class="text-center">DEBITO FISCAL A TERCERO</th>
                <th class="text-center">TOTAL VENTAS</th>
                <th class="text-center">DUI DEL CLIENTE</th>
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
                                @if ($sale->facturador == 3)
                                    1. IMPRESO POR IMPRENTA O TIQUETES
                                @endif
                            @endif
                        </td>
                        <td class="text-center">
                            03.COMPROBANTE DE CREDITO FISCAL
                        </td>
                        <td class="text-center">
                            {{ str_replace('-', '', $sale->numero) }}
                        </td>
                        <td class="text-center">
                            {{ str_replace('-', '', $sale->sello) }}
                        </td>
                        <td class="text-center">
                            {{ str_replace('-', '', $sale->codigo) }}
                        </td>
                        <td class="text-center">
                            {{ str_replace('-', '', $sale->numero) }}
                        </td>
                        <td class="text-center">
                            {{ (isset($sale->Rclientes->nit) && strlen($sale->Rclientes->nit) === 14)
                                ? $sale->Rclientes->nit
                                : '' }}
                        </td>
                        <td class="text-center">
                            {{ $sale->Rclientes->nombreCliente }}
                        </td>
                        <td class="text-center">
                            0
                        </td>
                        <td class="text-center">
                           0
                        </td>
                        <td class="text-end">
                            $ {{ number_format(($sale->total / 1.13) , 2)}}
                        </td>
                        <td class="text-end">
                            $ {{ number_format($sale->total -($sale->total / 1.13) , 2)}}
                        </td>
                        <td class="text-end">
                            0
                        </td>
                        <td class="text-end">
                            0
                        </td>
                        <td class="text-end">
                            {{ '$ ' . number_format($sale->total, 2) }}
                        </td>
                        <td class="text-center">
                            {{ (isset($sale->Rclientes->nit) && strlen($sale->Rclientes->nit) === 9)
                                ? $sale->Rclientes->nit
                                : '' }}
                        </td>
                        <td class="text-center">01</td>
                        <td class="text-center">03</td>
                        <td class="text-center">1</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('common.notis')
</div>
