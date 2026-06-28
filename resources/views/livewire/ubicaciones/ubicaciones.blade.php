<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Ubicaciones_Create')
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
                <th class="text-center">Sucursal</th>
                <th class="text-center">Caja</th>
                <th class="text-center">Usuario</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $ubicaciones as $ubi )
                <tr>
                    <td class="text-center">{{ $ubi->id }}</td>
                    <td class="text-center">{{ $ubi->empresas}}</td>
                    <td class="text-center">{{ $ubi->sucursales}}</td>
                    <td class="text-center">{{ $ubi->cajas }}</td>
                    <td class="text-center">{{ $ubi->name }}</td>
                    <td class="text-center">
                        @if ($ubi->estado == 'Activo')
                            <span class="badge bg-label-success me-1">{{ $ubi->estado }}</span>
                        @else
                            <span class="badge bg-label-warning me-1">{{ $ubi->estado }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @can('Ubicaciones_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$ubi->id}}')"><i class="bx bx-edit-alt"></i>Cambiar</a>
                        @endcan
                        @can('Ubicaciones_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$ubi->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @include('livewire.ubicaciones.form')
    </div>
    {{$ubicaciones->links()}}
</div>
@include('common.notis')
