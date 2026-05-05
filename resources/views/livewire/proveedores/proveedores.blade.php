<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive ">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Nombre del Proveedor</th>
                <th class="text-center">Telefono</th>
                <th class="text-center">Registro</th>
                <th class="text-center">NIT</th>
                <th class="text-center">Giro</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $proveedores as $pro )
                <tr>
                    <td class="text-center"><b>{{ $pro->id }}</b></td>
                    <td>{{ $pro->nombre }}</td>
                    <td>{{ $pro->telefono }}</td>
                    <td>{{ $pro->registro }}</td>
                    <td>{{ $pro->nit }}</td>
                    <td>{{ $pro->giro }}</td>
                    <td class="text-center">
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$pro->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$pro->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$proveedores->links()}}
    @include('livewire.proveedores.form')
</div>
@include('common.notis')
