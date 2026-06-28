
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DTE - {{ $dte->codigoGeneracion }}</title>
</head>

<body
    style="font-family: sans-serif; margin: 0.5%; padding:0; margin-left: 0px; margin-top: 0px; margin-right: 0px; margin-bottom:0px;">
    <table style="width: 100%; border: 1px; ">
        <thead>
            <th style="text-align:center;">
                DOCUMENTO TRIBUTARIO ELECTRÓNICO
                <br>
                @if ($dte->tipoDte == 1)
                    FACTURA
                @elseif ($dte->tipoDte == 2)
                    COMPROBANTE DE CREDITO FISCAL
                @elseif ($dte->tipoDte == 4)
                    NOTA DE CREDITO
                @endif
            </th>
        </thead>
    </table>
    <table style="width: 100%; font-size:10px;">
        <tr>
            <td width="40%" style="padding-right: 10px;">
                <img src="{{ $imagenUrl }}" alt="" width="150" height="90" style="margin-bottom: 2px;">
                <p style="margin: 0.5;">Código de Generación: <br>{{ $dte->codigoGeneracion }}</p>
                <p style="margin: 0.5;">Número de Control: <br>{{ $dte->numeroControl }}</p>
                <p style="margin: 0.5;">Sello de Recepción: <br>{{ $dte->sello }}</p>
            </td>
            <td width="23%" align="center">
                <img src="{{ $qrCode }}" alt="Código QR" width="100px" height="100px">
            </td>
            <td width="37%">
                <p style="margin: 0.5;">Módelo de Facturación: {{ $dte->modelo }}</p>
                <p style="margin: 0.5;">Tipo de Transmisión: {{ $dte->tipo }}</p>
                <p style="margin: 0.5;">Fecha y Hora de Generación: {{ $dte->fecEmi }} {{ $dte->horEmi }}</p>
            </td>
        </tr>
    </table>
    <table width="100%" cellpadding="0" cellspacing="0" style="font-size:10px; margin:0.5%;">
        <tr>
            <td width="49%">
                <div style="border: 1px solid black; border-radius:5px; padding:5px; height: 180px;">
                    <h5 style="text-align: center; margin: 2px;">EMISOR</h5>
                    <p style="margin: 2px 0;"><b>Nombre o razón social:</b> {{ $empresa->empresa }} </p>
                    <p style="margin: 2px 0;"><b>NIT:</b> {{ $empresa->nit }}</p>
                    <p style="margin: 2px 0;"><b>NRC:</b> {{ $empresa->registro }}</p>
                    <p style="margin: 2px 0;"><b>Actividad económica:</b> {{ $empresa->actiEco }}</p>
                    <p style="margin: 2px 0;"><b>Dirección:</b> {{ $sucursal->direccion }}</p>
                    <p style="margin: 2px 0;"><b>Número de teléfono:</b> {{ $sucursal->telefono }}</p>
                    <p style="margin: 2px 0;"><b>Correo Electrónico:</b> {{ $empresa->correo }}</p>
                    <p style="margin: 2px 0;"><b>Nombre Comercial:</b> {{ $empresa->razon }}</p>
                    <p style="margin: 2px 0;"><b>Tipo de establecimiento:</b> {{ $sucursal->establecimiento }}</p>
                </div>
            </td>
            <td width="2%" align="center"></td>
            <td width="49%">
                <div style="border: 1px solid black; border-radius:5px; padding:5px; height: 180px;">
                    <h5 style="text-align: center; margin: 2px;">RECEPTOR</h5>
                    <p style="margin: 2px 0;"><b>Nombre o razón social:</b> {{ $venta->nombreCliente }}</p>
                    <p style="margin: 2px 0;"><b>Tipo de doc. de Identificación:</b> {{ $venta->identificacion }}</p>
                    <p style="margin: 2px 0;"><b>N° de doc. de Identificación:</b>
                        @php
                            if ($venta->identificacion == 'DUI') {
                                echo $venta->dui;
                            } else {
                                echo $venta->nit;
                            }
                        @endphp
                    </p>
                    <p style="margin: 2px 0;"><b>NRC:</b> {{ $venta->registro }}</p>
                    @php
                        if ($venta->identificacion == 'NIT') {
                            echo "<p style='margin: 2px 0;'><b>Giro:</b> $venta->desActividad</p>";
                        }
                    @endphp
                    <p style="margin: 2px 0;"><b>Correo electrónico:</b> {{ $venta->email }}</p>
                    <p style="margin: 2px 0;"><b>Dirección:</b> {{ $venta->direccion }}</p>
                </div>
            </td>
        </tr>
    </table>

    @if ($dte->tipoDte == 4)
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:10px; margin:0.5%;">
            <tr>
                <td style="width: 100%;">
                    <div style="border: 1px solid black; border-radius:5px; padding:5px;">
                        <h5 style="text-align: center; margin: 2px;">DOCUMENTO RELACIONADO</h5>
                        <div style="display: flex; justify-content: space-between;">
                            <div><b>Tipo de Documento:</b> {{ $dte2->RtipoDte->codigo ?? '' }}
                                {{ $dte2->RtipoDte->valor ?? '' }}
                            </div>
                            <div><b>No. Documento: </b> {{ $dte2->codigoGeneracion ?? '' }}</div>
                            <div><b>Fecha del Documento: </b>{{ $dte2->fecEmi ?? '' }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    @endif


    <table width="100%" border="1" cellpadding="0" cellspacing="0" style="font-size:10px; margin:1%;">
        <tr>
            <td width="3%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">N°
            </td>
            <td width="9%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Código</td>
            <td width="9%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Cantidad</td>
            <td width="7%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Unidad</td>
            <td width="43%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Descripción</td>
            <td width="6%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Precio <br> Unitario</td>
            <td width="6%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Descuento <br> por item</td>
            <td width="6%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Ventas No <br> Sujetas</td>
            <td width="6%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Ventas Exentas</td>
            <td width="6%" align="center"
                style="text-align: center; border: 1px solid #000000; font-weight: bold; background-color: #CCCCCC;">
                Ventas Gravadas</td>
        </tr>
        @php
            $i = 1;
        @endphp
        @foreach ($detalleVentas as $detalle)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $i++ }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $detalle->codebar3 }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $detalle->cantidad }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $detalle->unidad }}</td>
                <td style="border: 1px solid #000000;">{{ $detalle->name }}</td>
                <td style="text-align: right; border: 1px solid #000000;">$
                    @if ($dte->tipoDte == 1)
                        {{ number_format($detalle->precio, 2) }}
                    @elseif ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                        {{ number_format($detalle->precio / 1.13, 2) }}
                    @endif
                </td>
                <td style="text-align: right; border: 1px solid #000000;">$
                    @if ($dte->tipoDte == 1)
                        {{ number_format($detalle->descuento, 2) }}
                    @elseif ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                        {{ number_format($detalle->descuento / 1.13, 2) }}
                    @endif
                </td>
                <td style="text-align: right; border: 1px solid #000000;">$ 0.00</td>
                <td style="text-align: right; border: 1px solid #000000;">$ 0.00</td>
                <td style="text-align: right; border: 1px solid #000000;">$
                    @if ($dte->tipoDte == 1)
                        {{ number_format($detalle->total, 2) }}
                    @elseif ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                        {{ number_format($detalle->total - $detalle->iva, 2) }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    <table width="100%" cellpadding="1" cellspacing="1" style="font-size:10px; margin:1%;">
        <tr>
            <td style="width: 60%;">
                <div
                    style="border: 1px solid black; border-radius:5px; @if ($dte->tipoDte == 2 || $dte->tipoDte == 4) height: 170px; @else height: 158px; @endif">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size:10px;">
                        <tr>
                            <td>
                                <p style="margin: 0.5;"><b>Valor en Letras:</b> {{ $resumen->totalLetras }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="margin: 0.5;"><b>Condición de la Operación:</b> {{ $resumen->condicion }}
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="margin: 0.5;"><b>Observaciones::</b></p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table width="100%" border="1" cellpadding="0" cellspacing="0"
                                    style="font-size:10px; border: 1px solid black;">
                                    <tr>
                                        <td colspan="4"
                                            style="text-align: center; width: 40%; background-color: #CCCCCC;" border:
                                            1px solid black;">
                                            <b>EXTENCIÓN</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid black;">Nombre Entrega</td>
                                        <td style="width:30%; border: 1px solid black;"">&nbsp;</td>
                                        <td style="border: 1px solid black;">No Documento</td>
                                        <td style="width:30%; border: 1px solid black;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid black;">Nombre Recibe</td>
                                        <td style="border: 1px solid black;">&nbsp;</td>
                                        <td style="border: 1px solid black;">No Documento</td>
                                        <td style="border: 1px solid black;">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 40%;">
                <div
                    style="border: 1px solid black; border-radius:5px; @if ($dte->tipoDte == 2 || $dte->tipoDte == 4) height: 170px; @else height: 158px; @endif ">
                    <table width="100%" border="1" cellpadding="0" cellspacing="0"
                        style="font-size:10px; border: 1px solid black;">
                        <tr>
                            <td style="text-align: center; width: 40%; background-color: #CCCCCC;" colspan="2">SUMA
                                TOTAL DE OPERACIONES:</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Ventas no Sujetas:</td>
                            <td style="text-align: right; width: 10%;">$ {{ number_format($resumen->totaNoSuj, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Total Gravada</td>
                            <td style="text-align: right; width: 10%;">
                                @if ($dte->tipoDte == 1)
                                    $ {{ number_format($resumen->totalGravada, 2) }}
                                @elseif ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                                    $ {{ number_format($resumen->totalGravada - $resumen->totalIva, 2) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Monto global Descuento, Bonificación, Rebajas y
                                otros a ventas gravadas:</td>
                            <td style="text-align: right; width: 10%;">$ @if ($dte->tipoDte == 1)
                                    {{ number_format($resumen->totalDescu, 2) }}
                                @elseif ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                                    {{ number_format($resumen->totalDescu / 1.13, 2) }}
                                @endif

                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Sumatoria de Ventas:</td>
                            <td style="text-align: right; width: 10%;">
                                @if ($dte->tipoDte == 1)
                                    $ {{ number_format($resumen->subTotalVentas, 2) }}
                                @elseif($dte->tipoDte == 2 || $dte->tipoDte == 4)
                                    $ {{ number_format($resumen->subTotalVentas - $resumen->totalIva, 2) }}
                                @endif
                            </td>
                        </tr>
                        @if ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                            <tr>
                                <td style="text-align: left; width: 40%;">IVA:</td>
                                <td style="text-align: right; width: 10%;">
                                    $ {{ number_format($resumen->totalIva, 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="text-align: left; width: 40%;">Sub-Total:</td>
                            <td style="text-align: right; width: 10%;">$
                                {{ number_format($resumen->subTotalVentas, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">IVA Retenido:</td>
                            <td style="text-align: right; width: 10%;">$ {{ number_format($resumen->ivaRete1, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Retención Renta:</td>
                            <td style="text-align: right; width: 10%;">$ {{ number_format($resumen->reteRenta, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Monto Total de la Operación:
                            </td>
                            <td style="text-align: right; width: 10%;">$
                                {{ number_format($resumen->montoTotalOperacion, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Total Otros Montos No Afectos:
                            </td>
                            <td style="text-align: right; width: 10%;">$ 0.00</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; width: 40%;">Total a Pagar:</td>
                            <td style="text-align: right; width: 10%;"> $ {{ number_format($resumen->totalPagar, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
