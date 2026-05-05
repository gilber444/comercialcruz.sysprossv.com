<script>
    document.addEventListener('DOMContentLoaded', function () {

        function getConteoInputs() {
            return Array.from(document.querySelectorAll('input.conteo-input'));
        }

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;

            var active = document.activeElement;
            if (!active) return;

            var inputs = getConteoInputs();
            var barcode = document.getElementById('code');

            // Navegación desde el input de código de barras
            if (active === barcode) {
                if (e.key === 'ArrowDown' && inputs.length > 0) {
                    e.preventDefault();
                    inputs[0].focus();
                    inputs[0].select();
                }
                return;
            }

            // Navegación entre inputs de conteo
            var idx = inputs.indexOf(active);
            if (idx === -1) return;

            e.preventDefault();

            if (e.key === 'ArrowDown') {
                if (idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                    inputs[idx + 1].select();
                }
            } else if (e.key === 'ArrowUp') {
                if (idx > 0) {
                    inputs[idx - 1].focus();
                    inputs[idx - 1].select();
                } else {
                    // Primer input → subir al código de barras
                    if (barcode) {
                        barcode.focus();
                        barcode.select();
                    }
                }
            }
        });

    });
</script>
