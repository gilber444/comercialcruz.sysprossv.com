<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Permisos_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Descripcion</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $permisos as $permiso )
                <tr>
                    <td class="text-center"><b>{{ $permiso->id }}</b></td>
                    <td>{{ $permiso->name }}</td>
                    <td class="text-center">
                        @can('Permisos_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$permiso->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Permisos_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$permiso->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{$permisos->links()}}
    @include('livewire.permisos.form')
</div>
@include('common.notis')
