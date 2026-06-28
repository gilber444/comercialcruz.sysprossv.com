<div class="table-responsibe">
    @include('common.searchbox')
    <table class="table table-hover table-sm">
        <thead>
            <th class="text-center">#</th>
            <th class="text-center">Factura</th>
            <th class="text-center">Proveedor</th>
            <th class="text-center">Fecha</th>
            <th class="text-center">Monto</th>
            <th class="text-center">Saldo</th>
            <th class="text-center"></th>
        </thead>
        <tbody>
            @forelse ($comprasCredito as $cc )
            <tr>
                <td class="text-center">{{ $cc->correlativo }}</td>
                <td class="text-center">{{ $cc->Rtipo->tipo }}</td>
                <td class="text-center">{{ $cc->Rproveedores->nombre }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($cc->fecha)->format('d/m/Y') }}</td>
                <td class="text-end"> $ {{ number_format($cc->rdetalles_sum_total, 2) }}</td>
                <td class="text-center">Pagar</td>
            </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center"><h5>No hay Compras al Credito</h5></td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $comprasCredito->links() }}
</div>
