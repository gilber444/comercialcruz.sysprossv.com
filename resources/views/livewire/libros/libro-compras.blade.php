<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>Libro de Compras</b></h5>
            <small class="text-muted">Respaldo del Anexo 3 (Detalle de compras) del F07</small>
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
            <button class="btn btn-label-primary btn-sm" wire:click="processCompras" wire:loading.attr="disabled" wire:target="processCompras">
                <span wire:loading wire:target="processCompras" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                <i class="fa-solid fa-magnifying-glass"></i> Consultar
            </button>
            @if (count($compras) > 0)
                <a href="{{ url('pdf/libroCompras' . '/' . $empresa . '/' . $sucursal . '/' . $desde . '/' . $hasta) }}"
                    class="btn btn-label-google-plus btn-sm" target="_blank">
                    <i class="fa-solid fa-file-pdf"></i> Libro PDF
                </a>
                <button class="btn btn-label-success btn-sm" wire:click="exportAnexoCompras" wire:loading.attr="disabled" wire:target="exportAnexoCompras"
                    title="CSV para el portal de Hacienda: 21 columnas, separado por ';' y sin encabezados">
                    <span wire:loading wire:target="exportAnexoCompras" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    <i class="fa-solid fa-file-csv"></i> Anexo 3 (CSV F07)
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-0">
        @if ($sinDesglose > 0)
            {{-- Aviso honesto: sin desglose, la compra saldria en cero en el anexo. --}}
            <div class="alert alert-warning mt-3 mb-2" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                {{ $sinDesglose }} compra(s) del periodo no tienen desglose fiscal (registradas antes de esta actualizacion).
                Salen en cero y <b>no</b> se incluyen en el anexo 3: revise o vuelva a guardar esas compras antes de declarar.
            </div>
        @endif

        @if (count($compras) < 1)
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
                            <th class="text-center">CLASE / TIPO</th>
                            <th class="text-center">NUMERO DE DOCUMENTO</th>
                            <th class="text-center">NIT / NRC</th>
                            <th class="text-center">PROVEEDOR</th>
                            <th class="text-center">EXENTAS</th>
                            <th class="text-center">GRAVADAS</th>
                            <th class="text-center">CREDITO FISCAL</th>
                            <th class="text-center">TOTAL</th>
                            <th class="text-center">ANEXO 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($compras as $i => $c)
                            @php
                                [$nitNrc, $dui] = \App\Support\AnexosF07::identificacion($c['nit'], $c['nrc'], '');
                            @endphp
                            <tr class="{{ $c['sin_desglose'] ? 'table-warning' : '' }}">
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($c['fecha'])->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $c['clase_documento'] }} / {{ $c['tipo_documento'] !== '' ? $c['tipo_documento'] : 'sin desglose' }}</td>
                                <td class="small"><code>{{ $c['documento'] }}</code></td>
                                <td class="text-center">{{ $nitNrc !== '' ? $nitNrc : $dui }}</td>
                                <td>{{ $c['proveedor'] }}</td>
                                <td class="text-end">$ {{ number_format((float) $c['compras_exentas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $c['compras_gravadas'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $c['credito_fiscal'], 2) }}</td>
                                <td class="text-end">$ {{ number_format((float) $c['total_compra'], 2) }}</td>
                                <td class="text-center">
                                    @if ($c['en_anexo'])
                                        <span class="badge bg-label-success">Si</span>
                                    @else
                                        <span class="badge bg-label-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">TOTALES ({{ count($compras) }} compras)</td>
                            <td class="text-end">$ {{ number_format((float) $totales['compras_exentas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['compras_gravadas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['credito_fiscal'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totales['total_compra'], 2) }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end">EN ANEXO 3 ({{ $enAnexo }} compras con credito fiscal)</td>
                            <td class="text-end">$ {{ number_format((float) $totalesAnexo['compras_exentas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totalesAnexo['compras_gravadas'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totalesAnexo['credito_fiscal'], 2) }}</td>
                            <td class="text-end">$ {{ number_format((float) $totalesAnexo['total_compra'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <small class="text-muted">
                Las facturas de consumidor final no dan credito fiscal y no se informan en el anexo 3.
                La pantalla de compras no captura numero de resolucion ni DUI del proveedor: esas columnas del anexo van vacias.
            </small>
        @endif
    </div>
    @include('common.notis')
</div>
