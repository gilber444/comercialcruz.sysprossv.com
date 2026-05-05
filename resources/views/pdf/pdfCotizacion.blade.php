<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $empresa->razon }} — Cotización: {{ $cotizacion->correlativo }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { padding: 0; vertical-align: middle; }
        hr { border: none; border-top: 1px solid #233446; margin: 6px 0; }
        .title    { font-size: 14px; font-weight: bold; text-align: center; margin: 4px 0; text-transform: uppercase; }
        .subtitle { font-size: 10px; text-align: center; color: #444; margin-bottom: 4px; }
        .info-box { border: 1px solid #ddd; padding: 8px; margin-bottom: 10px; font-size: 11px; }
        .info-box b { font-weight: bold; }
        .table-detalle { margin-bottom: 4px; }
        .table-detalle th, .table-detalle td { border: 1px solid #ddd; padding: 5px; }
        .table-detalle thead th { background-color: #233446; color: #fff; text-align: center; }
        .table-detalle tbody tr:nth-child(even) { background: #f9f9f9; }
        .table-footer td { text-align: right; font-weight: bold; padding: 3px 5px; }
        .table-firma td { text-align: center; padding: 3px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        p { margin: 4px 0; line-height: 1.4; }
        .footer-notes { font-size: 10px; color: #555; margin-top: 8px; }
    </style>
</head>
<body>

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td width="20%" class="text-center">
                <img src="{{ $imagenUrl }}" alt="Logo" width="110" height="65">
            </td>
            <td width="55%" class="text-center">
                <div style="font-size:14px; font-weight:bold;">{{ $empresa->razon }}</div>
                <div style="font-size:11px;">{{ $empresa->empresa }}</div>
                <div style="font-size:10px;">NIT: {{ $empresa->nit }} &nbsp;|&nbsp; NRC: {{ $empresa->registro }}</div>
                <div style="font-size:10px;">{{ $empresa->giro }}</div>
                <div style="font-size:10px;">{{ $empresa->direccion }}</div>
                <div style="font-size:10px;">Tel. {{ $empresa->telefono }} &nbsp;|&nbsp; {{ $empresa->correo }}</div>
            </td>
            <td width="25%" class="text-center">
                <div style="font-size:11px; font-weight:bold;">Cotización N°</div>
                <div style="font-size:16px; font-weight:bold; color:#233446;">{{ $cotizacion->correlativo }}</div>
                <img src="{{ $qrCode }}" alt="QR" width="70" height="70">
            </td>
        </tr>
    </table>
    <hr>

    {{-- INFO CLIENTE --}}
    <div class="info-box">
        <b>Cliente:</b> {{ $cotizacion->Rcliente->nombreCliente }}
        &nbsp;|&nbsp;
        <b>Dirección:</b> {{ $cotizacion->Rcliente->direccion }}
        &nbsp;|&nbsp;
        <b>Fecha:</b> {{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}
    </div>

    <div style="font-size:12px; font-weight:bold; margin-bottom:4px;">Detalle de la Cotización</div>
    <table class="table-detalle">
        <thead>
            <tr>
                <th width="8%" class="text-center">Cantidad</th>
                <th width="50%">Detalle</th>
                <th width="10%" class="text-center">Presentación</th>
                <th width="14%" class="text-right">Precio</th>
                <th width="18%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalleCotizacion as $detalle)
            <tr>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td>{{ $detalle->nombreProducto }}</td>
                <td class="text-center">{{ $detalle->unidad }}</td>
                <td class="text-right">$ {{ number_format(($detalle->precio ?? 0) / ($cotizacion->facturador == 3 ? 1.13 : 1), 2) }}</td>
                <td class="text-right">$ {{ number_format(($detalle->total ?? 0) / ($cotizacion->facturador == 3 ? 1.13 : 1), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="table-footer" style="margin-top:4px;">
        <tr>
            <td>Subtotal</td>
            <td width="15%">$ {{ number_format(($cotizacion->subtotal ?? 0) / ($cotizacion->facturador == 3 ? 1.13 : 1), 2) }}</td>
        </tr>
        <tr>
            <td>IVA 13%</td>
            <td>$ {{ number_format($cotizacion->facturador == 3 ? $cotizacion->iva : 0, 2) }}</td>
        </tr>
        <tr>
            <td>Percepción 1%</td>
            <td>$ {{ number_format($cotizacion->facturador == 2 ? $cotizacion->percecion : 0, 2) }}</td>
        </tr>
        <tr>
            <td style="font-size:12px;">Total</td>
            <td style="font-size:12px;">$ {{ number_format($cotizacion->total, 2) }}</td>
        </tr>
    </table>

    <div class="footer-notes">
        <p>*** Precios sujetos a cambios sin previo aviso</p>
        @if($cotizacion->facturador == 3)
        <p>*** Precios en dólares no incluyen IVA</p>
        @else
        <p>*** Precios en dólares incluyen IVA</p>
        @endif
        <p>*** {{ $cotizacion->observaciones }}</p>
    </div>

    <br><br><br>
    <table class="table-firma">
        <tr>
            <td>
                <p>_____________________________________</p>
                <p>{{ $cotizacion->Rvendedor->name }}</p>
            </td>
        </tr>
    </table>

</body>
</html>
