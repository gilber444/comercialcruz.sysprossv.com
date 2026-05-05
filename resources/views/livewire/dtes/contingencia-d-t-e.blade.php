<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Invalidaciones_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModalCon"> <i class="fa-solid fa-file-circle-exclamation"></i></i> Crear Contingencia</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsivep">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Codigo de Generacion</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Hora</th>
                <th class="text-center">DTE Enviados</th>
                <th class="text-center">Estado</th>
                <th class="text-center"></th>
            </thead>
            <tbody>
                @foreach ( $data as $con )
                <tr>
                    <td class="text-center">{{ $con->numero }}</td>
                    <td> {{ $con->codigoGeneracion }}</td>
                    <td class="text-center">{{ $con->fTransmision}}</td>
                    <td class="text-center">{{ $con->hTransmision }}</td>
                    <td class="text-center">
                        <a class="text-black" href="javascript:void(0);" wire:click="Detalle('{{ $con->id }}')">  {{ $con->contingencia_dte_detalles_count }}</a>
                    </td>
                    <td class="text-center">{{ $con->estado }}</td>
                    <td>
                        <a class="text-black btn btn-info" href="javascript:void(0);" wire:click="Procesar('{{ $con->id }}')"> Procesar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{$data->links()}}
    @include('livewire.dtes.formC')
    @include('livewire.dtes.detalleContigencia')
</div>
@include('common.notis')
