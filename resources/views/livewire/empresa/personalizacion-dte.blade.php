<div>
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1">Personalizacion de documentos DTE</h5>
                <small class="text-muted">Paleta compartida para la representacion grafica del DTE y el evento de invalidacion.</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" wire:click="restablecer">
                    <i class="bx bx-reset"></i> Restablecer
                </button>
                <button type="button" class="btn btn-primary" wire:click="guardar">
                    <i class="bx bx-save"></i> Guardar colores
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(count($empresas) > 1)
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <select class="form-select" wire:model="empresaId">
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->empresa }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="row g-3">
                        @foreach([
                            'colorPrimario' => 'Color principal',
                            'colorEncabezado' => 'Fondo de encabezados',
                            'colorTexto' => 'Texto principal',
                            'colorTextoSuave' => 'Texto secundario',
                            'colorBorde' => 'Bordes y divisiones',
                            'colorExito' => 'Estado procesado',
                            'colorPeligro' => 'Invalidado / alertas',
                            'colorFondo' => 'Fondo del documento',
                        ] as $campo => $etiqueta)
                            <div class="col-sm-6 col-lg-12">
                                <label class="form-label d-flex justify-content-between">
                                    <span>{{ $etiqueta }}</span>
                                    <code>{{ $$campo }}</code>
                                </label>
                                <input type="color" class="form-control form-control-color w-100" style="height:42px;" wire:model="{{ $campo }}">
                                @error($campo)<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-lg-8">
                    <label class="form-label">Vista previa en vivo</label>
                    <div class="p-3 rounded" style="background:#f4f5f7; overflow:auto;">
                        <div style="min-width:680px; background:{{ $colorFondo }}; color:{{ $colorTexto }}; border:1px solid {{ $colorBorde }}; padding:14px; font-family:Arial,sans-serif; font-size:10px;">
                            <div style="background:{{ $colorEncabezado }}; border:1px solid {{ $colorBorde }}; border-radius:10px; padding:12px; display:flex; align-items:center;">
                                <div style="width:20%; text-align:center; background:{{ $colorFondo }}; border:1px solid {{ $colorBorde }}; border-radius:8px; padding:12px 4px; font-weight:bold; color:{{ $colorPrimario }};">{{ optional($empresaSeleccionada)->empresa ?: 'MI EMPRESA' }}</div>
                                <div style="width:60%; text-align:center;">
                                    <div style="color:{{ $colorTextoSuave }}; letter-spacing:2px; font-size:8px;">DOCUMENTO TRIBUTARIO ELECTRONICO</div>
                                    <div style="font-size:18px; font-weight:bold; margin:4px 0;">Comprobante de Credito Fiscal</div>
                                    <span style="border:1px solid {{ $colorBorde }}; border-radius:10px; padding:2px 8px; color:{{ $colorPrimario }}; background:{{ $colorFondo }};">MODELO FACTURACION PREVIO</span>
                                </div>
                                <div style="width:20%; text-align:center;">
                                    <div style="background:{{ $colorFondo }}; border:5px solid {{ $colorTexto }}; width:54px; height:54px; margin:auto; padding:3px; box-sizing:border-box;">
                                        <div style="border:3px dotted {{ $colorTexto }}; width:100%; height:100%; box-sizing:border-box;"></div>
                                    </div>
                                    <div style="color:{{ $colorTextoSuave }}; margin-top:4px;">Escanea para verificar</div>
                                </div>
                            </div>

                            <div style="display:flex; gap:16px; margin:12px 0; color:{{ $colorPrimario }}; font-weight:bold;">
                                <div style="width:45%;">CODIGO DE GENERACION<br><span style="color:{{ $colorTexto }}; font-weight:normal;">EA1A...C4550</span></div>
                                <div style="width:30%;">SELLO DE RECEPCION</div>
                                <div style="width:25%;">TRANSMISION NORMAL</div>
                            </div>

                            <div style="display:flex; gap:14px;">
                                @foreach(['EMISOR', 'RECEPTOR'] as $titulo)
                                    <div style="width:50%; border:1px solid {{ $colorBorde }}; border-radius:9px; overflow:hidden;">
                                        <div style="background:{{ $colorEncabezado }}; color:{{ $colorPrimario }}; border-bottom:1px solid {{ $colorBorde }}; padding:6px 9px; font-weight:bold; letter-spacing:1px;">{{ $titulo }}</div>
                                        <div style="padding:9px; line-height:1.8;">
                                            <span style="color:{{ $colorTextoSuave }};">Razon social</span> &nbsp; <b>EMPRESA DE EJEMPLO</b><br>
                                            <span style="color:{{ $colorTextoSuave }};">NIT</span> &nbsp; <b>0614-000000-000-0</b><br>
                                            <span style="color:{{ $colorTextoSuave }};">Direccion</span> &nbsp; DIRECCION DEL DOCUMENTO
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div style="color:{{ $colorPrimario }}; font-weight:bold; margin:12px 0 5px;">DETALLE</div>
                            <div style="border:1px solid {{ $colorBorde }};">
                                <div style="background:{{ $colorEncabezado }}; color:{{ $colorPrimario }}; padding:7px; font-weight:bold;">N° &nbsp;&nbsp; Codigo &nbsp;&nbsp; Cant. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Descripcion &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Total</div>
                                <div style="padding:8px;">1 &nbsp;&nbsp;&nbsp; ITEM-01 &nbsp;&nbsp; 1 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Producto o servicio de ejemplo &nbsp;&nbsp;&nbsp; $ 100.00</div>
                            </div>

                            <div style="display:flex; justify-content:flex-end; margin-top:12px; gap:6px;">
                                <span style="background:{{ $colorExito }}; color:#fff; border-radius:10px; padding:3px 9px;">PROCESADO</span>
                                <span style="background:{{ $colorPeligro }}; color:#fff; border-radius:10px; padding:3px 9px;">INVALIDADO</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('common.notis')
