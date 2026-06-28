<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Libro de Contribuyente</title>
</head>

<body style="font-family: sans-serif; margin: 0.5%; padding:0; margin-left: 0px; margin-top: 0px; margin-right: 0px; margin-bottom:0px;">
    <table style="width: 100%;" style="border-bottom: 1px solid #00000">
        <tr>
            <td width="12%" style="padding-right: 10px; border-bottom:1px solid #0000">
                <img src="{{ $imagenUrl }}" alt="" width="170" height="90" style="margin-bottom: 2px;">
            </td>
            <td width="88%">
                <p style="font-size: 14px; margin: 0; font-weight: bold;">{{ $empresa->empresa }} / {{ $empresa->razon }}</p>
                <p style="font-size: 11px; margin: 1px 0; line-height: 1.2;">NIT:{{ $empresa->nit }}, NCR: {{ $empresa->registro }}</p>
                <p style="font-size: 11px; margin: 1px 0; line-height: 1.2;">DIRECCION: {{ $empresa->direccion }}, TEL.{{ $empresa->telefono }}
                    EMAIL: {{ $empresa->correo }}</p>
            </td>
    </table>
    <table style="width: 100%; border: 1px; ">
        <thead>
            <th style="text-align:center;">
                Libro de Contribuyentes
                <br>
                DESDE {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
                HASTA {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
            </th>
        </thead>
    </table>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="font-size:10px; margin:1%;">
        <thead class="bg-dark text-white">
            <th  style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Fecha Emi.</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Clase Doc.</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Tipo Doc.</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Num. Resolución</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Serie</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">DEL</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">AL</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">NIT o NRC</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Nombre, Razon Social</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Ventas Gravadas</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Debito Fiscal</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Total Ventas</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">DUI Cliente</th>
        </thead>
        <tbody>
            @foreach ($data as $d)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ \Carbon\Carbon::parse( $d->fecha)->format('d/m/Y') }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    03.COMPROBANTE DE CREDITO FISCAL
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->codigo }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->codigo }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->numero }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->numero }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ (isset($d->Rclientes->nit) && strlen($d->Rclientes->nit) === 14)
                        ? $d->Rclientes->nit
                        : '' }}
                </td>
                <td style="border: 1px solid #000000;">
                    {{ $d->Rclientes->nombreCliente }}
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    $ {{ number_format(($d->total / 1.13) , 2)}}
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    $ {{ number_format($d->total -($d->total / 1.13) , 2)}}
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    $ {{ number_format($d->total, 2)}}
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    {{ (isset($d->Rclientes->nit) && strlen($d->Rclientes->nit) === 9)
                        ? $d->Rclientes->nit
                        : '' }}
                </td>
            </tr>
            @endforeach
            <tr>
                <td colspan="8" style="text-align: right; border: 1px solid #000000;"><b>TOTALES</b></td>
                <td style="text-align: right; border: 1px solid #000000;">
                    <b>
                        {{-- '$ ' . number_format($totalVentasExentas, 2) --}}
                    </b>
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    <b>
                        {{ '$ ' . number_format($totalVentas / 1.13, 2) }}
                    </b>
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    <b>
                        {{ '$ ' . number_format($totalVentas - ($totalVentas/1.13), 2) }}
                    </b>
                </td>
                <td style="text-align: right; border: 1px solid #000000;">
                    <b>
                        {{ '$ ' . number_format($totalVentas, 2) }}
                    </b>
                </td>
                <td style="text-align: right; border: 1px solid #000000;">

                </td>
            </tr>
        </tbody>
    </table>
    <table width="100%" style="text-align: center; font-size: 10px; font-weight: bold; with: 100%">
        <tr>
            <td>
                <table>
                    <tr>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">
                            RESUMEN DE OPERACIONES
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">
                            DETALLE
                        </td>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">TOTAL VTAS. NETAS</td>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">DEBITO FISCAL</td>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">TOTAL VENTAS</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold;">
                            VENTAS NO SUJETAS
                        </td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentasNetas, 2) --}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($debitoFiscal, 2) --}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentas, 2) --}}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold;">
                            VENTAS EXENTAS
                        </td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentasNetas, 2) --}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($debitoFiscal, 2) --}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentas, 2) --}}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold;">
                            IMP. RETENIDOS
                        </td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentasNetas, 2) --}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($debitoFiscal, 2) --}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentas, 2) --}}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold;">
                            VENTAS GRAVADAS
                        </td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$
                            {{ number_format($totalVentas, 2) }}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$
                            {{ number_format($totalVentas - $totalVentas / 1.13, 2) }}</td>
                        <td style=" font-size: 10px; font-weight: bold;text-align: right;">$
                            {{ number_format($totalVentas, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="text-align: center; font-size: 10px; font-weight: bold;">
                <table width="100%">
                    <tr>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">
                            ___________________________________
                        </td>
                    </tr>
                </table>
                <table width="100%">
                    <tr>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; F. Contador</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
