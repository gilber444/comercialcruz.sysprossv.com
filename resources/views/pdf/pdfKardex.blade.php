<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Kardex</title>
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
        .saldo-inicial td { background: #e3f2fd; font-weight: bold; }
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

    <div class="title">Kardex de Producto</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursal }}</b>
        &nbsp;|&nbsp; Producto: <b>{{ $producto }}</b>
        &nbsp;|&nbsp; Período: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
    </div>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:12%;">Fecha</th>
                <th style="width:46%;">Descripción</th>
                <th class="text-right" style="width:14%;">Entrada</th>
                <th class="text-right" style="width:14%;">Salida</th>
                <th class="text-right" style="width:14%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            {{-- Saldo Inicial --}}
            @if (!empty($inicial2) && $inicial2->count() > 0)
            <tr class="saldo-inicial">
                <td class="text-center">{{ \Carbon\Carbon::parse($desde)->subDay()->format('d/m/Y') }}</td>
                <td>SALDO INICIAL</td>
                <td class="text-right">—</td>
                <td class="text-right">—</td>
                <td class="text-right">{{ number_format($inicial2->first()->saldoCantidad, 2) }}</td>
            </tr>
            @endif

            @foreach ($kardes as $item)
            <tr>
                <td class="text-center">{{ $item->fecha }}</td>
                <td>{{ $item->descripcion }}</td>
                <td class="text-right">{{ number_format($item->ingresoCantidad, 2) }}</td>
                <td class="text-right">{{ number_format($item->egresoCantidad, 2) }}</td>
                <td class="text-right">{{ number_format($item->saldoCantidad, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
