<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
            @can('Cotizaciones_Create')
                <div class="dropdown">
                    <a href="{{ route('nueva-cotizacion')}}" class="btn btn-primary btn-rounded mb-2" > <i class="fa-solid fa-plus"></i> Agregar Cotizacion</a>
                </div>
            @endcan
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">#</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Total</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @foreach ( $cotizaciones as $c )
                        <tr>
                            <td class="text-center">{{ $c->correlativo }}</td>
                            <td class="text-center"> {{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</td>
                            <td class="text-center">{{ $c->Rcliente->nombreCliente }}</td>
                            <td class="text-center">
                                <a href="javascript:void(0)" class="text-black" wire:click="cargarDetalles('{{ $c->id }}')">
                                    {{ $c->total_cantidad }}
                                </a>
                            </td>
                            <td class="text-center"> $ {{ number_format($c->total, 2) }}</td>
                            <td class="text-center">
                                    <span class="badge
                                        @if($c->estado == 'Facturado')
                                            bg-label-warning
                                        @elseif($c->estado == 'Cotizado')
                                            bg-label-warning
                                        @endif
                                        text-uppercase">
                                        {{ $c->estado }}
                                    </span>
                            </td>
                            <td class="text-center">
                                @can('Cotizaciones_Print')
                                    <a class="btn btn-icon btn-label-info" href="{{ route('reportCotizacion', ['id' => $c->id]) }}" title="Imprimir Cotizacion" target="_blank"><i class="fa-solid fa-print"></i></a>
                                @endcan
                                @can('Cotizacioness_Update')
                                    <a class="btn btn-icon btn-label-primary" href="{{ route('editar-cotizacion', ['id' => $c->id]) }}" title="Editar Cotizacion"><i class="fa-solid fa-edit"></i></a>
                                @endcan
                            {{--
                                @if ($ajuste->status !== 'Anulado' && DB::table('ajustes_detalles')->where('ajuste', $ajuste->id)->whereNull('ajustes_detalles.deleted_at')->count() > 0)
                                    <a href="javascript:void(0)" wire:click="anular('{{$ajuste->id}}')" class="btn btn-primary">
                                        <i class="fa-solid fa-box-archive"> Anular</i>
                                    </a>
                                @endif
                                @if ($ajuste->status !== 'Anulado')
                                <a class="btn btn-warning" href="javascript:void(0);" wire:click="Edit('{{$ajuste->id}}')"><i class="bx bx-edit-alt"></i>Editar</a>
                                @endif
                                @if(DB::table('ajustes_detalles')->where('ajuste', $ajuste->id)->whereNull('ajustes_detalles.deleted_at')->count() == 0)
                                <a class="btn btn-danger" href="javascript:void(0);"  onclick="Confirm('{{$ajuste->id}}')"><i class="bx bx-trash"></i>Eliminar</a>
                                @endif
                            --}}
                            </td>
                        </tr>
                @endforeach
            </tbody>
        </table>
        @include('livewire.cotizaciones.detCotizaciones')
    </div>
    {{$cotizaciones->links()}}
</div>
@include('common.notis')
