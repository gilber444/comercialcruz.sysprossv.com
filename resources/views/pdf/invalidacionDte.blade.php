@php
    $paletaDte = app(\App\Services\DtePaletteService::class)->obtener((int) $empresa->id);
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invalidación DTE - {{ $invalidacion->codigoGeneracion }}</title>
    <style>
        @page {
            margin: 22px;
        }

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

        .eyebrow {
            color: {{ $paletaDte['texto_suave'] }};
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .doc-title {
            color: {{ $paletaDte['texto'] }};
            font-size: 18px;
            font-weight: bold;
            margin: 2px 0 6px;
        }

        .logo-box {
            background-color: {{ $paletaDte['fondo'] }};
            border: 1px solid {{ $paletaDte['borde'] }};
            border-radius: 8px;
            padding: 8px;
            text-align: center;
        }

        .qr-box {
            background-color: {{ $paletaDte['fondo'] }};
            border: 1px solid {{ $paletaDte['borde'] }};
            border-radius: 8px;
            padding: 6px;
        }

        .badge {
            border-radius: 10px;
            font-size: 8.5px;
            font-weight: bold;
            padding: 2px 8px;
        }

        .badge-ok {
            background-color: {{ $paletaDte['exito'] }};
            color: #ffffff;
        }

        .badge-env {
            background-color: {{ $paletaDte['fondo'] }};
            border: 1px solid {{ $paletaDte['borde'] }};
            color: {{ $paletaDte['primario'] }};
        }

        .badge-muted {
            background-color: {{ $paletaDte['texto_suave'] }};
            border-radius: 10px;
            color: #ffffff;
            font-size: 8.5px;
            padding: 2px 8px;
        }

        .sect {
            color: {{ $paletaDte['primario'] }};
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8.5px;
            word-break: break-all;
        }

        .card {
            border: 1px solid {{ $paletaDte['borde'] }};
            border-radius: 10px;
            height: 152px;
        }

        .card-head {
            background-color: {{ $paletaDte['encabezado'] }};
            border-bottom: 1px solid {{ $paletaDte['borde'] }};
            border-radius: 10px 10px 0 0;
            color: {{ $paletaDte['primario'] }};
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 5px 10px;
            text-transform: uppercase;
        }

        .card-body {
            padding: 6px 10px;
        }

        .kv td {
            padding: 2px 0;
            vertical-align: top;
        }

        .kv .k {
            color: {{ $paletaDte['texto_suave'] }};
        }

        .kv .v {
            font-weight: bold;
            text-align: right;
            word-break: break-word;
        }

        .block-title {
            color: {{ $paletaDte['primario'] }};
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 12px 0 5px;
            text-transform: uppercase;
        }

        .data td,
        .data th {
            border: 1px solid {{ $paletaDte['borde'] }};
            font-size: 9px;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .data th {
            background-color: {{ $paletaDte['encabezado'] }};
            color: {{ $paletaDte['texto'] }};
            font-weight: bold;
            text-align: left;
            white-space: nowrap;
            width: 20%;
        }

        .notice {
            background-color: {{ $paletaDte['encabezado'] }};
            border: 1px solid {{ $paletaDte['borde'] }};
            border-radius: 8px;
            font-size: 9px;
            margin-top: 12px;
            padding: 8px 10px;
        }

        .footer {
            bottom: 0;
            color: {{ $paletaDte['texto_suave'] }};
            font-size: 8.5px;
            left: 0;
            position: fixed;
            right: 0;
            text-align: center;
        }

        .pagenum:before {
            content: counter(page) " de " counter(pages);
        }
    </style>
</head>
@php
    $identificacion = $estructura['identificacion'] ?? [];
    $emisor = $estructura['emisor'] ?? [];
    $documento = $estructura['documento'] ?? [];
    $motivo = $estructura['motivo'] ?? [];
    $tipoDte = ($tipoDteNombre ?? '') ?: ($documento['tipoDte'] ?? '');
    $ambiente = ($identificacion['ambiente'] ?? '') === '01' ? 'Producción (01)' : 'Pruebas (00)';
    $documentoReceptor = trim(($documento['tipoDocumento'] ?? '') . ' ' . ($documento['numDocumento'] ?? ''));
    $documentoResponsable = trim(($motivo['tipDocResponsable'] ?? '') . ' ' . ($motivo['numDocResponsable'] ?? ''));
    $documentoSolicitante = trim(($motivo['tipDocSolicita'] ?? '') . ' ' . ($motivo['numDocSolicita'] ?? ''));

    // Departamento, municipio y distrito del emisor (sucursal) y del receptor
    // (cliente). Si falta el municipio o el departamento se toma del distrito.
    $ubicacion = function ($modelo) {
        if (!$modelo) {
            return ['departamento' => null, 'municipio' => null, 'distrito' => null];
        }
        $distrito = optional($modelo->Rdistrito)->distrito;
        $municipio = $modelo->Rmunicipio;
        if (!$municipio && $modelo->Rdistrito) {
            $municipio = \App\Models\Municipios::find($modelo->Rdistrito->municipio);
        }
        $departamento = optional($modelo->Rdepartamento)->departamento
            ?: optional($municipio ? \App\Models\Departamentos::find($municipio->departamento) : null)->departamento;

        return ['departamento' => $departamento, 'municipio' => optional($municipio)->municipio, 'distrito' => $distrito];
    };
    try {
        $ubiEmisor = $ubicacion(\App\Models\Sucursales::find($dte->sucursal));
        $ubiReceptor = $ubicacion(\App\Models\Clientes::find($dte->receptor));
    } catch (\Throwable $e) {
        // La ubicacion es informativa: el PDF del evento sale igual, pero el
        // fallo queda en el log en vez de esconderse.
        report($e);
        $ubiEmisor = $ubiReceptor = ['departamento' => null, 'municipio' => null, 'distrito' => null];
    }
@endphp

<body>

    <!-- Cabecera -->
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td width="28%" valign="middle">
                    <div class="logo-box">
                        @if($logo)
                            <img src="{{ $logo }}" alt="Logo" style="max-height:95px; max-width:200px;">
                        @else
                            <span style="color:{{ $paletaDte['primario'] }}; font-weight:bold; font-size:11px;">{{ $empresa->empresa }}</span>
                        @endif
                    </div>
                </td>
                <td width="38%" valign="middle" style="padding-left:14px;">
                    <div class="eyebrow">Documento Tributario Electrónico</div>
                    <div class="doc-title">Evento de Invalidación</div>
                    <span class="badge badge-ok">{{ strtoupper($invalidacion->estado ?: 'PROCESADO') }}</span>
                    <span class="badge badge-env">{{ $ambiente }}</span>
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

    <!-- Metadatos del evento -->
    <table style="width:100%; font-size:10px; margin-top:12px;">
        <tr>
            <td width="34%" valign="top" style="padding-right:8px;">
                <div class="sect">Código de Generación</div>
                <div class="mono">{{ $identificacion['codigoGeneracion'] ?? $invalidacion->codigoGeneracion }}</div>
            </td>
            <td width="34%" valign="top" style="padding-right:8px;">
                <div class="sect">Sello de Recepción</div>
                <div class="mono">{{ $invalidacion->selloRecibido }}</div>
            </td>
            <td width="32%" valign="top">
                <div class="sect">Fecha / Hora de Generación</div>
                <div style="font-weight:bold;">{{ $identificacion['fecEmi'] ?? $invalidacion->fecAnula }} {{ $identificacion['horEmi'] ?? $invalidacion->horAnula }}</div>
                <div class="sect" style="margin-top:4px;">Procesado por MH</div>
                <div style="font-weight:bold;">{{ $invalidacion->fhProcesamiento }}</div>
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
                            <tr><td class="k">NIT</td><td class="v">{{ $emisor['nit'] ?? $empresa->nit }}</td></tr>
                            <tr><td class="k">Nombre comercial</td><td class="v">{{ $empresa->razon }}</td></tr>
                            <tr><td class="k">Establecimiento MH</td><td class="v">{{ $emisor['codEstableMH'] ?? '—' }}</td></tr>
                            <tr><td class="k">Punto de venta MH</td><td class="v">{{ $emisor['codPuntoVentaMH'] ?? '—' }}</td></tr>
                            <tr><td class="k">Teléfono</td><td class="v">{{ $emisor['telefono'] ?? $empresa->telefono }}</td></tr>
                            <tr><td class="k">Correo</td><td class="v">{{ $emisor['correo'] ?? $empresa->correo }}</td></tr>
                            <tr><td class="k">Departamento</td><td class="v">{{ $ubiEmisor['departamento'] ?? '—' }}</td></tr>
                            <tr><td class="k">Municipio</td><td class="v">{{ $ubiEmisor['municipio'] ?? '—' }}</td></tr>
                            <tr><td class="k">Distrito</td><td class="v">{{ $ubiEmisor['distrito'] ?? '—' }}</td></tr>
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
                            <tr><td class="k">Razón social</td><td class="v">{{ $documento['nombre'] ?? $cliente->nombreCliente }}</td></tr>
                            <tr><td class="k">Identificación</td><td class="v">{{ $documentoReceptor ?: 'No aplica' }}</td></tr>
                            <tr><td class="k">Teléfono</td><td class="v">{{ $documento['telefono'] ?? 'No registrado' }}</td></tr>
                            <tr><td class="k">Correo</td><td class="v">{{ $documento['correo'] ?? $cliente->email }}</td></tr>
                            <tr><td class="k">Departamento</td><td class="v">{{ $ubiReceptor['departamento'] ?? '—' }}</td></tr>
                            <tr><td class="k">Municipio</td><td class="v">{{ $ubiReceptor['municipio'] ?? '—' }}</td></tr>
                            <tr><td class="k">Distrito</td><td class="v">{{ $ubiReceptor['distrito'] ?? '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Documento invalidado -->
    <div class="block-title">Documento invalidado</div>
    <table class="data" style="width:100%;" cellpadding="0" cellspacing="0">
        <tr>
            <th>Tipo de DTE</th>
            <td width="30%">{{ $tipoDte }} ({{ $documento['tipoDte'] ?? '' }})</td>
            <th>Fecha de emisión</th>
            <td width="30%">{{ $documento['fecEmi'] ?? $dte->fecEmi }}</td>
        </tr>
        <tr>
            <th>Número de control</th>
            <td colspan="3" class="mono">{{ $documento['numeroControl'] ?? $dte->numeroControl }}</td>
        </tr>
        <tr>
            <th>Código de generación</th>
            <td colspan="3" class="mono">{{ $documento['codigoGeneracion'] ?? $dte->codigoGeneracion }}</td>
        </tr>
        <tr>
            <th>Sello de recepción</th>
            <td colspan="3" class="mono">{{ $documento['selloRecibido'] ?? $dte->sello }}</td>
        </tr>
        <tr>
            <th>Documento reemplazante</th>
            <td colspan="3">
                @if(!empty($documento['codigoGeneracionR']))
                    <span class="mono">{{ $documento['codigoGeneracionR'] }}</span>
                @else
                    <span class="badge-muted">No aplica</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Motivo -->
    <div class="block-title">Motivo de la invalidación</div>
    <table class="data" style="width:100%;" cellpadding="0" cellspacing="0">
        <tr>
            <th>Tipo</th>
            <td width="30%">{{ $motivo['tipoAnulacion'] ?? '' }}</td>
            <th>Motivo</th>
            <td width="30%">{{ $motivo['motivoAnulacion'] ?? optional($invalidacion->tipoInvalidacion)->valor }}</td>
        </tr>
        <tr>
            <th>Responsable</th>
            <td>{{ $motivo['nombreResponsable'] ?? '' }}</td>
            <th>Identificación</th>
            <td class="mono">{{ $documentoResponsable }}</td>
        </tr>
        <tr>
            <th>Solicitante</th>
            <td>{{ $motivo['nombreSolicita'] ?? '' }}</td>
            <th>Identificación</th>
            <td class="mono">{{ $documentoSolicitante }}</td>
        </tr>
    </table>

    <!-- Aviso -->
    <div class="notice">
        Este documento es la representación gráfica del <b>Evento de Invalidación</b> aceptado por el Ministerio de
        Hacienda. El Archivo JSON firmado y su Sello de Recepción constituyen el registro electrónico del evento.
    </div>

    <!-- Pie -->
    <div class="footer">
        <span>{{ $empresa->empresa }} · Nombre comercial: {{ $empresa->razon }} · Versión {{ $identificacion['version'] ?? '' }}</span>
        <br>
        <span class="pagenum"></span>
    </div>

</body>

</html>
