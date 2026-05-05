<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Libro de Consumidor</title>
</head>

<body style="font-family: sans-serif; margin: 0.5%; padding:0; margin-left: 0px; margin-top: 0px; margin-right: 0px; margin-bottom:0px;">
    <table style="width: 100%;" style="border-bottom: 1px solid #00000">
        <tr>
            <td width="12%" style="padding-right: 10px; border-bottom:1px solid #0000">
                <img src="{{ $imagenUrl }}" alt="" width="90" height="90" style="margin-bottom: 2px;">
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
                Libro de Invalidaciones
                <br>
                DESDE {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
                HASTA {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
            </th>
        </thead>
    </table>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="font-size:10px; margin:1%;">
        <thead class="bg-dark text-white">
            <th  style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Numero de Resolucion</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Clase Doc.</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Desde</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Hasta</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Tipo Documento</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Tipo Detalle</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Serie</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Desde</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Hasta</th>
            <th style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #233446;color: white; padding: 5px;">Codigo Generacion</th>
        </thead>
        <tbody>
            @foreach ($data as $d)

            <tr>

                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->numeroControl }}
                </td>
                 <td style="text-align: center; border: 1px solid #000000;">
                    4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    0
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    0
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ $d->RtipoDte->codigo}} {{ $d->RtipoDte->valor}}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    DocumentoDTE Invalidado
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ str_replace('-', '', $d->sello) }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    0
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    0
                </td>
                <td style="text-align: center; border: 1px solid #000000;">
                    {{ str_replace('-', '', $d->codigoGeneracion) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <table width="100%" style="text-align: center; font-size: 10px; font-weight: bold; with: 100%">
        <tr>
            <td>
                {{--<table>
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
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentasNetas, 2) -}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($debitoFiscal, 2) -}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentas, 2) -}}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold;">
                            VENTAS EXENTAS
                        </td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentasNetas, 2) -}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($debitoFiscal, 2) -}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentas, 2) -}}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold;">
                            IMP. RETENIDOS
                        </td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentasNetas, 2) -}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($debitoFiscal, 2) -}}</td>
                        <td style="font-size: 10px; font-weight: bold; text-align: right;">$ {{-- number_format($totalVentas, 2) -}}</td>
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
                </table>--}}
            </td>
            <td style="text-align: center; font-size: 10px; font-weight: bold;">
                <table width="100%">
                    <tr>
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">
                            <br><br><br><br><br>
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
