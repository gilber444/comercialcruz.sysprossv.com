<dl class="row mb-0">
    <dt class="col-sm-2 text-end">Total de Produtos</dt>
    <dd class="col-sm-2 fw-normal">{{ $itemsQuantity }}</dd>

    <dt class="col-sm-2 text-end">Costo Total</dt>
    <dd class="col-sm-2 fw-normal">${{ number_format((float) $total, 2) }} / {{ number_format((float) $total * 1.13, 2) }}
    </dd>

    {{-- <hr /> --}}

    <dt class="col-sm-2 text-end">Total</dt>
    <dd class="col-sm-2 fw-semibold fw-normal mb-0">
        ${{ number_format((float) $total, 2) }} / {{ number_format((float) $total * 1.13, 2) }}
    </dd>
</dl>