<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('Tocken_Create')
                    <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal"
                        data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Tocken</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($tockens as $t)
                    <tr>
                        <td class="text-center">
                            <div style="width: 1024px; font-size: 10px;">
                            <b>{{ $t->tocken }}</b>
                            </div>
                        </td>
                        <td>{{ $t->fecha }}</td>
                        <td class="text-center"><span
                                class="badge @if ($t->estado == 'Activo') bg-label-success text-uppercase @else bg-label-danger text-uppercase @endif">{{ $t->estado }}</span>
                        </td>
                        <td class="text-center">
                            @can('Tocken_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $t->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('Tocken_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $t->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $tockens->links() }}
    @include('livewire.tocken.form')
</div>

@include('common.notis')
