<div wire:ignore.self class="modal fade" id="modalSujetoExcluido" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Agregar/Editar Sujeto Excluido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form wire:submit.prevent="store">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Empresa</label>
                            <select class="form-control" wire:model="form.empresa">
                                <option value="">Seleccione</option>
                                @if(!empty($empresas) && count($empresas) > 0)
                                    @foreach($empresas as $e)
                                        <option value="{{ $e->id }}">{{ $e->empresa }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Sucursal</label>
                            <select class="form-control" wire:model="form.sucursal">
                                <option value="">Seleccione</option>
                                @if(!empty($sucursales) && count($sucursales) > 0)
                                    @foreach($sucursales as $s)
                                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                    @endforeach
                                @endif
                              
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Cliente</label>
                            <select class="form-control" wire:model="form.cliente">
                                <option value="">Seleccione</option>
                                @if(!empty($clientes) && count($clientes) > 0)
                                    @foreach($clientes as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                @endif
                               
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Fecha y Hora Generación</label>
                            <input type="datetime-local" class="form-control" wire:model="form.fecha_hora_generacion">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Emisor Nombre</label>
                            <input type="text" class="form-control" wire:model="form.emisor_nombre">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Emisor NIT</label>
                            <input type="text" class="form-control" wire:model="form.emisor_nit">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Receptor Nombre</label>
                            <input type="text" class="form-control" wire:model="form.receptor_nombre">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Receptor Tipo Doc</label>
                            <input type="text" class="form-control" wire:model="form.receptor_tipo_doc">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Receptor Número Doc</label>
                            <input type="text" class="form-control" wire:model="form.receptor_num_doc">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Total a Pagar</label>
                            <input type="number" step="0.01" class="form-control" wire:model="form.total_pagar">
                        </div>
                        <div class="col-md-12 mb-2">
                            <label>Observaciones</label>
                            <textarea class="form-control" wire:model="form.observaciones"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>