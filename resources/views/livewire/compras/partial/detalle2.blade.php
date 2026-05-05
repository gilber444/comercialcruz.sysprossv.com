<div class="row p-1">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">CODIGO</th>
                <th class="text-center">DESCRIPCION</th>
                <th class="text-center">MEDIDA</th>
                <th class="text-right">P.U.</th>
                <th class="text-center">CANTIDAD</th>
                <th class="text-center">FECHA VEN.</th>
                <th class="text-center">TOTAL</th>
                <th class="text-center"></th>
            </thead>
            <tbody>

                @forelse ( $car as $item)
                <tr>
                    <td class="text-center"><h6>{{$item->codebar}}</h6> </td>
                    <td>{{ $item->nombreProducto}}</td>
                    <td class="text-center">{{ $item->unidad}}</td>
                    <td class="text-end">$ {{ number_format($item->costo, 2)}}</td>
                    <td class="text-center">{{ $item->cantidad}}</td>
                    <td class="text-center">{{ $item->fechaVencimiento}}</td>
                    <td class="text-end">$ {{ number_format($item->total, 2) }}</td>
                    <td class="text-center">
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="Confirm2('{{$item->id}}')"><i class="bx bx-trash"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">SIN RESULTADOS</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
