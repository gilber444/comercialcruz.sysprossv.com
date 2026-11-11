{{-- Pie fijo con numero de pagina (se repite en todas las hojas). --}}
<div class="lb-pie">
    <table>
        <tr>
            <td>{{ $empresa->empresa }} &mdash; {{ $titulo }} &mdash; generado el {{ now()->format('d/m/Y H:i') }}</td>
            <td style="text-align:right;">Pagina <span class="pagina"></span></td>
        </tr>
    </table>
</div>
