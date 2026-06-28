<table class="table table-hover table-sm">
    <thead>
        <th class="text-center">Numero</th>
        <th class="text-center">Codigo Generacion</th>
        <th class="text-center">Cliente</th>
        <th class="text-center">Tipo Documento</th>
        <th class="text-center">Fecha Generacion</th>
        <th class="text-center">Monto</th>
        <th class="text-center">Estado</th>
        <th class="text-center">Acciones</th>
    </thead>
    <tbody>
        @foreach ($data as $dte)
            <tr>
                <td class="text-center">{{ $dte->numeroControl }}</td>
                <td class="text-center">{{ $dte->codigoGeneracion }}</td>
                <td>{{ $dte->nombreCliente }}</td>
                <td class="text-center">{{ $dte->tipo }}</td>
                <td class="text-center">{{ $dte->fecEmi }}</td>
                <td class="text-end">
                    @php
                        $mon = DB::table('resumen_dtes')->where('dte', $dte->id)->value('totalPagar');
                    @endphp
                    {{ $mon ? '$ '.number_format($mon,2) : 'Sin datos' }}
                </td>
                <td class="text-center">
                    @if($dte->estado == 'RECHAZADO')
                        <a class="text-black" href="javascript:void(0);" wire:click="Detalle('{{ $dte->id }}')"> {{ $dte->estado }} </a>
                    @else
                        {{ $dte->estado }}
                    @endif
                </td>
                <td class="text-center">
                    @can('DTE_Print')
                        <a class="btn btn-icon btn-label-info" href="{{ url('report/pdf/'.$dte->id) }}" target="_blank"><i class="fa-solid fa-print"></i></a>
                    @endcan
                    @if($dte->estado == 'PROCESADO')
                        @can('DTE_ReenviarCorreo')
                            <button class="btn btn-icon btn-label-primary" wire:click="enviarCorreo({{ $dte->id }})"><i class="fa-solid fa-paper-plane"></i></button>
                        @endcan
                        <a class="btn btn-icon btn-label-dark" onclick="handleJson({{ $dte->id }}, '{{ $dte->codigoGeneracion }}')"><i class="fa-solid fa-file-code"></i></a>
                    @elseif(in_array($dte->estado, ['Creado','Firmado','RECHAZADO']))
                        <button class="btn btn-icon btn-label-warning" onclick="startProcessing({{ $dte->id }})">
                            <i class="fa-solid fa-file-signature"></i>
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
