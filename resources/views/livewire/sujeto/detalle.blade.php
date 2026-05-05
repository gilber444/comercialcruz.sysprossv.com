@if (isset($itemsQuantity) && $itemsQuantity > 0)
    <div class="row p-1">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-sm">
                <thead>
                    <th class="text-center">PRODUCTO</th>
                    <th class="text-center">DESCRIPCIÓN</th>
                    <th class="text-center">UNIDAD</th>
                    <th class="text-center">CANTIDAD</th>
                    <th class="text-center">COSTO</th>
                    <th class="text-center">TOTAL</th>
                    <th class="text-center">ACCIÓN</th>
                </thead>
                <tbody>
    @php $hayResultados = false; @endphp
    @forelse ($cart as $item)
        @if($item && isset($item->id))
            @php $hayResultados = true; @endphp
            <tr>
                <td class="text-center">{{ $item->producto }}</td>
                <td class="text-center">{{ $item->name }}</td>
                <td class="text-center">{{ $item->unidad }}</td>
                <td class="text-center" width="10%">
                    <input type="text" class="form-control w-100 text-end"
                        wire:model="can.{{ $item->id }}"
                        wire:keydown.enter="updateQty({{ $item->id }})"
                        value="{{ $item->cantidad }}">
                </td>
                <td class="text-center" width="15%">
                    <input type="text" class="form-control w-100 text-end"
                        wire:model="costo.{{ $item->id }}"
                        wire:keydown.enter="updateCosto({{ $item->id }})"
                        value="{{ $item->costo }}">
                </td>
                <td class="text-center">{{ number_format($item->toatal, 2) }}</td>
                <td class="text-center">
                    <a href="#" class="text-danger" wire:click.prevent='removeItem({{ $item->id }})'>
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            </tr>
        @endif
    @empty
    @endforelse
    @if(!$hayResultados)
        <tr>
            <td colspan="7" class="text-center">SIN RESULTADOS</td>
        </tr>
    @endif
</tbody>
            </table>
        </div>
    </div>
@else
    <div class="alert alert-primary mb-4" role="alert">
        <div class="d-flex gap-3 text-center">
            <div class="flex-shrink-0">
                <span class="badge badge-center rounded-pill bg-primary border-label-primary p-5 me-2">
                    <i class="fa-solid fa-user-xmark fs-1"></i>
                </span>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">Agrega productos al sujeto excluido</div>
            </div>
        </div>
    </div>
@endif