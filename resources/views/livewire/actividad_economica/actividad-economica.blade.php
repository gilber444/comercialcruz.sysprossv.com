<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('ActividadEconomica_Create')
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
                @foreach ($actividad_economicas as $actividad)
                    <tr>
                        <td class="text-center"><b>{{ $actividad->codigo }}</b></td>
                        <td>{{ $actividad->valor }}</td>
                        <td class="text-center"><span
                                class="badge @if ($actividad->status == 'Activo') bg-label-success text-uppercase @else bg-label-danger text-uppercase @endif">{{ $actividad->status }}</span>
                        </td>
                        <td class="text-center">
                            @can('ActividadEconomica_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $actividad->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('ActividadEconomica_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $actividad->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $actividad_economicas->links() }}
    @include('livewire.actividad_economica.form')
</div>

@include('common.notis')
