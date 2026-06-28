<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Catalagos_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Codigo</th>
                <th class="text-center">Nombre</th>
                <th class="text-center">Descripcion</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $catalagos as $catalago )
                <tr>
                    <td class="text-center"><h6>{{ $catalago->codigo }}</h6></td>
                    <td>{{ $catalago->catalago }}</td>
                    <td style="width: 50%">{{ $catalago->descripcion }}</td>
                    <td class="text-center">
                        @can('Categorias_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$catalago->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Categorias_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$catalago->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$catalagos->links()}}
    @include('livewire.catalagos.catalagoForms')
</div>

@include('common.notis')

