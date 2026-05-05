<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Corte Z</title>
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

    <div class="title">Reporte de Corte Z</div>
    <div class="subtitle">
        Período: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        &nbsp;|&nbsp; Sucursal: <b>{{ $sucursales }}</b>
        &nbsp;|&nbsp; Caja: <b>{{ $cajas }}</b>
    </div>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:12%;">Número</th>
                <th class="text-center" style="width:12%;">Fecha</th>
                <th class="text-center" style="width:12%;">Hora</th>
                <th style="width:20%;">Caja</th>
                <th class="text-right" style="width:15%;">Venta Total</th>
                <th class="text-right" style="width:15%;">Efectivo</th>
                <th class="text-right" style="width:14%;">Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $d)
            <tr>
                <td class="text-center">{{ $d->corte }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($d->hora)->format('g:i:s A') }}</td>
                <td>{{ $d->caja }}</td>
                <td class="text-right">$ {{ number_format($d->totalGlobal * 0.6, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->totalEfectivo * 0.6, 2) }}</td>
                <td class="text-right">$ {{ number_format($d->diferencia, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
