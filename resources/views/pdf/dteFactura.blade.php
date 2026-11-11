@php
    $paletaDte = app(\App\Services\DtePaletteService::class)->obtener((int) $empresa->id);
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DTE - {{ $dte->codigoGeneracion }}</title>
    <style>
        @page { margin: 22px; }

        body {
            font-family: sans-serif;
            font-size: 10px;
            color: {{ $paletaDte['texto'] }};
            background-color: {{ $paletaDte['fondo'] }};
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: {{ $paletaDte['encabezado'] }};
            border: 1px solid {{ $paletaDte['borde'] }};
            border-radius: 10px;
            padding: 14px 16px;
        }

        .eyebrow { color: {{ $paletaDte['texto_suave'] }}; font-size: 8px; letter-spacing: 2px; text-transform: uppercase; }
        .doc-title { color: {{ $paletaDte['texto'] }}; font-size: 18px; font-weight: bold; margin: 2px 0 6px; }

        .logo-box { background-color: {{ $paletaDte['fondo'] }}; border: 1px solid {{ $paletaDte['borde'] }}; border-radius: 8px; padding: 8px; text-align: center; }
        .qr-box { background-color: {{ $paletaDte['fondo'] }}; border: 1px solid {{ $paletaDte['borde'] }}; border-radius: 8px; padding: 6px; }

        .badge { border-radius: 10px; font-size: 8.5px; font-weight: bold; padding: 2px 8px; }
        .badge-ok { background-color: {{ $paletaDte['exito'] }}; color: #ffffff; }
        .badge-danger { background-color: {{ $paletaDte['peligro'] }}; color: #ffffff; }
        .badge-env { background-color: {{ $paletaDte['fondo'] }}; border: 1px solid {{ $paletaDte['borde'] }}; color: {{ $paletaDte['primario'] }}; }
        .badge-muted { background-color: {{ $paletaDte['texto_suave'] }}; border-radius: 10px; color: #ffffff; font-size: 8.5px; padding: 2px 8px; }

        .sect { color: {{ $paletaDte['primario'] }}; font-size: 7.5px; font-weight: bold; letter-spacing: 1px; margin-bottom: 2px; text-transform: uppercase; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; word-break: break-all; }

        .card { border: 1px solid {{ $paletaDte['borde'] }}; border-radius: 10px; }
        .card-head {
            background-color: {{ $paletaDte['encabezado'] }}; border-bottom: 1px solid {{ $paletaDte['borde'] }}; border-radius: 10px 10px 0 0;
            color: {{ $paletaDte['primario'] }}; font-size: 9px; font-weight: bold; letter-spacing: 1px; padding: 5px 10px; text-transform: uppercase;
        }
        .card-body { padding: 6px 10px; }
        .kv td { padding: 2px 0; vertical-align: top; font-size: 8px; }
        .kv .k { color: {{ $paletaDte['texto_suave'] }}; width: 27%; }
        .kv .v { font-weight: bold; word-break: break-word; }

        .block-title { color: {{ $paletaDte['primario'] }}; font-size: 9px; font-weight: bold; letter-spacing: 1px; margin: 12px 0 5px; text-transform: uppercase; }

        .items { width: 100%; border: 1px solid {{ $paletaDte['borde'] }}; border-collapse: collapse; }
        .items th {
            background-color: {{ $paletaDte['encabezado'] }}; color: {{ $paletaDte['primario'] }}; border-bottom: 1px solid {{ $paletaDte['borde'] }};
            font-size: 8.5px; font-weight: bold; padding: 5px 4px; text-align: center;
        }
        .items td { font-size: 9px; padding: 3px 4px; }

        .totals { width: 100%; }
        .totals td { font-size: 9px; padding: 3px 6px; }
        .totals .th { background-color: {{ $paletaDte['encabezado'] }}; color: {{ $paletaDte['primario'] }}; font-weight: bold; text-align: center; }
        .totals .lbl { color: {{ $paletaDte['texto'] }}; }
        .totals .amt { text-align: right; font-weight: bold; }

        .infobox { border: 1px solid {{ $paletaDte['borde'] }}; border-radius: 10px; padding: 6px 8px; }

        .ext td { border: 1px solid {{ $paletaDte['borde'] }}; font-size: 8.5px; padding: 3px 5px; }
        .ext .h { background-color: {{ $paletaDte['encabezado'] }}; color: {{ $paletaDte['primario'] }}; font-weight: bold; text-align: center; }
        .ext .k { background-color: {{ $paletaDte['encabezado'] }}; font-weight: bold; }

        .footer { bottom: 0; color: {{ $paletaDte['texto_suave'] }}; font-size: 8.5px; left: 0; position: fixed; right: 0; text-align: center; }
        .pagenum:before { content: counter(page) " de " counter(pages); }

        .watermark {
            position: fixed; top: 35%; left: 0; right: 0; text-align: center;
            font-size: 100px; font-weight: bold; color: {{ $paletaDte['peligro'] }}; opacity: 0.18;
            transform: rotate(-30deg); z-index: 0;
        }
    </style>
</head>

<body>

    @if($mostrarMarcaAgua)
        <div class="watermark">INVALIDADO</div>
    @endif

    <!-- Cabecera -->
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td width="28%" valign="middle">
                    <div class="logo-box">
                        @if(!empty($imagenUrl))
                            <img src="{{ $imagenUrl }}" alt="Logo" style="max-height:95px; max-width:200px;">
                        @else
                            <span style="color:{{ $paletaDte['primario'] }}; font-weight:bold; font-size:11px;">{{ $empresa->empresa }}</span>
                        @endif
                    </div>
                </td>
                <td width="38%" valign="middle" style="text-align:center;">
                    <div class="eyebrow">Documento Tributario Electrónico</div>
                    <div class="doc-title">
                        @if ($dte->tipoDte == 1) Factura
                        @elseif ($dte->tipoDte == 2) Comprobante de Crédito Fiscal
                        @elseif ($dte->tipoDte == 4) Nota de Crédito
                        @elseif ($dte->tipoDte == 10) Factura de Sujeto Excluido
                        @endif
                    </div>
                    @if($mostrarMarcaAgua)
                        <span class="badge badge-danger">INVALIDADO</span>
                    @endif
                    <span class="badge badge-env">{{ $dte->modelo }}</span>
                </td>
                <td width="34%" valign="middle">
                    <table align="right" cellpadding="2" cellspacing="0">
                        <tr>
                            @if(!empty($qrCode))
                                <td class="qr-box"><img src="{{ $qrCode }}" alt="QR del sistema" width="66" height="66" style="display:block;"></td>
                            @endif
                            @if(!empty($qrHacienda))
                                <td class="qr-box"><img src="{{ $qrHacienda }}" alt="QR de Hacienda" width="66" height="66" style="display:block;"></td>
                            @endif
                        </tr>
                        <tr>
                            @if(!empty($qrCode))
                                <td style="color:{{ $paletaDte['texto_suave'] }}; font-size:7px; text-align:center;">Ver PDF</td>
                            @endif
                            @if(!empty($qrHacienda))
                                <td style="color:{{ $paletaDte['texto_suave'] }}; font-size:7px; text-align:center;">Verificar en Hacienda</td>
                            @endif
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Metadatos -->
    <table style="width:100%; font-size:10px; margin-top:12px;">
        <tr>
            <td width="34%" valign="top" style="padding-right:8px;">
                <div class="sect">Código de Generación</div>
                <div class="mono">{{ $dte->codigoGeneracion }}</div>
                <div class="sect" style="margin-top:5px;">Número de Control</div>
                <div class="mono">{{ $dte->numeroControl }}</div>
            </td>
            <td width="38%" valign="top" style="padding-right:8px;">
                <div class="sect">Sello de Recepción</div>
                <div class="mono">{{ $dte->sello }}</div>
            </td>
            <td width="28%" valign="top">
                <div class="sect">Tipo de Transmisión</div>
                <div style="font-weight:bold;">{{ $dte->tipo }}</div>
                <div class="sect" style="margin-top:5px;">Fecha y Hora de Generación</div>
                <div style="font-weight:bold;">{{ $dte->fecEmi }} {{ $dte->horEmi }}</div>
            </td>
        </tr>
    </table>

    <!-- Emisor / Receptor -->
    <table style="width:100%; margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="49%" valign="top">
                <div class="card">
                    <div class="card-head">Emisor</div>
                    <div class="card-body">
                        <table class="kv" style="width:100%;">
                            <tr><td class="k">Razón social</td><td class="v">{{ $empresa->empresa }}</td></tr>
                            <tr><td class="k">NIT</td><td class="v">{{ $empresa->nit }}</td></tr>
                            <tr><td class="k">NRC</td><td class="v">{{ $empresa->registro }}</td></tr>
                            <tr><td class="k">Actividad económica</td><td class="v">{{ $empresa->actiEco }}</td></tr>
                            <tr><td class="k">Nombre comercial</td><td class="v">{{ $empresa->razon }}</td></tr>
                            <tr><td class="k">Dirección</td><td class="v">{{ $sucursal->direccion }}</td></tr>
                            <tr><td class="k">Departamento</td><td class="v">{{ $sucursal->departamentoNombre }}</td></tr>
                            <tr><td class="k">Municipio</td><td class="v">{{ $sucursal->municipioNombre }}</td></tr>
                            <tr><td class="k">Distrito</td><td class="v">{{ $sucursal->distritoNombre }}</td></tr>
                            <tr><td class="k">Teléfono</td><td class="v">{{ $sucursal->telefono }}</td></tr>
                            <tr><td class="k">Correo</td><td class="v">{{ $empresa->correo }}</td></tr>
                            <tr><td class="k">Establecimiento</td><td class="v">{{ $sucursal->establecimiento }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
            <td width="2%"></td>
            <td width="49%" valign="top">
                <div class="card">
                    <div class="card-head">Receptor</div>
                    <div class="card-body">
                        <table class="kv" style="width:100%;">
                            <tr><td class="k">Razón social</td><td class="v">{{ $venta->nombreCliente }}</td></tr>
                            <tr><td class="k">Tipo de doc.</td><td class="v">{{ $venta->identificacion }}</td></tr>
                            <tr><td class="k">N° de doc.</td><td class="v">
                                @php
                                    if ($venta->identificacion == 'DUI') { echo $venta->dui; }
                                    else { echo $venta->nit . ' · Reg: ' . $venta->registro; }
                                @endphp
                            </td></tr>
                            @if ($venta->identificacion != 'DUI')
                                <tr><td class="k">Giro</td><td class="v">{{ $venta->desActividad }}</td></tr>
                            @endif
                            <tr><td class="k">Correo</td><td class="v">{{ $venta->email }}</td></tr>
                            <tr><td class="k">Dirección</td><td class="v">{{ $venta->direccion }}</td></tr>
                            <tr><td class="k">Departamento</td><td class="v">{{ $venta->departamentoNombre }}</td></tr>
                            <tr><td class="k">Municipio</td><td class="v">{{ $venta->municipioNombre }}</td></tr>
                            <tr><td class="k">Distrito</td><td class="v">{{ $venta->distritoNombre }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if ($dte->tipoDte == 4 && $dte2)
        <div class="block-title">Documento relacionado</div>
        <div class="infobox">
            <table style="width:100%; font-size:9px;">
                <tr>
                    <td><b>Tipo de Documento:</b> {{ optional($tipoRelacionado)->codigo }} {{ optional($tipoRelacionado)->valor }}</td>
                    <td><b>No. Documento:</b> {{ $dte2->codigoGeneracion }}</td>
                    <td><b>Fecha:</b> {{ $dte2->fecEmi }}</td>
                </tr>
            </table>
        </div>
    @endif

    <!-- Detalle -->
    <div class="block-title">Detalle</div>
    <table class="items" cellpadding="0" cellspacing="0">
        <tr>
            <th width="3%">N°</th>
            <th width="9%">Código</th>
            <th width="8%">Cant.</th>
            <th width="7%">Unidad</th>
            <th width="42%">Descripción</th>
            <th width="8%">Precio<br>Unitario</th>
            <th width="7%">Desc.<br>x ítem</th>
            <th width="8%">No<br>Sujetas</th>
            <th width="8%">Exentas</th>
            <th width="10%">Gravadas</th>
        </tr>
        @php $i = 1; @endphp
        @foreach ($detalleVentas as $detalle)
            <tr>
                <td style="text-align:center;">{{ $i++ }}</td>
                <td style="text-align:center;">{{ $detalle->codebar3 }}</td>
                <td style="text-align:center;">{{ $detalle->cantidad }}</td>
                <td style="text-align:center;">{{ $detalle->unidad }}</td>
                <td>{{ $detalle->name ?? $detalle->nombreProducto }}</td>
                <td style="text-align:right;">$
                    @if ($dte->tipoDte == 1) {{ number_format($detalle->precio, 2) }}
                    @elseif ($dte->tipoDte == 2) {{ number_format($detalle->precio / 1.13, 2) }}
                    @elseif ($dte->tipoDte == 4) {{ number_format(abs($detalle->precio), 2) }}
                    @else {{ number_format($detalle->precio, 2) }}
                    @endif
                </td>
                <td style="text-align:right;">$
                    @if ($dte->tipoDte == 1) {{ number_format($detalle->descuento, 2) }}
                    @elseif ($dte->tipoDte == 2) {{ number_format($detalle->descuento / 1.13, 2) }}
                    @elseif ($dte->tipoDte == 4) {{ round(abs($detalle->descuento), 2) }}
                    @else {{ number_format($detalle->descuento, 2) }}
                    @endif
                </td>
                <td style="text-align:right;">$ 0.00</td>
                <td style="text-align:right;">$ 0.00</td>
                <td style="text-align:right;">$
                    @if ($dte->tipoDte == 1) {{ number_format($detalle->total, 2) }}
                    @elseif ($dte->tipoDte == 2) {{ number_format($detalle->total / 1.13, 2) }}
                    @elseif ($dte->tipoDte == 4) {{ number_format(abs($detalle->total), 2) }}
                    @else {{ number_format($detalle->total, 2) }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <!-- Resumen -->
    <table style="width:100%; margin-top:12px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="58%" valign="top" style="padding-right:8px;">
                <div class="infobox" style="height:170px;">
                    <p style="margin:2px 0;"><b>Valor en letras:</b>
                        {{ strtoupper(\App\Helpers\Convertidor::montoALetras(round($resumen->totalPagar, 2))) }}</p>
                    <p style="margin:2px 0;"><b>Condición de la operación:</b> {{ $resumen->condicion }}</p>
                    <p style="margin:2px 0;"><b>Observaciones:</b></p>
                    <table class="ext" style="width:100%; margin-top:4px;" cellpadding="0" cellspacing="0">
                        <tr><td class="h" colspan="4">EXTENSIÓN</td></tr>
                        <tr>
                            <td class="k">Nombre entrega</td><td>{{ $venta->resemir ?? '' }}</td>
                            <td class="k">No. Documento</td><td>{{ $venta->duiemir ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Nombre recibe</td><td>{{ $venta->resrep ?? '' }}</td>
                            <td class="k">No. Documento</td><td>{{ $venta->duirep ?? '' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td width="42%" valign="top">
                <div class="infobox" style="padding:0; height:170px;">
                    <table class="totals" cellpadding="0" cellspacing="0">
                        <tr><td class="th" colspan="2">SUMA TOTAL DE OPERACIONES</td></tr>
                        <tr><td class="lbl">Ventas no sujetas:</td><td class="amt">$ {{ number_format((float) $resumen->totalNoSuj, 2) }}</td></tr>
                        <tr><td class="lbl">Total gravada:</td><td class="amt">
                            @if ($dte->tipoDte == 1) $ {{ number_format($resumen->totalGravada, 2) }}
                            @elseif ($dte->tipoDte == 2) $ {{ number_format($resumen->totalGravada - $resumen->totalIva, 2) }}
                            @elseif($dte->tipoDte == 4) $ {{ number_format(($resumen->totalGravada > 100 ? $resumen->totalGravada / 1.12 : $resumen->totalGravada / 1.13), 2) }}
                            @else $ {{ number_format($resumen->totalGravada, 2) }}
                            @endif
                        </td></tr>
                        <tr><td class="lbl">Descuento a ventas gravadas:</td><td class="amt">$
                            @if (in_array($dte->tipoDte, [2, 4])) {{ number_format($resumen->totalDescu / 1.13, 2) }}
                            @else {{ number_format($resumen->totalDescu, 2) }}
                            @endif
                        </td></tr>
                        <tr><td class="lbl">Sumatoria de ventas:</td><td class="amt">
                            @if ($dte->tipoDte == 1) $ {{ number_format($resumen->subTotalVentas, 2) }}
                            @elseif($dte->tipoDte == 2) $ {{ number_format($resumen->subTotalVentas - $resumen->totalIva, 2) }}
                            @elseif($dte->tipoDte == 4) $ {{ number_format(($resumen->subTotalVentas > 100 ? $resumen->subTotalVentas / 1.12 : $resumen->subTotalVentas / 1.13) + 0.0, 2) }}
                            @else $ {{ number_format($resumen->subTotalVentas, 2) }}
                            @endif
                        </td></tr>
                        @if ($dte->tipoDte == 2 || $dte->tipoDte == 4)
                            <tr><td class="lbl">IVA:</td><td class="amt">$ {{ number_format($resumen->totalIva, 2) }}</td></tr>
                        @endif
                        <tr><td class="lbl">Sub-total:</td><td class="amt">$
                            @if($dte->tipoDte == 4) {{ number_format($resumen->subTotalVentas + $resumen->reteRenta, 2) }}
                            @else {{ number_format($resumen->subTotalVentas, 2) }}
                            @endif
                        </td></tr>
                        <tr><td class="lbl">IVA retenido:</td><td class="amt">$ {{ number_format($dte->tipoDte == 10 ? 0 : (($venta->facturador ?? 0) == 1 ? $resumen->ivaPerci1 : $resumen->ivaRete1), 2) }}</td></tr>
                        <tr><td class="lbl">Retención renta:</td><td class="amt">$ {{ number_format($dte->tipoDte == 10 || ($venta->facturador ?? 0) == 1 ? $resumen->reteRenta : 0.0, 2) }}</td></tr>
                        <tr><td class="lbl">Monto total de la operación:</td><td class="amt">$ {{ number_format($resumen->montoTotalOperacion, 2) }}</td></tr>
                        <tr><td class="lbl">Otros montos no afectos:</td><td class="amt">$ 0.00</td></tr>
                        <tr style="background-color:{{ $paletaDte['encabezado'] }};"><td class="lbl" style="font-weight:bold;">Total a pagar:</td><td class="amt">$ {{ number_format($resumen->totalPagar, 2) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <span>{{ $empresa->razon }} · Versión {{ $dte->version }}</span><br>
        <span class="pagenum"></span>
    </div>

</body>

</html>
