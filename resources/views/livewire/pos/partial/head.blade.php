<div class="row">
    <div class="col-sm-12 col-md-6 border rounded p-1 mb-1 small">
        <h6 class="mb-1 me-1">Cajer@: {{ Auth::user()->name }}</h6>
        <span class="mb-1 me-1">Apertura: {{ \Carbon\Carbon::parse($aperturas->fechaApertura)->format('d/m/Y') }}
        </span>
        <!--<p>Corte</p>-->
        <i>F6 -> Convertir a otra Unidad</i>
        <div class="alert alert-warning p-1 mb-1" role="alert" style="font-size: 10px;">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <b>F11</b> → Arqueo / Corte X al finalizar turno.
        </div>

        <div class="alert alert-danger p-1 mb-1" role="alert" style="font-size: 10px;">
            <i class="fas fa-times-circle me-1"></i>
            <b>F10</b> → Cierre de Caja al finalizar el día <b>11:50 PM</b>.
        </div>
        <i>Alt + S -> Guardar Venta</i>

        @php
            $colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary', 'dark'];
        @endphp

        <div class="mt-2">
            @foreach ($escenarios as $esc)
                @if ($esc != 1)
                    @php
                        $color = $colors[($esc - 2) % count($colors)];
                        $res   = $escResumen[$esc] ?? ['total' => 0, 'items' => 0];
                    @endphp
                    <button class="btn btn-sm btn-outline-{{ $color }} rounded-pill me-1 mb-1"
                        wire:click="moverAEscenarioUno({{ $esc }})">
                        Venta {{ $esc }} — $ {{ number_format($res['total'], 2) }}
                    </button>
                @endif
            @endforeach
        </div>
    </div>
    <div class="col-sm-12 col-md-2 border rounded mb-1 p-1 small">
        <p class="mb-1"><b>Ultima Venta</b></p>
        <p class="mb-1 text-primary">Total   $ {{ number_format($ultimoTotal, 2) }}</p>
        <p class="mb-1">Efectivo $ {{ number_format($ultimoEfectivo, 2) }}</p>
        <p class="mb-1 text-danger">Cambio   $ {{ number_format($ultimoCambio, 2) }}</p>
    </div>
    <div class="col-sm-12 col-md-4 border rounded p-1 mb-1 text-end small">
        <h4 class="mb-1">Total $ {{ number_format($total, 2) }}</h4>
        <h5 class="mb-1">Descuentos $ {{ number_format($descu, 2); }} </h5>
        <h6 class="mb-1">Productos {{ $itemsQuantity }}</h6>
        <h6 class="mb-1">Items {{ $itemsProd }}</h6>
    </div>
</div>
