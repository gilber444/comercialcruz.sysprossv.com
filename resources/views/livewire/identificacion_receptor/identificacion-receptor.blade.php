<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('IdentificacionReceptor_Create')
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
                <th class="text-center">Codigo</th>
                <th class="text-center">Valores</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($identificacion_receptors as $receptor)
                    <tr>
                        <td class="text-center"><b>{{ $receptor->codigo }}</b></td>
                        <td>{{ $receptor->valor }}</td>
                        <td class="text-center"><span
                                class="badge @if ($receptor->status == 'Activo') bg-label-success text-uppercase @else bg-label-danger text-uppercase @endif">{{ $receptor->status }}</span>
                        </td>
                        <td class="text-center">
                            @can('IdentificacionReceptor_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $receptor->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('IdentificacionReceptor_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $receptor->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $identificacion_receptors->links() }}
    @include('livewire.identificacion_receptor.form')
</div>

@include('common.notis')
