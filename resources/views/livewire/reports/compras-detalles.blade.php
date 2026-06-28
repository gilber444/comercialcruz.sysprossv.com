<div wire:ignore.self class="modal fade" id="modalDetailsCompras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#233446,#2d4a63);">
                <div class="d-flex align-items-center gap-2">
                    <div style="background:rgba(255,255,255,.15); border-radius:8px; width:34px; height:34px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px;">
                        <i class="fa-solid fa-cart-flatbed"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="color:#fff; font-size:14px;">
                            Detalle de Compra
                            <span style="background:rgba(255,255,255,.2); border-radius:12px; padding:1px 10px; font-size:12px; font-weight:600; margin-left:6px;">
                                # {{ $ventaId }}
                            </span>
                        </h5>
                        <div style="color:rgba(255,255,255,.7); font-size:11px; margin-top:2px;">
                            {{ $countDetails }} items &nbsp;·&nbsp; Total $ {{ number_format($sumDetails, 2) }}
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div wire:loading wire:target="getDetails" class="text-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm me-2"></span> Cargando detalle...
                </div>
                <div wire:loading.remove wire:target="getDetails">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                            <thead>
                                <tr style="background:#233446; color:#fff;">
                                    <th class="text-center ps-3" style="width:40px;">#</th>
                                    <th class="text-center" style="width:130px;">Código</th>
                                    <th>Producto</th>
                                    <th class="text-end" style="width:100px;">Costo unit.</th>
                                    <th class="text-center" style="width:80px;">Cantidad</th>
                                    <th class="text-end pe-3" style="width:110px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($details as $i => $p)
                                <tr>
                                    <td class="text-center ps-3 text-muted small">{{ $i + 1 }}</td>
                                    <td class="text-center">
                                        <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:#233446;">
                                            {{ $p->codebar3 ?? '—' }}
                                        </code>
                                    </td>
                                    <td class="fw-semibold small">{{ $p->nombreProducto }}</td>
                                    <td class="text-end small">$ {{ number_format($p->precio, 4) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-primary rounded-pill">{{ $p->cantidad }}</span>
                                    </td>
                                    <td class="text-end pe-3 fw-semibold">$ {{ number_format($p->total, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Sin detalles.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background:#233446; color:#fff; font-weight:700; font-size:.8rem;">
                                    <td colspan="4" class="text-end ps-3 pe-3" style="letter-spacing:.4px;">TOTALES</td>
                                    <td class="text-center">{{ number_format($countDetails, 2) }}</td>
                                    <td class="text-end pe-3">$ {{ number_format($sumDetails, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-sm btn-label-secondary rounded-pill px-3" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
