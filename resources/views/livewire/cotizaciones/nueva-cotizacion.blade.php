<div class="card">
    <div class="card-header border rounded p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $pageTitle}} {{ $componentName }}</b></h5>

            <a href="javascript:void(0);" class="btn btn-primary" {{ $itemsQuantity == 0 ? 'disabled' : '' }} wire:click='Store()'><i class="fa-solid fa-tags"></i> Realiar Cotización</a>
        </div>
    </div>
    @include('livewire.cotizaciones.body')
    {{--
    @if($valid == 1)

    @else
        @if($estadoCaja == 0)

        @else

            @include('livewire.pos.partial.footer')
            @include('livewire.pos.modal-corteZ')

        @endif
    @endif
    @if ($aperturas)
    @include('livewire.pos.modal-arqueo')
    @endif
    @include('livewire.pos.modal-remesar')
    @include('livewire.pos.modal-anulaciones')

    @include('livewire.pos.moda-ftikect')
    @include('livewire.pos.modal-fconsumidor')
    @include('livewire.pos.modal-cfiscal')

    @include('livewire.pos.modal-autenticatex')
    @include('livewire.pos.modal-autenticate')
    @include('livewire.pos.modal-autenticate2')

    @include('livewire.pos.scripts.events')
    @include('livewire.pos.scripts.shortcuts')
    --}}
    @include('livewire.pos.cliente')
    @include('common.notis')
</div>
