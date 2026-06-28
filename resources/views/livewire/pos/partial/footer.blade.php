<div class="card-footer border rounded p-1 mb-1">
    <a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalSaveTikect" {{ $itemsProd == 0 ? 'disabled' : '' }}>F2 Ticket</a>
    <a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalSaveConsumidor" {{ $itemsProd == 0 ? 'disabled' : '' }}>F3 C. Final</a>
    <a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalSaveCFiscal" {{ $itemsProd == 0 ? 'disabled' : '' }}>F4 C. Fiscal</a>
    <!--<a href="javascript:void(0);" {{-- $itemsQuantity == 0 ? 'disabled' : '' --}}>F5 Credito</a>
    <a href="javascript:void(0);">F6 Cortes</a>-->
    <a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalRemesas" {{ $disponible < 200 ? 'disabled' : '' }}>F7 Remesar</a>
    <!--<a href="javascript:void(0);">F8 Cotizaciones</a>-->
    <a href="javascript:void(0);" class="btn btn-label text-body" wire:click='DetalleAnulacion'>F9 Anulaciones</a>
    <!--<a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalAnulaciones">F9 Anulaciones</a>-->
    <!--<a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalCorteZ">F10 Cierre Caja </a>-->
     <a href="javascript:void(0);" class="btn btn-label text-body" data-bs-toggle="modal" data-bs-target="#modalArqueo">F11 Arqueos / Corte X </a>
    <a href="javascript:void(0);" onclick="confirmarCierreCaja()" class="btn btn-label text-body">F10 Cierre Caja</a>
</div>
