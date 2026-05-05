@if ($itemsQuantity > 0)
    <div class="row p-1">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-sm">
                <thead>
                    <th class="text-center">CODIGO</th>
                    <th class="text-center">DESCRIPCION</th>
                    <th class="text-center">MEDIDA</th>
                    <th class="text-center">EXISTENCIA</th>
                    <th class="text-center">CANTIDAD</th>
                    <th class="text-center">EXISTENCIA AJUSTADA</th>
                    <th class="text-center">TOTAL</th>
                </thead>
                <tbody>
                    @forelse ($cart as $item)
                        <tr>
                            <td class="text-center" width='10%'>
                                <a href="#" class="text-black"
                                    wire:click.prevent='removeItem({{ $item->id }})'>
                                    <h6>{{ $item->codebar }}</h6>
                                </a>
                            </td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center" width='10%'>
                                {{-- <select class="form-control form-control-sm" wire:model='uni.{{ $item->id }}'
                                    wire:change='updateUni({{ $item->id }})'>
                                    <option value="{{ $item->unidad }}">{{ $item->medida }}</option>

                                    @php
                                        $query = DB::table('precios')
                                            ->join('medidas as m', 'm.id', 'precios.medida')
                                            ->select('m.id', 'm.unidad', 'precios.cantidad', 'precios.id as precio')
                                            ->where('precios.producto', $item->producto)
                                            ->where('precios.escala', 'No')
                                            ->whereNull('precios.deleted_at');

                                        if (Auth::user()->profile === 'Cajeros') {
                                            $query->where('precios.cantidad', 1);
                                        }

                                        $medidas = $query->get();
                                    @endphp

                                    @foreach ($medidas as $medi)
                                        <option value="{{ $medi->id }}|{{ $medi->precio }}">
                                            {{ $medi->unidad }} X {{ $medi->cantidad }}
                                        </option>
                                    @endforeach
                                </select> --}}
                                @php
                                    $medida = DB::table('medidas')->where('id', $item->unidad)->first();
                                    // echo $medida;
                                @endphp
                                {{ $medida->unidad }}
                            </td>
                            <td class="text-center" width='10%'>{{ $existenciaActual[$item->id] }}</td>
                            <td class="text-center" width='10%'>
                                <input type="text" class="form-control w-100 form-control-sm text-center"
                                    placeholder="{{ $item->quantity }}" id="can-{{ $item->id }}"
                                    wire:model='can.{{ $item->id }}'
                                    wire:keydown.enter="updateQty({{ $item->id }})"
                                    wire:blur="updateQty({{ $item->id }})"
                                    value="{{ $item->quantity }} ">{{ $item->ingreso }}
                            </td>
                            <td class="text-center" width='10%'>{{ $existenciaAjustada[$item->id] }}</td>
                            <td class="text-center" width='10%'>{{ round($item->total, 2) }} /
                                {{ round($item->total * 1.13, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">SIN RESULTADOS</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="alert alert-primary mb-4" role="alert">
        <div class="d-flex gap-3 text-center">
            <div class="flex-shrink-0">
                <span class="badge badge-center rounded-pill bg-primary border-label-primary p-5 me-2">
                    <i class="fa-solid fa-cart-shopping fs-1"></i>
                </span>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">Agrega productos al ajuste</div>
            </div>
        </div>
    </div>
@endif
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
