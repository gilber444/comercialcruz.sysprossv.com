<div class="row p-1">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">CODIGO</th>
                <th class="text-center">DESCRIPCION</th>
                <th class="text-center">MEDIDA</th>
                <th class="text-center">CANTIDAD</th>
                <th class="text-right">COSTO</th>
                <th class="text-center">TOTAL</th>
            </thead>
            <tbody style="font-size: 13px">

                @foreach ( $detalles as $item)
                <tr @if ($item->status == 'Pendiente') class="bg-label-danger" @endif>
                    <td class="text-center">{{ $item->codigo}}</td>
                    <td>
                        <a href="#" wire:click='Validar({{ $item->id }})'>
                            {{ $item->descripcion }}
                        </a>
                    </td>
                    <td>
                        <?php
                        $uni = DB::table('unidad_medidas')->where('codigo', $item->uniMedida)->first();
                        ?>
                        {{ $uni->valor }}
                    </td>
                    <td class="text-center">{{ $item->cantidad }}</td>
                    <td class="text-end">$ {{ number_format($item->precioUni, 2) }}</td>
                    <td class="text-end">$ {{ number_format($item->ventaGravada ,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
