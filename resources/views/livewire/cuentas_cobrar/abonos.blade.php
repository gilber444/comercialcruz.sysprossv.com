<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            {{--
            <div class="dropdown">
                @can('Departamentos_Create')
                    <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal"
                        data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div> --}}
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Correlativo</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Credito</th>
                <th class="text-center">Cobrado por</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Hora</th>
                <th class="text-center">Monto</th>
                <!--<th class="text-center">Actions</th>-->
            </thead>
            <tbody>
                @forelse ($data as  $d)
                    <tr>
                        <td class="text-center"><b>{{ $d->correlativo }}</b></td>
                        <td>{{ $d->Rcreditos->Rclientes->nombreCliente }}</td>
                        <td>Credito No. {{ $d->Rcreditos->correlativo }}</td>
                        <td>{{ $d->Rusers->name }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($d->hora)->format('H:i:s') }}</td>
                        <td class="text-end"> $ {{ number_format($d->monto, 2) }}</td>
                        {{--
                        <td class="text-center">
                            @can('Departamentoss_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $departamento->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('Departamentoss_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $departamento->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan

                        </td>
                        --}}
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" colspan="9">No hay registros para mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
    {{-- $data->links() --}}
</div>

@include('common.notis')
