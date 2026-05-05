<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('DTE_Create')
                    <!--<a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal"
                        data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>-->
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsivep">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Numero</th>
                <th class="text-center">Codigo Generacion</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Tipo Documento</th>
                <th class="text-center">Fecha Generacion</th>
                <th class="text-center">Monto</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($dtes as $dte)
                    <tr>
                        <td class="text-center">{{ $dte->numeroControl }}</td>
                        <td class="text-center">{{ $dte->codigoGeneracion }}</td>
                        <td>{{ $dte->nombreCliente }}</td>
                        <td class="text-center">{{ $dte->tipo }}</td>
                        <td class="text-center">{{ $dte->fecEmi }}</td>
                        <td class="text-end"> $ <p style="padding: 0.9px"></p>
                            @php
                                $mon = DB::table('resumen_dtes')
                                    ->where('dte', $dte->id)
                                    ->select('totalPagar')
                                    ->first();
                                if ($mon) {
                                    echo number_format($mon->totalPagar, 2);
                                } else {
                                    echo 'No se encontraron datos para este DTE';
                                }
                            @endphp
                        </td>
                        <td class="text-center">

                            @if($dte->estado == 'RECHAZADO')
                                <a class="text-black" href="javascript:void(0);" wire:click="Detalle('{{ $dte->id }}')">  {{ $dte->estado }}</a>
                            @else
                                {{ $dte->estado }}
                            @endif
                        </td>
                        <td class="text-center">
                            @can('DTE_Print')
                                <a class="btn btn-icon btn-label-info" href="{{ url('report/pdf' . '/' . $dte->id) }}" title="Ver PDF" target="_blank"><i class="fa-solid fa-print"></i></a>
                            @endcan
                            @if($dte->estado == 'PROCESADO')

                                @can('DTE_ReenviarCorreo')
                                    <button class="btn btn-icon btn-label-primary" title="Reenviar Correo Electronico" wire:click="enviarCorreo({{ $dte->id }})">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                @endcan
                                <a class="btn btn-icon btn-label-dark" onclick="handleJson({{ $dte->id }}, '{{ $dte->codigoGeneracion }}')" title="Ver JSON"><i class="fa-solid fa-file-code"></i></a>
                            @elseif ($dte->estado == 'Creado')
                                <button class="btn btn-icon btn-label-warning" title="Firmar  y Procesar DTE" onclick="startProcessing({{ $dte->id }})">
                                    <i class="fa-solid fa-file-signature"></i>
                                </button>
                            @elseif( $dte->estado == 'Firmado')
                                <button class="btn btn-icon btn-label-warning" title="Firmar  y Procesar DTE" onclick="startProcessing({{ $dte->id }})">
                                    <i class="fa-solid fa-file-signature"></i>
                                </button>
                            @elseif($dte->estado == 'RECHAZADO')
                                <button class="btn btn-icon btn-label-warning" title="Firmar  y Procesar DTE" onclick="startProcessing({{ $dte->id }})">
                                    Generar
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $dtes->links() }}
    @include('livewire.dtes.recepcion')
</div>

@include('common.notis')
<script>
    function handleJson(id, codigo) {
        Swal.fire({
            title: 'Acción requerida',
            text: `¿Qué deseas hacer con el archivo JSON "${codigo}.json"?`,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-regular fa-eye"></i> Ver',
            denyButtonText: '<i class="fa-solid fa-download"></i> Descargar',
        }).then((result) => {
            if (result.isConfirmed) {
                // Abrir el JSON en una nueva pestaña
                window.open(`/json/${id}`, '_blank');
            } else if (result.isDenied) {
                // Descargar el JSON
                window.location.href = `/json/download/${id}`;
            }
        });
    }
</script>
