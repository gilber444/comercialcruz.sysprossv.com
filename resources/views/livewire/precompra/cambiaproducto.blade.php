<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">Actualizando Producto</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">Producto de la Factura</label>
                        <input type="text" readonly wire:model.lazy='productoDetalle' class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">Seleccione producto del Sistema a Validar</label>
                        <div class="input-group" wire:ignore style="width: 100%">
                        <select class="form-control select2" id="producto" style="width: 100%">
                            <option value="Seleccione producto del Sistema a Validar">Seleccione producto del Sistema a Validar</option>
                            @foreach ($productos as $prod)
                                <option value="{{ $prod->id }}"> {{ $prod->codebar3 }} {{ $prod->nombreProducto }}</option>
                            @endforeach
                        </select>
                        </div>
                        <a href="{{ route('productos') }}" class="btn btn-primary btn-rounded mb-2" target="_blank"> <i class="fa-solid fa-plus"></i> Agregar Producto</a>
                    </div>
                    <div class="col-sm-12 col-md-12 mb-3">
                        <label for="">Seleccione Unidad de Medida</label>
                        <div class="input-group">
                        <select class="form-control" wire:change='Cambiar()' wire:model.lazy='medida'>
                            <option value="">Elegir...</option>
                            @foreach ($medidas as $medida)
                                <option value="{{ $medida->medida }}">{{ $medida->presentacion }}</option>
                            @endforeach
                        </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="guardar" wire:click.prevent="Cambiar()"
                    class="btn btn-primary"><i class='bx bxs-save'></i> Realizar Cambio</button>
            </div>
        </div>
    </div>
</div>
