<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Empresa_Create')
                <a href="javascript:void(0)" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsivep">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Logo</th>
                <th class="text-center">Empresa</th>
                <th class="text-center">Telefono</th>
                <th class="text-center">Direccion</th>
                <th class="text-center">NIT</th>
                <th class="text-center">Giro</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $empresas as $empresa )
                <tr>
                    <td class="text-center">
                        @if ($empresa->image != null )
                        <img src="{{ route('empresa.mostrar', ['imagen' => $empresa->image]) }}" alt="Imagen" class=" w-px-40 h-auto rounded-circle">
                        @endif
                    </td>
                    <td>{{ $empresa->empresa }}</td>
                    <td class="text-center">{{ $empresa->telefono }}</td>
                    <td>{{ $empresa->direccion }}</td>
                    <td class="text-center">{{ $empresa->nit }}</td>
                    <td>{{ $empresa->giro }}</td>
                    <td class="text-center">
                        @can('Empresa_Update')
                        <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$empresa->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                        @endcan
                        @can('Empresa_Destroy')
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm('{{$empresa->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                        @endcan

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$empresas->links()}}
    @include('livewire.empresa.form')
</div>
@include('common.notis')
