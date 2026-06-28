<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Utilidades Sintetizado</title>
    <style>
        body  { font-family: sans-serif; font-size: 9px; margin: 0; padding: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 5px; }

        .header { background: #233446; color: #fff; padding: 10px; }
        .header td { color: #fff; vertical-align: middle; }
        .header .logo { width: 60px; text-align: center; }
        .header .logo img { width: 50px; height: 50px; background: #fff; padding: 2px; }
        .header .company { font-size: 10px; line-height: 1.3; }
        .header .company strong { font-size: 13px; display: block; margin-bottom: 2px; }
        .header .title-section { text-align: right; }
        .header .title-section .title { font-size: 14px; font-weight: bold; }
        .header .title-section .subtitle { font-size: 9px; margin-top: 3px; }

        .kpi-table { margin: 8px 0; }
        .kpi-table td { border: 1px solid #ccc; text-align: center; padding: 5px; }
        .kpi-table .label { font-size: 8px; color: #555; text-transform: uppercase; }
        .kpi-table .value { font-size: 12px; font-weight: bold; }

        thead th { background: #233446; color: #fff; font-size: 8px; text-align: center; }
        tbody td { font-size: 8px; border-bottom: 1px solid #ddd; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        tfoot td { background: #233446; color: #fff; font-weight: bold; font-size: 9px; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td class="logo" width="60">
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

    <table class="kpi-table">
        <tr>
            <td>
                <div class="label">Total Ventas</div>
                <div class="value">$ {{ number_format($totalSales, 2) }}</div>
            </td>
            <td>
                <div class="label">Total Costo</div>
                <div class="value">$ {{ number_format($totalCosto, 2) }}</div>
            </td>
            <td>
                <div class="label">Utilidad</div>
                <div class="value">$ {{ number_format($utilidadTotal, 2) }}</div>
            </td>
            <td>
                <div class="label">Margen</div>
                <div class="value">{{ $margenTotal }} %</div>
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
