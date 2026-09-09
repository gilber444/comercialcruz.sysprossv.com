<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Descargar documentos DTE</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="fromDate">Desde:</label>
                <input type="date" id="fromDate" wire:model="fromDate" class="form-control" @if($procesando) disabled @endif>
                @error('fromDate') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="toDate">Hasta:</label>
                <input type="date" id="toDate" wire:model="toDate" class="form-control" @if($procesando) disabled @endif>
                @error('toDate') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="tipoDte">Tipo de documento:</label>
                <select id="tipoDte" wire:model="tipoDte" class="form-control" @if($procesando) disabled @endif>
                    <option value="todos">Todos los documentos</option>
                    <option value="01">Factura / consumidor final</option>
                    <option value="03">Comprobante de credito fiscal</option>
                    <option value="05">Nota de credito</option>
                    <option value="14">Sujeto excluido</option>
                </select>
                @error('tipoDte') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-sm-12 col-md-3 mb-3">
                <label for="formato">Archivos a descargar:</label>
                <select id="formato" wire:model="formato" class="form-control" @if($procesando) disabled @endif>
                    <option value="json">Solo JSON</option>
                    <option value="pdf">Solo PDF</option>
                    <option value="ambos">JSON y PDF</option>
                </select>
                @error('formato') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>

        <button type="button" class="btn btn-primary" wire:click="Procesar"
                wire:loading.attr="disabled" wire:target="Procesar" @if($procesando) disabled @endif>
            @if($procesando)
                <span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>
                Descargando... {{ $progreso }}%
            @else
                <span wire:loading.remove wire:target="Procesar"><i class="fa-solid fa-download"></i> Preparar ZIP</span>
                <span wire:loading wire:target="Procesar">
                    <span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>
                    Preparando...
                </span>
            @endif
        </button>

        @if($procesando || $progreso === 100)
            <div class="mt-3" aria-live="polite">
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ $procesando ? 'Generando archivos...' : 'Descarga preparada' }}</span>
                    <span>{{ $procesados }} de {{ $total }} ({{ $progreso }}%)</span>
                </div>
                <div class="progress" style="height: 24px;">
                    <div class="progress-bar progress-bar-striped {{ $procesando ? 'progress-bar-animated' : 'bg-success' }}"
                         role="progressbar" style="width: {{ $progreso }}%;"
                         aria-valuenow="{{ $progreso }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $progreso }}%
                    </div>
                </div>
                @if($errores > 0)
                    <small class="text-warning">{{ $errores }} documento(s) no pudieron generarse. El detalle se incluye en ERRORES.txt.</small>
                @endif
            </div>
        @endif

        <p class="text-muted mt-3 mb-0">
            Los PDF se generan uno por uno con el formato vigente, por lo que una descarga grande puede tardar varios minutos. Puede dejar esta pantalla abierta mientras avanza el porcentaje.
        </p>
    </div>

    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('procesar-lote-descarga', function () {
                window.setTimeout(function () {
                    @this.call('procesarLoteDescarga');
                }, 100);
            });

            Livewire.on('descarga-dte-lista', function (url) {
                window.location.href = url;
            });
        });
    </script>
</div>

@include('common.notis')
