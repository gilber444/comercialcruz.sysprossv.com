<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Utilidades Sintetizado</title>
    <style>
        body  { font-family: sans-serif; font-size: 10px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 5px; }
        .header-table td { padding: 0; vertical-align: middle; }
        .title    { font-size: 13px; font-weight: bold; text-align: center; margin: 6px 0 2px; text-transform: uppercase; }
        .subtitle { font-size: 10px; text-align: center; margin-bottom: 6px; color: #444; }
        thead th  { background-color: #233446; color: #fff; font-size: 10px; text-align: center; padding: 5px; }
        tbody tr:nth-child(even) { background: #f5f5f5; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .total-row   { background: #233446; color: #fff; font-weight: bold; }
        .total-row td { padding: 4px 5px; }
        hr { border: none; border-top: 1px solid #233446; margin: 4px 0; }
        .kpi-table td { padding: 4px 10px; font-size: 10px; }
        .kpi-label    { color: #555; }
        .kpi-value    { font-weight: bold; font-size: 12px; color: #233446; }
    </style>
</head>
<body>

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td width="15%">
                <img src="{{ $imagenUrl }}" alt="" width="100" height="55">
            </td>
            <td width="85%">
                <div style="font-size:12px; font-weight:bold;">{{ $empresa->razon }}</div>
                <div>NIT: {{ $empresa->nit }} — NCR: {{ $empresa->registro }}</div>
                <div>{{ $empresa->direccion }} | Tel. {{ $empresa->telefono }}</div>
            </td>
        </tr>
    </table>
    <hr>

    <div class="title">Reporte de Utilidades Sintetizado</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursal }}</b>
        @if($dateFrom && $dateTo)
        &nbsp;|&nbsp; Período: <b>{{ $dateFrom }}</b> al <b>{{ $dateTo }}</b>
        @endif
    </div>
    <hr>

    {{-- RESUMEN KPIs --}}
    @php $utilidadTotal = $totalSales - $totalCosto; $margenTotal = $totalCosto > 0 ? round(($utilidadTotal / $totalCosto) * 100, 2) : 0; @endphp
    <table class="kpi-table" style="margin-bottom:6px;">
        <tr>
            <td class="kpi-label">Total Ventas:</td>
            <td class="kpi-value" style="color:#1a7a3a;">$ {{ number_format($totalSales, 2) }}</td>
            <td width="20"></td>
            <td class="kpi-label">Total Costo:</td>
            <td class="kpi-value">$ {{ number_format($totalCosto, 2) }}</td>
            <td width="20"></td>
            <td class="kpi-label">Utilidad:</td>
            <td class="kpi-value" style="color:#1a7a3a;">$ {{ number_format($utilidadTotal, 2) }}</td>
            <td width="20"></td>
            <td class="kpi-label">Margen:</td>
            <td class="kpi-value">{{ $margenTotal }} %</td>
        </tr>
    </table>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th style="width:30%;">Sucursal</th>
                <th class="text-center" style="width:15%;">Caja</th>
                <th class="text-right" style="width:15%;">Total Ventas</th>
                <th class="text-right" style="width:15%;">Total Costo</th>
                <th class="text-right" style="width:15%;">Utilidad</th>
                <th class="text-right" style="width:10%;">% Utilidad</th>
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
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL GENERAL</td>
                <td class="text-right">$ {{ number_format($totalSales, 2) }}</td>
                <td class="text-right">$ {{ number_format($totalCosto, 2) }}</td>
                <td class="text-right">$ {{ number_format($utilidadTotal, 2) }}</td>
                <td class="text-right">{{ $margenTotal }} %</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
