<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Arqueos</title>
    <style>
        body  { font-family: sans-serif; font-size: 9px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 4px; }
        .header-table td { padding: 0; vertical-align: middle; }
        .title    { font-size: 13px; font-weight: bold; text-align: center; margin: 6px 0 2px; text-transform: uppercase; }
        .subtitle { font-size: 10px; text-align: center; margin-bottom: 6px; color: #444; }
        thead th  { background-color: #233446; color: #fff; font-size: 9px; text-align: center; padding: 4px; }
        tbody tr:nth-child(even) { background: #f5f5f5; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .total-row   { background: #233446; color: #fff; font-weight: bold; }
        .total-row td { padding: 3px 4px; }
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

    <div class="title">Reporte de Arqueos</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursales }}</b>
        &nbsp;|&nbsp; Caja: <b>{{ $cajas }}</b>
        &nbsp;|&nbsp; Cajero: <b>{{ $cajeros }}</b>
    </div>
    <hr>

    {{-- RESUMEN KPIs --}}
    @php
        $sumEfectivo    = $data->sum('efectivo');
        $sumTarjetas    = $data->sum('tarjeta');
        $sumVentas      = $data->sum('totalVentas');
        $sumDiferencia  = $data->sum('diferencia');
        $sumEntregado   = $data->sum(fn($d) => $d->totalEfectivo + $d->remesas);
    @endphp
    <table class="kpi-table" style="margin-bottom:6px;">
        <tr>
            <td class="kpi-label">Total Ventas:</td>
            <td class="kpi-value">$ {{ number_format($sumVentas, 2) }}</td>
            <td width="20"></td>
            <td class="kpi-label">Total Efectivo:</td>
            <td class="kpi-value">$ {{ number_format($sumEfectivo, 2) }}</td>
            <td width="20"></td>
            <td class="kpi-label">Total Tarjetas:</td>
            <td class="kpi-value">$ {{ number_format($sumTarjetas, 2) }}</td>
            <td width="20"></td>
            <td class="kpi-label">Diferencia:</td>
            <td class="kpi-value" style="color:{{ $sumDiferencia < 0 ? '#c0392b' : '#1a7a3a' }};">
                $ {{ number_format($sumDiferencia, 2) }}
            </td>
        </tr>
    </table>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:7%;">N°</th>
                <th class="text-center" style="width:9%;">Fecha</th>
                <th class="text-center" style="width:9%;">Hora</th>
                <th style="width:10%;">Caja</th>
                <th style="width:10%;">Cajero</th>
                <th class="text-right" style="width:9%;">Efectivo</th>
                <th class="text-right" style="width:9%;">Tarjetas</th>
                <th class="text-right" style="width:9%;">V. Total</th>
                <th class="text-right" style="width:9%;">Remesas</th>
                <th class="text-right" style="width:9%;">Efect.</th>
                <th class="text-right" style="width:9%;">Entregado</th>
                <th class="text-right" style="width:9%;">Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $d)
            <tr>
                <td class="text-center">{{ $d->numero }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($d->hora)->format('g:i A') }}</td>
                <td>{{ $d->caja }}</td>
                <td>{{ $d->cajero }}</td>
                <td class="text-right">$ {{ number_format($d->efectivo, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->tarjeta, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->totalVentas, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->remesas, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->totalEfectivo, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->totalEfectivo + $d->remesas, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->diferencia, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTALES</td>
                <td class="text-right">$ {{ number_format($sumEfectivo, 2) }}</td>
                <td class="text-right">$ {{ number_format($sumTarjetas, 2) }}</td>
                <td class="text-right">$ {{ number_format($sumVentas, 2) }}</td>
                <td class="text-right">$ {{ number_format($data->sum('remesas'), 2) }}</td>
                <td class="text-right">—</td>
                <td class="text-right">$ {{ number_format($sumEntregado, 2) }}</td>
                <td class="text-right">$ {{ number_format($sumDiferencia, 2) }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
