@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;
@endphp
<div class="row p-2">
    <div style="max-height: 200px; overflow-y: auto;">
        <table class="table table-hover table-sm">
            <thead>
                {{-- <th class="text-center">CODIGO</th> --}}
                <th class="text-center">DESCRIPCION</th>
                <th class="text-center">MEDIDA</th>
                <th class="text-center">CANTIDAD</th>
                <th class="text-center">COSTO</th>
                    <th class="text-center">TOTAL</th>
                <th class="text-center"></th>
            </thead>
            <tbody>
                @foreach ($solicitudes as $item)
                    <tr>
                        {{-- <td></td> --}}
                        <td>
                            <h6>{{ $item->Rproductos->nombreProducto ?? '' }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->unidad }}</h6>
                        </td>
                        <td class="text-center" width='15%'>
                                <input type="text" class="form-control form-control-sm" id="can-{{ $item->id }}"
                                    wire:model="can.{{ $item->id }}"
                                    wire:keydown.enter="updateQty({{ $item->id }})"
                                    onclick="this.select()">{{ $item->descargar }}
                            </td>
                            <td class="text-end"> $ {{ number_format($item->costo, 2) }} /
                                {{ number_format($item->costo * 1.13, 2) }}</td>
                            <td class="text-end"> $ {{ number_format($item->costo * $item->cantidad, 2) }} /
                                {{ number_format($item->costo * 1.13 * $item->descargar, 2) }}</td>
                            <td class="text-center"></td>
                        <td class="text-center">
                            <a class="btn btn-danger" href="javascript:void(0);"
                                onclick="Confirm2('{{ $item->id }}')"><i class="bx bx-trash"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Mantiene el evento de enfoque actual
        window.livewire.on('focus-input', function(event) {
            setTimeout(() => {
                const input = document.getElementById('can-' + event.id);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 300);
        });

        // Flechas y tecla Supr
        document.addEventListener('keydown', function(e) {
            if (e.target && e.target.id && e.target.id.startsWith('can-')) {
                const currentInput = e.target;
                const inputs = Array.from(document.querySelectorAll('input[id^="can-"]'));
                const index = inputs.indexOf(currentInput);

                // ↓ siguiente input
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextInput = inputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                        nextInput.select();
                    }
                }

                // ↑ input anterior
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevInput = inputs[index - 1];
                    if (prevInput) {
                        prevInput.focus();
                        prevInput.select();
                    }
                }

                // Suprimir → eliminar producto
                if (e.key === 'Delete') {
                    e.preventDefault();
                    const id = currentInput.id.replace('can-', ''); // usa el mismo ID del enlace
                    Livewire.emit('removeItem', parseInt(id));
                }
            }
        });
    });
</script>
