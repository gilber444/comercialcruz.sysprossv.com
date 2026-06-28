<div class="card shadow-sm">

    {{-- ── HEADER ── --}}
    <div class="card-header border-bottom py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

            {{-- INFO DE LA HOJA (izquierda) --}}
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-clipboard-list fa-lg text-primary"></i>
                        <span class="fw-bold fs-6">
                            Hoja&nbsp;
                            <span class="badge bg-label-primary rounded-pill" style="font-size: .85rem;">
                                {{ $hojaCorrelativo }}
                            </span>
                        </span>
                        @php
                            $estadoClass = match(strtolower($hojaEstado)) {
                                'activa', 'pendiente' => 'bg-label-warning',
                                'cerrado', 'realizado' => 'bg-label-success',
                                default => 'bg-label-secondary',
                            };
                        @endphp
                        <span class="badge {{ $estadoClass }} rounded-pill" style="font-size: .75rem;">
                            {{ $hojaEstado }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-1 text-muted" style="font-size: .78rem;">
                        <span><i class="fa-solid fa-store me-1"></i>{{ $hojaSucursalNombre }}</span>
                        <span><i class="fa-regular fa-calendar me-1"></i>{{ $hojaFecha }}</span>
                        <span><i class="fa-regular fa-user me-1"></i>{{ $hojaResponsable }}</span>
                    </div>
                </div>
            </div>

            {{-- BOTONES (derecha) --}}
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('hoja_inventarios') }}" class="btn btn-sm btn-label-secondary rounded-pill">
                    <i class="fa-solid fa-arrow-left me-1"></i> Regresar
                </a>
                @if ($inventarios && $inventarios->count())
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3"
                        wire:click="Store"
                        wire:loading.attr="disabled" wire:target="Store">
                        <span wire:loading.remove wire:target="Store">
                            <i class="fa-solid fa-check me-1"></i> Finalizar hoja
                        </span>
                        <span wire:loading wire:target="Store">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Guardando...
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ── BARRA DE HERRAMIENTAS ── --}}
    <div class="px-3 py-2 border-bottom d-flex align-items-center gap-3 flex-wrap">
        {{-- Scanner de código de barras (prominente) --}}
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 380px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text">
                    <i class="fa-solid fa-barcode text-primary"></i>
                </span>
                <input type="text" wire:ignore.self id="code"
                    class="form-control"
                    placeholder="Escanear o digitar código de barras"
                    wire:keydown.enter.prevent="$emit('scan-code', $('#code').val())"
                    onkeydown="if(event.key==='Backspace') this.value='';"
                    style="font-size: .85rem;">
            </div>
        </div>
        <button class="btn btn-sm btn-label-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalSearchProduct">
            <i class="fa-solid fa-crosshairs me-1"></i> Buscar producto
        </button>
        <livewire:modal-hoja>
    </div>

    {{-- ── TABLA ── --}}
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="font-size: .82rem;">
            <thead class="table-light">
                <tr>
                    <th class="text-center ps-3" style="width: 110px;">CÓDIGO</th>
                    <th>DESCRIPCIÓN</th>
                    <th class="text-center" style="width: 80px;">MEDIDA</th>
                    <th class="text-center" style="width: 70px;">CONT.</th>
                    <th class="text-center" style="width: 90px;">EXISTENCIA</th>
                    <th class="text-center" style="width: 120px;">CONTEO FÍSICO</th>
                    <th class="text-center" style="width: 80px;">CANTIDAD</th>
                    <th class="text-center" style="width: 90px;">DIFERENCIA</th>
                    <th class="text-center" style="width: 90px;">COSTO</th>
                    <th class="text-center" style="width: 90px;">TOTAL</th>
                    <th class="text-center pe-3" style="width: 50px;"></th>
                </tr>
            </thead>
            <tbody>

                @foreach ($inventarios as $item)
                    <tr class="product-row" data-id="{{ $item->id }}">
                        <td class="text-center ps-3">
                            <code style="background: #f0f2f5; padding: 2px 6px; border-radius: 4px; font-size: 11px; color: #233446;">
                                {{ $item->codebar }}
                            </code>
                        </td>
                        <td class="fw-semibold">{{ $item->name }}</td>
                        <td class="text-center text-muted">
                            {{ DB::table('medidas')->where('id', $item->medida)->value('unidad') ?? '—' }}
                        </td>
                        <td class="text-center text-muted">{{ $item->limit }}</td>
                        <td class="text-center">{{ $item->existencia }}</td>
                        <td class="text-center">
                            <input id="can-{{ $item->id }}" type="text"
                                class="form-control form-control-sm text-center conteo-input"
                                placeholder="{{ $item->conteoFisico }}"
                                wire:model.defer="conteo.{{ $item->id }}"
                                wire:keydown.enter.prevent="saveConteo({{ $item->id }})"
                                inputmode="decimal" pattern="^\d+(\.\d+)?$"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./, '$1')"
                                style="font-size: 12px; min-width: 70px;">
                            <style>
                                #can-{{ $item->id }}::placeholder {
                                    color: #ff4d4f;
                                    opacity: 1;
                                    font-weight: 600;
                                }
                            </style>
                        </td>
                        <td class="text-center">{{ $item->cantidad }}</td>
                        <td class="text-center">
                            <span class="badge rounded-pill {{ $item->diferencia > 0 ? 'bg-label-warning' : ($item->diferencia < 0 ? 'bg-label-danger' : 'bg-label-success') }}" style="min-width: 52px;">
                                {{ $item->diferencia > 0 ? '+' . $item->diferencia : $item->diferencia }}
                            </span>
                        </td>
                        <td class="text-end pe-2">$ {{ number_format($item->costo, 2) }}</td>
                        <td class="text-end pe-2">
                            <span class="fw-semibold {{ $item->total > 0 ? 'text-warning' : ($item->total < 0 ? 'text-danger' : 'text-success') }}">
                                $ {{ number_format($item->total, 2) }}
                            </span>
                        </td>
                        <td class="text-center pe-3">
                            <button wire:click.prevent='removeItem({{ $item->id }})' type="button"
                                class="btn btn-sm btn-icon btn-label-danger rounded-circle">
                                <i class="fa-solid fa-xmark" style="font-size: 11px;"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="8" class="text-end pe-3 small">Total diferencia:</td>
                    <td colspan="3" class="pe-3">
                        $ {{ number_format($totalConteoProducto, 2) }}
                        <span class="small ms-1">/ C/IVA $ {{ number_format($totalConteoProducto * 1.13, 2) }}</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @include('livewire.hoja_inventarios.detalle-precios')
