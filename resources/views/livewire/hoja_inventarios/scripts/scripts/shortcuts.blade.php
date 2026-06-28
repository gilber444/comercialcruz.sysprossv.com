<script>

    var listener = new window.keypress.Listener();

    listener.simple_combo("ctrl", function(){

        $('#modalSearchProduct').modal('show');

    })



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

