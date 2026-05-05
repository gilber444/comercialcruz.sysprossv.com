<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('Apis_Create')
                    <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal"
                        data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Nombre</th>
                <th class="text-center">Metodo</th>
                <th class="text-center">Tipo</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($apis as $api)
                    <tr>
                        <td><b>{{ $api->nombre }}</b></td>
                        <td class="text-center">{{ $api->metodo }}</td>
                        <td class="text-center">{{ $api->tipo }}</td>
                        <td class="text-center"><span
                                class="badge @if ($api->estado == 'Activo') bg-label-success text-uppercase @else bg-label-danger text-uppercase @endif">{{ $api->estado }}</span>
                        </td>
                        <td class="text-center">
                            @can('Apis_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $api->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('Apis_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $api->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $apis->links() }}
    @include('livewire.apis.forms')
</div>

@include('common.notis')
