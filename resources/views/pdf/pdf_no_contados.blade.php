<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos No Contados</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 5px; }
        .header-table td { padding: 0; vertical-align: middle; }
        .title { font-size: 13px; font-weight: bold; text-align: center; margin: 6px 0 2px; }
        .subtitle { font-size: 10px; text-align: center; margin-bottom: 6px; color: #444; }
        thead th { background-color: #233446; color: #fff; font-size: 10px; text-align: center; }
        tbody tr:nth-child(even) { background: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background: #233446; color: #fff; font-weight: bold; }
        .zero { color: #888; }
        .has-stock { color: #c00; font-weight: bold; }
        hr { border: none; border-top: 1px solid #233446; margin: 4px 0; }
    </style>
</head>
<body>
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
    <div class="title">PRODUCTOS NO CONTADOS EN INVENTARIO</div>
    <div class="subtitle">
        Apertura #{{ $apertura->id }} |
        Sucursal: {{ $sucursal->nombre ?? '—' }} |
        Fecha: {{ $apertura->fecha_apertura }}
        @if($apertura->fecha_cierre) | Cerrado: {{ $apertura->fecha_cierre }} @endif
    </div>
    <hr>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:130px">CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th class="text-right" style="width:110px">EXIST. SISTEMA</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productos as $p)
                <tr>
                    <td class="text-center">{{ $p->codebar ?? '—' }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td class="text-right {{ $p->existencia > 0 ? 'has-stock' : 'zero' }}">
                        {{ number_format($p->existencia, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="padding:4px 5px;">TOTAL PRODUCTOS NO CONTADOS</td>
                <td class="text-right" style="padding:4px 5px;">{{ $productos->count() }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
