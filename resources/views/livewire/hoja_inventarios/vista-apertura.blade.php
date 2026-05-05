<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Consolidado — Apertura #{{ $apertura->id }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand:     #233446;
            --brand-lt:  #2d4a63;
            --accent:    #4a90d9;
            --success:   #28a745;
            --danger:    #dc3545;
            --light-bg:  #f8f9fb;
            --border:    #dee2e6;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            background: var(--light-bg);
            color: #2c3e50;
            margin: 0;
        }

        /* ── HEADER ── */
        .doc-header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-lt) 100%);
            color: #fff;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .doc-header .logo-wrap {
            background: #fff;
            border-radius: 8px;
            padding: 6px 10px;
            flex-shrink: 0;
        }
        .doc-header .logo-wrap img { height: 52px; width: auto; object-fit: contain; display: block; }
        .doc-header .company-info { flex: 1; }
        .doc-header .company-name { font-size: 17px; font-weight: 700; letter-spacing: .3px; }
        .doc-header .company-sub  { font-size: 11px; opacity: .85; line-height: 1.6; margin-top: 2px; }
        .doc-header .doc-title-block { text-align: right; flex-shrink: 0; }
        .doc-header .doc-title { font-size: 15px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .doc-header .doc-meta  { font-size: 11px; opacity: .85; line-height: 1.7; margin-top: 3px; }
        .doc-header .badge-apertura {
            display: inline-block;
            background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.35);
            border-radius: 20px;
            padding: 2px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }

        /* ── KPIs ── */
        .kpi-row {
            display: flex;
            gap: 16px;
            padding: 16px 28px 0;
        }
        .kpi-card {
            flex: 1;
            background: #fff;
            border-radius: 10px;
            padding: 14px 18px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .kpi-card .kpi-label { font-size: 11px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .kpi-card .kpi-value { font-size: 20px; font-weight: 700; color: var(--brand); margin-top: 4px; }
        .kpi-card .kpi-sub   { font-size: 11px; color: #6c757d; margin-top: 2px; }
        .kpi-card.kpi-danger .kpi-value  { color: var(--danger); }
        .kpi-card.kpi-success .kpi-value { color: var(--success); }
        .kpi-card .kpi-icon {
            width: 38px; height: 38px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            float: right;
            margin-top: -4px;
        }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            gap: 12px;
        }
        .toolbar .search-wrap { position: relative; }
        .toolbar .search-wrap input {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 12px 6px 34px;
            font-size: 13px;
            outline: none;
            width: 260px;
            transition: border-color .2s;
        }
        .toolbar .search-wrap input:focus { border-color: var(--accent); }
        .toolbar .search-wrap i {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
            color: #aaa; font-size: 13px;
        }
        .btn-brand { background: var(--brand); color: #fff; border: none; }
        .btn-brand:hover { background: var(--brand-lt); color: #fff; }

        /* ── TABLE ── */
        .table-wrap {
            padding: 0 28px 20px;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(0,0,0,.07);
            font-size: 12.5px;
        }
        .inv-table thead th {
            background: var(--brand);
            color: #fff;
            padding: 10px 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
            border: none;
        }
        .inv-table tbody tr { border-bottom: 1px solid #f0f2f5; }
        .inv-table tbody tr:hover { background: #f0f4f8; }
        .inv-table tbody td { padding: 8px 10px; vertical-align: middle; }
        .inv-table tfoot td {
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            padding: 10px 10px;
            font-size: 13px;
        }

        /* diff badges */
        .diff-pos { color: var(--success); font-weight: 700; }
        .diff-neg { color: var(--danger);  font-weight: 700; }
        .diff-zero{ color: #6c757d; }

        /* row number */
        .row-num { color: #adb5bd; font-size: 11px; width: 28px; text-align: center; }

        /* breakdown popover trigger */
        .bd-trigger {
            cursor: pointer;
            border-bottom: 1.5px dashed var(--brand);
            padding-bottom: 1px;
            font-weight: 600;
        }

        /* ── RESUMEN ── */
        .resumen-row { padding: 0 28px 28px; display: flex; gap: 16px; }
        .resumen-card {
            flex: 1;
            background: #fff;
            border-radius: 10px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .resumen-card .rc-header {
            background: var(--brand);
            color: #fff;
            padding: 10px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .resumen-card .rc-body { padding: 14px 16px; }
        .resumen-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f2f5;
            font-size: 13px;
        }
        .resumen-line:last-child { border-bottom: none; }
        .resumen-line .rl-label { color: #6c757d; }
        .resumen-line .rl-value { font-weight: 700; text-align: right; }
        .resumen-line .rl-sub   { font-size: 11px; color: #adb5bd; }
        .resumen-line.rl-total  { background: var(--light-bg); border-radius: 6px; padding: 10px 12px; margin-top: 8px; }
        .rl-danger  { color: var(--danger) !important; }
        .rl-success { color: var(--success) !important; }

        /* ── FOOTER ── */
        .doc-footer {
            background: var(--brand);
            color: rgba(255,255,255,.6);
            text-align: center;
            padding: 10px;
            font-size: 11px;
        }

        /* ── PRINT ── */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; font-size: 11px; }
            .doc-header { padding: 12px 16px; }
            .kpi-row, .toolbar { padding-left: 16px; padding-right: 16px; }
            .table-wrap, .resumen-row { padding-left: 16px; padding-right: 16px; }
            .kpi-card, .resumen-card, .inv-table { box-shadow: none; border: 1px solid #ccc; }
            .inv-table thead th,
            .inv-table tfoot td,
            .doc-header,
            .resumen-card .rc-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { margin: 10mm; size: A4 landscape; }
        }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="doc-header">
    @if($empresa->image)
    <div class="logo-wrap">
        <img src="{{ asset('logo/' . $empresa->image) }}" alt="Logo">
    </div>
    @endif
    <div class="company-info">
        <div class="company-name">{{ $empresa->empresa }}</div>
        <div class="company-sub">
            NIT: {{ $empresa->nit }} &nbsp;|&nbsp; NCR: {{ $empresa->registro }}<br>
            {{ $empresa->direccion }} &nbsp;|&nbsp; Tel. {{ $empresa->telefono }} &nbsp;|&nbsp; {{ $empresa->correo }}
        </div>
    </div>
    <div class="doc-title-block">
        <div class="doc-title"><i class="fa-solid fa-boxes-stacking me-2"></i>Inventario Consolidado</div>
        <div class="doc-meta">
            Sucursal: <strong>{{ $sucursal->nombre ?? '—' }}</strong><br>
            Apertura: {{ \Carbon\Carbon::parse($apertura->fecha_apertura)->format('d/m/Y') }}
            @if($apertura->fecha_cierre)
                &nbsp;→&nbsp; Cierre: {{ \Carbon\Carbon::parse($apertura->fecha_cierre)->format('d/m/Y') }}
            @endif
        </div>
        <div class="badge-apertura">Apertura #{{ $apertura->id }}</div>
    </div>
</div>

{{-- ── KPIs ── --}}
@php
    $totalProductos    = $hojasDetalle->count();
    $totalFaltantes    = $hojasDetalle->where('diferencia', '<', 0)->count();
    $totalSobrantes    = $hojasDetalle->where('diferencia', '>', 0)->count();
    $totalExactos      = $hojasDetalle->where('diferencia', 0)->count();
    $difPositiva       = $hojasDetalle->where('diferencia', '>', 0)->sum(fn($h) => $h->diferencia * $h->costo);
    $difNegativa       = $hojasDetalle->where('diferencia', '<', 0)->sum(fn($h) => $h->diferencia * $h->costo);
@endphp
<div class="kpi-row no-print">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#e8edf2; color:var(--brand);"><i class="fa-solid fa-cubes-stacked"></i></div>
        <div class="kpi-label">Productos</div>
        <div class="kpi-value">{{ number_format($totalProductos) }}</div>
        <div class="kpi-sub">en este inventario</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#e8f4fd; color:#0d6efd;"><i class="fa-solid fa-warehouse"></i></div>
        <div class="kpi-label">Inventario anterior</div>
        <div class="kpi-value">$ {{ number_format($totalinventarioAnterior, 2) }}</div>
        <div class="kpi-sub">C/IVA $ {{ number_format($totalinventarioAnterior * 1.13, 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#e8f5e9; color:var(--success);"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="kpi-label">Conteo físico</div>
        <div class="kpi-value">$ {{ number_format($totalGeneral, 2) }}</div>
        <div class="kpi-sub">C/IVA $ {{ number_format($totalGeneral * 1.13, 2) }}</div>
    </div>
    <div class="kpi-card {{ $totalDiferencia < 0 ? 'kpi-danger' : 'kpi-success' }}">
        <div class="kpi-icon" style="background:{{ $totalDiferencia < 0 ? '#fdecea' : '#e8f5e9' }}; color:{{ $totalDiferencia < 0 ? 'var(--danger)' : 'var(--success)' }};"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="kpi-label">Diferencia neta</div>
        <div class="kpi-value">$ {{ number_format($totalDiferencia, 2) }}</div>
        <div class="kpi-sub">C/IVA $ {{ number_format($totalDiferencia * 1.13, 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fff3e0; color:#fd7e14;"><i class="fa-solid fa-chart-pie"></i></div>
        <div class="kpi-label">Estado conteo</div>
        <div class="kpi-value" style="font-size:14px; display:flex; gap:10px; margin-top:6px;">
            <span style="color:var(--danger);" title="Faltantes"><i class="fa-solid fa-arrow-down me-1"></i>{{ $totalFaltantes }}</span>
            <span style="color:var(--success);" title="Sobrantes"><i class="fa-solid fa-arrow-up me-1"></i>{{ $totalSobrantes }}</span>
            <span style="color:#6c757d;" title="Exactos"><i class="fa-solid fa-equals me-1"></i>{{ $totalExactos }}</span>
        </div>
        <div class="kpi-sub">faltantes / sobrantes / exactos</div>
    </div>
</div>

{{-- ── TOOLBAR ── --}}
<div class="toolbar no-print">
    <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscador" placeholder="Buscar producto, código...">
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="text-muted small me-2"><i class="fa-solid fa-list me-1"></i>{{ $totalProductos }} productos</span>
        <a href="{{ route('hoja_inventarios.pdfApertura', $apertura->id) }}"
           class="btn btn-sm btn-brand rounded-pill px-3" target="_blank">
            <i class="fa-solid fa-file-pdf me-1"></i> PDF
        </a>
        <a href="{{ route('hoja_inventarios.excelApertura', $apertura->id) }}"
           class="btn btn-sm btn-success rounded-pill px-3">
            <i class="fa-solid fa-file-excel me-1"></i> Excel
        </a>
        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Imprimir
        </button>
    </div>
</div>

{{-- ── TABLA ── --}}
<div class="table-wrap">
    <table class="inv-table" id="tablaInventario">
        <thead>
            <tr>
                <th class="text-center row-num">#</th>
                <th class="text-center">Código</th>
                <th>Descripción</th>
                <th class="text-center">Medida</th>
                <th class="text-center">E. Anterior</th>
                <th class="text-center">Cont. Físico</th>
                <th class="text-center">Diferencia</th>
                <th class="text-end">Dif. $ s/IVA</th>
                <th class="text-end">Dif. $ c/IVA</th>
                <th class="text-end">Costo</th>
                <th class="text-end">Total s/IVA</th>
                <th class="text-end">Total c/IVA</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($hojasDetalle as $i => $h)
            @php
                $difClass  = $h->diferencia < 0 ? 'diff-neg' : ($h->diferencia > 0 ? 'diff-pos' : 'diff-zero');
                $difLabel  = $h->diferencia > 0 ? '+' . $h->diferencia : $h->diferencia;
                $rowBg     = $h->diferencia < 0 ? 'background:#fff8f8;' : ($h->diferencia > 0 ? 'background:#f8fff9;' : '');
            @endphp
            <tr style="{{ $rowBg }}" class="inv-row"
                data-nombre="{{ strtolower($h->nombre) }}"
                data-codebar="{{ strtolower($h->codebar) }}">
                <td class="row-num text-center">{{ $i + 1 }}</td>
                <td class="text-center">
                    <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:11px; color:var(--brand);">
                        {{ $h->codebar }}
                    </code>
                </td>
                <td style="font-weight:500;">{{ $h->nombre }}</td>
                <td class="text-center text-muted">
                    {{ DB::table('medidas')->where('id', $h->medida)->value('unidad') ?? '—' }}
                </td>
                <td class="text-center">{{ number_format($h->cantidadAnterior, 0) }}</td>
                <td class="text-center">
                    @if(count($h->breakdown ?? []) >= 1)
                        @php
                            $filas = collect($h->breakdown)->map(fn($b) =>
                                '<tr>'
                                . '<td style="padding:4px 8px;white-space:nowrap;">'
                                . '<span style="background:var(--brand,#233446);color:#fff;border-radius:4px;padding:2px 7px;font-size:11px;font-weight:600;">' . e($b['correlativo']) . '</span>'
                                . '</td>'
                                . '<td style="padding:4px 12px;text-align:right;font-weight:700;font-size:13px;">' . $b['cantidad'] . '</td>'
                                . '<td style="padding:4px 8px;color:#666;font-size:11px;white-space:nowrap;"><i class="fa-solid fa-user" style="margin-right:4px;opacity:.5;"></i>' . e($b['usuario']) . '</td>'
                                . '</tr>'
                            )->implode('');
                            $totalBd  = collect($h->breakdown)->sum('cantidad');
                            $filaTotal = count($h->breakdown) > 1
                                ? '<tr style="border-top:2px solid #dee2e6;background:#f8f9fb;">'
                                  . '<td style="padding:5px 8px;font-size:11px;color:#888;font-weight:600;">TOTAL</td>'
                                  . '<td style="padding:5px 12px;text-align:right;font-weight:800;color:#233446;font-size:13px;">' . $totalBd . '</td>'
                                  . '<td></td></tr>'
                                : '';
                            $popContent = '<table style="width:100%;border-collapse:collapse;">'
                                . '<thead><tr style="background:#f0f2f5;border-bottom:1px solid #ddd;">'
                                . '<th style="padding:5px 8px;font-size:11px;color:#233446;font-weight:700;">Hoja</th>'
                                . '<th style="padding:5px 12px;font-size:11px;color:#233446;font-weight:700;text-align:right;">Cant.</th>'
                                . '<th style="padding:5px 8px;font-size:11px;color:#233446;font-weight:700;">Contador</th>'
                                . '</tr></thead>'
                                . '<tbody>' . $filas . $filaTotal . '</tbody></table>';
                        @endphp
                        <span class="bd-trigger"
                              data-bs-toggle="popover"
                              data-bs-trigger="click"
                              data-bs-placement="top"
                              data-bs-html="true"
                              title="<i class='fa-solid fa-layer-group me-1'></i> Conteo por hoja"
                              data-bs-content="{{ $popContent }}">
                            {{ number_format($h->cantidadActual, 0) }}
                            <i class="fa-solid fa-layer-group" style="font-size:10px; color:#aaa; margin-left:3px;"></i>
                        </span>
                    @else
                        {{ number_format($h->cantidadActual, 0) }}
                    @endif
                </td>
                <td class="text-center {{ $difClass }}">
                    @if($h->diferencia != 0)
                        <span style="display:inline-flex; align-items:center; gap:3px;">
                            <i class="fa-solid fa-{{ $h->diferencia < 0 ? 'arrow-down' : 'arrow-up' }}" style="font-size:10px;"></i>
                            {{ $difLabel }}
                        </span>
                    @else
                        <span class="diff-zero">—</span>
                    @endif
                </td>
                <td class="text-end {{ $h->diferencia < 0 ? 'text-danger' : ($h->diferencia > 0 ? 'text-success' : 'text-muted') }}">
                    {{ $h->diferencia != 0 ? (($h->diferencia > 0 ? '+' : '') . '$' . number_format($h->diferencia * $h->costo, 2)) : '—' }}
                </td>
                <td class="text-end text-muted" style="font-size:12px;">
                    {{ $h->diferencia != 0 ? (($h->diferencia > 0 ? '+' : '') . '$' . number_format($h->diferencia * $h->costo * 1.13, 2)) : '—' }}
                </td>
                <td class="text-end" style="font-size:12px; color:#6c757d;">$ {{ number_format($h->costo, 4) }}</td>
                <td class="text-end" style="font-weight:600;">$ {{ number_format($h->total, 2) }}</td>
                <td class="text-end text-muted" style="font-size:12px;">$ {{ number_format($h->total * 1.13, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-end" style="font-size:12px; letter-spacing:.5px;">TOTAL GENERAL</td>
                <td class="text-end">$ {{ number_format($totalDiferencia, 2) }}</td>
                <td class="text-end">$ {{ number_format($totalDiferencia * 1.13, 2) }}</td>
                <td></td>
                <td class="text-end">$ {{ number_format($totalGeneral, 2) }}</td>
                <td class="text-end">$ {{ number_format($totalGeneral * 1.13, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- ── RESUMEN ── --}}
<div class="resumen-row">
    <div class="resumen-card" style="max-width:480px;">
        <div class="rc-header">
            <i class="fa-solid fa-chart-bar"></i> Resumen de diferencias
        </div>
        <div class="rc-body">
            <div class="resumen-line">
                <span class="rl-label">Total Inventario Anterior</span>
                <span class="rl-value">
                    $ {{ number_format($totalinventarioAnterior, 2) }}
                    <span class="rl-sub d-block">C/IVA $ {{ number_format($totalinventarioAnterior * 1.13, 2) }}</span>
                </span>
            </div>
            <div class="resumen-line">
                <span class="rl-label">Total Conteo Físico Actual</span>
                <span class="rl-value">
                    $ {{ number_format($apertura->total_fisico ?? $totalGeneral, 2) }}
                    <span class="rl-sub d-block">C/IVA $ {{ number_format(($apertura->total_fisico ?? $totalGeneral) * 1.13, 2) }}</span>
                </span>
            </div>
            <div class="resumen-line" style="border-bottom:none;">
                <span class="rl-label">Diferencia neta</span>
                <span class="rl-value {{ $totalDiferencia < 0 ? 'rl-danger' : 'rl-success' }}">
                    {{ $totalDiferencia >= 0 ? '+' : '' }}$ {{ number_format($totalDiferencia, 2) }}
                    <span class="rl-sub d-block">C/IVA {{ $totalDiferencia >= 0 ? '+' : '' }}$ {{ number_format($totalDiferencia * 1.13, 2) }}</span>
                </span>
            </div>
            <div class="resumen-line rl-total" style="border-bottom:none; margin-top:12px;">
                <span style="font-size:12px; color:#6c757d;">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Sobrantes: <strong class="text-success">${{ number_format($difPositiva, 2) }}</strong>
                    &nbsp;&nbsp;
                    Faltantes: <strong class="text-danger">${{ number_format(abs($difNegativa), 2) }}</strong>
                </span>
            </div>
        </div>
    </div>
    @if($apertura->observacion)
    <div class="resumen-card" style="flex:1;">
        <div class="rc-header">
            <i class="fa-solid fa-note-sticky"></i> Observaciones
        </div>
        <div class="rc-body" style="white-space:pre-wrap; color:#495057; line-height:1.6;">{{ $apertura->observacion }}</div>
    </div>
    @endif
</div>

{{-- ── FOOTER ── --}}
<div class="doc-footer">
    Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Sistema SuperTienda Robert
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Popovers
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        new bootstrap.Popover(el, { sanitize: false, customClass: 'pop-inv' });
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('[data-bs-toggle="popover"]')) {
            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
                bootstrap.Popover.getInstance(el)?.hide();
            });
        }
    });

    // Buscador en tiempo real
    document.getElementById('buscador').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('#tablaInventario .inv-row').forEach(row => {
            const match = row.dataset.nombre.includes(q) || row.dataset.codebar.includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
</script>
<style>
    .pop-inv { max-width: 380px !important; font-size: 12px; }
    .pop-inv .popover-header {
        background: #233446; color: #fff; font-size: 12px;
        padding: 7px 14px; border-bottom: none;
    }
    .pop-inv .popover-header::before { border-bottom-color: #233446 !important; }
    .pop-inv .popover-body { padding: 0; }
    .pop-inv .popover-body table { margin: 0; }
</style>
</body>
</html>
