<div class="row g-3 mb-3">
    <div class="col-10 col-xxl-10 col-xl-10">
        <livewire:search-cotizaciones-controller>
    </div>
    <div class="col-2 col-xxl-2 col-xl-2">
        <button class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#modalSearchProduct"> <i class="fas fa-search"></i> Buscar</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        livewire.on('scan-code', action => {
            $('#code').val('');
        });
    });
</script>
