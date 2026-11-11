@php
    $paletaDte = app(\App\Services\DtePaletteService::class)
        ->obtener((int) $empresa->id);
    $titulo = 'Libro de Compras';
    $sinDesglose = collect($data)->where('sin_desglose', true)->count();
    $nombresTipo = ['01' => '01 Factura', '03' => '03 CCF', '05' => '05 N. credito', '06' => '06 N. debito', '11' => '11 F. exportacion', '12' => '12 Decl. mercancias', '13' => '13 Mandamiento'];
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

    <div class="lb-tabla-card">
        <table cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:3%;">N.</th>
                    <th style="width:6%;">Fecha</th>
                    <th style="width:8%;">Tipo doc.</th>
                    <th style="width:17%;">Numero de documento</th>
                    <th style="width:8%;">NIT / NRC</th>
                    <th style="width:16%;">Proveedor</th>
                    <th style="width:7%;">Compras exentas</th>
                    <th style="width:8%;">Compras gravadas</th>
                    <th style="width:7%;">Credito fiscal</th>
                    <th style="width:8%;">Total compra</th>
                    <th style="width:5%;">Anexo 3</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $i => $d)
                    @php
                        [$nitNrc, $dui] = \App\Support\AnexosF07::identificacion($d['nit'], $d['nrc'], '');
                    @endphp
                    <tr class="{{ $i % 2 ? 'par' : '' }}">
                        <td class="c">{{ $i + 1 }}</td>
                        <td class="c">{{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') }}</td>
                        <td class="c">{{ $nombresTipo[$d['tipo_documento']] ?? 'Sin desglose' }}</td>
                        <td class="mono">{{ $d['documento'] }}</td>
                        <td class="c">{{ $nitNrc !== '' ? $nitNrc : $dui }}</td>
                        <td>{{ $d['proveedor'] }}</td>
                        <td class="r">$ {{ number_format((float) $d['compras_exentas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['compras_gravadas'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['credito_fiscal'], 2) }}</td>
                        <td class="r">$ {{ number_format((float) $d['total_compra'], 2) }}</td>
                        <td class="c">{{ $d['en_anexo'] ? 'Si' : 'No' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="lb-vacio">No hay datos en las fechas seleccionadas.</td>
                    </tr>
                @endforelse
                <tr class="lb-totales">
                    <td colspan="6" class="r">TOTALES ({{ count($data) }} compras)</td>
                    <td class="r">$ {{ number_format((float) $totales['compras_exentas'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['compras_gravadas'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['credito_fiscal'], 2) }}</td>
                    <td class="r">$ {{ number_format((float) $totales['total_compra'], 2) }}</td>
                    <td></td>
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
                            <th>Exentas</th>
                            <th>Gravadas</th>
                            <th>Credito fiscal</th>
                            <th>Total</th>
                        </tr>
                        <tr>
                            <td>Compras con credito fiscal (anexo 3)</td>
                            <td>$ {{ number_format((float) $totalesAnexo['compras_exentas'], 2) }}</td>
                            <td>$ {{ number_format((float) $totalesAnexo['compras_gravadas'], 2) }}</td>
                            <td>$ {{ number_format((float) $totalesAnexo['credito_fiscal'], 2) }}</td>
                            <td>$ {{ number_format((float) $totalesAnexo['total_compra'], 2) }}</td>
                        </tr>
                        <tr class="total">
                            <td>Total compras del periodo</td>
                            <td>$ {{ number_format((float) $totales['compras_exentas'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['compras_gravadas'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['credito_fiscal'], 2) }}</td>
                            <td>$ {{ number_format((float) $totales['total_compra'], 2) }}</td>
                        </tr>
                    </table>
                    @if ($sinDesglose > 0)
                        <p class="lb-nota"><b>Atencion:</b> {{ $sinDesglose }} compra(s) sin desglose fiscal (registradas antes de la actualizacion); salen en cero y no entran al anexo 3.</p>
                    @endif
                    <p class="lb-nota">Las facturas de consumidor final no dan derecho a credito fiscal: se listan como compra gravada con IVA incluido y no se informan en el anexo 3.</p>
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
