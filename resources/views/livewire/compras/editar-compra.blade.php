<div wire:ignore.self class="card shadow-none border border-primary mb-2">
    <div class="card-header d-flex justify-content-between">
        <div class="d-grid">
        </div>
        <div class="d-grid">
            <button type="button" class="btn btn-primary btn-next" wire:click.prevent="Update({{ $select_id }})"><i class="fa-solid fa-cash-register"></i> Editar Compra</button>
        </div>
    </div>
    <div class="card-body">
        <div id="checkout-cart" class="content">
            <div class="row">
                <div class="col">
                    @include('livewire.compras.partial.head2')
                </div>
            </div>
            <hr>
            <div class="row">
                <!-- Cart left -->
                <div class="col-xl-9 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">
                    @include('livewire.compras.partial.detalle2')

                </div>
                <!-- Cart right -->
            {{--    <div class="col-xl-3">
                    <div class="border rounded p-3 mb-3">
                        <!-- Offer-->
                        {{-- @include('livewire.compras.partial.product')
                        <livewire:modal-compra>

                        <hr class="mx-n3" />

                        @include('livewire.compras.partial.total2')
                    </div>
                </div>
            </div>
            @include('livewire.compras.partial.proveedores')
            --}}
        </div>
    </div>
</div>

@include('common.notis')
@include('livewire.compras.scripts.events')
@include('livewire.compras.scripts.shortcuts')
