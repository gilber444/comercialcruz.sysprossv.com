<script>
    var listener = new window.keypress.Listener();

    // Abrir modal con Ctrl + B
    listener.simple_combo("ctrl b", function(event) {
        $('#modalSearchPosProduct').modal('show'); // Abrir modal
        setTimeout(() => {
            document.getElementById('modal-search-input').focus(); // Enfocar el campo de búsqueda
        }, 200); // Esperar un poco para que el modal termine de abrirse
    });

    // Limpiar carrito con F9
    listener.simple_combo("f9", function() {
        livewire.emit('clearCart');
    });

    // Guardar con F10
    listener.simple_combo("f10", function() {
        livewire.emit('Store');
    });

    // Eliminar elemento con Supr
    listener.simple_combo("supr", function() {
        livewire.emit('removeItem');
    });
</script>
