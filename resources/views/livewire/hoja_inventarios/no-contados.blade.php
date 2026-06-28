<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos no contados — Apertura #{{ $apertura->id }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand:    #233446;
            --brand-lt: #2d4a63;
            --accent:   #4a90d9;
            --warning:  #fd7e14;
            --danger:   #dc3545;
            --success:  #28a745;
            --light-bg: #f8f9fb;
            --border:   #dee2e6;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; font-size: 13px; background: var(--light-bg); color: #2c3e50; margin: 0; }

        /* HEADER */
        .doc-header { background: linear-gradient(135deg, var(--brand) 0%, var(--brand-lt) 100%); color: #fff; padding: 18px 28px; display: flex; align-items: center; gap: 20px; }
        .doc-header .logo-wrap { background: #fff; border-radius: 8px; padding: 6px 10px; flex-shrink: 0; }
        .doc-header .logo-wrap img { height: 52px; width: auto; object-fit: contain; display: block; }
        .doc-header .company-name { font-size: 17px; font-weight: 700; letter-spacing: .3px; }
        .doc-header .company-sub  { font-size: 11px; opacity: .85; line-height: 1.6; margin-top: 2px; }
        .doc-header .doc-title-block { text-align: right; flex-shrink: 0; margin-left: auto; }
        .doc-header .doc-title { font-size: 15px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .doc-header .doc-meta  { font-size: 11px; opacity: .85; line-height: 1.7; margin-top: 3px; }
        .badge-doc { display: inline-block; background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.35); border-radius: 20px; padding: 2px 12px; font-size: 12px; font-weight: 600; margin-top: 4px; }

        /* KPIs */
        .kpi-row { display: flex; gap: 16px; padding: 16px 28px 0; }
        .kpi-card { flex: 1; background: #fff; border-radius: 10px; padding: 14px 18px; border: 1px solid var(--border); box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .kpi-label { font-size: 11px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .kpi-value { font-size: 22px; font-weight: 700; color: var(--brand); margin-top: 4px; }
        .kpi-sub   { font-size: 11px; color: #6c757d; margin-top: 2px; }
        .kpi-icon  { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; float: right; margin-top: -4px; }

        /* TOOLBAR */
        .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 28px; gap: 12px; }
        .search-wrap { position: relative; }
        .search-wrap input { border: 1px solid var(--border); border-radius: 6px; padding: 6px 12px 6px 34px; font-size: 13px; outline: none; width: 260px; transition: border-color .2s; }
        .search-wrap input:focus { border-color: var(--accent); }
        .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; }
        .btn-brand { background: var(--brand); color: #fff; border: none; }
        .btn-brand:hover { background: var(--brand-lt); color: #fff; }

        /* TABLE */
        .table-wrap { padding: 0 28px 20px; }
        .inv-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.07); font-size: 12.5px; }
        .inv-table thead th { background: var(--brand); color: #fff; padding: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; border: none; }
        .inv-table tbody tr { border-bottom: 1px solid #f0f2f5; }
        .inv-table tbody tr:hover { background: #f0f4f8; }
        .inv-table tbody td { padding: 8px 10px; vertical-align: middle; }
        .inv-table tfoot td { background: var(--brand); color: #fff; font-weight: 700; padding: 10px; font-size: 13px; }
        .row-num { color: #adb5bd; font-size: 11px; width: 28px; text-align: center; }

        /* risk badge */
        .risk-high { color: var(--danger); font-weight: 700; }
        .risk-zero { color: #6c757d; }

        /* FOOTER */
        .doc-footer { background: var(--brand); color: rgba(255,255,255,.6); text-align: center; padding: 10px; font-size: 11px; }

        /* PRINT */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; font-size: 11px; }
            .doc-header, .kpi-row, .toolbar, .table-wrap { padding-left: 16px; padding-right: 16px; }
            .kpi-card, .inv-table { box-shadow: none; border: 1px solid #ccc; }
            .inv-table thead th, .inv-table tfoot td, .doc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 10mm; size: A4 portrait; }
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="doc-header">
    @if($empresa->image)
    <div class="logo-wrap">
        <img src="{{ asset('logo/' . $empresa->image) }}" alt="Logo">
    </div>
    @endif
    <div>
        <div class="company-name">{{ $empresa->empresa }}</div>
        <div class="company-sub">
            NIT: {{ $empresa->nit }} &nbsp;|&nbsp; {{ $empresa->direccion }}<br>
            Tel. {{ $empresa->telefono }}
        </div>
    </div>
    <div class="doc-title-block">
        <div class="doc-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Productos No Contados</div>
        <div class="doc-meta">
            Sucursal: <strong>{{ $sucursal->nombre ?? '—' }}</strong><br>
            Apertura: {{ \Carbon\Carbon::parse($apertura->fecha_apertura)->format('d/m/Y') }}
            @if($apertura->fecha_cierre)
                &nbsp;→&nbsp; Cierre: {{ \Carbon\Carbon::parse($apertura->fecha_cierre)->format('d/m/Y') }}
            @endif
        </div>
        <div class="badge-doc">Apertura #{{ $apertura->id }}</div>
    </div>
</div>

{{-- KPIs --}}
@php
    $total       = $productos->count();
    $conStock    = $productos->where('existencia', '>', 0)->count();
    $sinStock    = $productos->where('existencia', '<=', 0)->count();
    $valorRiesgo = $productos->sum('existencia');
@endphp
<div class="kpi-row no-print">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fff3e0; color:var(--warning);"><i class="fa-solid fa-box-open"></i></div>
        <div class="kpi-label">Total no contados</div>
        <div class="kpi-value" style="color:var(--warning);">{{ number_format($total) }}</div>
        <div class="kpi-sub">productos sin conteo</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fdecea; color:var(--danger);"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="kpi-label">Con existencia en sistema</div>
        <div class="kpi-value" style="color:var(--danger);">{{ number_format($conStock) }}</div>
        <div class="kpi-sub">requieren atención</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#f0f2f5; color:#6c757d;"><i class="fa-solid fa-box-archive"></i></div>
        <div class="kpi-label">Con existencia 0</div>
        <div class="kpi-value" style="color:#6c757d;">{{ number_format($sinStock) }}</div>
        <div class="kpi-sub">sin impacto económico</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fdecea; color:var(--danger);"><i class="fa-solid fa-layer-group"></i></div>
        <div class="kpi-label">Existencia total</div>
        <div class="kpi-value" style="color:var(--danger);">{{ number_format($valorRiesgo, 0) }}</div>
        <div class="kpi-sub">unidades sin verificar</div>
    </div>
</div>

{{-- TOOLBAR --}}
<div class="toolbar no-print">
    <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscador" placeholder="Buscar producto, código...">
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="text-muted small me-2"><i class="fa-solid fa-list me-1"></i>{{ $total }} productos</span>
        <a href="{{ route('hoja_inventarios.noContadosPdf', $apertura->id) }}"
           class="btn btn-sm btn-brand rounded-pill px-3" target="_blank">
            <i class="fa-solid fa-file-pdf me-1"></i> PDF
        </a>
        <a href="{{ route('hoja_inventarios.noContadosExcel', $apertura->id) }}"
           class="btn btn-sm btn-success rounded-pill px-3">
            <i class="fa-solid fa-file-excel me-1"></i> Excel
        </a>
        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Imprimir
        </button>
    </div>
</div>

{{-- CONTENIDO --}}
<div class="table-wrap">
    @if($productos->isEmpty())
        <div style="background:#fff; border-radius:10px; padding:40px; text-align:center; box-shadow:0 1px 6px rgba(0,0,0,.07);">
            <div style="font-size:48px; color:var(--success); margin-bottom:12px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div style="font-size:18px; font-weight:700; color:var(--success);">¡Inventario completo!</div>
            <div style="color:#6c757d; margin-top:6px;">Todos los productos han sido contados en esta apertura.</div>
        </div>
    @else
        <table class="inv-table" id="tablaInventario">
            <thead>
                <tr>
                    <th class="text-center row-num">#</th>
                    <th class="text-center" style="width:150px;">Código</th>
                    <th>Descripción</th>
                    <th class="text-end" style="width:160px;">Existencia sistema</th>
                    <th class="text-center no-print" style="width:100px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($productos as $i => $p)
                <tr class="inv-row"
                    data-nombre="{{ strtolower($p->nombre) }}"
                    data-codebar="{{ strtolower($p->codebar ?? '') }}"
                    style="{{ $p->existencia > 0 ? 'background:#fff8f8;' : '' }}">
                    <td class="row-num text-center">{{ $i + 1 }}</td>
                    <td class="text-center">
                        <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:var(--brand);">
                            {{ $p->codebar ?? '—' }}
                        </code>
                    </td>
                    <td style="font-weight:500;">{{ $p->nombre }}</td>
                    <td class="text-end {{ $p->existencia > 0 ? 'risk-high' : 'risk-zero' }}">
                        @if($p->existencia > 0)
                            <i class="fa-solid fa-circle-exclamation me-1" style="font-size:11px;"></i>
                        @endif
                        {{ number_format($p->existencia, 2) }}
                    </td>
                    <td class="text-center no-print">
                        @if($p->existencia > 0)
                            <span style="background:#fdecea; color:var(--danger); border-radius:12px; padding:2px 10px; font-size:11px; font-weight:600;">
                                Riesgo
                            </span>
                        @else
                            <span style="background:#f0f2f5; color:#6c757d; border-radius:12px; padding:2px 10px; font-size:11px; font-weight:600;">
                                Sin stock
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end" style="font-size:12px; letter-spacing:.5px;">TOTAL PRODUCTOS NO CONTADOS</td>
                    <td class="text-end">{{ $total }}</td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>

{{-- FOOTER --}}
<div class="doc-footer">
    Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Sistema SuperTienda Robert
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('buscador')?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('#tablaInventario .inv-row').forEach(row => {
            const match = row.dataset.nombre.includes(q) || row.dataset.codebar.includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
</script>
</body>
</html>
