<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Evento de Retorno {{ $evento->codigoGeneracion }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #222; margin: 0; }
        .titulo { text-align: center; margin-bottom: 10px; }
        .titulo h1 { font-size: 15px; margin: 0 0 2px; letter-spacing: 1px; }
        .titulo small { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        .caja { border: 1px solid #999; padding: 6px; margin-bottom: 8px; }
        .caja h2 { font-size: 10px; margin: 0 0 4px; text-transform: uppercase;
                   border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        .caja td { padding: 1px 3px; vertical-align: top; }
        .etq { color: #666; width: 105px; }
        .items th { background: #eee; border: 1px solid #999; padding: 4px; font-size: 9px; }
        .items td { border: 1px solid #ccc; padding: 3px 4px; font-size: 9px; }
        .der { text-align: right; }
        .cen { text-align: center; }
        .totales td { padding: 2px 4px; }
        .sello { border: 1px solid #999; padding: 6px; margin-top: 8px; font-size: 9px; word-break: break-all; }
        .pie { margin-top: 12px; font-size: 8px; color: #666; text-align: center; }
    </style>
</head>

<body>
    <div class="titulo">
        <h1>EVENTO DE RETORNO</h1>
        <small>Documento Tributario Electronico &mdash; Codigo de evento 18</small>
    </div>

    <div class="caja">
        <h2>Identificacion del evento</h2>
        <table>
            <tr>
                <td class="etq">Codigo generacion</td>
                <td><b>{{ $evento->codigoGeneracion }}</b></td>
                <td class="etq">Fecha del evento</td>
                <td>{{ \Carbon\Carbon::parse($evento->fecEmi)->format('d/m/Y') }} {{ $evento->horEmi }}</td>
            </tr>
            <tr>
                <td class="etq">Modelo</td>
                <td>{{ $evento->tipoModelo == 1 ? 'Previo' : 'Diferido' }}</td>
                <td class="etq">Transmision</td>
                <td>{{ $evento->tipoOperacion == 1 ? 'Normal' : 'Contingencia' }}</td>
            </tr>
        </table>
    </div>

    <div class="caja">
        <h2>Emisor</h2>
        <table>
            <tr>
                <td class="etq">Nombre</td>
                <td>{{ $evento->Rempresa?->razon ?: $evento->Rempresa?->empresa }}</td>
                <td class="etq">NIT</td>
                <td>{{ $evento->Rempresa?->nit }}</td>
            </tr>
            <tr>
                <td class="etq">NRC</td>
                <td>{{ $evento->Rempresa?->registro }}</td>
                <td class="etq">Sucursal</td>
                <td>{{ $evento->Rsucursal?->direccion }}</td>
            </tr>
        </table>
    </div>

    <div class="caja">
        <h2>Receptor</h2>
        <table>
            <tr>
                <td class="etq">Nombre</td>
                <td>{{ $evento->Rcliente?->nombreCliente ?? 'Consumidor final' }}</td>
                <td class="etq">Documento</td>
                <td>{{ $evento->Rcliente?->nit ?: $evento->Rcliente?->dui }}</td>
            </tr>
        </table>
    </div>

    <div class="caja">
        <h2>Documento relacionado</h2>
        <table>
            <tr>
                <td class="etq">Factura</td>
                <td>{{ $evento->Rventa?->correlativo }}</td>
                <td class="etq">Fecha emision</td>
                <td>{{ $evento->Rdte ? \Carbon\Carbon::parse($evento->Rdte->fecEmi)->format('d/m/Y') : '' }}</td>
            </tr>
            <tr>
                <td class="etq">Codigo generacion</td>
                <td colspan="3">{{ $evento->Rdte?->codigoGeneracion }}</td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:28px">N</th>
                <th>Descripcion</th>
                <th style="width:55px">Cantidad</th>
                <th style="width:65px">Precio uni.</th>
                <th style="width:70px">Retornado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($evento->Rdetalles as $d)
                <tr>
                    <td class="cen">{{ $d->numItem }}</td>
                    <td>{{ $d->descripcion }}</td>
                    <td class="der">{{ number_format($d->cantidad, 2) }}</td>
                    <td class="der">{{ number_format($d->precioUni, 2) }}</td>
                    <td class="der">{{ number_format($d->ventaGravada, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="margin-top:6px">
        <tr>
            <td style="width:70%"></td>
            <td class="etq">Total gravado</td>
            <td class="der">{{ number_format($evento->totalGravada, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="etq">IVA incluido</td>
            <td class="der">{{ number_format($evento->totalIva, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="etq"><b>Total retornado</b></td>
            <td class="der"><b>{{ number_format($evento->montoTotalOperacion, 2) }}</b></td>
        </tr>
    </table>

    <div style="margin-top:6px; font-size:9px;">
        <b>Son:</b> {{ $evento->totalLetras }}
    </div>

    <div class="sello">
        <b>Sello de recepcion:</b> {{ $evento->selloRecibido ?: 'PENDIENTE DE TRANSMISION' }}<br>
        <b>Fecha de procesamiento:</b> {{ $evento->fhProcesamiento ?: '-' }}
    </div>

    <div class="pie">
        Generado por {{ $evento->Rresponsable?->name }} &mdash;
        este documento es la representacion grafica del Evento de Retorno y debe
        entregarse al receptor de la factura relacionada.
    </div>
</body>

</html>
