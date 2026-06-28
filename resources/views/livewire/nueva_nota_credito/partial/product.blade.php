<div class="row g-3 mb-3">

    <div class="w-100">

        <div class="d-grid">

            <button 

                class="btn btn-label-primary" 

                data-bs-toggle="modal" 

                data-bs-target="#modalSearchNotaProduct"

            >

                <i class="fas fa-search"></i> Buscar Producto

            </button>

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