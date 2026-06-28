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
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Numero</th>
                <th class="text-center">Empresa</th>
                <th class="text-center">Sucursal</th>
                <th class="text-center">Caja</th>
                <th class="text-center">Realizado Por</th>
                <th class="text-center">Monto</th>
                <th class="text-center">Fecha y Hora</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($remesas as $r)
                    <tr>
                        <td class="text-center"><b>{{ $r->numero }}</b></td>
                        <td>{{ $r->Rempresas->empresa }}</td>
                        <td class="text-center">{{ $r->Rsucursales->nombre }}</td>
                        <td class="text-center">{{ $r->Rcajas->caja }}</td>
                        <td>{{ $r->Rcajeros->name }}</td>
                        <td class="text-end"> $ {{ number_format($r->monto, 2) }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }} -- {{ $r->hora }}</td>
                        <td class="text-center">
                            @can('Remesas_Destroy')
                                <a class="btn btn-label-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $r->id }}')"><i class="bx bx-trash"></i></a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $remesas->links() }}
</div>
@include('common.notis')
