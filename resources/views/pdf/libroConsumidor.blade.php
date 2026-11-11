@php
    $paletaDte = app(\App\Services\DtePaletteService::class)
        ->obtener((int) $empresa->id);
    $titulo = 'Libro de Ventas a Consumidor Final';
    // Resumen: base por division y debito POR RESTA de valores redondeados.
    $netoGravadas = \App\Support\AnexosF07::monto(\App\Support\Decimal::div($totales['gravadas'], '1.13'));
    $debitoGravadas = \App\Support\AnexosF07::monto(\App\Support\Decimal::sub($totales['gravadas'], $netoGravadas));
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

    {{-- Solo ventas DTE finalizadas (Procesado + sello) del ambiente de la
         empresa, agrupadas por dia y caja: es el mismo rango del anexo 2. --}}
    <div class="lb-tabla-card">
        <table cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:6%;">Fecha</th>
                    <th style="width:9%;">Sucursal / Caja</th>
                    <th style="width:4%;">Docs.</th>
                    <th style="width:8%;">Clase / Tipo</th>
                    <th style="width:17%;">Documento del (cod. generacion)</th>
                    <th style="width:17%;">Documento al (cod. generacion)</th>
                    <th style="width:6%;">Correl. del - al</th>
                    <th style="width:8%;">Ventas exentas</th>
                    <th style="width:8%;">Ventas no sujetas</th>
                    <th style="width:8%;">Ventas gravadas (IVA incl.)</th>
                    <th style="width:9%;">Total ventas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $i => $d)
                    <tr class="{{ $i % 2 ? 'par' : '' }}">
                        <td class="c">{{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') }}</td>
                        <td>{{ $d['sucursal'] }} / Caja {{ $d['caja'] }}</td>
                        <td class="c">{{ $d['documentos'] }}</td>
                        <td class="c">4 DTE / 01 Factura</td>
                        <td class="mono">{{ \App\Support\AnexosF07::documentoDte($d['fecha'], $d['del_codigo'], $d['del_numero']) }}</td>
                        <td class="mono">{{ \App\Support\AnexosF07::documentoDte($d['fecha'], $d['al_codigo'], $d['al_numero']) }}</td>
                        <td class="c">{{ $d['del_correlativo'] }} - {{ $d['al_correlativo'] }}</td>
                        <td class="r">$ {{ number_format((float) $d['exentas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['no_sujetas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['gravadas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="lb-vacio">No hay datos en las fechas seleccionadas.</td>
                    </tr>
                @endforelse
                <tr class="lb-totales">
                    <td colspan="7" class="r">TOTALES ({{ collect($data)->sum('documentos') }} documentos)</td>
                    <td class="r">$ {{ number_format((float) $totales['exentas'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['no_sujetas'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['gravadas'], 2) }}</td>
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
                            <td>$ {{ number_format((float) $netoGravadas, 2) }}</td>
                            <td>$ {{ number_format((float) $debitoGravadas, 2) }}</td>
                            <td>$ {{ number_format((float) $totales['gravadas'], 2) }}</td>
                        </tr>
                        <tr class="total">
                            <td>Total ventas a consumidor final</td>
                            <td>$ {{ number_format((float) $netoGravadas, 2) }}</td>
                            <td>$ {{ number_format((float) $debitoGravadas, 2) }}</td>
                            <td>$ {{ number_format((float) $totales['total'], 2) }}</td>
                        </tr>
                    </table>
                    <p class="lb-nota">Solo DTE procesados y sellados por Hacienda, del ambiente configurado en la empresa.</p>
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
