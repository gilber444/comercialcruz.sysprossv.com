<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Invalidaciones_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-list-check"></i> Procesar Nuevo Lote</a>
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
                <th class="text-center">Codigo Lote</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Hora</th>
                <th class="text-center">DTE Enviados</th>
                <th class="text-center">Estado</th>
            </thead>
            <tbody>
                @foreach ( $lotes as $lote )
                <tr>
                    <td class="text-center">{{ $lote->numero }}</td>
                    <td> {{ $lote->idEnvio }}</td>
                    <td> {{ $lote->codigoLote }}</td>
                    <td class="text-center">{{ $lote->fecha}}</td>
                    <td class="text-center">{{ $lote->hora }}</td>
                    <td class="text-center">
                        <a class="text-black" href="javascript:void(0);" wire:click="Detalle('{{ $lote->id }}')">  {{ $lote->lotedte_detalles_count }}</a>
                    </td>
                    <td class="text-center">{{ $lote->estado }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{$lotes->links()}}
    @include('livewire.dtes.formL')
    @include('livewire.dtes.detalleLotes')
</div>
@include('common.notis')
