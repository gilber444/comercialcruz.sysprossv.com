<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Sucursales_Create')
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
                <th class="text-center">Empresa</th>
                <th class="text-center">Nombre</th>
                <th class="text-center">Direccion</th>
                <th class="text-center">N. Cajas</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $sucursales as $sucursal )
                <tr>
                    <td class="text-center">{{ $sucursal->numero }}</td>
                    <td>{{ $sucursal->empresa }}</td>
                    <td>{{ $sucursal->nombre }}</td>
                    <td>{{ $sucursal->direccion }}</td>
                    <td class="text-center">{{ $sucursal->cajas }}</td>
                    <td class="text-center">
                        @can('Sucursales_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$sucursal->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Sucursales_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$sucursal->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$sucursales->links()}}
    @include('livewire.sucursales.form')
</div>
@include('common.notis')
