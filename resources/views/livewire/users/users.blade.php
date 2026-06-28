<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('User_Create')
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
                <th class="text-center"></th>
                <th class="text-center">Nombre</th>
                <th class="text-center">Usuario</th>
                <th class="text-center">Telefono</th>
                <th class="text-center">Email</th>
                <th class="text-center">Estatus</th>
                <th class="text-center">Perfil</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $data as $r )
                <tr>
                    <td class="text-center">
                        @if ($r->image != null )
                        <img src="{{ route('users.mostrar', ['imagen' => $r->image]) }}" alt="Imagen" class=" w-px-40 h-auto rounded-circle">
                        @endif
                    </td>
                    <td><h6>{{$r->name}}</h6></td>
                    <td>{{$r->user}}</td>
                    <td class="text-center"><h6>{{$r->phone}}</h6></td>
                    <td class="text-center"><h6>{{$r->email}}</h6></td>
                    <td class="text-center">
                        <span class="badge {{ $r->status == 'ACTIVE' ? 'bg-label-success' : 'bg-label-danger' }} text-uppercase">{{ $r->status}}</span>
                    </td>
                    <td class="text-center">
                        @if($r->profile == 'Super')
                        <h6>Administradors</h6>
                        @else
                        <h6>{{$r->profile}}</h6>
                        @endif
                    </td>
                    <td class="text-center">
                        @can('User_Update')
                        <a href="javascript:void(0)" class="btn btn-warning btn-rounded mtmobile" wire:click="Edit({{ $r->id }})" title="Editar">
                            <i class="far fa-edit"></i> Editar
                        </a>
                        @endcan
                        @can('User_Destroy')
                        <a href="javascript:void(0)" onclick="Confirm('{{ $r->id }}')" class="btn btn-danger btn-rounded mtmobile" title="Eliminar">
                            <i class="far fa-trash-alt"></i> Eliminar
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{$data->links()}}
    @include('livewire.users.form')
</div>
@include('common.notis')
