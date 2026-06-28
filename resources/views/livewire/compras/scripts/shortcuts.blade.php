<script>
    var listener = new window.keypress.Listener();
    listener.simple_combo("ctrl", function(event) {
        $('#modalSearchProduct').modal('show'); // Abrir modal
        setTimeout(() => {
            document.getElementById('modal-search-input').focus(); // Enfocar el campo de búsqueda
        }, 200); // Esperar un poco para que el modal termine de abrirse
    });

    listener.simple_combo('f9', function(){
        livewire.emit('clearCart')
    })

    listener.simple_combo('f10', function(){
      livewire.emit('Store');
      livewire.emit('Update');
    });


    listener.simple_combo('supr', function(){
        livewire.emit('removeItem')
    });

</script>
