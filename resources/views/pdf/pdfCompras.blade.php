<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras</title>
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
                <div style="font-size:12px; font-weight:bold;">{{ $empresa->empresa }}</div>
                <div>NIT: {{ $empresa->nit }} — NCR: {{ $empresa->registro }}</div>
                <div>{{ $empresa->direccion }} | Tel. {{ $empresa->telefono }}</div>
            </td>
        </tr>
    </table>
    <hr>

    <div class="title">Reporte de Compras</div>
    <div class="subtitle">
        Período: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        &nbsp;|&nbsp; Sucursal: <b>{{ $sucursales }}</b>
        &nbsp;|&nbsp; Proveedor: <b>{{ $proveedores }}</b>
    </div>
    <hr>

    {{-- RESUMEN KPIs --}}
    <table class="kpi-table" style="margin-bottom:6px;">
        <tr>
            <td class="kpi-label">Subtotal:</td>
            <td class="kpi-value">$ {{ number_format($subtotal, 2) }}</td>
            <td width="30"></td>
            <td class="kpi-label">IVA (13%):</td>
            <td class="kpi-value">$ {{ number_format($iva, 2) }}</td>
            <td width="30"></td>
            <td class="kpi-label">Percepción:</td>
            <td class="kpi-value">$ {{ number_format($percepcion, 2) }}</td>
            <td width="30"></td>
            <td class="kpi-label">Total General:</td>
            <td class="kpi-value" style="color:#1a7a3a;">$ {{ number_format($totales, 2) }}</td>
        </tr>
    </table>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:11%;">N° Control</th>
                <th class="text-center" style="width:11%;">Cód. Generación</th>
                <th class="text-center" style="width:8%;">Fecha</th>
                <th class="text-center" style="width:9%;">Tipo</th>
                <th style="width:21%;">Proveedor</th>
                <th class="text-center" style="width:5%;">Items</th>
                <th class="text-right" style="width:10%;">Subtotal</th>
                <th class="text-right" style="width:8%;">IVA</th>
                <th class="text-right" style="width:8%;">Percepción</th>
                <th class="text-right" style="width:9%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $d)
            <tr>
                <td class="text-center">{{ $d->correlativo }}</td>
                <td class="text-center">{{ $d->serie ?? '—' }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                <td class="text-center">{{ $d->facturadors }}</td>
                <td>{{ $d->nombre }}</td>
                <td class="text-center">{{ number_format($d->items, 0) }}</td>
                <td class="text-right">$ {{ number_format($d->subtotal, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->iva, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->percepcion, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right">TOTALES</td>
                <td class="text-right">$ {{ number_format($subtotal, 2) }}</td>
                <td class="text-right">$ {{ number_format($iva, 2) }}</td>
                <td class="text-right">$ {{ number_format($percepcion, 2) }}</td>
                <td class="text-right">$ {{ number_format($totales, 2) }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
