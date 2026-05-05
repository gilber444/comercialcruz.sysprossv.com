@php

    use Illuminate\Support\Facades\DB;

    use Carbon\Carbon;

@endphp

@if ($cart->isNotEmpty())

<div class="row p-2">

    <div class="table-responsive text-nowrap">

        <table class="table table-hover table-sm">

            <thead>

                <th class="text-center">CODIGO</th>

                <th class="text-center">DESCRIPCION</th>

                <th class="text-center">CANTIDAD</th>

                <th class="text-center">PRECIO</th>

                <th class="text-center">TOTAL</th>

                <th class="text-center"></th>

            </thead>

            <tbody>



                @forelse ( $cart as $item)

                <tr>

                    @if($item->Rproductos->codebar)

                        <td class="text-center">{{$item->Rproductos->codebar}}</td>

                    @else

                        <td class="text-center">N/A</td>

                    @endif



                    @can('DescripcionNotaCredito')

                        <td style="cursor: pointer" class="product-cell text-center">

                            <input

                            type="text"

                            class="form-control"

                            value= {{$item->name}}

                            id="name-{{ $item->id }}"

                            wire:model="name.{{ $item->id }}"

                            wire:keydown.enter="updateName({{ $item->id }})"

                        </td>

                    @else

                        <td class="text-center">{{$item->name}}</td>

                    @endcan

                    <td class="text-center" width='15%'>

                        <input

                        type="text"

                        class="form-control"

                        value= {{$item->cantidad}}

                        id="can-{{ $item->id }}"

                        wire:model="can.{{ $item->id }}"

                        wire:keydown.enter="updateQty({{ $item->id }})"

                        onclick="this.select()">{{ $item->descargar }}

                    </td>

                    <td class="text-center" width='15%'>

                        <input

                        type="text"

                        class="form-control"

                        value= {{$item->precio}}

                        id="cos-{{ $item->id }}"

                        wire:model="cos.{{ $item->id }}"

                        wire:keydown.enter="updateCosto({{ $item->id }})"

                    </td>

                    {{-- <td class="text-end"> $ {{ number_format($item->costo, 2) }}</td> --}}

                    <td class="text-end"> $ {{ number_format( $item->total, 2) }}</td>

                    <td class="text-center">

                        <button wire:click.prevent='removeItem({{$item->id}})' type="button" class="btn btn-sm btn-label-primary">

                            <i class="fa-solid fa-minus"></i>

                        </button>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="8" class="text-center">SIN RESULTADOS</td>

                </tr>

                @endforelse

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

                <div class="fw-bold">Productos del credito fiscal</div>

            </div>

        </div>

    </div>

@endif



