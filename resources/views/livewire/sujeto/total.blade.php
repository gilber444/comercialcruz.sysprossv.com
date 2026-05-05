
@php
    $select_id = isset($select_id) && is_numeric($select_id) ? $select_id : 0;
    $itemsQuantity = isset($itemsQuantity) && is_numeric($itemsQuantity) ? $itemsQuantity : 0;
    $total = isset($total) && is_numeric($total) ? $total : 0;
@endphp

@if ($select_id > 0)
<dl class="row mb-0">
    <dt class="col-sm-10 fw-normal text-end">Sujetos Excluidos</dt>
    <dd class="col-sm-2 text-end">{{ $itemsQuantity }}</dd>
    <hr />
    <dt class="col-sm-10 text-end">Total</dt>
    <dd class="col-sm-2 fw-semibold text-end mb-0">{{ number_format($total, 2) }}</dd>
</dl>
@else
<dl class="row mb-0">
    <dt class="col-sm-10 fw-normal text-end">Sujetos Excluidos</dt>
    <dd class="col-sm-2 text-end">{{ $itemsQuantity }}</dd>
    <hr />
    <dt class="col-sm-10 text-end">Total</dt>
    <dd class="col-sm-2 fw-semibold text-end mb-0">{{ number_format($total, 2) }}</dd>
</dl>
@endif