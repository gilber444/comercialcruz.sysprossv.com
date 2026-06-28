<div class="col-sm-12 col-md-12 border rounded p-1 mb-1" style="height: 350px">
    <style>
        .detalle-table tbody tr:hover,
        .detalle-table tbody tr:focus-within {
            background-color: rgba(105, 108, 255, 0.16);
        }

        .detalle-table tbody tr:focus-within {
            outline: 2px solid rgba(105, 108, 255, 0.16);
            outline-offset: -2px;
        }

        @media (prefers-color-scheme: dark) {

            .detalle-table tbody tr:hover,
            .detalle-table tbody tr:focus-within {
                background-color: rgba(105, 108, 255, 0.16);
            }
        }
    </style>
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
        <table class="table table-hover table-sm detalle-table">
            <thead>
                <th></th>
                <th class="text-center">Cant.</th>
                <th class="text-center">Descripcion</th>
                <th class="text-center">Medida</th>
                <th class="text-center">Precio</th>
                <th class="text-center">Des.</th>
                <th class="text-center">Total</th>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3">
                        <input type="text" wire:ignore.self id="code" class="form-control form-control-sm"
                            placeholder="Digite Codigo de Barras" aria-label="Digite Codigo de Barras" onfocus=""
                            style="width: 50%" wire:keydown.enter.prevent="$emit('scan-code-byid', $('#code').val())"
                            onkeydown="if(event.key === 'Backspace') this.value = '';">
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @foreach ($cart as $item)
                    <tr style="font-size: 85%">
                        <td style="width: 5%;">
                            <button wire:click.prevent='removeItem({{ $item->id }})' type="button"
                                class="btn btn-sm btn-label">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                        </td>
                        <td style="width: 10%;">
                            <input type="text" class="form-control form-control-sm input-cantidad"
                                placeholder="{{ $item->quantity }}" wire:model='can.{{ $item->id }}'
                                wire:keydown.enter="updateCanti({{ $item->id }})" value="{{ $item->quantity }}"
                                id='can.{{ $item->id }}'
                                wire:keydown.enter="actualizarCantidadYFoco({{ $item->id }})">
                        </td>
                        <td style="width: 55%;">{{ $item->name }}</td>
                        <td class="text-center">
                            {{ $item->medida }} / {{ $item->limit }}
                        </td>
                        <td class="text-end">{{ number_format($item->price, 2) }}</td>
                        <td class="text-center">{{ number_format($item->descuento * $item->quantity, 2) }}</td>
                        <td class="text-end"> {{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    Livewire.on('focus-barcode', () => {
        const barcode = document.getElementById('code');
        if (barcode) barcode.focus();
    });
</script>
