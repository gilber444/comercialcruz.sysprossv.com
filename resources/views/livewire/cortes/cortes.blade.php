<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Corte</th>
                <th class="text-center">Caja</th>
                <th class="text-center">Sucursal</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Total</th>
                <th class="text-center">Efectivo</th>
                <th class="text-center">Diferencia</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($cortes as $c)
                    <tr>
                        <td class="text-center"><b>{{ $c->corte }}</b></td>
                        <td class="text-center">{{ $c->Rparametros->caja }}</td>
                        <td class="text-center">{{ $c->Rsucursal->nombre }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}
                        </td>
                        <td class="text-end">${{ number_format( $c->totalGlobal, 2) }}</td>
                        <td class="text-end">${{ number_format($c->totalEfectivo, 2) }}</td>
                        <td class="text-end">${{ number_format($c->diferencia, 2) }}</td>
                        <td class="text-center">
                            @can('Corte_Update')
                                <a class="btn btn-label-primary" href="javascript:void(0);" wire:click="CorteZ('{{ $c->id }}')">Corte</a>
                            @endcan
                            @can('Cortez_Print')
                                <a class="btn btn-label-info btn-sm" href="javascript:void(0);" wire:click="Print('{{ $c->id }}')"><i class="fa-solid fa-print"></i></a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{  $cortes->links() }}
    @include('livewire.cortes.corteZ')
</div>

@include('common.notis')
