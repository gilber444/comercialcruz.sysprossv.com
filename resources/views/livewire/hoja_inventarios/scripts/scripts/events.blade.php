
<script>
    // Obtener el elemento del campo de entrada por su ID
    const fechaInput = document.getElementById('fechaInput');

    // Obtener la fecha y hora actual en formato ISO (yyyy-MM-ddTHH:mm)
    const fechaHoraActual = new Date().toISOString().slice(0, 16);

    // Establecer el valor del campo de entrada con la fecha y hora actual
    fechaInput.value = fechaHoraActual;
</script>

<script>
    // Obtén una referencia al input
    var inputModal = document.getElementById("modal-search-input");

    // Cuando la modal se muestra
    document.getElementById("modalSearchProduct").addEventListener("shown.bs.modal", function () {
      // Establece automáticamente el foco en el input
      inputModal.focus();
    });
  </script>
 