<div class="row">


    <div class="col-sm-12 col-md-4 mb-2">


        <label for="">Tienda Entrega</label>


        <div class="input-group">


            @if ($rol === 'Super' || $rol === 'Administrador' || $rol === 'Auditor')


                <select class="form-select" wire:model='origen'>


                    <option selected="">Elegir...</option>


                    @if ($origen1)


                    @foreach ($origen1 as $o)


                        <option value="{{ $o->id }}">{{$o->nombre }}, {{$o->Rempresa->empresa }}</option>


                    @endforeach


                    @endif


                </select>


            @else


            <input type="text" readonly value="{{-- $origen1->nombre --}}, {{$origen1->Rempresa->empresa }}" class="form-control">


            @endif


        </div>


        @error('origen') <span class="text-danger er">{{ $message}}</span>@enderror


    </div>


    <div class="col-sm-12 col-md-4 mb-2">


        <label for="">Tienda Recibe</label>


        <div class="input-group">


            <select class="form-select" wire:model='destino'>


                <option selected="">Elegir...</option>


                @if ($destino1)


                    @foreach ($destino1 as $d)


                        <option value="{{ $d->id }}">{{$d->nombre }}, {{$d->Rempresa->empresa }}</option>


                    @endforeach


                @endif


            </select>


        </div>


        @error('destino') <span class="text-danger er">{{ $message}}</span>@enderror


    </div>


    <div class="col-sm-12 col-md-2 mb-2">


        <label for="">Numero</label>


        <div class="input-group">


            <span class="input-group-text" id="basic-addon11"><i class="fa-solid fa-hashtag"></i></span>


            <input type="text" wire:model.lazy='correlativo' class="form-control" readonly>


        </div>


        @error('correlativo') <span class="text-danger er">{{$message}}</span>@enderror


    </div>


    <div class="col-sm-12 col-md-2 mb-2">


        <label for="">Fecha</label>


        <div class="input-group">


            <input id="fechaInput" type="datetime-local" class="form-control" placeholder="0000" wire:model='fecha'>


        </div>


        @error('fecha') <span class="text-danger er">{{ $message}}</span>@enderror


    </div>


</div>


<div class="row">


    <div class="col-sm-12 col-md-12 mb-2" wire:ignore>


        <label for="">Detalle</label>


        <div class="form-group">


            <input type="text" wire:model='detalle' placeholder="Detalle de la solicitud" class="form-control">


        </div>


        @error('detalle') <span class="text-danger er">{{ $message}}</span>@enderror


    </div>


</div>


