{{--
    Encabezado comun de los libros: logo a la izquierda, datos del
    contribuyente y titulo/periodo. Recibe $empresa, $imagenUrl, $titulo,
    $desde, $hasta y opcionalmente $sucursales.
--}}
<div class="lb-header">
    <table>
        <tr>
            <td width="18%" style="vertical-align:middle;">
                @if (!empty($empresa->image))
                    <img src="{{ $imagenUrl }}" alt="" width="130" height="70">
                @endif
            </td>
            <td width="50%" style="vertical-align:middle;">
                <p class="lb-eyebrow">Contribuyente</p>
                <p class="lb-empresa">{{ $empresa->empresa }}</p>
                <p class="lb-dato">{{ $empresa->razon }}</p>
                <p class="lb-dato">NIT: {{ $empresa->nit }} &nbsp;&nbsp; NRC: {{ $empresa->registro }}</p>
                <p class="lb-dato">{{ $empresa->direccion }}</p>
                <p class="lb-dato">Tel. {{ $empresa->telefono }} &nbsp;&nbsp; {{ $empresa->correo }}</p>
            </td>
            <td width="32%" style="vertical-align:middle;">
                <p class="lb-eyebrow" style="text-align:center;">Periodo</p>
                <p class="lb-titulo">{{ $titulo }}</p>
                <p class="lb-periodo">
                    Del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
                </p>
                <p class="lb-periodo">
                    {{ !empty($sucursales) ? 'Sucursal: ' . $sucursales->nombre : 'Todas las sucursales' }}
                </p>
            </td>
        </tr>
    </table>
</div>
