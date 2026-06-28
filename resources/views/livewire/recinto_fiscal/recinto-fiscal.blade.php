<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('RecintoFiscal_Create')
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
                @foreach ($recinto_fiscals as $recinto)
                    <tr>
                        <td class="text-center"><b>{{ $recinto->codigo }}</b></td>
                        <td>{{ $recinto->valor }}</td>
                        <td class="text-center"><span
                                class="badge @if ($recinto->status == 'Activo') bg-label-success text-uppercase @else bg-label-danger text-uppercase @endif">{{ $recinto->status }}</span>
                        </td>
                        <td class="text-center">
                            @can('RecintoFiscal_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $recinto->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('RecintoFiscal_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $recinto->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $recinto_fiscals->links() }}
    @include('livewire.recinto_fiscal.form')
</div>
@include('common.notis')
