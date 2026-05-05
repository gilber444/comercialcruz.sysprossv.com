<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('Precompra_Create')
                    <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal"
                        data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive ">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Numero de Control</th>
                <th class="text-center">Codigo de Generacion</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Proveedor</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Total</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($data as $pre)
                    <tr>
                        <td>{{ $pre->numeroControl }}</td>
                        <td>{{ $pre->codigoGeneracion }}</td>
                        <td class="text-center">{{ $pre->fecEmi }}</td>
                        <td>{{ $pre->Proveedores->nombre }}</td>
                        <td class="text-center">{{ $pre->detalles_count }}</td>
                        <td>$ {{ $pre->totalPagar }}</td>
                        <td>{{ $pre->estado }}</td>
                        <td class="text-center">
                            @if($pre->estado == 'Ingresado')
                                @can('Precompra_Update')
                                <a class="btn btn-warning" href="{{ route('procesar', $pre->id) }}"><i class="fa-solid fa-refresh"></i>Procesar</a>
                                @endcan
                                @can('Precompra_Destroy')
                                    <a class="btn btn-danger" href="javascript:void(0);"
                                        onclick="Confirm('{{ $pre->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $data->links() }}
    @include('livewire.precompra.form')
</div>
@include('common.notis')
