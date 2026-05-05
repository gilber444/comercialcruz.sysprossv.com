<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Parametros_Create')
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
                <th class="text-center">Caja #</th>
                <th class="text-center">Sucursal</th>
                <th class="text-center">Token</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $parametros as $parametro )
                <tr>
                    <td class="text-center">{{ $parametro->caja }}</td>
                    <td>{{ $parametro->nombre }}</td>
                    <td>{{ $parametro->token }}</td>
                    <td class="text-center">
                        @can('Parametros_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$parametro->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Parametros_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$parametro->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$parametros->links()}}
    @include('livewire.parametros.form')
</div>
@include('common.notis')
