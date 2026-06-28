<div class="col-8 col-xxl-8 col-xl-12">
    <input type="text" class="form-control" placeholder="Digite Codigo de Barras" aria-label="Digite Codigo de Barras" wire:keydown.enter.prevent="$emit('scan-code-byid', $('#code').val())" id="code" onfocus="">
</div>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        livewire.on('scan-code-byid', action => {
            $('#code').val('')
        });
        var inputElement = document.getElementById("code");

        // Enfoca automáticamente el input cuando la página se carga
        inputElement.focus();
    })
</script>
