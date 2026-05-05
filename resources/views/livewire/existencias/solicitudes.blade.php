<div wire:ignore.self class="card shadow-none border border-primary mb-2">

    @php
        $appModo = env('APP_MODO', 'local');
        $userProfile = auth()->user()->profile ?? '';
        $esPedidoLocal = ($appModo === 'local' && $userProfile !== 'Administrador' && $userProfile !== 'Super' && $userProfile !== 'BODEGA');
    @endphp

    <div class="card-header d-flex justify-content-between">

        <div class="d-grid">

        </div>

        <div class="d-grid">

            @if ($itemsQuantity > 0)

                <button wire:click.prevent='clearCart()' class="btn btn-label-dark"> Cancelar (F9)</button>

            @endif

            <button

                type="button"

                class="btn btn-primary btn-next"

                wire:click.prevent="Store"

                wire:loading.attr="disabled"

                wire:target="Store"

                @if ($itemsQuantity == 0) disabled @endif

            >

                <span wire:loading.remove wire:target="Store">

                    @if ($esPedidoLocal)
                        <i class="fa-solid fa-save"></i> Guardar Pedido (F10)
                    @else
                        <i class="fa-solid fa-cash-register"></i> Procesar Solicitud (F10)
                    @endif

                </span>



                <span wire:loading wire:target="Store">

                    <i class="fa-solid fa-spinner fa-spin"></i> 

                    @if ($esPedidoLocal)
                        Guardando pedido...
                    @else
                        Guardando solicitud...
                    @endif

                </span>

            </button>

        </div>

    </div>

    <div class="card-body">

        <div id="checkout-cart" class="content">

            <div class="row g-0">

                <div class="col">

                    @include('livewire.existencias.partial.head')

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

                    @include('livewire.existencias.partial.detalle')

                    @include('livewire.existencias.partial.detalle-precios')



                </div>

                <!-- Cart right -->



            </div>

            <div class="row">

                <div class="col ms-auto">

                    <div class="border rounded p-3 mb-3">

                        @include('livewire.existencias.modal-solicitud')

                        {{-- <hr class="mx-n3" /> --}}

                        @include('livewire.existencias.partial.total')

                    </div>

                </div>

            </div>

            @include('livewire.existencias.partial.formas')

            {{-- @include('livewire.existencias.print') --}}

        </div>

    </div>

</div>



@include('common.notis')

@include('livewire.existencias.scripts.events')

@include('livewire.existencias.scripts.shortcuts')

{{-- @include('livewire.existencias.scripts.scripts') --}}

{{-- @include('livewire.existencias.scripts.scan') --}}

{{--









--}}

