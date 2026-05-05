<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle}} </b></h5>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Código de barras</th>
                <th class="text-center">Nombre del producto</th>
                <th class="text-center">Sucursal</th>
                <th class="text-center">Desde</th>
                <th class="text-center">Hasta</th>
                <th class="text-center"></th>
            </thead>
            <tbody>
                @foreach ( $data as $dat )
                <tr>
                    <td class="text-center"><b>{{ $dat->codebar3 }}</b></td>
                    <td>{{ $dat->nombreProducto }}</td>
                    <td class="text-center">
                        <select class="form-select" wire:model="sucursal.{{ $dat->id }}">
                            <option value="">Elegir</option>
                            @if(in_array(Auth::user()->profile, ['Super', 'Administrador', 'Gerente']))
                                <option value="0">Todos</option>
                            @endif
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                        @error('sucursal.' . $dat->id)
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </td>
                    <td class="text-center">
                        <input type="date" wire:model.lazy='fechaDesde.{{ $dat->id }}' class="form-control">
                    </td>
                    <td class="text-center">
                        <input type="date" wire:model.lazy='fechaHasta.{{ $dat->id }}' class="form-control">
                    </td>
                    <td class="text-center">
                        <button class="btn btn-primary" wire:click='Generar({{ $dat->id }})' > <i class="fa-solid fa-download"></i> Generar</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{$data->links()}}
    @include('livewire.kardex.report')
    @include('common.notis')
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/TableExport/5.2.0/js/tableexport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>


<script>
    function imprimirDiv() {
        var contenido = document.getElementById("imprimir").innerHTML;
        var ventana = window.open('', '', 'width=1200,height=720');
        ventana.document.write('<html><head><title>Reporte</title>');
        ventana.document.write('<link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}">');
        ventana.document.write('</head><body>');
        ventana.document.write(contenido);
        ventana.document.write('</body></html>');
        ventana.document.close();
        ventana.focus();
        ventana.print();
    }

    function exportToExcel(tableId, filename = '') {
        const sheetName = filename || 'Exported Table';
        const ws = XLSX.utils.table_to_sheet(document.querySelector(tableId));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        XLSX.writeFile(wb, `${sheetName}.xlsx`);
    }
</script>
