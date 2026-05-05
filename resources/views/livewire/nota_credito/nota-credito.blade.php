<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('NuevaNotaCredito_Index')
                    <a href="{{ route('nueva_nota_credito') }}" class="btn btn-primary btn-rounded mb-2"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Numero</th>
                <th class="text-center">Codigo</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Sello</th>
                <th class="text-center">Total</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($notas as $n)
                    <tr>
                        <td class="text-center"><b>{{ $n->numero }}</b></td>
                        <td class="text-center"><b>{{ $n->codigo }}</b></td>
                        <td>{{ $n->Rclientes->nombreCliente }}</td>
                        <td>{{ $n->fecha }}</td>
                        <td>{{ $n->sello }}</td>
                        <td class="text-end">$ {{ number_format($n->total,2) }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-info" wire:click='Generar({{ $n->id }})'> <i class="fa-solid fa-refresh"></i>
                                Generar</button>
                            {{--@can('Municipios_Update')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $n->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('Municipios_Destroy')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $n->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan--}}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $notas->links() }}
</div>

@include('common.notis')
