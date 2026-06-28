<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Hoja de Inventario</title>
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

    <div class="title">Detalle de Hoja de Inventario</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursal->nombre }}</b>
    </div>
    <hr>

    {{-- RESUMEN KPIs --}}
    <table class="kpi-table" style="margin-bottom:6px;">
        <tr>
            <td class="kpi-label">Total General:</td>
            <td class="kpi-value" style="color:#1a7a3a;">$ {{ number_format($totalGeneral, 2) }}</td>
        </tr>
    </table>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:10%;">Código</th>
                <th style="width:28%;">Descripción</th>
                <th class="text-center" style="width:8%;">Medida</th>
                <th class="text-right" style="width:11%;">E. Anterior</th>
                <th class="text-right" style="width:11%;">Cont. Físico</th>
                <th class="text-right" style="width:11%;">Diferencia</th>
                <th class="text-right" style="width:10%;">Costo</th>
                <th class="text-right" style="width:11%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($hojasDetalle as $h)
            <tr>
                <td class="text-center">{{ $h->codebar }}</td>
                <td>{{ $h->nombre }}</td>
                <td class="text-center">@php
                    $medida = DB::table('medidas')->where('id', $h->medida)->value('unidad');
                    echo $medida;
                @endphp</td>
                <td class="text-right">{{ $h->cantidadAnterior }}</td>
                <td class="text-right">{{ $h->cantidadActual }}</td>
                <td class="text-right">{{ $h->diferencia }}</td>
                <td class="text-right">{{ $h->costo }}</td>
                <td class="text-right">{{ $h->total }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAL GENERAL</td>
                <td class="text-right">$ {{ number_format($totalGeneral, 2) }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
