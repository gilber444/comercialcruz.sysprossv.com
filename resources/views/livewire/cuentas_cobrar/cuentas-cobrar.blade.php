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
                <th class="text-center">#</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Fecha Credito</th>
                <th class="text-center">Fecha Vencimiento</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Deuda</th>
                <th class="text-center">Saldo</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    @php
                        $fechaVencimiento = \Carbon\Carbon::parse($d->fechaPago);
                        $diasRestantes = $fechaVencimiento->diffInDays(now());
                        $colorClass = ''; // Default color

                        if ($diasRestantes <= 0) {
                            $colorClass = 'text-danger';
                        } elseif ($diasRestantes <= 5) {
                            $colorClass = 'text-warning';
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $d->correlativo }}</td>
                        <td class="text-center"><b>{{ $d->Rclientes->nombreCliente }}</b></td>
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($d->fechaCredito)->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            <span class="{{ $colorClass }}">
                                {{ $fechaVencimiento->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0);" wire:click="CargaProductos({{ $d->id }})" class="btn btn-label">
                                {{ $d->Rventas->rdetalle_ventas_count }}
                            </a>
                        <td class="text-end">
                            $ {{ number_format($d->total, 2) }}
                        </td>
                        <td class="text-end">
                            $ {{ number_format($d->saldo,2) }}
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0);" wire:click="CargaDatos({{ $d->id }})" class="btn btn-label-primary">
                                <i class="fa-solid fa-dollar"></i> Cobrar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" colspan="7">No hay registros para mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $data->links() }}
    @include('livewire.cuentas_cobrar.detalle')
    @include('livewire.cuentas_cobrar.form')
</div>

@include('common.notis')
