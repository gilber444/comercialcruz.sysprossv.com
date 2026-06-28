<div class="card shadow-none border border-primary mb-2">
    <div class="card-header d-flex justify-content-between">
        <div class="d-grid">
        </div>
        <div class="d-grid">
            @if($itemsQuantitys > 0)
                <button wire:click.prevent='clearCart()'  class="btn btn-label-dark"> Cancelar (F9)</button>
            @endif
            <button type="button" class="btn btn-primary btn-next @if($itemsQuantitys == 0) disabled @endif" wire:click.prevent="Update()"><i class="fa-solid fa-cash-register"></i> Procesar Ajuste (F10)</button>
        </div>
    </div>
    <div class="card-body">
        <div id="checkout-cart" class="content">
            <div class="row">
                <div class="col">
                    @include('livewire.ajustes.head2')
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-xl-12 mb-3 mb-xl-12">
                    @include('livewire.ajustes.product')
                </div>
            </div>
            <div class="row">
                <!-- Cart left -->
                <div class="col-xl-12 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">
                    @include('livewire.ajustes.detalle2')
                </div>
                <livewire:modal-ajuste :sucursal="$sucursal">
                <!-- Cart right -->
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <!-- Price Details -->
                    @include('livewire.ajustes.total2')
                </div>
            </div>
        </div>
    </div>
</div>
@include('common.notis')
@include('livewire.ajustes.scripts.events')
@include('livewire.ajustes.scripts.shortcuts')
