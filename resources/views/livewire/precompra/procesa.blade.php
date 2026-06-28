<div class="card shadow-none border border-primary mb-2">
    <div class="card-header d-flex justify-content-between">
        <div class="d-grid">
        </div>
        <div class="d-grid">
            @php
                $conteo = DB::table('precompra_detalles')->where('precompra', $select_id)->where('status','Pendiente')->count();
            @endphp
            <button type="button" class="btn btn-primary btn-next" wire:click.prevent="GuardarCompra()" {{ $conteo == 0 ? '' : 'disabled' }}><i class="fa-solid fa-cash-register"></i> Procesar Compra (F10)</button>
        </div>
    </div>
    <div class="card-body">
        <div id="checkout-cart" class="content">
            <div class="row">
                <div class="col">
                    @include('livewire.precompra.partial.head')
                </div>
            </div>
            <hr>

            <div class="row">
                <!-- Cart left -->
                <div class="col-xl-9 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">
                    @include('livewire.precompra.partial.detalle')

                </div>
             <!-- Cart right -->
                <div class="col-xl-3">
                    <div class="border rounded p-3 mb-3">
                        @include('livewire.precompra.partial.total')
                    </div>
                </div>
            </div>
            @include('livewire.precompra.cambiaproducto')
        </div>
    </div>
</div>

@include('common.notis')
@include('livewire.precompra.scripts.events')
@include('livewire.precompra.scripts.shortcuts')
