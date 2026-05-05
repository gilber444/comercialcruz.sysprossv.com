<div wire:ignore.self class="card shadow-none border border-primary mb-2">
    <div class="card-header d-flex justify-content-between">
        <div class="d-grid">
        </div>
        <div class="d-grid">
            @if($itemsQuantity > 0)
                <button wire:click.prevent='clearCart()'  class="btn btn-label-dark"> Cancelar (F9)</button>
            @endif
            <button type="button" class="btn btn-primary btn-next @if($itemsQuantity == 0) disabled @endif" wire:click.prevent="Store()"><i class="fa-solid fa-cash-register"></i> Procesar Compra (F10)</button>
        </div>
    </div>
    <div class="card-body">
        <div id="checkout-cart" class="content">
            <div class="row">
                <div class="col">
                    @include('livewire.compras.partial.head')
                </div>
            </div>
            <hr class="g-0 mt-0">
            <div class="row g-0 mt-0">
                <div class="col">
                    @include('livewire.compras.partial.product')
                </div>
            </div>
            <hr>
            <div class="row">
                <!-- Cart left -->
                <div class="col-xl-12 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">
                    @include('livewire.compras.partial.detalle')
                    @include('livewire.compras.partial.detalle-precios')
                </div>
                <!-- Cart right -->
                {{-- <div class="col-xl-3">
                    <div class="border rounded p-3 mb-3"> --}}
                        <!-- Offer -->
                        {{-- @include('livewire.compras.partial.product') --}}
                        <livewire:modal-compra>
                        <!-- Gift wrap -->
                        {{-- <hr class="mx-n3" /> --}}
                        <!-- Price Details -->
                        {{-- @include('livewire.compras.partial.total') --}}
                    {{-- </div>
                </div> --}}
            </div>
            <div class="row">
                <div class="col ms-auto">
                    <div class="border rounded p-3 mb-3">
                        {{-- @include('livewire.existencias.modal-solicitud') --}}
                        {{-- <hr class="mx-n3" /> --}}
                        @include('livewire.compras.partial.total')
                    </div>
                </div>
            </div>
            @include('livewire.compras.partial.proveedores')
        </div>
    </div>
</div>

@include('common.notis')
@include('livewire.compras.scripts.events')
@include('livewire.compras.scripts.shortcuts')