</div>

@include('common.notis')
@include('livewire.hoja_inventarios.detalle')
@include('livewire.hoja_inventarios.scripts.shortcuts')
@include('livewire.hoja_inventarios.scripts.navigation')

<script>
    document.addEventListener('livewire:load', function () {

        // Enfocar #code al cargar la página
        const barcode = document.getElementById('code');
        if (barcode) barcode.focus();

        // Después de guardar conteo → volver a #code
        Livewire.on('focus-barcode', () => {
            const b = document.getElementById('code');
            if (b) { b.value = ''; b.focus(); }
        }); 

        // Después de Add() → enfocar el input de conteo del producto agregado
        // 400ms para ganar sobre Bootstrap que restaura foco al cerrar modal (~300ms)
        Livewire.on('focus-conteo', (id) => {
            setTimeout(() => {
                const input = document.getElementById('can-' + id);
                if (input) {
                    input.focus();
                    input.select();
                    document.querySelectorAll('tbody tr').forEach(r => r.classList.remove('tr-focus'));
                    const tr = input.closest('tr');
                    if (tr) tr.classList.add('tr-focus');
                }
            }, 400);
        });

        // Resaltar fila al enfocar cualquier input de conteo
        document.addEventListener('focusin', function (event) {
            if (event.target.matches('input[id^="can-"]')) {
                document.querySelectorAll('tbody tr').forEach(r => r.classList.remove('tr-focus'));
                const tr = event.target.closest('tr');
                if (tr) tr.classList.add('tr-focus');
            }
        });

    });
</script>

<style>
    input[id^="can-"]:focus { background-color: #4359710f !important; }
    .tr-focus { background-color: #4359710f; }
</style>
