<!DOCTYPE html>

<html lang="es">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Detalle de Kardex</title>
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

    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="font-size:10px; margin:1%;">

        <thead class="bg-dark text-white">

            <th  style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Fecha</th>

            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Descripcion</th>

            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Entrada</th>

            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Salida</th>

            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Saldo</th>

        </thead>

        <tbody>

            {{-- @foreach ($inicial as $ini)

            <tr>

                <td style="text-align: center; border: 1px solid #000000;">{{ $ini->fecha }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ number_format($ini->ingresoCantidad, 2) }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ number_format($ini->egresoCantidad, 2) }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ number_format($ini->saldoCantidad, 2) }}</td>

            </tr>

            @endforeach --}}

            @foreach ($kardes as $item)

            <tr>

                <td style="text-align: center; border: 1px solid #000000;">{{ $item->fecha }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ $item->descripcion }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ number_format($item->ingresoCantidad, 2) }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ number_format($item->egresoCantidad, 2) }}</td>

                <td style="text-align: center; border: 1px solid #000000;">{{ number_format($item->saldoCantidad, 2) }}</td>

            </tr>

            @endforeach

        </tbody>

    </table>

</body>

</html>

