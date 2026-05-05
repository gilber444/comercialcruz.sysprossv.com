<div wire:ignore.self class="modal fade" id="myModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModal">Reporte de Kardex
                </h5>
                <h6 class="text-center text-warning" wire:loading> POR FAVOR ESPERE</h6>
            </div>
            <div class="modal-body" id="imprimir">
                <div class="row">
                    <div class="col text-center">
                        <h6>Sucursal: {{ $suc }}</h6>
                        <h6>Kardex del producto: {{ $product }}</h6>
                        <h6>Desde {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} </h6>
                        <h6>hasta {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</h6>

                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col">
                        <div class="table-responsible">
                            <table class="table table-sm" id="tabla">
                                <thead>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Descripcion</th>
                                    <th class="text-center">Entrada</th>
                                    <th class="text-center">Salida</th>
                                    <th class="text-center">Saldo</th>
                                </thead>
                                <tbody>
                                    @foreach ($inicial as $ini)
                                        <tr>
                                            <td class="text-center">{{ $ini->fecha }}</td>
                                            <td>Saldo Anterior</td>
                                            <td class="text-center">{{ number_format($ini->ingresoCantidad, 2) }}</td>
                                            <td class="text-center">{{ number_format($ini->egresoCantidad, 2) }}</td>
                                            <td class="text-center">{{ number_format($ini->saldoCantidad, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($kardes as $item)
                                        <tr>
                                            <td class="text-center">{{ $item->fecha }}</td>
                                            <td>{{ $item->descripcion }}</td>
                                            <td class="text-center">{{ number_format($item->ingresoCantidad, 2) }}</td>
                                            <td class="text-center">{{ number_format($item->egresoCantidad, 2) }}</td>
                                            <td class="text-center">{{ number_format($item->saldoCantidad, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex gap-2">
                <button onclick="exportToExcel('#imprimir', 'Reporte de Kardex')" class="btn btn-primary">
                    <i class="fa-solid fa-file-export"></i> Exportar a Excel
                </button>

                <a href="{{ url(
                    'kardex/pdf/' . $sucur . '/' . $prod . (isset($desde) ? '/' . $desde : '') . (isset($hasta) ? '/' . $hasta : ''),
                ) }}"
                    class="btn btn-primary" target="_blank">
                    <i class="fa-solid fa-file-pdf"></i> Generar Pdf
                </a>



                {{-- Botón para cerrar --}}
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
