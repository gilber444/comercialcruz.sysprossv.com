
<script>
    const fechaInput = document.getElementById('fechaInput');

    if (fechaInput) {
        const fechaHoraActual = new Date().toISOString().slice(0, 16);
        fechaInput.value = fechaHoraActual;
    }
</script>

<script>
    var inputModal = document.getElementById("modal-search-input");

    document.getElementById("modalSearchProduct").addEventListener("shown.bs.modal", function () {
      inputModal.focus();
    });
  </script>
 