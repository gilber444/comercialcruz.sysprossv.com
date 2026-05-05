<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario por Categoría</title>
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

    <div class="title">Reporte de Inventario por Categoría</div>
    <div class="subtitle">
        Sucursal: <b>{{ $sucursales }}</b>
        &nbsp;|&nbsp; Categoría: <b>{{ $categorias }}</b>
    </div>
    <hr>

    {{-- TABLA --}}
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:12%;">Código</th>
                <th class="text-center" style="width:13%;">Categoría</th>
                <th style="width:30%;">Descripción</th>
                <th class="text-center" style="width:8%;">Medida</th>
                <th class="text-center" style="width:14%;">Sucursal</th>
                <th class="text-right" style="width:11%;">Existencia</th>
                <th class="text-center" style="width:12%;">Conteo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $d)
            <tr>
                <td class="text-center">{{ $d->codebar3 }}</td>
                <td class="text-center">{{ $d->categoria }}</td>
                <td>{{ $d->nombreProducto }}</td>
                <td class="text-center">{{ $d->medida }}</td>
                <td class="text-center">{{ $d->sucursal }}</td>
                <td class="text-right">{{ number_format($d->existencia, 2) }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
