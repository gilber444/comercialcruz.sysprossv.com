{{-- <h6>Detalle de la Compra</h6>
@if ($select_id > 0)
<dl class="row mb-0">
    <dt class="col-sm-6 fw-normal">Total de Produtos</dt>
    <dd class="col-sm-6 text-end">{{$itemsQuantity}}</dd>

    <dt class="col-sm-6 fw-normal">Sub Total</dt>
    <dd class="col-sm-6 text-end">${{ number_format($subtotal, 2)}}</dd>
    <dt class="col-sm-6 fw-normal">IVA</dt>
    <dd class="col-sm-6 text-end">${{ number_format(($iva) ,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Sub Total</dt>
    <dd class="col-sm-6 text-end">${{ number_format($total,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Percepcion 1%</dt>
    <dd class="col-sm-6 text-end">
        @php
        if($total >= 100)
        {
            $percepcion = number_format($total * 0.01,2);
        }
        else
        {
            $percepcion = 0.00;
        }

        @endphp
        ${{ number_format($percepcion ,2) }}
    </dd>
    <hr />

    <dt class="col-sm-6">Total</dt>
    <dd class="col-sm-6 fw-semibold text-end mb-0">
        {{  number_format($totales,2)}}
    </dd>
</dl>
@else
<dl class="row mb-0">
    <dt class="col-sm-6 fw-normal">Total de Produtos</dt>
    <dd class="col-sm-6 text-end">{{$itemsQuantity}}</dd>

    <dt class="col-sm-6 fw-normal">Total Sin IVA</dt>
    <dd class="col-sm-6 text-end" >${{ number_format($subtotal, 2)}}</dd>

    <dt class="col-sm-6 fw-normal">IVA</dt>
    <dd class="col-sm-6 text-end">${{ number_format($iva ,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Sub Total</dt>
    <dd class="col-sm-6 text-end">${{ number_format($total,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Percepción</dt>
    <dd class="col-sm-6 text-success text-end"><input type="text" wire:model.lazy='percepcion' wire:keydown.enter="calcularPercepcion" class="form-control"></dd>
    <hr />
    <dt class="col-sm-6">Total</dt>
    <dd class="col-sm-6 fw-semibold text-end mb-0">$ {{  number_format($totales,2)}}</dd>
</dl>
@endif --}}

<dl class="row mb-0 small">

    <dt class="col-sm-2 text-end py-0 my-0">Total de Productos</dt>
    <dd class="col-sm-2 fw-normal py-0 my-0">{{ $itemsQuantity }}</dd>

    <dt class="col-sm-2 text-end py-0 my-0">
        {{ $select_id > 0 ? 'Sub Total' : 'Total Sin IVA' }}
    </dt>
    <dd class="col-sm-2 fw-normal py-0 my-0">
        ${{ number_format($subtotal, 2) }}
    </dd>

    <dt class="col-sm-2 text-end py-0 my-0">IVA</dt>
    <dd class="col-sm-2 fw-normal py-0 my-0">
        ${{ number_format($iva, 2) }}
    </dd>

    <dt class="col-sm-2 text-end py-0 my-0">Sub Total</dt>
    <dd class="col-sm-2 fw-normal py-0 my-0">
        ${{ number_format($total, 2) }}
    </dd>

    <dt class="col-sm-2 text-end py-0 my-0">
        {{ $select_id > 0 ? 'Percepción 1%' : 'Percepción' }}
    </dt>
    <dd class="col-sm-2 fw-normal py-0 my-0">
        @if ($select_id > 0)
            @php
                if($total >= 100) {
                    $percepcion = number_format($total * 0.01,2);
                } else {
                    $percepcion = 0.00;
                }
            @endphp
            ${{ number_format($percepcion ,2) }}
        @else
            <input type="text" 
                   wire:model.lazy="percepcion" 
                   wire:keydown.enter="calcularPercepcion" 
                   class="form-control form-control-sm py-0 my-0">
        @endif
    </dd>

    <dt class="col-sm-2 text-end py-0 my-0 fs-5">Total</dt>
    <dd class="col-sm-2 fw-semibold py-0 my-0 fs-5">
        ${{ number_format($totales,2) }}
    </dd>

</dl>
