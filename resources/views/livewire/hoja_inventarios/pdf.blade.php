<!DOCTYPE html>

<html lang="es">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Detalle de la hoja de inventario</title>

</head>



<body
    style="font-family: sans-serif; margin: 0.5%; padding:0; margin-left: 0px; margin-top: 0px; margin-right: 0px; margin-bottom:0px;">

    <table style="width: 100%;" style="border-bottom: 1px solid #00000">

        <tr>

            <td width="12%" style="padding-right: 10px; border-bottom:1px solid #0000">

                <img src="{{ $imagenUrl }}" alt="" width="170" height="90" style="margin-bottom: 2px;">

            </td>

            <td width="88%">

                <p style="font-size: 14px; margin: 0; font-weight: bold;">{{ $empresa->empresa }}</p>

                <p style="font-size: 11px; margin: 1px 0; line-height: 1.2;">NIT:{{ $empresa->nit }}, NCR:
                    {{ $empresa->registro }}</p>

                <p style="font-size: 11px; margin: 1px 0; line-height: 1.2;">DIRECCION: {{ $empresa->direccion }},
                    TEL.{{ $empresa->telefono }}

                    EMAIL: {{ $empresa->correo }}

                    <br>

                    <b>SUCURSAL: {{ $sucursal->nombre }}</b>
                </p>

            </td>

    </table>

    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="font-size:10px; margin:1%;">

        <thead class="bg-dark text-white">

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Codigo</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Descripcion</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Medida</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                E. Anterior</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Cont. Fisico</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Diferencia</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Costo</th>

            <th
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">
                Total</th>

        </thead>

        <tbody>

            @foreach ($hojasDetalle as $h)
                <tr>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->codebar }}</td>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->nombre }}</td>

                    <td style="text-align: center; border: 1px solid #000000;">@php

                        $medida = DB::table('medidas')->where('id', $h->medida)->value('unidad');

                        echo $medida;

                    @endphp</td>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->cantidadAnterior }}</td>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->cantidadActual }}</td>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->diferencia }}</td>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->costo }}</td>

                    <td style="text-align: center; border: 1px solid #000000;">{{ $h->total }} /
                        {{ $h->total * 1.13 }}</td>
                </tr>

                <tr>
                    <td style="text-align: center; border: 1px solid #000000;">{{ $totalGeneral }} /
                        {{ $totalGeneral * 1.13 }}</td>
                </tr>
            @endforeach

        </tbody>

    </table>

    <script>
        window.onload = () => {

            window.print();

        };
    </script>



</body>

</html>
