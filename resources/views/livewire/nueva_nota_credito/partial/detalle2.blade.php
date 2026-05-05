{{-- @if ($itemsQuantity > 0) --}}


<div class="row p-2">


    <div class="table-responsive text-nowrap">


        <table class="table table-hover table-sm">


            <thead>


                <th class="text-center">CODIGO</th>


                <th class="text-center">DESCRIPCION</th>


                <th class="text-center">CANTIDAD</th>


                <th class="text-center"></th>


            </thead>


            <tbody>


                @foreach ( $solicitudes as $item)


                <tr>


                    <td></td>


                    <td><h6>{{$item->Rproducto->nombreProducto}}</h6></td>


                    <td class="text-center" width='15%'>


                        {{-- <input type="text" class="form-control" wire:model='can.{{$item->id}}' wire:keydown.enter="updateQty({{$item->id}})"> --}}


                        {{ $item->cantidad }}


                    </td>


                    <td class="text-center">


                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm2('{{$item->id}}')"><i class="bx bx-trash"></i></a>


                    </td>


                </tr>


                @endforeach


                @foreach ( $cart as $item)


                <tr>


                    <td class="text-center">{{$item->codebar}}</td>


                    <td><h6>{{ $item->name }}</h6></td>


                    <td class="text-center" width='15%'>


                        <input type="text" class="form-control" wire:model='can.{{$item->id}}' wire:keydown.enter="updateQty({{$item->id}})">


                    </td>


                    <td class="text-center">


                        <a class="btn btn-danger" href="javascript:void(0);" wire:click.prevent='removeItem({{$item->id}})'><i class="bx bx-trash"></i></a>


                    </td>


                </tr>


                @endforeach


                


            </tbody>


        </table>


    </div>


</div>