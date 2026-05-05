<div wire:ignore.self class="card shadow-none border border-primary mb-2">

    @php
        $estadoPedido = false;
        if ($solicitudes && $solicitudes->count() > 0) {
            $estadoPedido = $solicitudes->first()->estado === 'Pedido';
        }
    @endphp

    <div class="card-header d-flex justify-content-between">


        <div class="d-grid">


        </div>


        <div class="d-flex justify-content-between gap-2">


     {{-- Botón Print con clase info --}}


    @can('Solicitudes_Print')


        <button type="button"


            class="btn btn-info btn-next @if($selected_id == 0) disabled @endif" title="Vista Previa"


            onclick="window.open('{{ url('existencias/pdf/' . $selected_id) }}', '_blank')">


            <i class="fa-solid fa-print"></i> Vista Previa


        </button>


    @endcan


    {{-- Botones a la derecha --}}


    <div class="d-flex gap-2">


        @if($selected_id > 0)


            <button wire:click.prevent='clearCart()' class="btn btn-label-dark">Cancelar (F9)</button>


        @endif


        @if ($estadoPedido)


            <button type="button" class="btn btn-success btn-next"


                wire:click.prevent="EnviarPedido({{ $selected_id }})">


                <i class="fa-solid fa-paper-plane"></i> Enviar Pedido (F10)


            </button>


        @else


            <button type="button" class="btn btn-primary btn-next @if($selected_id == 0) disabled @endif"


                wire:click.prevent="Despachar()">


                <i class="fa-solid fa-cash-register"></i> Despachar Solicitud (F10)


            </button>


        @endif


    </div>


</div>





    </div>


    <div class="card-body">


        <div id="checkout-cart" class="content">


            <div class="row g-0">


                <div class="col">


                    @include('livewire.existencias.partial.head2')


                </div>


            </div>


            <hr class="g-0 mt-0">


            <div class="row g-0 mt-0">


                <div class="col">


                    @include('livewire.existencias.partial.product')


                </div>


            </div>





            <div class="row">


                <!-- Cart left -->


                <div class="col-xl-12 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">


                    @include('livewire.existencias.partial.detalle2')


                    @include('livewire.existencias.partial.detalle-precios')





                </div>


                <!-- Cart right -->





            </div>


            <div class="row">


                <div class="col ms-auto">


                    <div class="border rounded p-3 mb-3">


                        @include('livewire.existencias.modal-solicitud')


                        {{-- <hr class="mx-n3" /> --}}


                        @include('livewire.existencias.partial.total2')


                    </div>


                </div>


            </div>


            @include('livewire.existencias.partial.formas')


            @include('livewire.existencias.partial.detalle-precios')


            {{-- @include('livewire.existencias.print') --}}


        </div>


    </div>


</div>





@include('common.notis')


@include('livewire.existencias.scripts.events')


@include('livewire.existencias.scripts.shortcuts')


{{--@include('livewire.existencias.scripts.scripts')--}}


{{--@include('livewire.existencias.scripts.scan')--}}


{{--














--}}


