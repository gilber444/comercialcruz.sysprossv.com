<div class="col-sm-12 col-md-3 border rounded p-3 mb-3">
    <dl class="row mb-0 text-center">
        <livewire:search-cotizaciones-controller>
            <button class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#modalSearchPosProduct"> <i
                    class="fas fa-search"></i> por Nombre (Alt+F)</button>

            <button class="btn btn-label-primary" data-bs-toggle='modal' data-bs-target="#modalNewCliente"> <i
                    class="fas fa-plus"></i> Crear Cliente (Alt+N)</button>
    </dl>
    <hr>
    <div class="text-base">
        <h4>Total $ {{ number_format($total, 2) }}</h4>
        <h5>Descuentos $ {{ number_format($descu, 2) }} </h5>
        <h6>Productos {{ $itemsQuantity }}</h6>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        livewire.on('scan-code', action => {
            $('#code').val('');
        });
    });
</script>
