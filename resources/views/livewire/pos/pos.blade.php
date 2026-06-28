<div class="card">
    <div class="card-header border rounded p-1 mb-1">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $pageTitle}} </b></h5>
            {{-- @if($disponible >= 200)
                <button class="btn btn-outline-danger remesar-btn" type="button" data-bs-toggle="modal" data-bs-target="#modalRemesas">
                    Remesar F7
                </button>
            @endif--}}
        </div>
    </div>
    @if($valid == 1)
        @include('livewire.pos.partial.cierre')
        @if ($act == 1)
            @include('livewire.pos.modal-corteZ2')
            @include('livewire.pos.modal-anulaciones')
        @endif
    @else
        @if($estadoCaja == 0)
            @include('livewire.pos.partial.apertura')
        @else
            @include('livewire.pos.partial.body')
            @include('livewire.pos.partial.footer')
            @include('livewire.pos.modal-corteZ')

        @endif
    @endif
    @if ($aperturas)
    @include('livewire.pos.modal-arqueo')
    @endif
    @include('livewire.pos.modal-remesar')
    @include('livewire.pos.modal-anulaciones')
    @include('livewire.pos.cliente')
    @include('livewire.pos.moda-ftikect')
    @include('livewire.pos.modal-fconsumidor')
    @include('livewire.pos.modal-cfiscal')

    @include('livewire.pos.modal-autenticatex')
    @include('livewire.pos.modal-autenticate')
    @include('livewire.pos.modal-autenticate2')
    @include('livewire.pos.modal-autenticateImpre')
    @include('livewire.pos.modal-reimpresiones')
    @include('livewire.pos.detalle-precios')
    @include('livewire.pos.detalle-unidades')
    @include('livewire.pos.detalle-anulacion')
    @include('common.notis')
    @include('livewire.pos.scripts.events')
    @include('livewire.pos.scripts.shortcuts')
</div>
