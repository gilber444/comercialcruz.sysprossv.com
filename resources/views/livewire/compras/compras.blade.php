<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('Compras_Create')
                    <a href="{{ route('nueva-comra') }}" class="btn btn-primary btn-rounded mb-2"> <i
                            class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Factura #</th>
                <th class="text-center">Proveedor</th>
                <th class="text-center">Fecha de Compra</th>
                <th class="text-center">Status</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Total</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ($compras as $compra)
                    <tr>
                        <td><b>{{ $compra->factura }} # {{ $compra->correlativo }}</b></td>
                        <td>{{ $compra->nombre }}</td>
                        <td class="text-center">{{ $compra->fecha }}</td>
                        <td class="text-center">
                            <span class="badge
                                {{ $compra->estado == 'Pagado' ? 'bg-label-success' : '' }}
                                {{ $compra->estado == 'Pendiente' ? 'bg-label-warning' : '' }}
                                {{ $compra->estado == 'Anulado' ? 'bg-label-dark' : '' }}
                                text-uppercase">
                                {{ $compra->estado }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="text-black"
                                wire:click="cargarDetallesCompra('{{ $compra->id }}')">
                                @php
                                    $totalProductos = DB::table('compras_detalles')
                                        ->where('compra', $compra->id)
                                        ->sum('cantidad');
                                    echo $totalProductos;
                                @endphp
                            </a>
                        </td>
                        <td class="text-end">$
                            @php
                                $total = DB::table('compras_detalles')
                                    ->where('compra', $compra->id)
                                    ->sum('total');
                                echo number_format($total, 2);
                            @endphp
                        </td>
                        <td class="text-center">
                            @if (
                                $compra->estado !== 'Anulado' &&
                                    DB::table('compras_detalles')->where('compra', $compra->id)->count() > 0)
                                <a href="javascript:void(0)" class="btn btn-primary" onclick="ConfirmA('{{ $compra->id }}')">
                                    <i class="fa-solid fa-box-archive"> </i> Anular
                                </a>
                            @endif
                            @if ($compra->estado !== 'Anulado' && $compra->estado !== 'Realizado')
                                @can('Compras_Update')
                                    <a class="btn btn-warning" href="{{ route('editarCompra', ['id' => $compra->id]) }}"><i
                                            class="bx bx-edit-alt"></i>Editar</a>
                                @endcan
                            @endif
                            @if (
                                $compra->estado !== 'Anulado' &&
                                    $compra->estado !== 'Realizado' &&
                                    DB::table('compras_detalles')->where('compra', $compra->id)->count() == 0)
                                @can('Compras_Destroy')
                                    <a class="btn btn-danger" href="javascript:void(0);"
                                        onclick="Confirm('{{ $compra->id }}')"><i class="bx bx-trash"></i>Eliminar</a>
                                @endif
                    @endif
                    </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
        {{ $compras->links() }}
        @include('livewire.compras.detCompra')
    </div>
    @include('common.notis')
