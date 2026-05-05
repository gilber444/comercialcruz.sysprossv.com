<div wire:ignore.self class="card shadow-none border border-primary mb-2">
    <div class="card-header d-flex justify-content-between">
        <div class="d-grid">
        </div>
        <div class="d-grid">
            @if(isset($itemsQuantity) && $itemsQuantity > 0)
                <button wire:click.prevent='resetForm' class="btn btn-label-dark">Cancelar (F9)</button>
            @endif
          <!-- <button type="button" class="btn btn-primary btn-next @if($itemsQuantity == 0) disabled @endif" wire:click.prevent="Store()"><i class="fa-solid fa-cash-register"></i> Guardar</button> -->
<button type="button" class="btn btn-primary btn-next" wire:click.prevent="Store()">
    <i class="fa-solid fa-cash-register"></i> Procesar Sujeto Excluido (F10) 
</button>
        </div>
    </div>
    <div class="card-body">
        <div id="checkout-cart" class="content">
            <div class="row">
                <div class="col">
                    @include('livewire.sujeto.head')
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-xl-12 mb-3 mb-xl-12">
                    @include('livewire.sujeto.product')
                </div>
            </div>
                        <div class="row">
                <!-- Cart left -->
                <div class="col-xl-12 mb-3 mb-xl-0" style="max-height: 500px; overflow-y: auto;">   
                    @include('livewire.sujeto.detalle')
                </div>
                   <livewire:modal-sujeto-controller/> 
                              <!-- Cart right -->
            </div>
                @include('livewire.sujeto.modal-sujetoexcluido')
                <!-- Cart right -->
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <!-- Price Details -->
                    @include('livewire.sujeto.total')
                </div>
            </div>
        </div>
    </div>
</div>

{{--@include('common.notis')--}}
@include('livewire.sujeto.scripts.shortcuts')
@include('livewire.sujeto.scripts.events')