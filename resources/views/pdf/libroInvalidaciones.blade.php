@php
    $paletaDte = app(\App\Services\DtePaletteService::class)
        ->obtener((int) $empresa->id);
    $titulo = 'Libro de Invalidaciones';
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    @include('pdf.partials.libro-estilos')
</head>

<body>
    @include('pdf.partials.libro-pie')
    @include('pdf.partials.libro-encabezado')

    {{-- DTE invalidados que habian sido sellados por Hacienda, del ambiente
         configurado en la empresa. --}}
    <div class="lb-tabla-card">
        <table cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:3%;">N.</th>
                    <th style="width:7%;">Fecha emision</th>
                    <th style="width:10%;">Tipo de documento</th>
                    <th style="width:20%;">Numero de control</th>
                    <th style="width:22%;">Codigo de generacion</th>
                    <th style="width:22%;">Sello de recepcion</th>
                    <th style="width:6%;">Correlativo</th>
                    <th style="width:10%;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $i => $d)
                    <tr class="{{ $i % 2 ? 'par' : '' }}">
                        <td class="c">{{ $i + 1 }}</td>
                        <td class="c">{{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') }}</td>
                        <td class="c">{{ trim($d['tipo_codigo'] . ' ' . $d['tipo_nombre']) }}</td>
                        <td class="mono">{{ $d['numero_control'] }}</td>
                        <td class="mono">{{ \App\Support\AnexosF07::sinGuiones($d['codigo']) }}</td>
                        <td class="mono">{{ $d['sello'] }}</td>
                        <td class="c">{{ $d['correlativo'] }}</td>
                        <td class="r">$ {{ number_format((float) $d['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="lb-vacio">No hay datos en las fechas seleccionadas.</td>
                    </tr>
                @endforelse
                <tr class="lb-totales">
                    <td colspan="7" class="r">TOTAL INVALIDADO ({{ count($data) }} documentos)</td>
                    <td class="r">$ {{ number_format((float) $totales['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <table style="width:100%; border-collapse:separate; border-spacing:0;">
        <tr>
            <td style="width:68%; vertical-align:top; padding-right:8px;">
                <div class="lb-resumen">
                    <p class="titulo">Resumen del periodo</p>
                    <table>
                        <tr>
                            <th>Detalle</th>
                            <th>Documentos</th>
                            <th>Monto</th>
                        </tr>
                        <tr class="total">
                            <td>DTE invalidados</td>
                            <td>{{ count($data) }}</td>
                            <td>$ {{ number_format((float) $totales['total'], 2) }}</td>
                        </tr>
                    </table>
                    <p class="lb-nota">Los documentos invalidados no se informan en los anexos de ventas del periodo con su monto original.</p>
                </div>
            </td>
            <td style="width:32%; vertical-align:top;">
                <div class="lb-firma">
                    <p class="titulo">Firma</p>
                    <div class="linea"></div>
                    <p class="rol">Contador</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
