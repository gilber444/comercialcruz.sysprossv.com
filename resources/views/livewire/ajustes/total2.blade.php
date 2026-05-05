@if ($selected_id > 0)
<dl class="row mb-0">
    <dt class="col-sm-10 fw-normal text-end">Produtos</dt>
    <hr />
    <dt class="col-sm-10 text-end">Total</dt>
    <dd class="col-sm-2 fw-semibold text-end mb-0">{{  number_format($totales ,2)}}</dd>
</dl>
@else
<dl class="row mb-0">
    <dt class="col-sm-10 fw-normal text-end">Produtos</dt>
    <dd class="col-sm-2 text-end">{{$this->itemsQuantitys}}</dd>
    <hr />
    <dt class="col-sm-10 text-end">Total</dt>
    <dd class="col-sm-2 fw-semibold text-end mb-0">{{  number_format($totales ,2)}}</dd>
</dl>
@endif
