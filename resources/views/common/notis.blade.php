<script>
    document.addEventListener('DOMContentLoaded', function() {

        if (window.__sneat_toast_ready) return;
        window.__sneat_toast_ready = true;

        function showSneatToast({ title = 'Notificación', message = '', icon = 'bx bx-bell', type = '', delay = 2500 }) {
            const container = document.getElementById('toast-container-sneat');
            if (!container) return;

            // type puede ser: '' | 'bg-success' | 'bg-danger' | 'bg-warning' | 'bg-info' | 'bg-primary' ...
            const toastEl = document.createElement('div');
            toastEl.className = `bs-toast toast toast-ex animate__animated animate__rubberBand my-2 ${type}`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.setAttribute('data-bs-delay', delay); 

            toastEl.innerHTML = `
                <div class="toast-header">
                    <i class="${icon} me-2"></i>
                    <div class="me-auto fw-semibold">${title}</div>
                    <small>Ahora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;

            container.appendChild(toastEl);

            const bsToast = new bootstrap.Toast(toastEl);
            bsToast.show();

            // limpiar del DOM cuando termine
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }


        $('select2-Actiempresa').select2()
        $('#select2-Actiempresa').on('change', function(e) {
            var mId = $('#select2-Actiempresa').select2('val')
            var mName = $('#select2-Actiempresa option:selected').text()
            @this.set('actividadSelectId', mId)
            @this.set('actividadSelectName', mName)
        });

        $('cliente').select2()
        $('#cliente').on('change', function(e) {
            var cId = $('#cliente').select2('val')
            var cName = $('#cliente option:selected').text()
            @this.set('clienteSelectId', cId)
            @this.set('clienteSelectName', cName)
        });

        $('producto').select2()
        $('#producto').on('change', function(e) {
            var cId = $('#producto').select2('val')
            var cName = $('#producto option:selected').text()
            @this.set('productoSelectId', cId)
            @this.set('productoSelectName', cName)
        });

        $('actividadCliente').select2()
        $('#actividadCliente').on('change', function(e) {
            var cId = $('#actividadCliente').select2('val')
            var cName = $('#actividadCliente option:selected').text()
            @this.set('clienteAddSelectId', cId)
            @this.set('clienteAddSelectName', cName)
        });

        $('ActiProveedor').select2()
        $('#ActiProveedor').on('change', function(e) {
            var pId = $('#ActiProveedor').select2('val')
            var pName = $('#ActiProveedor option:selected').text()
            @this.set('proveedorAddSelectId', pId)
            @this.set('proveedorAddSelectName', pName)
        });

        $('proveedor').select2()
        $('#proveedor').on('change', function(e) {
            var pId = $('#proveedor').select2('val')
            var pName = $('#proveedor option:selected').text()
            @this.set('proveedorSelectId', pId)
            @this.set('proveedorSelectName', pName)
            @this.call('CalcularPercicionProveedor');
        });

        $('#proveedor').select2();

        $('#proveedor').on('change', function(e) {
            var pId = $(this).val(); // Obtener el ID del proveedor seleccionado
            var pName = $('#proveedor option:selected').text(); // Obtener el nombre del proveedor

            console.log("Proveedor seleccionado:", pId,
                pName); // Verifica que los valores sean correctos

            @this.set('proveedorSelectId', pId);
            @this.set('proveedorSelectName', pName);
            @this.call('obtenerProductos'); // Llama al método en Livewire
        });

        Livewire.on('sshow-modal', msg => {
            $('#myModals').modal('show');

        });

        Livewire.on('ajuste-modal', msg => {
            let modal = new bootstrap.Modal(document.getElementById('detalleAjusteModal'));
            modal.show();
        });

        Livewire.on('detalle-modal', msg => {
            $('#detalleSolicitudModal').modal('show');
            $('#detalleCompraModal').modal('show');
        });

        Livewire.on('toma-modal', msg => {
            $('#detalleTomaModal').modal('show');
        });

        Livewire.on('show-modal', msg => {
            $('#myModal').modal('show');
        });

        Livewire.on('show-modal', msg => {
            $('#ingresoModal').modal('show');
        });

        Livewire.on('modal-show', msg => {
            $('#ProveedorCompra').modal('show');
        });

        Livewire.on('modal-show', msg => {
            $('#ProveedorToma').modal('show');
        });

        Livewire.on('show-modal', msg => {
            $('#myModals').modal('hide');
            $('#modal-factura').modal('show');
        });

        Livewire.on('modal-factura', msg => {
            $('#myModals').modal('hide');
            $('#modal-factura').modal('show');
        });

        Livewire.on('sshoww-modal', msg => {
            $('#Detelles').modal('show');
            $('#DetellesDTE').modal('show');
            $('#myModalRecep').modal('show');

        });

        Livewire.on('anulaciones-show', msg => {
            $('#modalAnulacionesDetalle').modal('show');
        });

        Livewire.on('item-added', msg => {
            $('#myModal').modal('hide');
            $('#modalNewCliente').modal('hide');
            $('#modalAnulaciones').modal('hide');
            $('#ProveedorCompra').modal('hide');
            $('#myModalCon').modal('hide');

            /*Swal.fire({
                icon: 'success',
                title: 'Agregado!',
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
                text: msg,
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });*/
            //toastr.success(msg, 'Agregado!');
            showSneatToast({ title: 'Agregado!', message: msg, icon: 'bx bx-check-circle', type: 'bg-success' });
        });

        Livewire.on('no-stock', msg => {
            /*Swal.fire({
                icon: 'warning',
                title: 'Advertencia!',
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
                text: msg,
                customClass: {
                    confirmButton: 'btn btn-warning'
                }
            });*/
            showSneatToast({ title: 'Advertencia!', message: msg, icon: 'bx bx-error', type: 'bg-warning', delay: 3500 });
        });

        Livewire.on('scan-notfound', msg => {
            /*Swal.fire({
                icon: 'warning',
                title: 'Advertencia!',
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
                text: msg,
                customClass: {
                    confirmButton: 'btn btn-warning'
                }
            });*/
            showSneatToast({ title: 'Advertencia!', message: msg, icon: 'bx bx-search-alt', type: 'bg-warning', delay: 3500 });
        });

        Livewire.on('item-updated', msg => {
            $('#myModal').modal('hide');
            $('#ingresoModal').modal('hide');
            /*Swal.fire({
                icon: 'success',
                title: 'Actualizado!',
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
                text: msg,
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });*/
            showSneatToast({ title: 'Actualizado!', message: msg, icon: 'bx bx-refresh', type: 'bg-warning'});
        });

        Livewire.on('item-error', msg => {
            /*Swal.fire({
                title: 'Error',
                text: msg,
                icon: 'error',
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
            });*/
            $('#modalAnulaciones').modal('hide');
            $('#myModalCon').modal('hide');

            showSneatToast({ title: 'Error', message: msg, icon: 'bx bx-x-circle', type: 'bg-danger', delay: 4500 });

        });

        Livewire.on('item-confirmar', msg => {
            /*Swal.fire({
                title: 'Hecho',
                text: msg,
                icon: 'success',
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
            });*/

            showSneatToast({ title: 'Hecho', message: msg, icon: 'bx bx-check-circle', type: 'bg-success' });
        });

        Livewire.on('print-ticket', $data => {
            //alert('ejecutando');
            var newWindow = window.open("print://" + $data, '_blank');
            setTimeout(function() {
                newWindow.close();
                window.location.href = '/pos';
            }, 100); // Ajusta el tiempo según tus necesidades
            //$('#modalSaveTikect').modal('hide');
            //$('#modalRemesas').modal('hide');
        });

        Livewire.on('print-ticket2', $data => {
            //alert('ejecutando');
            var newWindow = window.open("print://" + $data, '_blank');
            setTimeout(function() {
                newWindow.close();
                //window.location.href = '/pos';
            }, 100); // Ajusta el tiempo según tus necesidades
            //$('#modalSaveTikect').modal('hide');
            //$('#modalRemesas').modal('hide');
        });

        Livewire.on('print-credito', $data => {
            //alert('ejecutando');
            var newWindow = window.open("print://" + $data, '_blank');
            setTimeout(function() {
                newWindow.close();
                window.location.href = '/cuentas_cobrar';
            }, 100); // Ajusta el tiempo según tus necesidades
            //$('#modalSaveTikect').modal('hide');
            //$('#modalRemesas').modal('hide');
        });

        Livewire.on('modal-updated', msg => {
            $('#modalAutenticate').modal('hide');
            $('#modalCorteZ').modal('show');
        });

        Livewire.on('modal-cierre', msg => {
            $('#modalAutenticate2').modal('hide');
            $('#modalCorteZ2').modal('show');
        });

        Livewire.on('modal-cierrex', msg => {
            $('#modalAutenticatex').modal('hide');
            $('#modalArqueo').modal('show');
        });

        Livewire.on('modal-cerrarZ', msg => {
            $('#modalCorteZ').modal('hide');
        });

        Livewire.on('modal-reimpresion', msg => {
            $('#modalAutenticateImpre').modal('hide');
            $('#modalReimpresion').modal('show');
        });

        Livewire.on('abrirModal', msg => {
            $('#detallePrecios').modal('show');
        });

        Livewire.on('show-sucursalP', msg => {
            $('#mypreciosSucursal').modal('show');
        });

        Livewire.on('item-errorSearch', msg => {
            Swal.fire({
                title: "Cliente no Encontrado",
                text: msg,
                icon: "error",
                allowEnterKey: true, // Permitir cerrar con Enter
                allowEscapeKey: true, // Permitir cerrar con Esc
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si, Registrar Cliente"
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#modalSaveConsumidor').modal('hide');
                    $('#modalSaveCFiscal').modal('hide');
                    $('#modalNewCliente').modal('show');
                }
            });

        });


        Livewire.on('print-ticketR', $data => {
            var newWindow = window.open("print://" + $data, '_blank');
            setTimeout(function() {
                newWindow.close();
                window.location.href = '/solicitudesVer';
            }, 100); // Ajusta el tiempo según tus necesidades
            //$('#modalSaveTikect').modal('hide');
            //$('#modalRemesas').modal('hide');
        });

    });

    function Confirm(id) {

        Swal.fire({
            title: 'Confirmar',
            text: 'Estas seguro de eliminar este registro!',
            icon: 'warning',
            allowEnterKey: true, // Permitir cerrar con Enter
            allowEscapeKey: true, // Permitir cerrar con Esc
            showCancelButton: true,
            confirmButtonText: 'Si, Eliminar',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                Livewire.emit('deleteRow', id)
                Livewire.on('item-deleted', msg => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado!',
                        allowEnterKey: true, // Permitir cerrar con Enter
                        allowEscapeKey: true, // Permitir cerrar con Esc
                        text: msg,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                });
            }
        });


    }

    function imprimir() {

        // Cierra la ventana modal
        setTimeout(function() {
            $('#myModal').modal('hide');
        }, 100);

        var contenidoDiv = document.getElementById("reporte").innerHTML;
        var contenidoOriginal = document.body.innerHTML;
        document.body.innerHTML = contenidoDiv;

        // Abre una nueva pestaña para imprimir la página
        var ventanaImpresion = window.open('', '', 'height=900,width=1024');
        ventanaImpresion.document.write('<html><head><title>Impresión</title>');
        ventanaImpresion.document.write('<link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}">');
        ventanaImpresion.document.write('</head><body>');
        ventanaImpresion.document.write(contenidoDiv);
        ventanaImpresion.document.write('</body></html>');
        ventanaImpresion.document.close();
        ventanaImpresion.focus();
        ventanaImpresion.print();

        document.body.innerHTML = contenidoOriginal;


    };

    function Revocar() {
        Swal.fire({
            title: 'Confirmar',
            text: 'Confirmas Revocar todos los Permisos !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, Eliminar!',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                Livewire.emit('revokeall')
                Swal.fire({
                    icon: 'success',
                    title: 'Actualizado!',
                    allowEnterKey: true, // Permitir cerrar con Enter
                    allowEscapeKey: true, // Permitir cerrar con Esc
                    text: msg,
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
            }
        })
    }

    function Confirm2(id) {

        Swal.fire({
            title: 'Confirmar',
            text: 'Estas seguro de eliminar este Producto de la compra!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, Eliminar',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                Livewire.emit('deleteRoww', id)
                Livewire.on('item-deleted', msg => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado!',
                        allowEnterKey: true, // Permitir cerrar con Enter
                        allowEscapeKey: true, // Permitir cerrar con Esc
                        text: msg,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                });
            }
        });
    }

    function ConfirmA(id) {

        Swal.fire({
            title: 'Confirmar',
            text: 'Estas seguro de anular la compra!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, Eliminar',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                Livewire.emit('anulaRow', id)
                Livewire.on('item-deleted', msg => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Anulada!',
                        allowEnterKey: true, // Permitir cerrar con Enter
                        allowEscapeKey: true, // Permitir cerrar con Esc
                        text: msg,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                });
            }
        });
    }
</script>
<script>
    document.addEventListener("keydown", function(event) {
        if (event.key === "Enter" || event.key === "Escape") {
            if (Swal.isVisible()) { // Solo si hay un Swal abierto
                Swal.close();
            }
        }
    });
</script>
<script>
    function startProcessing(id) {
        // Mostrar alerta de procesamiento antes de ejecutar el Livewire call
        Swal.fire({
            title: 'Procesando DTE...',
            text: 'Por favor espera mientras firmamos y procesamos el DTE.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Delay para dar tiempo a que el modal se muestre antes de ejecutar el proceso largo
        setTimeout(function() {
            // Llamar al método de Livewire después de un pequeño retraso
            Livewire.emit('GenerarDTE', id);
        }, 100); // 500 milisegundos de retraso
    }

    document.addEventListener('livewire:load', function() {
        Livewire.on('item-addedd', (message) => {
            // Cerrar el modal de procesamiento y mostrar éxito
            Swal.close();
            Swal.fire(
                'Completado',
                message,
                'success'
            );
        });
    });
</script>
<script>
    function startProcessingLote() {
        // Mostrar alerta de procesamiento antes de ejecutar el Livewire call
        Swal.fire({
            title: 'Procesando Lote...',
            text: 'Por favor espera mientras procesamos el lote.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading(); // Mostrar spinner de carga
            }
        });

        // Llamar al método de Livewire
        Livewire.emit('Store');
    }

    document.addEventListener('livewire:load', function() {
        // Escuchar el evento cuando el proceso de lote ha terminado
        Livewire.on('item-addedd', (message) => {
            Swal.close(); // Cerrar el modal de procesamiento
            Swal.fire(
                'Completado',
                message,
                'success' // Icono de éxito
            );
        });

        Livewire.on('item-errorr', (message) => {
            Swal.close(); // Cerrar el modal de procesamiento
            Swal.fire(
                'Error',
                message,
                'error' // Icono de error
            );
        });
    });
</script>
<script>
    function startContingencyEvent(id) {
        Swal.fire({
            title: 'Generando Evento de Contingencia...',
            text: 'Por favor espera mientras procesamos el evento.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading(); // Mostrar spinner de carga
            }
        });

        // Emitir el evento Livewire con el ID del evento de contingencia
        Livewire.emit('Store');
    }

    document.addEventListener('livewire:load', function() {
        // Escuchar el evento cuando el proceso de contingencia ha terminado
        Livewire.on('item-addedd', (message) => {
            Swal.close(); // Cerrar el modal de procesamiento
            Swal.fire(
                'Completado',
                message,
                'success' // Icono de éxito
            );
        });

        Livewire.on('item-errorr', (message) => {
            Swal.close(); // Cerrar el modal de procesamiento
            Swal.fire(
                'Error',
                message,
                'error' // Icono de error
            );
        });
    });
</script>
<script>
    // Variable global para almacenar los datos del ticket
    let ticketData = null;

    // Función para manejar el procesamiento del DTE
    function startProcessing2(id, data) {
        // Guardar los datos del ticket para usarlos después
        ticketData = data;

        // Mostrar alerta de procesamiento antes de ejecutar el Livewire call
        Swal.fire({
            title: 'Procesando DTE...',
            text: 'Por favor espera mientras firmamos y procesamos el DTE.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Delay para dar tiempo a que el modal se muestre antes de ejecutar el proceso largo
        setTimeout(function() {
            // Llamar al método de Livewire después de un pequeño retraso
            Livewire.emit('GenerarDTE', id);
        }, 100); // 500 milisegundos de retraso
    }

    // Función para imprimir ticket
    function imprimirTicket(data) {
        console.log('Imprimiendo ticket con datos:', data);
        try {
            var newWindow = window.open("print://" + data, '_blank');
            setTimeout(function() {
                if (newWindow) {
                    newWindow.close();
                }
            }, 100);
            return true;
        } catch (error) {
            console.error('Error al imprimir ticket:', error);
            return false;
        }
    }

    // Listener para iniciar el proceso de DTE y otras funciones
    document.addEventListener('livewire:load', function() {
        // Listener para iniciar el procesamiento del DTE
        Livewire.on('startProcessing2', function(id, data) {
            console.log('Evento startProcessing2 recibido con ID:', id);
            startProcessing2(id, data); // Llamar a la función JavaScript con el ID y datos
        });

        // Listener específico para imprimir ticket
        Livewire.on('imprimir-ticket', function(data) {
            console.log('Evento imprimir-ticket recibido con datos:', data);
            imprimirTicket(data);
        });

        // Listener para cuando el procesamiento termine
        Livewire.on('item-addedd', function(message, data) {
            // Actualizar los datos del ticket con los nuevos datos
            ticketData = data;

            // Cerrar el modal de procesamiento y mostrar éxito
            Swal.close();
            Swal.fire({
                title: 'Completado',
                text: message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                // Imprimir el ticket usando los datos actualizados
                if (ticketData) {
                    imprimirTicket(ticketData);
                }

                // Pequeño retraso antes de redirigir para dar tiempo a la impresión
                setTimeout(function() {
                    window.location.href = '/pos';
                }, 100);
            });
        });

        // Listener para procesamiento con error
        Livewire.on('item-errorr', function(message, data = null) {
            if (data) {
                ticketData = data;
            }
            Swal.close();
            Swal.fire({
                title: 'Error',
                text: message,
                icon: 'error',
                confirmButtonText: 'Revisar'
            }).then(() => {
                // Imprimir el ticket incluso en caso de error
                if (ticketData) {
                    imprimirTicket(ticketData);
                }

                // Pequeño retraso antes de redirigir para dar tiempo a la impresión
                setTimeout(function() {
                    window.location.href = '/pos';
                }, 100);
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.confirmarDevolucion = function() {
            @this.call('obtenerTotalDevolucion').then(total => {
                if (parseFloat(total) <= 0) {
                    Swal.fire('Error', 'No hay productos en la devolución.', 'error');
                    return;
                }

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `La devolución total es de $${parseFloat(total).toFixed(2)}. ¿Deseas continuar?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, procesar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('ProcesaDevolucion');
                    }
                });
            });
        }
    });
</script>
<script>
    function confirmarCierreCaja() {
        Swal.fire({
            title: '¿Deseas cerrar la caja?',
            text: 'Cierre de Caja o Corte Z. Si realizas esta opción ya no podrás seguir usando esta caja durante este día hasta el siguiente día.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar caja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Abre el modal si confirma
                let modal = new bootstrap.Modal(document.getElementById('modalCorteZ'));
                modal.show();
            }
        });
    }
</script>
