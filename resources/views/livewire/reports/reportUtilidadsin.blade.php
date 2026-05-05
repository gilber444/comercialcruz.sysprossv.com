<div class="row">
    <div class="col-sm-12">
        <div class="card shadow-none border border-primary mb-3">
            <div class="card-body">
                <h4 class="card-title text-center"><b>{{ $componentName }}</b></h4>
                <div class="row">
                    <div class="col-sm-12 col-md-122">
                        <div class="row">
                            <div class="col-sm-2 mb-2">
                                <div class="mb-2">
                                    <label for="">Elige Sucursal</label>
                                    <select class="form-control" wire:model='sucursal' wire:change='getCaja'>
                                        <option value="0">Todas Las Sucursales</option>
                                        @foreach ($sucursales as $s)
                                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2 mb-2">
                                <div class="mb-2">
                                    <label for="">Elige Caja</label>
                                    <select class="form-control" wire:model='caja'>
                                        <option value="0">Todas Las Cajas</option>
                                        @foreach ($cajas as $c)
                                            <option value="{{ $c->id }}">{{ $c->caja }}</option>
                                        @endforeach
                                    </select>
                                </div> 
                            </div>
                            <div class="col-sm-2 mb-2">
                                <div class="mb-2">
                                    <label for="">Elige Tipo de Reporte</label>
                                    <select class="form-control" wire:model='reporteType'>
                                        <option value="0">Ventas del dia</option>
                                        <option value="1">Ventas por Fecha</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2 mb-2">
                                <div class="mb-2">
                                    <label for="">Fecha Desde</label>
                                    <input type="date" wire:model='dateFrom' class="form-control"
                                        @if ($reporteType == 0) disabled @endif>
                                </div>
                            </div>
                            <div class="col-sm-2 mb-2">
                                <div class="mb-2">
                                    <label for="">Fecha Hasta</label>
                                    <input type="date" wire:model='dateTo' class="form-control"
                                        @if ($reporteType == 0) disabled @endif>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <button class="btn btn-primary rounded-pill col-12 mb-2" wire:click="SalesByDate"><i
                                        class="fa-solid fa-magnifying-glass"></i> Consultar</button>
                            </div>
                            <div class="col-sm-2">
                                <a href="{{ url(
                                    'report/pdfUtilidadSin' .
                                        '/' .
                                        ($sucursal ?? 0) .
                                        '/' .
                                        ($caja ?? 0) .
                                        '/' .
                                        ($reporteType ?? 0) .
                                        (isset($dateFrom) && $dateFrom !== '' ? '/' . $dateFrom : '') .
                                        (isset($dateTo) && $dateTo !== '' ? '/' . $dateTo : ''),
                                ) }}"
                                    class="btn btn-primary rounded-pill col-12 mb-2 {{ count($data) < 1 ? 'disabled' : '' }}"
                                    target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i> Generar Pdf
                                </a>

                            </div>
                            <div class="col-sm-2">
                                <a href="{{ url(
                                    'report/excelUtilidad' .
                                        '/' .
                                        ($sucursal ?? 0) .
                                        '/' .
                                        ($caja ?? 0) .
                                        '/' .
                                        ($reporteType ?? 0) .
                                        (isset($dateFrom) && $dateFrom !== '' ? '/' . $dateFrom : '') .
                                        (isset($dateTo) && $dateTo !== '' ? '/' . $dateTo : ''),
                                ) }}"
                                    class="btn btn-primary rounded-pill col-12 mb-2 {{ count($data) < 1 ? 'disabled' : '' }}"
                                    target="_blank"><i class="fa-solid fa-file-excel"></i> Exportar a Excel</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12">
                        <!-- TABLA-->
                        <div class="table-responsive" style="height: 700px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-hover">
                                <thead class="bg-dark text-white" style="position: sticky; top: 0; z-index: 10;">
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Sucursal</th>
                                    <th class="text-center">Caja</th>
                                    <th class="text-center">Total Ventas</th>
                                    <th class="text-center">Total Costo</th>
                                    <th class="text-center">Utilidad</th>
                                    <th class="text-center">% Utilidad</th>
                                </thead>
                                <tbody>
                                    @forelse($data as $row)
                                        <tr>
                                            <td class="text-end">{{ $dateFrom }} al {{ $dateTo }}</td>
                                            <td class="text-end">{{ $row->nombre_sucursal }}</td>
                                            <td class="text-end">{{ $row->caja }}</td>
                                            <td class="text-end">${{ number_format($row->total_venta, 2) }}</td>
                                            <td class="text-end">${{ number_format($row->total_costo * 1.13, 2) }}</td>
                                            <td class="text-end">
                                                ${{ number_format($row->total_venta - $row->total_costo * 1.13, 2) }}
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $venta = $row->total_venta;
                                                    $costo = $row->total_costo * 1.13;

                                                    $porcentaje = $venta > 0
                                                        ? round((($venta - $costo) / $venta) * 100, 2)
                                                        : 0;
                                                @endphp

                                                {{ $porcentaje }} %
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No hay datos para el rango
                                                seleccionado</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <td class="text-end" colspan="4">
                                        <b> $ {{ number_format($totalSales, 2) }}</b>

                                    </td>
                                    <td class="text-end">
                                        <b> $ {{ number_format($totalCosto * 1.13, 2) }}</b>
                                    </td>
                                    <td class="text-end">
                                        <b> $ {{ number_format($totalSales - $totalCosto * 1.13, 2) }}</b>
                                    </td>
                                    <td class="text-end">
                                        <b>
                                            @php
                                                $margen = 0;
                                                if (isset($totalCosto) && $totalCosto > 0 && isset($totalSales)) {
                                                    $ventaT = $totalSales;
                                                    $costoT = $totalCosto * 1.13;
                                                    $margen = (($ventaT - $costoT) / $ventaT) * 100;
                                                    echo number_format($margen, 2) . '%';
                                                }

                                            @endphp
                                        </b>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
