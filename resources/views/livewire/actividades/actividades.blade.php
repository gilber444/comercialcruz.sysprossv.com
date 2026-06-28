<div class="card text-centrer">
    <div class="card-header">
        <h6 class="card-title">Seleccione una Caja</h6>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach ($data as $ca)
            <div class="col-sm-12 col-md-3 mb-3">
                <div class="card border-primary border shadow-none">
                    <div class="card-body position-relative pt-4">
                        <div class="my-5 pt-6 text-center">
                            <i class="fa-solid fa-cash-register fa-10x text-primary"></i>
                        </div>
                        <h4 class="card-title text-center text-capitalize mb-1">{{ $ca->sucursales->nombre }}</h4>
                        <div class="text-center h-px-50">
                            <div class="d-flex justify-content-center">
                                <h1 class="price-toggle price-yearly text-primary mb-0">{{ $ca->caja }}</h1>
                            </div>
                        </div>
                        <input type="hidden" wire:model='caja.{{ $ca->id }}' value="{{ $ca->id }}">
                        <button type="button" class="btn btn-label-primary d-grid w-100" wire:click='Validad({{ $ca->id }})'>Aperturar</button>
                        @error('caja.{{ $ca->id }}')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
</div>
@include('common.notis')
