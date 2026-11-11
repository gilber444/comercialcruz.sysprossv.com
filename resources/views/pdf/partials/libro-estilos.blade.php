{{--
    Estilos comunes de los libros de IVA (mismo diseno del PDF del DTE).
    Toda la paleta sale de DtePaletteService (variable $paletaDte que define
    cada libro al inicio): sin colores fijos.

    Trampas de DomPDF evitadas: border-radius solo en <div> (no en <table>),
    tablas con margin:0 y table-layout:fixed dentro de la tarjeta para que no
    se desborden ni se corte la ultima columna, nada de height:100% en celdas,
    y pie con position:fixed + counter(page).
--}}
<style>
    @page { margin: 22px 22px 34px 22px; }

    body {
        font-family: sans-serif;
        font-size: 9px;
        color: {{ $paletaDte['texto'] }};
        background-color: {{ $paletaDte['fondo'] }};
        margin: 0;
        padding: 0;
    }

    .lb-header {
        background-color: {{ $paletaDte['encabezado'] }};
        border: 1px solid {{ $paletaDte['borde'] }};
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 8px;
    }

    .lb-header table { width: 100%; border-collapse: collapse; }

    .lb-eyebrow {
        color: {{ $paletaDte['texto_suave'] }};
        font-size: 7px;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin: 0 0 2px 0;
    }

    .lb-empresa { font-size: 12px; font-weight: bold; margin: 0; color: {{ $paletaDte['primario'] }}; }
    .lb-dato    { font-size: 8px; margin: 1px 0; line-height: 1.3; color: {{ $paletaDte['texto_suave'] }}; }

    .lb-titulo {
        text-align: center;
        font-size: 12px;
        font-weight: bold;
        color: {{ $paletaDte['primario'] }};
        margin: 0 0 2px 0;
    }

    .lb-periodo { text-align: center; font-size: 9px; color: {{ $paletaDte['texto_suave'] }}; margin: 0; }

    .lb-pie {
        position: fixed;
        bottom: -22px;
        left: 0;
        right: 0;
        font-size: 7px;
        color: {{ $paletaDte['texto_suave'] }};
        border-top: 1px solid {{ $paletaDte['borde'] }};
        padding-top: 3px;
    }

    .lb-pie table { width: 100%; border-collapse: collapse; }
    .lb-pie .pagina:after { content: counter(page); }

    /* --- Tarjeta que redondea la tabla de detalle ---------------------- */
    .lb-tabla-card {
        border: 1px solid {{ $paletaDte['borde'] }};
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .lb-tabla-card table {
        border-collapse: collapse;
        width: 100%;
        border: 0;
        margin: 0;
        table-layout: fixed;
    }

    .lb-tabla-card th {
        background-color: {{ $paletaDte['primario'] }};
        color: {{ $paletaDte['fondo'] }};
        font-weight: bold;
        font-size: 7.5px;
        text-align: center;
        padding: 5px 2px;
        word-wrap: break-word;
        border: 0;
    }

    .lb-tabla-card td {
        font-size: 7.5px;
        padding: 4px 2px;
        word-wrap: break-word;
        border: 0;
        border-bottom: 1px solid {{ $paletaDte['borde'] }};
        vertical-align: top;
    }

    .lb-tabla-card td:last-child, .lb-tabla-card th:last-child { padding-right: 6px; }
    .lb-tabla-card tr.par td { background-color: {{ $paletaDte['encabezado'] }}; }

    .lb-tabla-card .c { text-align: center; }
    .lb-tabla-card .r { text-align: right; }
    .lb-tabla-card .mono { font-family: monospace; font-size: 6.5px; }

    .lb-tabla-card tr.lb-totales td {
        font-weight: bold;
        background-color: {{ $paletaDte['encabezado'] }};
        border-top: 2px solid {{ $paletaDte['primario'] }};
        border-bottom: 0;
        color: {{ $paletaDte['primario'] }};
    }

    .lb-vacio {
        text-align: center;
        padding: 14px 4px;
        color: {{ $paletaDte['texto_suave'] }};
    }

    /* --- Resumen del periodo ------------------------------------------- */
    .lb-resumen {
        border: 1px solid {{ $paletaDte['borde'] }};
        border-radius: 10px;
        padding: 8px 10px;
    }

    .lb-resumen .titulo, .lb-firma .titulo {
        color: {{ $paletaDte['primario'] }};
        font-size: 9px;
        font-weight: bold;
        margin: 0 0 6px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .lb-resumen table { width: 100%; border-collapse: collapse; }

    .lb-resumen th {
        background-color: {{ $paletaDte['encabezado'] }};
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: {{ $paletaDte['texto_suave'] }};
        text-align: right;
        padding: 3px 6px;
        border-bottom: 1px solid {{ $paletaDte['borde'] }};
    }

    .lb-resumen th:first-child { text-align: left; }

    .lb-resumen td {
        font-size: 8px;
        padding: 3px 6px;
        text-align: right;
        border-bottom: 1px solid {{ $paletaDte['borde'] }};
    }

    .lb-resumen td:first-child { text-align: left; color: {{ $paletaDte['texto_suave'] }}; }

    .lb-resumen tr.total td {
        font-weight: bold;
        color: {{ $paletaDte['primario'] }};
        border-bottom: 0;
        border-top: 2px solid {{ $paletaDte['primario'] }};
    }

    .lb-nota { font-size: 7px; color: {{ $paletaDte['texto_suave'] }}; margin: 5px 0 0 0; }

    /* --- Firma del contador -------------------------------------------- */
    .lb-firma {
        border: 1px solid {{ $paletaDte['borde'] }};
        border-radius: 10px;
        padding: 8px 10px;
    }

    .lb-firma .linea {
        border-top: 1px solid {{ $paletaDte['texto'] }};
        margin: 30px 12px 3px 12px;
    }

    .lb-firma .rol {
        text-align: center;
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: {{ $paletaDte['texto_suave'] }};
        margin: 0 0 4px 0;
    }
</style>
