<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> Libro de Invalidaciones</b></h5>
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
                <label for="">Tipo Factura:</label>
                <select wire:model='facturador' class="form-control">
                    <option value="">Elegir Caja</option>
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
                @can('GenerarAnexoInvalidacion_CSV')
                    <div class="col-sm-3 mt-3">
                        <label for="">&nbsp;</label>
                        <button class="btn btn-label-success btn-sm" wire:click="exportCSV">
                            <i class="fa-solid fa-file-excel"></i> Generar Anexo
                        </button>
                    </div>
                @endcan
                @can('GenerarLibroInvalidacion_PDF')
                    <div class="col-sm-3 mt-3">
                        <a href="{{ url('pdf/libroInvalidaciones' . '/' . $empresa . '/' . $sucursal . '/' . $facturador . '/' . $desde . '/' . $hasta) }}"
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
                <th class="text-center">NUMERO DE RESOLUCION</th>
                <th class="text-center">CLASE DE DOCUMENTO</th>
                <th class="text-center">DESDE</th>
                <th class="text-center">HASTA</th>
                <th class="text-center">TIPO DE DOCUMENTO</th>
                <th class="text-center">TIPO DE DETALLE</th>
                <th class="text-center">SERIE</th>
                <th class="text-center">DESDE</th>
                <th class="text-center">HASTA</th>
                <th class="text-center">CODIGO DE GENERACION</th>
            </thead>
            <tbody>
                @foreach ($this->sales as $sale)
                    <tr>
                        <td class="text-center">
                            {{ $sale->numeroControl }}
                        </td>
                        <td class="text-center">
                            4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)
                        </td>
                        <td class="text-center">
                            0
                        </td>
                        <td class="text-center">
                            0
                        </td>
                        <td class="text-center">
                            {{ $sale->RtipoDte->codigo }}
                        </td>
                        <td class="text-center">
                            D
                        </td>
                        <td class="text-center">
                            {{ str_replace('-', '', $sale->sello) }}
                        </td>
                        <td class="text-center">
                            0
                        </td>
                        <td class="text-center">
                            0
                        </td>
                        <td class="text-center">
                            {{ str_replace('-', '', $sale->codigoGeneracion) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('common.notis')
</div>
