<h6>Detalle de la Compra</h6>

<dl class="row mb-0">
    <dt class="col-sm-6 fw-normal">Total de Produtos</dt>
    <dd class="col-sm-6 text-end">{{$itemsQuantity}}</dd>

    <dt class="col-sm-6 fw-normal">Total Sin IVA</dt>
    <dd class="col-sm-6 text-end">${{ number_format($subtotal, 2)}}</dd>

    <dt class="col-sm-6 fw-normal">IVA</dt>
    <dd class="col-sm-6 text-end">${{ number_format($iva ,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Sub Total</dt>
    {{--  <dd class="col-sm-6 text-end">${{ number_format(($subtotal + $iva),2) }}</dd>--}}
    <dt class="col-sm-6 fw-normal">Percepción</dt>
    <dd class="col-sm-6 text-success text-end"><input type="text" wire:model.lazy='percepcion' class="form-control"></dd>
    <hr />
    <dt class="col-sm-6">Total</dt>
    <dd class="col-sm-6 fw-semibold text-end mb-0">{{  number_format($total, 2)}}</dd>
</dl>

