
<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
            <div class="dropdown">
                <a href="{{ route('nuevo-sujetoexcluido') }}" class="btn btn-primary btn-rounded mb-2">
                    <i class="fa-solid fa-plus"></i> Agregar Sujeto
                </a>
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Detalle</th>
                <th class="text-center">Sucursal</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Total</th>
                <th class="text-center">Status</th>
                <th class="text-center">Acciones</th>
            </thead>
            <tbody>
                @foreach ($sujetos as $sujeto)
                    @if($sujeto && isset($sujeto->id))
                    <tr>
                        <td class="text-center">{{ $sujeto->id }}</td>
                        <td class="text-center">{{ $sujeto->fecha_hora_generacion }}</td>
                        <td class="text-center">{{ $sujeto->detalle ?? '-' }}</td>
                        <td class="text-center">{{ $sujeto->sucursal_nombre ?? '-' }}</td>
                        <td class="text-center">{{ $sujeto->cliente_nombre ?? '-' }}</td>
                        <td class="text-end">
                            $ {{ number_format($sujeto->total_pagar, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge
                                @if ($sujeto->status == 'Realizado') bg-label-success
                                @elseif($sujeto->status == 'Pendiente') bg-label-warning
                                @elseif($sujeto->status == 'Anulado') bg-label-danger
                                @else bg-label-secondary @endif
                                text-uppercase">
                                {{ $sujeto->status ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @can('Print_Ajuste')
                            <a class="btn btn-label-info btn-sm" href="javascript:void(0);" wire:click="Print('{{ $sujeto->id }}')">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>