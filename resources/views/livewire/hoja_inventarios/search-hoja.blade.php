<div class="col-8 col-xl-8 col-xl-12">
    <input type="text" class="form-control" placeholder="Digite Codigo de Barras" aria-label="Digite Codigo de Barras" wire:keydown.enter.prevent="$emit('scan-code', $('#code').val())" id="code" onfocus="">
</div>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        livewire.on('scan-code', action => {
            $('#code').val('')
        });
        var inputElement = document.getElementById("code");

        // Enfoca automáticamente el input cuando la página se carga
        inputElement.focus();
    })
</script>
