<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Utilidades Sintetizado</title>
    <style>
        body  { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10px; margin: 0; padding: 0; color: #333; background: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; }

        .header { background: #233446; color: #fff; padding: 14px 18px; margin-bottom: 14px; }
        .header td { color: #fff; vertical-align: middle; padding: 0; }
        .header .logo { width: 70px; text-align: center; }
        .header .logo img { width: 62px; height: 62px; background: #fff; padding: 4px; }
        .header .company { font-size: 11px; line-height: 1.45; }
        .header .company strong { font-size: 15px; display: block; margin-bottom: 4px; }
        .header .title-section { text-align: right; }
        .header .title-section .title { font-size: 16px; font-weight: 700; }
        .header .title-section .subtitle { font-size: 10px; margin-top: 5px; }

        .kpi-container { margin-bottom: 14px; }
        .kpi-box { border: 1px solid #e0e0e0; padding: 10px 8px; text-align: left; background: #fff; position: relative; }
        .kpi-box .icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 26px; opacity: .18; }
        .kpi-box .label { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: .6px; font-weight: 600; }
        .kpi-box .value { font-size: 16px; font-weight: 700; color: #233446; margin-top: 4px; }
        .kpi-box.ventas  { border-left: 4px solid #28a745; }
        .kpi-box.costo   { border-left: 4px solid #233446; }
        .kpi-box.utilidad{ border-left: 4px solid #4a90d9; }
        .kpi-box.margen  { border-left: 4px solid #fd7e14; }
        .kpi-box.ventas  .value { color: #28a745; }
        .kpi-box.costo   .value { color: #233446; }
        .kpi-box.utilidad .value { color: #4a90d9; }
        .kpi-box.margen  .value { color: #fd7e14; }

        thead th { background: #233446; color: #fff; font-size: 9px; text-align: center; padding: 7px 6px; font-weight: 600; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody td { font-size: 9px; border-bottom: 1px solid #eee; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        tfoot td { background: #233446; color: #fff; font-weight: 700; font-size: 10px; padding: 7px 6px; }
    </style>
</head>
<body>

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

    <table class="kpi-container">
        <tr>
            <td width="25%">
                <div class="kpi-box ventas">
                    <div class="icon">💵</div>
                    <div class="label">Total Ventas</div>
                    <div class="value">$ {{ number_format($totalSales, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-box costo">
                    <div class="icon">🧾</div>
                    <div class="label">Total Costo</div>
                    <div class="value">$ {{ number_format($totalCosto, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-box utilidad">
                    <div class="icon">📈</div>
                    <div class="label">Utilidad</div>
                    <div class="value">$ {{ number_format($utilidadTotal, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-box margen">
                    <div class="icon">📊</div>
                    <div class="label">Margen</div>
                    <div class="value">{{ $margenTotal }} %</div>
                </div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:30%;">Sucursal</th>
                <th style="width:30%;">Caja</th>
                <th style="width:15%;" class="text-right">T. Costo</th>
                <th style="width:15%;" class="text-right">T. Venta</th>
                <th style="width:10%;" class="text-right">Utilidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $d)
            @php
                $venta    = $d->total_venta;
                $costo    = $d->total_costo;
                $utilidad = $venta - $costo;
            @endphp
            <tr>
                <td>{{ $d->sucursal }}</td>
                <td>{{ $d->caja }}</td>
                <td class="text-right">$ {{ number_format($costo, 2) }}</td>
                <td class="text-right">$ {{ number_format($venta, 2) }}</td>
                <td class="text-right">$ {{ number_format($utilidad, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right">TOTALES</td>
                <td class="text-right">$ {{ number_format($totalCosto, 2) }}</td>
                <td class="text-right">$ {{ number_format($totalSales, 2) }}</td>
                <td class="text-right">$ {{ number_format($utilidadTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
