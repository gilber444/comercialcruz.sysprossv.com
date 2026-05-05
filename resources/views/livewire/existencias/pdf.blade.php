<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Hoja de Traslado</title>
</head>

<body style="font-family: sans-serif; margin: 0.5%; padding:0; margin-left: 0px; margin-top: 0px; margin-right: 0px; margin-bottom:0px;">
    <table style="width: 100%;" style="border-bottom: 1px solid #00000">
        <tr>
            <td width="12%" style="padding-right: 10px; border-bottom:1px solid #0000">
                <img src="{{ $imagenUrl }}" alt="" width="170" height="90" style="margin-bottom: 2px;">
            </td>
            <td width="88%">
                <p style="font-size: 14px; margin: 0; font-weight: bold;">{{ $empresa->empresa }}</p>
                <p style="font-size: 11px; margin: 1px 0; line-height: 1.2;">NIT:{{ $empresa->nit }}, NCR: {{ $empresa->registro }}</p>
                <p style="font-size: 11px; margin: 1px 0; line-height: 1.2;">DIRECCION: {{ $empresa->direccion }}, TEL.{{ $empresa->telefono }}
                    EMAIL: {{ $empresa->correo }}</p>
            </td>
    </table>
    <table style="width: 100%; border: 1px; font-size: 12px;">
        <tr>
            <td style="text-align:center;" colspan="2">
                <h2>HOJA DE TRASLADO DE PRODUCTOS</h3>
            </td>
        </tr>
        <tr>
            <td style="text-align:center;">
                SUCURSAL QUE ENTREGA: {{ $encabezado->Rorigen->nombre }}
            </td>
            <td style="text-align:center;">
                SUCURSAL QUE RECIBE: {{ $encabezado->Rdestino->nombre }}
            </td>
        </tr>
        <tr>
            <td style="text-align:center;">
                FECHA DE SOLICITUD: {{ \Carbon\Carbon::parse($encabezado->fecha)->format('d/m/Y') }}
            </td>
            <td style="text-align:center;">
                Numero: {{ $encabezado->numero }}, {{ $encabezado->estado }}
            </td>
        </tr>
    </table>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="font-size:13px; margin:1%;">
        <thead class="bg-dark text-white">
            <th  style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Codigo de Barra</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Descripcion del Producto</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Medida</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Cantidad</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Costo</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Total</th>
        </thead>
        <tbody>
            @foreach ($data as $d)
            <tr>
                <td style="border: 1px solid #000000;">{{ optional($d->Rproducto)->codebar3 }}</td>
                <td style="border: 1px solid #000000;">{{ optional($d->Rproducto)->nombreProducto }}</td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->unidad }}
                </td>

                <td style="text-align: center; border: 1px solid #000000;">{{ $d->cantidad }}</td>
                <td style="text-align: center; border: 1px solid #000000;">$ {{ number_format($d->costo * 1.13, 2) }}</td>
                <td align="right" style="border: 1px solid #000000;">
                    $ {{ number_format($d->total * 1.13, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <td align="right" colspan="5" style="border: 1px solid #000000;"><b>TOTALES</b></td>
            <td align="right" style="border: 1px solid #000000;"><b>$ {{ number_format($totales * 1.13, 2) }}</b></td>
        </tfoot>
    </table>
</body>
</html>
