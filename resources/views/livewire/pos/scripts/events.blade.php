
{{-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalSearchPosProduct');
        const searchInput = modal.querySelector('#searchInput');
        let productRows = modal.querySelectorAll('tbody tr');

        const cartTable = document.querySelector('.table-hover tbody');
        let cartRows = cartTable.querySelectorAll('tr');

        // Función para enfocar una fila en la modal
        function focusRow(index) {
            if (index >= 0 && index < productRows.length) {
                productRows[index].focus();
            }
        }

        // Teclas en el modal de búsqueda
        modal.addEventListener('keydown', function(event) {
            let focusedElement = document.activeElement;
            let index = Array.from(productRows).indexOf(focusedElement);

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (index === -1) {
                    focusRow(0);
                } else if (index < productRows.length - 1) {
                    focusRow(index + 1);
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index > 0) {
                    focusRow(index - 1);
                } else if (index === 0) {
                    searchInput.focus();
                }
            } else if (event.key === 'Enter' && index !== -1) {
                event.preventDefault();
                const codebar = productRows[index].querySelector('a[data-codebar]').dataset.codebar;
                if (codebar) {
                    Livewire.emit('Add2', codebar);
                    $('#modalSearchPosProduct').modal('hide');
                }
            }
        });

        // Al cerrar la modal, enfocar el primer input del carrito
        $('#modalSearchPosProduct').on('hidden.bs.modal', function() {
            setTimeout(() => {
                let firstInput = cartTable.querySelector('input[type="text"]');
                if (firstInput) {
                    firstInput.focus();
                    firstInput.select(); // Seleccionar contenido automáticamente
                }
            }, 300);
        });

        // Actualizar filas después de Livewire
        document.addEventListener('livewire:update', function() {
            productRows = modal.querySelectorAll('tbody tr');
            cartRows = cartTable.querySelectorAll('tr');
        });

        // Navegar dentro de la tabla del carrito con teclas de dirección
        cartTable.addEventListener('keydown', function(event) {
            let focusedElement = document.activeElement;
            let row = focusedElement.closest('tr');
            let focusableElements = Array.from(row.querySelectorAll('td input, td select')); // Incluye inputs y selects
            let currentIndex = focusableElements.indexOf(focusedElement);
            let rowIndex = Array.from(cartRows).indexOf(row);

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (rowIndex < cartRows.length - 1) {
                    let nextRow = cartRows[rowIndex + 1];
                    let nextInput = nextRow.querySelector('input[type="text"], select');
                    if (nextInput) {
                        nextInput.focus();
                        if (nextInput.tagName === 'INPUT') nextInput.select();
                    }
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (rowIndex > 0) {
                    let prevRow = cartRows[rowIndex - 1];
                    let prevInput = prevRow.querySelector('input[type="text"], select');
                    if (prevInput) {
                        prevInput.focus();
                        if (prevInput.tagName === 'INPUT') prevInput.select();
                    }
                }
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                if (currentIndex < focusableElements.length - 1) {
                    let nextElement = focusableElements[currentIndex + 1];
                    nextElement.focus();
                    if (nextElement.tagName === 'INPUT' || nextElement.tagName === 'SELECT') {
                        nextElement.select();
                    }
                }
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                if (currentIndex > 0) {
                    let prevElement = focusableElements[currentIndex - 1];
                    prevElement.focus();
                    if (prevElement.tagName === 'INPUT' || prevElement.tagName === 'SELECT') {
                        prevElement.select();
                    }
                }
            } else if (event.key === 'Delete' || event.key === 'Backspace') {
                event.preventDefault();
                let removeButton = row.querySelector('button');
                if (removeButton) {
                    removeButton.click();
                    setTimeout(() => {
                        // Mover foco a la siguiente fila después de eliminar
                        if (rowIndex < cartRows.length - 1) {
                            let nextRow = cartRows[rowIndex + 1];
                            let nextInput = nextRow.querySelector('input[type="text"], select');
                            if (nextInput) {
                                nextInput.focus();
                                if (nextInput.tagName === 'INPUT') nextInput.select();
                            }
                        } else if (rowIndex > 0) {
                            let prevRow = cartRows[rowIndex - 1];
                            let prevInput = prevRow.querySelector('input[type="text"], select');
                            if (prevInput) {
                                prevInput.focus();
                                if (prevInput.tagName === 'INPUT') prevInput.select();
                            }
                        }
                    }, 300);
                }
            }
        });

        // Enfocar input de búsqueda al abrir la modal
        $('#modalSearchPosProduct').on('shown.bs.modal', function() {
            searchInput.focus();
        });

    });
</script>--}}


