<div class="card-body">
    @include('livewire.cotizaciones.head')
    <div class="row">
        @include('livewire.cotizaciones.detalle')
        <livewire:modal-cotizaciones>
        @include('livewire.cotizaciones.botones')
    </div>
</div>
