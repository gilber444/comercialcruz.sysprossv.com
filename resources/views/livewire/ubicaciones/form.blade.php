<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">{{$componentName}} | {{ $selected_id > 0 ? 'Editar' : 'Nuevo' }}</h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <select class="form-select" wire:model='usuario' {{ $selected_id > 0 ? 'disabled' : ''}}>
                        <option>Elegir</option>
                        @if (!is_null($usuarios))
                            @foreach ($usuarios as $usu)
                                <option value="{{$usu->id}}"> {{$usu->name}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @error('usuario') <span class="text-danger er">{{ $message}}</span>@enderror

                <div class="mb-3">
                    <label class="form-label">Empresa </label>
                    <select class="form-select"  wire:model='selectedEmpresa'>
                        <option>Elegir</option>
                        @foreach ($empresas as $em )
                        <option value="{{$em->id}}">{{$em->empresa}}</option>
                        @endforeach
                    </select>
                </div>
                @error('empresa') <span class="text-danger er">{{ $message}}</span>@enderror

                <div class="mb-3">
                    <label class="form-label">Sucursal {{ $selected_id > 0 ? ': ' . $s : ''}}</label>
                    <select class="form-select" wire:model='selectedSucursal'>
                        <option>Elegir</option>
                        @if (!is_null($sucursales))
                            @foreach ($sucursales as $su)
                                <option value="{{$su->id}}">{{$su->nombre}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @error('selectedSucursal') <span class="text-danger er">{{ $message}}</span>@enderror

                <div class="mb-3">
                    <label class="form-label">Caja {{ $selected_id > 0 ? ': ' . $c : ''}}</label>
                    <select class="form-select" wire:model='selectedParametro'>
                        <option>Elegir</option>
                        @if (!is_null($parametros))
                            @foreach ($parametros as $para)
                                <option value="{{$para->id}}">Caja {{$para->caja}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @error('selectedParametros') <span class="text-danger er">{{ $message}}</span>@enderror
@include('common.modalFooter')
