<div class="col-sm-12 col-md-3 text-center border rounded p-3 mb-3">
    <livewire:search-pos-controller>
    <button class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#modalSearchPosProduct"> <i class="fas fa-search"></i> por Nombre (CTRL+B)</button>

    <button class="btn btn-label-primary" data-bs-toggle='modal' data-bs-target="#modalNewCliente"> <i class="fas fa-plus"></i> Crear Cliente (Alt+N)</button>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        livewire.on('scan-code-byid', action => {
            $('#code').val('');
        });
    });
</script>
