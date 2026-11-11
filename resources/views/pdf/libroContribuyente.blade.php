@php
    $paletaDte = app(\App\Services\DtePaletteService::class)
        ->obtener((int) $empresa->id);
    $titulo = 'Libro de Ventas a Contribuyentes';
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

    {{-- Solo creditos fiscales DTE finalizados (Procesado + sello) del
         ambiente de la empresa. Mismas filas que el anexo 1. --}}
    <div class="lb-tabla-card">
        <table cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:3%;">N.</th>
                    <th style="width:6%;">Fecha</th>
                    <th style="width:14%;">Numero de control</th>
                    <th style="width:15%;">Codigo de generacion</th>
                    <th style="width:7%;">NIT / NRC / DUI</th>
                    <th style="width:14%;">Cliente</th>
                    <th style="width:6%;">Exentas</th>
                    <th style="width:6%;">No sujetas</th>
                    <th style="width:7%;">Gravadas</th>
                    <th style="width:7%;">Debito fiscal</th>
                    <th style="width:7%;">Total</th>
                    <th style="width:8%;">Retencion 1% (cliente)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $i => $d)
                    @php
                        [$nitNrc, $dui] = \App\Support\AnexosF07::identificacion($d['nit'], $d['nrc'], $d['dui']);
                    @endphp
                    <tr class="{{ $i % 2 ? 'par' : '' }}">
                        <td class="c">{{ $i + 1 }}</td>
                        <td class="c">{{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') }}</td>
                        <td class="mono">{{ \App\Support\AnexosF07::sinGuiones($d['numero_control']) }}</td>
                        <td class="mono">{{ \App\Support\AnexosF07::documentoDte($d['fecha'], $d['codigo'], $d['numero_control']) }}</td>
                        <td class="c">{{ $nitNrc !== '' ? $nitNrc : $dui }}</td>
                        <td>{{ $d['cliente'] }}</td>
                        <td class="r">$ {{ number_format((float) $d['exentas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['no_sujetas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['gravada'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['debito'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['total'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['retencion'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="lb-vacio">No hay datos en las fechas seleccionadas.</td>
                    </tr>
                @endforelse
                <tr class="lb-totales">
                    <td colspan="6" class="r">TOTALES ({{ count($data) }} documentos)</td>
                    <td class="r">$ {{ number_format((float) $totales['exentas'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['no_sujetas'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['gravada'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['debito'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['total'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['retencion'], 2) }}</td>
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
                            <th>Valor neto</th>
                            <th>Debito fiscal</th>
                            <th>Total</th>
                        </tr>
                        {{-- El negocio NO genera ventas exentas ni no sujetas: se
                             informan en 0.00 en vez de dejar la celda vacia. --}}
                        <tr>
                            <td>Ventas exentas</td>
                            <td>$ 0.00</td>
                            <td>$ 0.00</td>
                            <td>$ 0.00</td>
                        </tr>
                        <tr>
                            <td>Ventas no sujetas</td>
                            <td>$ 0.00</td>
                            <td>$ 0.00</td>
                            <td>$ 0.00</td>
                        </tr>
                        <tr>
                            <td>Ventas gravadas locales</td>
                            <td>$ {{ number_format((float) $totales['gravada'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['debito'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['total'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Retencion 1% IVA efectuada por clientes (anexo 7)</td>
                            <td></td>
                            <td></td>
                            <td>$ {{ number_format((float) $totales['retencion'], 2) }}</td>
                        </tr>
                        <tr class="total">
                            <td>Total ventas a contribuyentes</td>
                            <td>$ {{ number_format((float) $totales['gravada'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['debito'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['total'], 2) }}</td>
                        </tr>
                    </table>
                    <p class="lb-nota">Montos con IVA antes de la retencion del 1%. Solo DTE procesados y sellados por Hacienda, del ambiente configurado en la empresa.</p>
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
