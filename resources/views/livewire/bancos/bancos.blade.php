<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            <div class="dropdown">
                @can('Bancos_Create')
                <a href="javascript:void(0)" class="btn btn-label-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#myModal"> <i class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Banco</th>
                <th class="text-center">Cuenta</th>
                <th class="text-center">Correlativo</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $bancos as $b )
                <tr>
                    <td class="text-center"><b>{{ $b->id }}</b></td>
                    <td>{{ $b->nombre }}</td>
                    <td class="text-center">{{ $b->cuenta }}</td>
                    <td class="text-center">{{ $b->correlativo }}</td>
                    <td class="text-center">
                        @can('Bancos_Update')
                        <a class="btn btn-label-warning" href="javascript:void(0);" wire:click="Edit('{{$b->id}}')"><i class="bx bx-edit-alt"></i></a>
                        @endcan
                        @can('Bancos_Destroy')
                        <a class="btn btn-label-danger" href="javascript:void(0);" onclick="Confirm('{{$b->id}}')"><i class="bx bx-trash"></i></a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$bancos->links()}}
    @include('livewire.bancos.form')
</div>

@include('common.notis')
