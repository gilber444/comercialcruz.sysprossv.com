<h6>Detalle de Nota Credito</h6>

<dl class="row mb-0">

    <dt class="col-sm-6 fw-normal">Total Produtos</dt>

    <dd class="col-sm-6 text-end">{{$itemsQuantity}}</dd>



    <dt class="col-sm-6 fw-normal">Total Sin IVA</dt>

    <dd class="col-sm-6 text-end" >${{ number_format($total, 2)}}</dd>

    <dt class="col-sm-6 fw-normal">IVA</dt>

    <dd class="col-sm-6 text-end">${{ number_format($iva ,2) }}</dd>

    <dt class="col-sm-6 fw-normal">Sub Total</dt>

    <dd class="col-sm-6 text-end">${{ number_format($totales,2) }}</dd>

    <dt class="col-sm-6 fw-normal">Percepción</dt>

    <dd class="col-sm-6 text-end">${{ number_format($percepcion,2) }}</dd>





    <hr />



    <dt class="col-sm-6">Total</dt>

    <dd class="col-sm-6 fw-semibold text-end mb-0">

        ${{ number_format((float)$totales, 2) }}

    </dd>

</dl>

