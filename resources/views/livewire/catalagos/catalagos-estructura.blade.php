<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Categorias_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Codigo</th>
                <th class="text-center">Valores</th>
                <th class="text-center">Catalago</th>
                <th class="text-center">Referencia</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $estructuras as $estructura )
                <tr>
                    <td class="text-center"><b>{{ $estructura->codigo }}</b></td>
                    <td style="width: 25%">{{ $estructura->valores }}</td>
                    <td>{{ $estructura->cata }}</td>
                    <td>
                        @if ($estructura->dependencia <> 0)
                            @php
                                $ref = DB::table('catalagos_estructuras')->find($estructura->dependencia);
                                echo $ref->valores;
                            @endphp
                        @endif
                    </td>
                    <td>{{ $estructura->estado }}</td>
                    <td class="text-center">
                        @can('Categorias_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$estructura->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Categorias_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$estructura->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$estructuras->links()}}
    @include('livewire.catalagos.estructuraForms')
</div>
@include('common.notis')
