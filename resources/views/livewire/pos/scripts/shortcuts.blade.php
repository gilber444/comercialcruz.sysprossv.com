<script>
    var listener = new window.keypress.Listener();
    listener.simple_combo("ctrl", function(event) {
        $('#modalSearchPosProduct').modal('show'); // Abrir modal
        setTimeout(() => {
            document.getElementById('modal-search-input').focus(); // Enfocar el campo de búsqueda
        }, 200); // Esperar un poco para que el modal termine de abrirse
    });

    listener.simple_combo("alt n", function() {
        $('#modalNewCliente').modal('show');
    })

    /*listener.simple_combo('alt s', function() {
        livewire.emit('save-clientes');
    });*/

    listener.simple_combo('f2', function() {
        $('#modalSaveTikect').modal('show');
        //livewire.emit('lanzarAlertaPago');
    });

    listener.simple_combo('f3', function() {
        $('#modalSaveConsumidor').modal('show');
    });

    listener.simple_combo('f4', function() {
        $('#modalSaveCFiscal').modal('show');
    });


    listener.simple_combo('f7', function() {
        $('#modalRemesas').modal('show');
    });

    /*listener.simple_combo('f10', function() {
        $('#modalCorteZ').modal('show');
    });*/

    listener.simple_combo("f10", function(event) {
        confirmarCierreCaja();
    });

    /*listener.simple_combo('f10', function() {
        $('#modalCorteZ').modal('show');
    });*/

    //listener.simple_combo('f9', function(){
    //$('#modalAnulaciones').modal('show');
    //});

    listener.simple_combo('f9', function() {
        livewire.emit('modalAnulacionesDetalle');
    });

    listener.simple_combo('f11', function() {
        $('#modalArqueo').modal('show');
    });

    /*listener.simple_combo('alt s', function() {
        $('#modalArqueo').modal('show');
    });*/

    listener.simple_combo('alt s', function() {
        livewire.emit('cambiarEscenario');
    });

    listener.simple_combo('alt r', function() {
        $('#modalAutenticateImpre').modal('show');
    });


    document.addEventListener("DOMContentLoaded", function() {
        /** =====================
         *  1️⃣ MANEJO DEL INPUT DE CÓDIGO DE BARRAS Y TABLA DEL CARRITO
        ========================**/
        const barcodeInput = document.getElementById("code");
        const cartTable = document.querySelector(".table-hover tbody");

        let focusToBarcodeNext = false;
        let qtyFocusTimer = null;   // ✅ ahora ya está definida

        function focusBarcodeInput() {
            setTimeout(() => {
                if (barcodeInput) {
                    barcodeInput.focus();
                    barcodeInput.select();
                }
            }, 200);
        }

        function focusFirstQuantityInput() {
            setTimeout(() => {
                const firstQuantityInput = document.querySelector("input[wire\\:model^='can.']");
                if (firstQuantityInput) {
                    firstQuantityInput.focus();
                    firstQuantityInput.select();
                }
            }, 200);
        }

        // Enfocar el código de barras al cargar la página
        focusBarcodeInput();

        // Reenfocar después de cada actualización de Livewire
        document.addEventListener("livewire:update", function() {
            if (modalOpen) {
                setTimeout(updateProductRows, 200);
            }

            // 👇 NUEVO: si había un timeout pendiente para enfocar cantidad, cancélalo
            if (qtyFocusTimer) {
                clearTimeout(qtyFocusTimer);
                qtyFocusTimer = null;
            }

            if (focusToBarcodeNext) {
                focusToBarcodeNext = false; // consumir banderita
                focusBarcodeInput(); // ir al #code esta vez
                return; // evita que el bloque de abajo robe el foco
            }

            // 👇 SOLO si el foco NO está en #code programamos enfocar la cantidad
            qtyFocusTimer = setTimeout(() => {
                // si por cualquier razón el foco ya está en #code, no muevas el foco
                if (document.activeElement && document.activeElement.id === 'code') return;

                const inputCantidad = document.querySelector(
                    "tbody tr:nth-child(2) .input-cantidad");
                if (inputCantidad) {
                    inputCantidad.focus();
                    inputCantidad.select();
                }
            }, 400);
        });


        // Manejo de tecla Enter en el input de código de barras
        barcodeInput.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                const value = barcodeInput.value.trim();

                console.log("Código escaneado:", value); // ✅ Depuración

                if (value !== "") {
                    Livewire.emit("scan-code-byid", value);

                    // 🔹 No borrar el código, solo seleccionarlo para ser reemplazado
                    setTimeout(() => {
                        barcodeInput.select();
                    }, 100);

                    // 🔹 Enfocar el primer input de cantidad si existe
                    setTimeout(() => {
                        const firstQuantityInput = document.querySelector(
                            "input[wire\\:model^='can.']");
                        if (firstQuantityInput) {
                            firstQuantityInput.focus();
                            firstQuantityInput.select();
                        } else {
                            barcodeInput
                                .select(); // 🔹 Si no hay input de cantidad, seguir en código de barras
                        }
                    }, 300);
                }
            }
        });

        // Navegación con teclas en la tabla del carrito
        cartTable.addEventListener("keydown", function(event) {
            let focusedElement = document.activeElement;
            let row = focusedElement.closest("tr");
            let rowIndex = Array.from(cartTable.querySelectorAll("tr")).indexOf(row);
            let quantityInputs = Array.from(cartTable.querySelectorAll("input[wire\\:model^='can.']"));
            let currentIndex = quantityInputs.indexOf(focusedElement);

            if (event.key === "ArrowDown") {
                event.preventDefault();
                if (currentIndex < quantityInputs.length - 1) {
                    quantityInputs[currentIndex + 1].focus();
                    quantityInputs[currentIndex + 1].select();
                }
            }

            if (event.key === "ArrowUp") {
                event.preventDefault();
                if (currentIndex > 0) {
                    quantityInputs[currentIndex - 1].focus();
                    quantityInputs[currentIndex - 1].select();
                } else {
                    focusBarcodeInput();
                }
            }

            if (event.key === "Enter") {
                event.preventDefault();
                let itemId = focusedElement.getAttribute("wire:model").split(".")[1];
                //Livewire.emit("updateCanti", itemId);
                //focusBarcodeInput();
                focusToBarcodeNext = true;
                if (itemId) {
                    Livewire.emit("updateCanti", itemId);
                    /*setTimeout(() => {
                        Livewire.emit('focus-primer-cantidad');
                    }, 400);*/
                    focusBarcodeInput();
                }
            }

            if (event.key === "Delete" || event.key === "Backspace") {
                event.preventDefault();
                let itemId = focusedElement.getAttribute("wire:model").split(".")[1];
                Livewire.emit("removeItem", itemId);
                setTimeout(() => {
                    focusFirstQuantityInput();
                }, 300);
            }

            // Evitar que el punto (.) haga perder el foco del input de cantidad
            if (event.key === "." || event.key === ",") {
                if (focusedElement.matches("input[wire\\:model^='can.']")) {
                    event.preventDefault();
                    let inputValue = focusedElement.value;
                    if (!inputValue.includes(".")) {
                        focusedElement.value += ".";
                    }
                }
            }

            if (event.key === "F6") {
                event.preventDefault();
                let itemId = focusedElement.getAttribute("wire:model").split(".")[
                    1]; // Obtiene el ID del item
                Livewire.emit("update-canti", itemId); // Envía el ID del producto
            }
        });

        /** =====================
         *  2️⃣ MODAL DE BÚSQUEDA (CTRL)
         ========================**/
        const modalSearch = document.getElementById("modalSearchPosProduct");
        const searchInput = document.getElementById("searchInput");
        let productRows = [];
        let selectedIndex = -1;
        let modalOpen = false;

        document.addEventListener("keydown", function(event) {
            if (event.ctrlKey) {
                event.preventDefault();
                $("#modalSearchPosProduct").modal("show");
            }
        });

        $("#modalSearchPosProduct").on("shown.bs.modal", function() {
            modalOpen = true;
            selectedIndex = -1;
            searchInput.focus();
            searchInput.select();
            setTimeout(updateProductRows, 200);
        });

        $("#modalSearchPosProduct").on("hidden.bs.modal", function() {
            modalOpen = false;
        });

        function updateProductRows() {
            productRows = Array.from(document.querySelectorAll("#modalSearchPosProduct tbody tr"));
            productRows.forEach((row, index) => {
                row.setAttribute("tabindex", "0");
                row.addEventListener("click", () => focusRow(index));
            });
            selectedIndex = -1;
        }

        function focusRow(index) {
            if (productRows.length === 0) return;
            if (index >= 0 && index < productRows.length) {
                productRows.forEach(row => row.classList.remove("focused"));
                productRows[index].classList.add("focused");
                productRows[index].focus();
                selectedIndex = index;
            }
        }

        modalSearch.addEventListener("keydown", function(event) {
            if (!modalOpen) return;

            if (event.key === "ArrowDown") {
                event.preventDefault();
                if (selectedIndex < productRows.length - 1) {
                    focusRow(selectedIndex + 1);
                }
            }

            if (event.key === "ArrowUp") {
                event.preventDefault();
                if (selectedIndex > 0) {
                    focusRow(selectedIndex - 1);
                } else {
                    searchInput.focus();
                    selectedIndex = -1;
                }
            }

            if (event.key === "Enter" && selectedIndex !== -1) {
                event.preventDefault();
                let selectedProduct = productRows[selectedIndex].querySelector("a[data-codebar]");
                if (selectedProduct) {
                    let codebarModal = selectedProduct.dataset.codebar.trim();
                    if (codebarModal !== "") {
                        Livewire.emit("Add2", codebarModal);
                        $("#modalSearchPosProduct").modal("hide");
                    }
                }
            }
        });

        document.addEventListener("livewire:update", function() {
            if (modalOpen) {
                setTimeout(updateProductRows, 200);
            }
        });

        /** =====================
         *  3️⃣ MODAL DE PAGO (Arrow Keys)
         ========================**/
        const modalPago = document.getElementById("modalSaveTikect");

        function mantenerFocoEfectivo() {
            setTimeout(() => {
                const efectivoInput = document.getElementById("efectivo");
                if (efectivoInput && document.activeElement !== efectivoInput) {
                    efectivoInput.focus();
                    efectivoInput.setSelectionRange(efectivoInput.value.length, efectivoInput.value
                        .length);
                }
            }, 50);
        }

        document.addEventListener("keydown", function(event) {
            if (!modalPago || !modalPago.classList.contains("show") || document.activeElement.closest(
                    ".modal") !== modalPago) {
                return;
            }

            const efectivoInput = document.getElementById("efectivo");
            const metodoSelect = document.getElementById("metodo");
            const comprobanteInput = document.getElementById("comprobante");
            const focusedElement = document.activeElement;

            // ✅ Solo activar `select` y `comprobante` si el usuario usa `ArrowUp` o `ArrowDown`
            if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                metodoSelect.removeAttribute("disabled");
                comprobanteInput.removeAttribute("disabled");
            }

            // ✅ Solo mover el foco si los campos están activos
            if (event.key === "ArrowDown") {
                event.preventDefault();
                if (focusedElement === metodoSelect) {
                    efectivoInput.focus();
                    efectivoInput.select();
                } else if (focusedElement === efectivoInput && !comprobanteInput.disabled) {
                    comprobanteInput.focus();
                }
            } else if (event.key === "ArrowUp") {
                event.preventDefault();
                if (focusedElement === comprobanteInput) {
                    efectivoInput.focus();
                    efectivoInput.select();
                } else if (focusedElement === efectivoInput && !metodoSelect.disabled) {
                    metodoSelect.focus();
                }
            }

            // ✅ Permitir escribir `.` o `,` sin perder el foco
            if ((event.key === "." || event.key === ",") && focusedElement === efectivoInput) {
                event.preventDefault();
                if (!efectivoInput.value.includes(".")) {
                    efectivoInput.value += ".";
                }
                mantenerFocoEfectivo();
            }
        });

        // ✅ Evitar que Livewire cambie el foco después de actualizar el DOM
        document.addEventListener("livewire:update", function() {
            mantenerFocoEfectivo();
        });

        // ✅ Asegurar que el input `efectivo` recibe el foco al abrir la modal
        $("#modalSaveTikect").on("shown.bs.modal", function() {
            setTimeout(() => {
                mantenerFocoEfectivo();
            }, 100);
        });


    });
</script>

<script>
    document.addEventListener('livewire:load', function() {
        const listener = new window.keypress.Listener();
        const escenarios = @json($escenarios ?? []);
        const compId = @json($_instance->id);

        escenarios.forEach((n) => {
            n = Number(n);
            if (n !== 1) {
                listener.simple_combo(`alt ${n}`, function() {
                    Livewire.find(compId).emit('moverAEscenarioUno', n);
                });
            }
        });
    });
</script>
