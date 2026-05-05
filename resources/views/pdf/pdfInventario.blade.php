<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
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

    <div class="title">Reporte de Inventario</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursales }}</b>
    </div>
    <hr>

    {{-- RESUMEN KPIs --}}
    <table class="kpi-table" style="margin-bottom:6px;">
        <tr>
            <td class="kpi-label">Total Inventario (S/IVA):</td>
            <td class="kpi-value">$ {{ number_format((float)$totales, 2) }}</td>
            <td width="30"></td>
            <td class="kpi-label">Total Inventario (C/IVA):</td>
            <td class="kpi-value" style="color:#1a7a3a;">$ {{ number_format((float)$totales * 1.13, 2) }}</td>
        </tr>
    </table>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:11%;">Código</th>
                <th class="text-center" style="width:12%;">Categoría</th>
                <th style="width:27%;">Descripción</th>
                <th class="text-center" style="width:7%;">Medida</th>
                <th class="text-center" style="width:13%;">Sucursal</th>
                <th class="text-right" style="width:10%;">Costo</th>
                <th class="text-right" style="width:10%;">Total</th>
                <th class="text-right" style="width:10%;">Existencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
            <tr>
                <td class="text-center">{{ $row->codebar3 }}</td>
                <td class="text-center">{{ $row->categoria }}</td>
                <td>{{ $row->nombreProducto }}</td>
                <td class="text-center">{{ $row->medida }}</td>
                <td class="text-center">{{ $row->sucursal }}</td>
                <td class="text-right">$ {{ number_format((float)$row->costosiva, 4) }}</td>
                <td class="text-right">$ {{ number_format((float)$row->total_costo, 2) }}</td>
                <td class="text-right">{{ number_format((float)$row->existencia, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right">TOTALES</td>
                <td class="text-right">$ {{ number_format((float)$totales, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
