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
                <th class="text-center">Proveedor</th>
                <th class="text-center">Factura</th>
                <th class="text-center">Fecha Compra</th>
                <th class="text-center">Fecha Vencimiento</th>
                <th class="text-center">Deuda</th>
                <th class="text-center">Saldo</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    @php
                        $fechaVencimiento = \Carbon\Carbon::parse($d->fecha_vencimiento);
                        $diasRestantes = $fechaVencimiento->diffInDays(now());
                        $colorClass = ''; // Default color

                        if ($diasRestantes <= 0) {
                            $colorClass = 'text-danger';
                        } elseif ($diasRestantes <= 5) {
                            $colorClass = 'text-warning';
                        }
                    @endphp
                    <tr>
                        <td class="text-center"><b>{{ $d->Rproveedores->nombre }}</b></td>
                        <td>{{ $d->Rcompras->RtipoCompra->tipo }} :  {{ $d->Rcompras->correlativo }}</td>
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($d->Rcompras->fecha)->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            <span class="{{ $colorClass }}">
                                {{ $fechaVencimiento->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-end">
                            $ {{ number_format($d->monto_total, 2) }}
                        </td>
                        <td class="text-end">
                            $ {{ number_format($d->saldo,2) }}
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0);" wire:click="CargaDatos({{ $d->id }})" class="btn btn-label-primary">
                                <i class="fa-solid fa-dollar"></i> Pagar
                            </a>
                            @can('Departamentos_Updates')
                                <a class="btn btn-warning" href="javascript:void(0);"
                                    wire:click="Edit('{{ $departamento->id }}')"><i class="bx bx-edit-alt"></i>Editar</a>
                            @endcan
                            @can('Departamentos_Destroys')
                                <a class="btn btn-danger" href="javascript:void(0);"
                                    onclick="Confirm('{{ $departamento->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                            @endcan

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
    @include('livewire.cuentas_pagar.form')
</div>

@include('common.notis')
