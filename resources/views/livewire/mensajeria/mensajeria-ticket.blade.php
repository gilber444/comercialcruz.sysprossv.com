<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Mensajerias_Create')
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
                <th class="text-center">Lema</th>
                <th class="text-center">Mensaje</th>
                <th class="text-center">Aviso</th>
                <th class="text-center">Notificacion</th>
                <th class="text-center">Empresa</th>
            </thead>
            <tbody>
                @foreach ( $mensajerias as $mensajeria )
                <tr>
                  
                    <td>{{ $mensajeria->lema }}</td>
                    <td>{{ $mensajeria->mensaje }}</td>
                    <td>{{ $mensajeria->aviso }}</td>
                    <td>{{ $mensajeria->notificacion }}</td>
                    <td>{{ $mensajeria->empresa }}</td>
                    <td class="text-center">
                        @can('Mensajerias_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$mensajeria->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Mensajerias_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$mensajeria->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$mensajerias->links()}}
    @include('livewire.mensajeria.form')   
</div>
@include('common.notis')

