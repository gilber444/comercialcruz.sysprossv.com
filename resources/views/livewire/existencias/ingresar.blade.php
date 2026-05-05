<div wire:ignore.self class="modal fade" id="ingresoModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ingresoModal">{{ $componentName }} | Autorizar Solicitud
                </h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
                @if($estado === 'Solicitado')
                <button type="button" wire:click.prevent="Update()" class="btn btn-primary"><i class='bx bx-revision'></i> Actualizar Datos</button>
                @elseif($estado === 'Autorizado')
                <button type="button" wire:click.prevent="DespacharTodos({{$selected_id}})" class="btn btn-primary"><i class="fa-solid fa-truck"></i> Despachar Todos</button>
                @endif
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-6 mb-2">
                        <div class="input-group">
                            <label class="input-group-text" for="inputGroupSelect01">Solicitud No. {{$numero}}</label>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 mb-2">
                        <div class="input-group">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">Fecha: {{$fecha}}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-6 mb-2">
                        <div class="input-group">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">Solicitante: {{$origenes}}</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 mb-2">
                        <div class="input-group">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">Pedido a: {{$destinos}}</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12 mb-2">
                        <div class="input-group">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">Detalle de la Solicitud: {{$solicitud}}</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                <div class="row">
                    <!-- Cart left -->
                    <div class="col-xl-12 mb-3" style="max-height: 500px; overflow-y: auto;">
                        @if (isset($detalle) && $detalle instanceof \Illuminate\Support\Collection && $detalle->count() > 0)
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <th class="text-center">CODIGO</th>
                                        <th class="text-center">DESCRIPCION</th>
                                        <th class="text-center">MEDIDA</th> 
                                        <th class="text-center">CANTIDAD</th>
                                        <th class="text-center">ENVIA DESDE</th>
                                        <th class="text-center"></th>
                                    </thead>
                                    <tbody wire:ignore>
                                        @forelse ($detalle as $item)
                                        <tr>
                                            <td class="text-center">{{ $item->codebar3 }}</td>
                                            <td class="text-center"><h6>{{ $item->nombreProducto }}</h6></td>
                                            <td class="text-center">
                                                @if($item->estado == 'Solicitado')
                                                <input type="text" class="form-control input-mini" wire:model.lazy="cantidad.{{$item->id}}" value="{{ $item->cantidad }}" placeholder="{{ $item->cantidad }}">
                                                @else
                                                {{ $item->cantidad }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->estado == 'Solicitado')
                                                <select wire:model.lazy="selectedDestination.{{ $item->id }}" class="form-control">
                                                    <option value="{{$sid}}">{{$snombre}}</option>
                                                    @foreach (DB::table('inventarios')->join('sucursales as s', 's.id', 'inventarios.sucursal')->where('inventarios.producto', $item->producto)->get() as $desde)
                                                        <option value="{{ $desde->id }}">
                                                            {{ $desde->nombre }} -> {{$desde->existencia}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @else
                                                {{$snombre}}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($item->estado === 'Solicitado')
                                                    <button wire:click.prevent="Aprobar({{$item->id}})" type="button" class="btn btn-sm btn-label-success">
                                                        <i class="fa-solid fa-check"></i> Autorizar
                                                    </button>

                                                @elseif($item->estado === 'Autorizado')
                                                    <button wire:click.prevent="Despachar({{$item->id}})" type="button" class="btn btn-sm btn-label-warning">
                                                        <i class="fa-solid fa-truck"></i> Despachar
                                                    </button>
                                                @elseif($item->estado === 'Despachado')
                                                    <a href="" class="btn btn-sm btn-label-info">
                                                        <i class="fa-solid fa-download"></i> Descargar
                                                        </a>
                                                @else
                                                <!-- Estado desconocido -->
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">SIN RESULTADOS</td>
                                        </tr>
                                    @endforelse

                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-primary mb-4" role="alert">
                                <div class="d-flex gap-3 text-center">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-center rounded-pill bg-primary border-label-primary p-5 me-2">
                                            <i class="fa-solid fa-cart-shopping fs-1"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">Agrega productos a la Solicitud</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                        <!-- Shopping bag -->
                        @if(DB::table('solicitudes_detalles')->where('solicitud', $selected_id)->get())
                        @foreach(DB::table('solicitudes_detalles')->where('solicitud', $selected_id)->get() as $product)
                        @endforeach
                        @else
                       <h5> No hay productos en la solicitud</h5>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" wire:click.prevent='resetUI()' class="btn btn-label-secondary" data-bs-dismiss="modal" >Cerrar</button>
            </div>
        </div>
    </div>
</div>
