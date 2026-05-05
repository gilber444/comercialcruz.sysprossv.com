@if ($itemsQuantity > 0)
<div class="row p-1">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">CODIGO</th>
                <th class="text-center">DESCRIPCION</th>
                <th class="text-center">MEDIDA</th>
                <th class="text-right">PRECIO COMPRA</th>
                <th class="text-center">EXISTENCIA</th>
                <th class="text-center">CANTIDAD</th>
                <th class="text-center">NUEVA EXISTENCIA</th>
                <!--<th class="text-center">FECHA VEN.</th>-->
                <th class="text-center">TOTAL</th>
                <th class="text-center"></th>
            </thead>
            <tbody>

                @forelse ( $cart as $item)
                <tr>
                    <td class="text-center"><h6>{{$item->codebar}}</h6> </td>
                    <td>{{ $item->name }}</td>
                    <td>
                        {{-- <select class="form-control" wire:model='uni.{{ $item->id }}' wire:change='updateUni({{ $item->id }})'>
                            <option value="">Elegir</option>
                            @foreach (DB::table('precios')->where('producto', $item->producto)->get(); as $medi)
                            <option value="{{ $medi->id }}">{{ $medi->presentacion }} X {{ $medi->cantidad }} </option>
                            @endforeach
                        </select> --}}
                        @php
                            $medida = DB::table('medidas')->where('id', $item->medida)->first();
                            // echo $medida;
                        @endphp
                        {{ $medida->unidad }}
                    </td>
                    <td>
                        @php
                            $costo = DB::table('precios')->where('producto', $item->producto)->where('medida', $item->medida)->where('cantidad', $item->ingreso)->first();

                            // Definir la clase del input según la condición
                            $clase = '';

                            if ($costo) {
                                if ($item->price < $costo->costosiva) {
                                    $clase = 'border border-success text-success fw-bold';
                                } elseif ($item->price > $costo->costosiva) {
                                    $clase = 'border border-danger text-danger fw-bold';
                                }
                            }
                        @endphp
                        @can('ComprasPrice_Edit')
                            <input type="text" class="form-control w-100 {{ $clase }}" placeholder="{{$item->price}}" wire:model='pri.{{$item->id}}' wire:keydown.enter="updatePre({{$item->id}})" wire:change="updatePre({{$item->id}})" value="{{$item->preci}}">
                        @else
                            $ {{$item->price}}
                        @endcan

                    </td>
                    <td class="text-center" width='10%'>{{ $existenciaActual[$item->id] }}</td>
                    <td class="text-center" width='25%'>
                        <input type="text" class="form-control w-100" placeholder="{{$item->quantity}}" wire:model='can.{{$item->id}}' wire:keydown.enter="updateQty({{$item->id}})" wire:change="updateQty({{$item->id}})" value="{{$item->quantity}} " id="can-{{ $item->id }}">{{ $item->ingreso }}
                    </td>
                    <td class="text-center" width='10%'>{{ $nuevaExistencia[$item->id] }}</td>
                    {{--<td class="text-center">
                        <input type="date" class="form-control w-100" placeholder="{{ $item->vencimiento }}" wire:model='fechaV.{{$item->id}}' wire:change="updateFV({{$item->id}})">
                    </td>--}}
                    <td class="text-center">{{ number_format($item->quantity * $item->price ,2) }}</td>
                    <td class="text-center">
                        <button wire:click.prevent='removeItem({{$item->id}})' type="button" class="btn btn-sm btn-label-primary">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                    </td>
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
                <div class="fw-bold">Agrega productos a la Compra</div>
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
