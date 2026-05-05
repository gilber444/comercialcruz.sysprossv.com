<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Categorias_Create')
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
                <th class="text-center">Categorias</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $categorias as $categoria )
                <tr>
                    <td class="text-center"><b>{{ $categoria->id }}</b></td>
                    <td>{{ $categoria->categoria }}</td>
                    <td class="text-center">
                        @can('Categorias_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$categoria->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Categorias_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$categoria->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$categorias->links()}}
    @include('livewire.categorias.form')
</div>

@include('common.notis')
