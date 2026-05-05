<h6>Detalle de la Compra</h6>
<dl class="row mb-0">
    <dt class="col-sm-6 fw-normal">Total de Produtos</dt>
    <dd class="col-sm-6 text-end">{{$detallesCount}}</dd>

    <dt class="col-sm-6 fw-normal">Total Sin IVA</dt>
    <dd class="col-sm-6 text-end">${{ number_format($totalVentaGravada, 2)}}</dd>

    <dt class="col-sm-6 fw-normal">IVA</dt>
    <dd class="col-sm-6 text-end">${{ number_format($totalIva ,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Sub Total</dt>
    <dd class="col-sm-6 text-end">${{ number_format($subTotal ,2) }}</dd>
    <dt class="col-sm-6 fw-normal">Percepcion 1%</dt>
    <dd class="col-sm-6 text-end">${{ number_format($precepcion ,2) }}
    </dd>
    <hr />
    <dt class="col-sm-6">Total</dt>
    <dd class="col-sm-6 fw-semibold text-end mb-0">{{  number_format($totalPagar,2)}}</dd>
</dl>
