<div class="row g-3 mb-3">
    <livewire:search-compra-controller>
    <div class="col-4 col-xxl-4 col-xl-12">
        <div class="d-grid">
            <button class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#modalSearchProduct"> <i class="fas fa-search"></i> Buscar (ctrl + B)</button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        livewire.on('scan-code', action => {
            $('#code').val('');
        });
    });
</script>
