<div wire:ignore.self class="modal fade" id="mypreciosSucursal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">
                    Precios por sucursales
                </h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="card mb-6">
                    <div class="card-body">
                        @foreach ($sucursalesPrecios as $sp )
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked{{ $sp->id }}" wire:model="sp.{{ $sp->id }}" wire:change="updateSucursal({{ $sp->id }})" {{ $sp->activo ? 'checked' : '' }}>
                            <label class="form-check-label" for="flexSwitchCheckChecked{{ $sp->id }}">{{ $sp->Rsucursal->nombre }}</label>
                        </div>
                        @endforeach
                    </div>
                  </div>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
