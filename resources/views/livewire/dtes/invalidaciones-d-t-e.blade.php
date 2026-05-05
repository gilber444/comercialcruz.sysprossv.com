<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Invalidaciones_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
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
                <th class="text-center">Tipo</th>
                <th class="text-center">Emisor</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $invalidaciones as $inva )
                <tr>
                    <td class="text-center">{{ $inva->numero }}</td>
                    <td> {{ $inva->codigoGeneracion }}</td>
                    <td class="text-center">{{ $inva->fecAnula }}</td>
                    <td class="text-center">{{ $inva->horAnula }}</td>
                    <td class="text-center">{{ $inva->tipo }}</td>
                    <td>{{ $inva->nombreCliente }}</td>
                    <td class="text-center">{{ $inva->estado }}</td>
                    <td class="text-center">
                        @if($inva->estado <> 'PROCESADO')
                        @can('Invalidaciones_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$inva->id}}')"><i class="fa-solid fa-edit"></i></a>
                        @endcan
                        @can('Invalidaciones_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$inva->id}}')"><i class="fa-solid fa-trash"></i></a>
                        @endcan
                        <button class="btn btn-icon btn-label-primary" title="Procesar Invalidacion" wire:click="ProcesarInva({{ $inva->id }})"><i class="fa-solid fa-paper-plane"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$invalidaciones->links()}}
    @include('livewire.dtes.form')
</div>
@include('common.notis')
