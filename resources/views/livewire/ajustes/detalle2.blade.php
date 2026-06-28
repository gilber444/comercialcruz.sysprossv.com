@if ($itemsQuantitys > 0)
    <div class="row p-1">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-sm">
                <thead>
                    <th class="text-center">CODIGO</th>
                    <th class="text-center">DESCRIPCION</th>
                    <th class="text-center">MEDIDA</th>
                    <th class="text-center">CANTIDAD</th>
                    <th class="text-center">TOTAL</th>
                </thead>
                <tbody>
                    @foreach ($carts as $item)
                        <tr>
                            <td></td>
                            <td>{{ $item->Rproductos->nombreProducto }}</td>
                            <td>{{ $item->Rmedida->unidad }}</td>
                            <td class="text-center" width='10%'>
                                {{ $item->cantidad }}
                            </td>
                            <td class="text-center" width='10%'>{{ number_format($item->total, 2) }}</td>
                            <td class="text-center">
                                <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm2('{{$item->id}}')"><i class="bx bx-trash"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    @foreach ($cart as $item)
                        <tr>
                            <td>{{ $item->codebar }}</td>
                            <td>{{ $item->name }}</td>
                            {{-- <td class="text-center" width='10%'>
                                <select class="form-control" wire:model='uni.{{ $item->id }}'
                                    wire:change='updateUni({{ $item->id }})'>
                                    <option value="{{$item->unidad}}">{{$item->medida}}</option>
                                    @foreach (DB::table('precios')->join('medidas as m', 'm.id', 'precios.medida')->select('m.id','m.unidad')->where('precios.producto', $item->producto)->whereNull('precios.deleted_at')->groupBy('m.id', 'm.unidad')->get() as $medi)
                                        <option value="{{ $medi->id }}">{{ $medi->unidad }}</option>
                                    @endforeach
                                </select>
                            </td> --}}
                            <td>{{ $item->medida }}</td>
                            <td class="text-center" width='10%'>
                                <input type="text" class="form-control w-100" placeholder="{{ $item->quantity }}"
                                    wire:model='can.{{ $item->id }}'
                                    wire:keydown.enter="updateQty({{ $item->id }})"
                                    value="{{ $item->quantity }} ">{{ $item->ingreso }}
                            </td>
                            <td class="text-center" width='10%'>{{ number_format($item->total, 2) }}</td>
                            <td class="text-center" width='10%'>
                                <a href="#" class="btn btn-danger" wire:click.prevent='removeItem({{ $item->id }})'><i class="bx bx-trash"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
                <div class="fw-bold">Agrega productos al ajuste</div>
            </div>
        </div>
    </div>
@endif

{{-- @include('livewire.compras.precios') --}}
