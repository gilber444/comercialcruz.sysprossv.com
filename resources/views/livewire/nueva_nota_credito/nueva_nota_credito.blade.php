<div wire:ignore.self class="card shadow-none border border-gray mb-2">

    <div class="card-header d-flex justify-content-between">

        <div class="d-grid">

        </div>

        <div class="d-grid">

            <button type="button" class="btn btn-primary btn-next @if($itemsQuantity == 0) disabled @endif" wire:click.prevent="Store()"><i class="fa-solid fa-cash-register"></i> Procesar Nota (F10)</button>

        </div>

    </div>

    <div class="card-body">

        <div id="checkout-cart" class="content">

            <div class="row">

                <div class="col">

                    @include('livewire.nueva_nota_credito.partial.head')

                </div>

            </div>

            <hr>

            <div class="row">

                <!-- Cart left -->

                <div class="col-xl-9 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">

                    @include('livewire.nueva_nota_credito.partial.detalle')

                </div>

                <div class="col-xl-3">

                    <div class="border rounded p-3 mb-3">

                        @include('livewire.nueva_nota_credito.partial.product')

                        @include('livewire.nueva_nota_credito.modal_nota')

                        @include('livewire.nueva_nota_credito.partial.total')

                    </div>

                </div>

            </div>

            @include('livewire.nueva_nota_credito.partial.formas')

        </div>

    </div>

</div>



@include('common.notis')

@include('livewire.nueva_nota_credito.scripts.events')

@include('livewire.nueva_nota_credito.scripts.shortcuts')