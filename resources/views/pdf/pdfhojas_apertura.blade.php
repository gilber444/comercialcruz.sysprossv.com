<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario Consolidado por Apertura</title>
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
        .kpi-value    { font-weight: bold; font-size: 11px; color: #233446; }
        .summary-box  { border: 1px solid #233446; padding: 8px; margin-top: 10px; font-size: 10px; }
        .summary-box .row-line { border-bottom: 1px solid #ddd; padding: 4px 0; }
        .summary-box .row-line:last-child { border-bottom: none; }
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

    <div class="title">Inventario Consolidado por Apertura</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursal->nombre ?? '' }}</b>
        &nbsp;|&nbsp; Apertura: <b>#{{ $apertura->id }}</b>
    </div>
    <hr>

    {{-- RESUMEN KPIs --}}
    <table class="kpi-table" style="margin-bottom:6px;">
        <tr>
            <td class="kpi-label">Total General:</td>
            <td class="kpi-value">$ {{ number_format($totalGeneral, 2) }}</td>
            <td width="15"></td>
            <td class="kpi-label">Inv. Anterior:</td>
            <td class="kpi-value">$ {{ number_format($totalinventarioAnterior, 2) }}</td>
            <td width="15"></td>
            <td class="kpi-label">Conteo Físico:</td>
            <td class="kpi-value">$ {{ number_format($apertura->total_fisico, 2) }}</td>
            <td width="15"></td>
            <td class="kpi-label">Diferencia:</td>
            <td class="kpi-value" style="color:{{ $totalDiferencia < 0 ? '#c0392b' : '#1a7a3a' }};">
                $ {{ number_format($totalDiferencia, 2) }}
            </td>
        </tr>
    </table>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:9%;">Código</th>
                <th style="width:20%;">Descripción</th>
                <th class="text-center" style="width:6%;">Medida</th>
                <th class="text-right" style="width:8%;">E. Anterior</th>
                <th class="text-right" style="width:8%;">Cont. Físico</th>
                <th class="text-right" style="width:7%;">Diferencia</th>
                <th class="text-right" style="width:10%;">Dif. $ S/IVA</th>
                <th class="text-right" style="width:10%;">Dif. $ C/IVA</th>
                <th class="text-right" style="width:8%;">Costo</th>
                <th class="text-right" style="width:7%;">Total</th>
                <th class="text-right" style="width:7%;">Total C/IVA</th>
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
                <td class="text-right">@php
                    $dife = $h->diferencia > 0 ? '+ '.$h->diferencia : $h->diferencia;
                    echo $dife;
                @endphp</td>
                <td class="text-right">{{ number_format($h->diferencia * $h->costo, 2) }}</td>
                <td class="text-right">{{ number_format($h->diferencia * $h->costo * 1.13, 2) }}</td>
                <td class="text-right">{{ number_format($h->costo, 4) }}</td>
                <td class="text-right">{{ number_format($h->total, 2) }}</td>
                <td class="text-right">{{ number_format($h->total * 1.13, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right">TOTAL GENERAL</td>
                <td class="text-right">$ {{ number_format($totalGeneral, 2) }}</td>
                <td class="text-right">$ {{ number_format($totalGeneral * 1.13, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- RESUMEN DE DIFERENCIAS Y OBSERVACIONES --}}
    <table style="margin-top:12px;">
        <tr>
            <td width="50%" style="padding-right:6px; vertical-align:top;">
                <div class="summary-box">
                    <div style="font-weight:bold; font-size:11px; margin-bottom:6px; color:#233446;">Resumen de Diferencias</div>
                    <div class="row-line">
                        <span>Total Inventario Anterior:</span>
                        <span style="float:right; font-weight:bold;">$ {{ number_format($totalinventarioAnterior, 2) }} — C/IVA $ {{ number_format($totalinventarioAnterior * 1.13, 2) }}</span>
                    </div>
                    <div class="row-line">
                        <span>Total Conteo Físico:</span>
                        <span style="float:right; font-weight:bold;">$ {{ number_format($apertura->total_fisico, 2) }} — C/IVA $ {{ number_format($apertura->total_fisico * 1.13, 2) }}</span>
                    </div>
                    <div class="row-line">
                        <span>Diferencia:</span>
                        <span style="float:right; font-weight:bold;">$ {{ number_format($totalDiferencia, 2) }} — C/IVA $ {{ number_format($totalDiferencia * 1.13, 2) }}</span>
                    </div>
                </div>
            </td>
            <td width="50%" style="vertical-align:top;">
                <div class="summary-box">
                    <div style="font-weight:bold; font-size:11px; margin-bottom:6px; color:#233446;">Observaciones</div>
                    <div style="white-space:pre-wrap;">{{ $apertura->observacion }}</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
