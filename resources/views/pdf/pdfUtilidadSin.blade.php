<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Utilidades Sintetizado</title>
    <style>
        body  { font-family: sans-serif; font-size: 10px; margin: 0; padding: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; }

        /* Header oscuro */
        .header { background: #233446; color: #fff; padding: 12px 15px; border-radius: 6px; margin-bottom: 10px; }
        .header td { color: #fff; vertical-align: middle; padding: 0; }
        .header .logo { width: 70px; text-align: center; }
        .header .logo img { width: 60px; height: 60px; object-fit: contain; border-radius: 6px; background: #fff; padding: 3px; }
        .header .company { font-size: 11px; line-height: 1.4; }
        .header .company strong { font-size: 14px; display: block; margin-bottom: 3px; }
        .header .title-section { text-align: right; }
        .header .title-section .title { font-size: 15px; font-weight: bold; letter-spacing: .5px; }
        .header .title-section .subtitle { font-size: 10px; opacity: .9; margin-top: 4px; }

        /* KPIs */
        .kpi-container { margin-bottom: 12px; }
        .kpi-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px; text-align: center; background: #fafafa; }
        .kpi-box .label { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: .5px; }
        .kpi-box .value { font-size: 15px; font-weight: bold; color: #233446; margin-top: 3px; }
        .kpi-box.ventas .value { color: #28a745; }
        .kpi-box.utilidad .value { color: #4a90d9; }
        .kpi-box.margen .value { color: #fd7e14; }

        /* Tabla */
        .table-wrapper { border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; }
        thead th { background: #233446; color: #fff; font-size: 9px; text-align: center; padding: 6px; font-weight: 600; }
        tbody tr:nth-child(even) { background: #f7f7f7; }
        tbody td { font-size: 9px; border-bottom: 1px solid #eee; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        tfoot td { background: #233446; color: #fff; font-weight: bold; font-size: 10px; padding: 6px; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header">
        <tr>
            <td class="logo" width="70">
                <img src="{{ $imagenUrl }}" alt="">
            </td>
            <td class="company" width="55%">
                <strong>{{ $empresa->razon }}</strong>
                <div>NIT: {{ $empresa->nit }} — NCR: {{ $empresa->registro }}</div>
                <div>{{ $empresa->direccion }} | Tel. {{ $empresa->telefono }}</div>
            </td>
            <td class="title-section" width="35%">
                <div class="title">REPORTE DE UTILIDADES SINTETIZADO</div>
                <div class="subtitle">
                    Sucursal: <b>{{ $sucursal }}</b><br>
                    @if($dateFrom && $dateTo)
                        Período: {{ $dateFrom }} al {{ $dateTo }}
                    @else
                        Ventas del día
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @php
        $utilidadTotal = $totalSales - $totalCosto;
        $margenTotal   = $totalCosto > 0 ? round(($utilidadTotal / $totalCosto) * 100, 2) : 0;
    @endphp

    {{-- KPIs --}}
    <table class="kpi-container">
        <tr>
            <td width="25%">
                <div class="kpi-box ventas">
                    <div class="label">Total Ventas</div>
                    <div class="value">$ {{ number_format($totalSales, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-box">
                    <div class="label">Total Costo</div>
                    <div class="value">$ {{ number_format($totalCosto, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-box utilidad">
                    <div class="label">Utilidad</div>
                    <div class="value">$ {{ number_format($utilidadTotal, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-box margen">
                    <div class="label">Margen</div>
                    <div class="value">{{ $margenTotal }} %</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- TABLA --}}
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:30%;">Sucursal</th>
                    <th style="width:12%;" class="text-center">Caja</th>
                    <th style="width:14%;" class="text-right">Total Ventas</th>
                    <th style="width:14%;" class="text-right">Total Costo</th>
                    <th style="width:15%;" class="text-right">Utilidad</th>
                    <th style="width:15%;" class="text-right">% Utilidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                @php
                    $venta    = $row->total_venta;
                    $costo    = $row->total_costo;
                    $utilidad = $venta - $costo;
                    $porcent  = $costo > 0 ? round(($utilidad / $costo) * 100, 2) : 0;
                @endphp
                <tr>
                    <td>{{ $row->nombre_sucursal }}</td>
                    <td class="text-center">{{ $row->caja }}</td>
                    <td class="text-right">$ {{ number_format($venta, 2) }}</td>
                    <td class="text-right">$ {{ number_format($costo, 2) }}</td>
                    <td class="text-right">$ {{ number_format($utilidad, 2) }}</td>
                    <td class="text-right">{{ $porcent }} %</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right">TOTALES</td>
                    <td class="text-right">$ {{ number_format($totalSales, 2) }}</td>
                    <td class="text-right">$ {{ number_format($totalCosto, 2) }}</td>
                    <td class="text-right">$ {{ number_format($utilidadTotal, 2) }}</td>
                    <td class="text-right">{{ $margenTotal }} %</td>
                </tr>
            </tfoot>
        </table>
    </div>

</body>
</html>
