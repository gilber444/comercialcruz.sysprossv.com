<?php

namespace App\Http\Livewire;

use App\Models\Sucursales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KardexAuditorController extends Component
{
    public $pageTitle     = 'Auditor de Kardex';
    public $componentName = 'Kardex';

    public $sucursalId   = '';
    public $sucursales   = [];
    public $escaneado    = false;
    public $soloConError = true;

    // Rango de fechas (por defecto: 2026-01-01 a hoy)
    public $fechaDesde = '';
    public $fechaHasta = '';

    // Paginación
    public $resultados       = [];
    public $offset           = 0;
    public $porPagina        = 500;
    public $totalInventarios = 0;
    public $hayMas           = false;

    // Conteos VPS por inventario_id local → int
    public array $vpsConteos      = [];
    public int   $vpsTotalPeriodo = 0;
    public int   $localTotalPeriodo = 0;

    // Modal de detalle (un solo producto)
    public $modalInventarioId  = null;
    public $modalNombre        = '';
    public $modalLineas        = [];
    public $modalErrores       = [];
    public $modalSaldoInicial  = 0.0;
    public $modalSaldoFinal    = 0.0;   // último saldoCorrecto calculado del período
    public $modalExistencia    = null;  // inventarios.existencia actual
    public $modalInventarioDbId = null; // inventarios.id para el UPDATE
    public $modalKey           = 0;     // fuerza re-render del contenido del modal

    // Modal VPS — líneas del VPS para comparar/bajar
    public array  $modalVpsLineas       = [];  // registros del VPS para el producto
    public array  $modalVpsEnLocal      = [];  // sincro_ids que ya existen localmente
    public int    $modalVpsInventarioId = 0;   // inventario.id en el VPS
    public int    $modalVpsKeyModal     = 0;

    public function mount()
    {
        $this->sucursales  = Sucursales::orderBy('nombre')->get(['id', 'nombre'])->toArray();
        $this->fechaDesde  = '2026-01-01';
        $this->fechaHasta  = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.kardex.auditor')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    // ----------------------------------------------------------------
    //  ESCANEAR
    // ----------------------------------------------------------------
    public function escanear()
    {
        $this->validarPerfil();

        $this->resultados         = [];
        $this->vpsConteos         = [];
        $this->vpsTotalPeriodo    = 0;
        $this->localTotalPeriodo  = 0;
        $this->offset             = 0;
        $this->totalInventarios   = 0;
        $this->hayMas             = false;
        $this->escaneado          = false;

        $this->totalInventarios = $this->contarInventarios();
        $this->cargarPagina();
        $this->cargarConteosVPS();

        $this->escaneado = true;
    }

    // ----------------------------------------------------------------
    //  CARGAR MÁS
    // ----------------------------------------------------------------
    public function cargarMas()
    {
        $this->validarPerfil();
        $this->cargarPagina();
    }

    // ----------------------------------------------------------------
    //  Carga por LIMIT/OFFSET — detecta errores en el periodo
    // ----------------------------------------------------------------
    private function cargarPagina()
    {
        [$desde, $hasta] = $this->getFechas();
        $sucId           = $this->getSucId();

        // Filtro de sucursal en el inner join (limita filas procesadas por MySQL)
        $filtroJoinSuc = $sucId
            ? "JOIN inventarios fi ON fi.id = k2.inventario AND fi.sucursal = ?"
            : "";

        // WHERE outer: sucursal + soloConError
        $conditions = [];
        if ($sucId)              $conditions[] = "inv.sucursal = ?";
        if ($this->soloConError) $conditions[] = "stats.lineas_error > 0";
        $filtroWhere = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        /*
         * Subquery de saldo inicial: último saldoCantidad antes de fechaDesde
         * para cada inventario. Se usa como base para el recálculo acumulativo.
         */
        $subInicial = "
            SELECT inventario, saldoCantidad AS saldo_inicial
            FROM (
                SELECT inventario, saldoCantidad,
                    ROW_NUMBER() OVER (PARTITION BY inventario ORDER BY fecha DESC, hora DESC, id DESC) AS rn
                FROM kardexes2
                WHERE fecha < ?
            ) t_ini
            WHERE rn = 1
        ";

        $sql = "
            SELECT
                inv.id             AS inventario_id,
                inv.producto       AS producto_id,
                inv.sucursal       AS sucursal_id,
                p.codebar3         AS codebar,
                p.nombreProducto   AS nombre,
                stats.total_lineas AS total,
                stats.lineas_error AS errores
            FROM inventarios inv
            JOIN productos p ON p.id = inv.producto
            JOIN (
                SELECT
                    k.inventario,
                    COUNT(*)  AS total_lineas,
                    SUM(CASE WHEN ROUND(k.saldoCantidad, 4) <> ROUND(k.saldo_correcto, 4)
                             THEN 1 ELSE 0 END) AS lineas_error
                FROM (
                    SELECT
                        k2.inventario,
                        k2.saldoCantidad,
                        COALESCE(si.saldo_inicial, 0) +
                            SUM(k2.ingresoCantidad - k2.egresoCantidad)
                                OVER (PARTITION BY k2.inventario ORDER BY k2.fecha, k2.hora, k2.id
                                      ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                            AS saldo_correcto
                    FROM kardexes2 k2
                    LEFT JOIN ({$subInicial}) si ON si.inventario = k2.inventario
                    {$filtroJoinSuc}
                    WHERE k2.fecha BETWEEN ? AND ?
                ) k
                GROUP BY k.inventario
            ) stats ON stats.inventario = inv.id
            {$filtroWhere}
            ORDER BY stats.lineas_error DESC, p.nombreProducto ASC
            LIMIT ? OFFSET ?
        ";

        $bindings = [];
        $bindings[] = $desde;            // subInicial: fecha < desde
        if ($sucId) $bindings[] = $sucId; // filtroJoinSuc
        $bindings[] = $desde;            // WHERE k2.fecha BETWEEN desde
        $bindings[] = $hasta;            // AND hasta
        if ($sucId) $bindings[] = $sucId; // filtroWhere sucursal
        $bindings[] = $this->porPagina;
        $bindings[] = $this->offset;

        $rows = DB::select($sql, $bindings);

        foreach ($rows as $r) {
            $this->resultados[] = (array) $r;
        }

        $this->offset += count($rows);
        $this->hayMas  = $this->offset < $this->totalInventarios;
    }

    // ----------------------------------------------------------------
    //  Contar inventarios del periodo con/sin errores
    // ----------------------------------------------------------------
    private function contarInventarios(): int
    {
        [$desde, $hasta] = $this->getFechas();
        $sucId           = $this->getSucId();

        $filtroJoinSuc = $sucId
            ? "JOIN inventarios fi ON fi.id = k2.inventario AND fi.sucursal = ?"
            : "";

        $conditions  = $sucId ? ["inv.sucursal = ?"] : [];
        $filtroWhere = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        $having      = $this->soloConError ? "HAVING lineas_error > 0" : "";

        $subInicial = "
            SELECT inventario, saldoCantidad AS saldo_inicial
            FROM (
                SELECT inventario, saldoCantidad,
                    ROW_NUMBER() OVER (PARTITION BY inventario ORDER BY fecha DESC, hora DESC, id DESC) AS rn
                FROM kardexes2
                WHERE fecha < ?
            ) t_ini
            WHERE rn = 1
        ";

        $sql = "
            SELECT COUNT(*) AS total FROM (
                SELECT inv.id,
                    SUM(CASE WHEN ROUND(k.saldoCantidad, 4) <> ROUND(k.saldo_correcto, 4)
                             THEN 1 ELSE 0 END) AS lineas_error
                FROM inventarios inv
                JOIN (
                    SELECT
                        k2.inventario,
                        k2.saldoCantidad,
                        COALESCE(si.saldo_inicial, 0) +
                            SUM(k2.ingresoCantidad - k2.egresoCantidad)
                                OVER (PARTITION BY k2.inventario ORDER BY k2.fecha, k2.hora, k2.id
                                      ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                            AS saldo_correcto
                    FROM kardexes2 k2
                    LEFT JOIN ({$subInicial}) si ON si.inventario = k2.inventario
                    {$filtroJoinSuc}
                    WHERE k2.fecha BETWEEN ? AND ?
                ) k ON k.inventario = inv.id
                {$filtroWhere}
                GROUP BY inv.id
                {$having}
            ) sub
        ";

        $bindings = [];
        $bindings[] = $desde;
        if ($sucId) $bindings[] = $sucId;
        $bindings[] = $desde;
        $bindings[] = $hasta;
        if ($sucId) $bindings[] = $sucId;

        $result = DB::selectOne($sql, $bindings);
        return (int) ($result->total ?? 0);
    }

    // ----------------------------------------------------------------
    //  CORREGIR UN PRODUCTO COMPLETO — solo líneas del periodo
    // ----------------------------------------------------------------
    public function corregirProducto($inventarioId)
    {
        $this->validarPerfil();
        $this->ejecutarUpdateKardex($inventarioId);

        foreach ($this->resultados as &$r) {
            if ($r['inventario_id'] == $inventarioId) {
                $r['errores'] = 0;
                break;
            }
        }

        if ($this->modalInventarioId == $inventarioId) {
            $this->recargarModal();
        }

        $this->emit('noti', 'Corregido', 'Kardex del producto corregido.', 'success');
    }

    // ----------------------------------------------------------------
    //  CORREGIR UNA SOLA LÍNEA — desde el modal
    // ----------------------------------------------------------------
    public function corregirLineaModal($kardexId, $saldoCorrecto)
    {
        $this->validarPerfil();

        DB::table('kardexes2')
            ->where('id', $kardexId)
            ->update(['saldoCantidad' => $saldoCorrecto, 'updated_at' => now()]);

        $this->recargarModal();

        $erroresRestantes = collect($this->modalErrores)->where('tieneError', true)->count();
        foreach ($this->resultados as &$r) {
            if ($r['inventario_id'] == $this->modalInventarioId) {
                $r['errores'] = $erroresRestantes;
                break;
            }
        }

        $this->emit('noti', 'Corregido', 'Línea de kardex corregida.', 'success');
    }

    // ----------------------------------------------------------------
    //  CORREGIR TODOS — solo los cargados con errores
    // ----------------------------------------------------------------
    public function corregirTodos()
    {
        $this->validarPerfil();

        $corregidos = 0;

        foreach ($this->resultados as &$r) {
            if ((int) $r['errores'] === 0) continue;
            $this->ejecutarUpdateKardex($r['inventario_id']);
            $r['errores'] = 0;
            $corregidos++;
        }

        $this->emit('noti', 'Completado', "Se corrigieron {$corregidos} producto(s).", 'success');
    }

    // ----------------------------------------------------------------
    //  VER DETALLE
    // ----------------------------------------------------------------
    public function verDetalle($inventarioId)
    {
        $this->validarPerfil();

        $this->modalInventarioId = $inventarioId;
        $this->modalNombre       = '';

        foreach ($this->resultados as $r) {
            if ($r['inventario_id'] == $inventarioId) {
                $this->modalNombre = $r['nombre'];
                break;
            }
        }

        $this->recargarModal();
        $this->emit('show-auditor-modal');
    }

    // ----------------------------------------------------------------
    //  Helpers privados
    // ----------------------------------------------------------------
    private function recargarModal()
    {
        [$desde, $hasta] = $this->getFechas();

        // Saldo inicial: último registro antes de fechaDesde
        $saldoInicial = DB::table('kardexes2')
            ->where('inventario', $this->modalInventarioId)
            ->whereDate('fecha', '<', $desde)
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->value('saldoCantidad');

        $this->modalSaldoInicial = (float) ($saldoInicial ?? 0);

        // Líneas del periodo — convertidas a array para que Livewire no pierda el tipo al rehidratar
        $this->modalLineas = DB::table('kardexes2')
            ->where('inventario', $this->modalInventarioId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $saldo = $this->modalSaldoInicial;
        $this->modalErrores = [];

        foreach ($this->modalLineas as $index => $linea) {
            $esperado   = $saldo + (float) $linea['ingresoCantidad'] - (float) $linea['egresoCantidad'];
            $tieneError = round((float) $linea['saldoCantidad'], 4) !== round($esperado, 4);

            $this->modalErrores[$index] = [
                'tieneError'    => $tieneError,
                'saldoCorrecto' => $esperado,
                'saldoGuardado' => (float) $linea['saldoCantidad'],
            ];

            $saldo = $esperado;
        }

        // Saldo final del período (último saldoCorrecto calculado)
        $this->modalSaldoFinal = $saldo;

        // Existencia actual en inventarios
        $inv = DB::table('inventarios')
            ->where('id', $this->modalInventarioId)
            ->first(['id', 'existencia']);

        $this->modalInventarioDbId = $inv->id ?? null;
        $this->modalExistencia     = $inv ? (float) $inv->existencia : null;

        $this->modalKey++;
    }

    // ----------------------------------------------------------------
    //  ACTUALIZAR EXISTENCIA — sincroniza inventarios.existencia con
    //  el saldo final correcto del kardex
    // ----------------------------------------------------------------
    public function actualizarExistenciaModal()
    {
        $this->validarPerfil();

        if ($this->modalInventarioDbId === null) return;

        DB::table('inventarios')
            ->where('id', $this->modalInventarioDbId)
            ->update(['existencia' => $this->modalSaldoFinal]);

        $this->modalExistencia = $this->modalSaldoFinal;

        $this->emit('noti', 'Actualizado', 'Existencia actualizada al saldo del kardex.', 'success');
    }

    // ----------------------------------------------------------------
    //  ACCIONES SOBRE LÍNEAS — Solo Super
    // ----------------------------------------------------------------
    public function eliminarLineaAuditor($kardexId)
    {
        abort_unless(Auth::user()->can('KardexAuditor_Eliminar'), 403);

        DB::table('kardexes2')->where('id', $kardexId)->delete();
        $this->ejecutarUpdateKardex($this->modalInventarioId);
        $this->recargarModal();

        $this->emit('noti', 'Eliminado', 'Línea eliminada y saldos recalculados.', 'success');
    }

    public function anularLineaAuditor($kardexId)
    {
        abort_unless(Auth::user()->can('KardexAuditor_Anular'), 403);

        DB::table('kardexes2')->where('id', $kardexId)->update([
            'ingresoCantidad' => 0, 'ingresoValor'   => 0,
            'egresoCantidad'  => 0, 'egresoValor'    => 0,
            'updated_at'      => now(),
        ]);
        $this->ejecutarUpdateKardex($this->modalInventarioId);
        $this->recargarModal();

        $this->emit('noti', 'Anulado', 'Línea anulada y saldos recalculados.', 'success');
    }

    public function zeroIngresoAuditor($kardexId)
    {
        abort_unless(Auth::user()->can('KardexAuditor_Anular'), 403);

        DB::table('kardexes2')->where('id', $kardexId)->update([
            'ingresoCantidad' => 0, 'ingresoValor' => 0,
            'updated_at'      => now(),
        ]);
        $this->ejecutarUpdateKardex($this->modalInventarioId);
        $this->recargarModal();

        $this->emit('noti', 'Anulado', 'Ingreso puesto a 0.', 'success');
    }

    public function zeroEgresoAuditor($kardexId)
    {
        abort_unless(Auth::user()->can('KardexAuditor_Anular'), 403);

        DB::table('kardexes2')->where('id', $kardexId)->update([
            'egresoCantidad' => 0, 'egresoValor' => 0,
            'updated_at'     => now(),
        ]);
        $this->ejecutarUpdateKardex($this->modalInventarioId);
        $this->recargarModal();

        $this->emit('noti', 'Anulado', 'Egreso puesto a 0.', 'success');
    }

    public function ajustarCantidad($kardexId, string $campo, int $delta)
    {
        abort_unless(Auth::user()->can('KardexAuditor_Editar'), 403);

        // campo: ingresoCantidad o egresoCantidad
        if (!in_array($campo, ['ingresoCantidad', 'egresoCantidad'])) return;

        $linea = DB::table('kardexes2')->where('id', $kardexId)->first();
        if (!$linea) return;

        $nuevo = max(0, (float) $linea->{$campo} + $delta);

        DB::table('kardexes2')->where('id', $kardexId)->update([
            $campo       => $nuevo,
            'updated_at' => now(),
        ]);

        $this->ejecutarUpdateKardex($this->modalInventarioId);
        $this->recargarModal();

        $this->emit('noti', 'Actualizado', "Cantidad ajustada en {$delta}.", 'success');
    }

    private function ejecutarUpdateKardex($inventarioId)
    {
        [$desde, $hasta] = $this->getFechas();

        DB::statement("
            UPDATE kardexes2 k
            JOIN (
                SELECT
                    k2.id,
                    COALESCE(si.saldo_inicial, 0) +
                        SUM(k2.ingresoCantidad - k2.egresoCantidad)
                            OVER (PARTITION BY k2.inventario ORDER BY k2.fecha, k2.hora, k2.id
                                  ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                        AS saldo_correcto
                FROM kardexes2 k2
                LEFT JOIN (
                    SELECT inventario, saldoCantidad AS saldo_inicial
                    FROM (
                        SELECT inventario, saldoCantidad,
                            ROW_NUMBER() OVER (PARTITION BY inventario ORDER BY fecha DESC, hora DESC, id DESC) AS rn
                        FROM kardexes2
                        WHERE inventario = ? AND fecha < ?
                    ) t_ini
                    WHERE rn = 1
                ) si ON si.inventario = k2.inventario
                WHERE k2.inventario = ?
                  AND k2.fecha BETWEEN ? AND ?
            ) calc ON k.id = calc.id
            SET k.saldoCantidad = calc.saldo_correcto,
                k.updated_at   = NOW()
            WHERE ROUND(k.saldoCantidad, 4) <> ROUND(calc.saldo_correcto, 4)
        ", [$inventarioId, $desde, $inventarioId, $desde, $hasta]);
    }

    private function getFechas(): array
    {
        $desde = $this->fechaDesde ?: '2026-01-01';
        $hasta = $this->fechaHasta ?: now()->format('Y-m-d');
        return [$desde, $hasta];
    }

    private function getSucId(): ?int
    {
        return ($this->sucursalId !== '' && $this->sucursalId != 0)
            ? (int) $this->sucursalId
            : null;
    }

    // ----------------------------------------------------------------
    //  CONTEOS VPS — cuántas líneas tiene el VPS por producto/sucursal
    // ----------------------------------------------------------------
    private function cargarConteosVPS(): void
    {
        if (empty($this->resultados)) return;

        [$desde, $hasta] = $this->getFechas();

        try {
            DB::connection('vpsmysql')->getPdo();
        } catch (\Throwable) {
            return; // VPS no disponible — no bloquear
        }

        // Construir lista de (producto, sucursal) de los resultados cargados
        $pares = collect($this->resultados)->map(fn($r) => [
            'inv_local'  => $r['inventario_id'],
            'producto'   => $r['producto_id'],
            'sucursal'   => $r['sucursal_id'],
        ]);

        // Contar líneas en VPS para cada producto+sucursal en el período
        $productosIds  = $pares->pluck('producto')->unique()->values()->all();
        $sucursalesIds = $pares->pluck('sucursal')->unique()->values()->all();

        $vpsRows = DB::connection('vpsmysql')
            ->table('kardexes2 as k')
            ->join('inventarios as i', 'i.id', '=', 'k.inventario')
            ->whereIn('i.producto', $productosIds)
            ->whereIn('i.sucursal', $sucursalesIds)
            ->whereBetween('k.fecha', [$desde, $hasta])
            ->groupBy('i.producto', 'i.sucursal')
            ->selectRaw('i.producto, i.sucursal, COUNT(k.id) AS total')
            ->get();

        // Indexar por producto+sucursal para búsqueda rápida
        $vpsMap = [];
        foreach ($vpsRows as $row) {
            $vpsMap[$row->producto . '_' . $row->sucursal] = (int) $row->total;
        }

        $localTotal = 0;
        $vpsTotal   = 0;

        foreach ($this->resultados as &$r) {
            $key = $r['producto_id'] . '_' . $r['sucursal_id'];
            $vpsCount = $vpsMap[$key] ?? 0;
            $r['vps_total'] = $vpsCount;
            $r['vps_diff']  = $vpsCount - (int) $r['total'];
            $localTotal += (int) $r['total'];
            $vpsTotal   += $vpsCount;
        }
        unset($r);

        $this->localTotalPeriodo = $localTotal;
        $this->vpsTotalPeriodo   = $vpsTotal;

        // Construir índice inventario_id → vps_total para acceso rápido en vista
        $this->vpsConteos = collect($this->resultados)
            ->pluck('vps_total', 'inventario_id')
            ->map(fn($v) => (int) $v)
            ->toArray();
    }

    // ----------------------------------------------------------------
    //  VER LÍNEAS DEL VPS — abre modal con kardex del VPS para ese producto
    // ----------------------------------------------------------------
    public function verDetalleVPS($inventarioId)
    {
        $this->validarPerfil();

        [$desde, $hasta] = $this->getFechas();

        // Obtener producto+sucursal locales
        $invLocal = DB::table('inventarios')->where('id', $inventarioId)->first(['id', 'producto', 'sucursal']);
        if (!$invLocal) return;

        // Encontrar inventario en VPS con mismo producto+sucursal
        try {
            $invVps = DB::connection('vpsmysql')
                ->table('inventarios')
                ->where('producto', $invLocal->producto)
                ->where('sucursal', $invLocal->sucursal)
                ->first(['id']);
        } catch (\Throwable) {
            $this->emit('noti', 'Error', 'No se pudo conectar al VPS.', 'error');
            return;
        }

        if (!$invVps) {
            $this->emit('noti', 'Sin datos', 'No hay inventario en VPS para este producto/sucursal.', 'warning');
            return;
        }

        $this->modalInventarioId    = $inventarioId;
        $this->modalVpsInventarioId = $invVps->id;

        foreach ($this->resultados as $r) {
            if ($r['inventario_id'] == $inventarioId) {
                $this->modalNombre = $r['nombre'];
                break;
            }
        }

        // Líneas VPS del período
        $vpsLineas = DB::connection('vpsmysql')
            ->table('kardexes2')
            ->where('inventario', $invVps->id)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        // Sincro_ids que ya existen en local
        $sincroIds = array_filter(array_column($vpsLineas, 'sincro_id'));
        $enLocal = DB::table('kardexes2')
            ->whereIn('sincro_id', $sincroIds)
            ->pluck('sincro_id')
            ->flip()
            ->toArray();

        $this->modalVpsLineas  = $vpsLineas;
        $this->modalVpsEnLocal = $enLocal;
        $this->modalVpsKeyModal++;

        $this->emit('show-vps-modal');
    }

    // ----------------------------------------------------------------
    //  BAJAR UNA LÍNEA DEL VPS
    // ----------------------------------------------------------------
    public function bajarLineaVPS(string $sincroId)
    {
        $this->validarPerfil();
        $this->importarLineasVPS([$sincroId]);
        $this->emit('noti', 'Importado', 'Línea bajada del VPS.', 'success');
        $this->refrescarDespuesDeImportar();
    }

    // ----------------------------------------------------------------
    //  BAJAR LÍNEAS SELECCIONADAS DEL VPS
    // ----------------------------------------------------------------
    public function bajarSeleccionVPS(array $sincroIds)
    {
        $this->validarPerfil();
        if (empty($sincroIds)) return;
        $n = $this->importarLineasVPS($sincroIds);
        $this->emit('noti', 'Importado', "{$n} línea(s) bajada(s) del VPS.", 'success');
        $this->refrescarDespuesDeImportar();
    }

    // ----------------------------------------------------------------
    //  BAJAR TODAS LAS LÍNEAS FALTANTES DEL VPS
    // ----------------------------------------------------------------
    public function bajarTodasVPS()
    {
        $this->validarPerfil();

        $faltantes = collect($this->modalVpsLineas)
            ->filter(fn($l) => !isset($this->modalVpsEnLocal[$l['sincro_id'] ?? '']))
            ->pluck('sincro_id')
            ->filter()
            ->values()
            ->all();

        if (empty($faltantes)) {
            $this->emit('noti', 'Sin cambios', 'Todas las líneas ya existen en local.', 'info');
            return;
        }

        $n = $this->importarLineasVPS($faltantes);
        $this->emit('noti', 'Importado', "{$n} línea(s) bajada(s) del VPS.", 'success');
        $this->refrescarDespuesDeImportar();
    }

    // ----------------------------------------------------------------
    //  Lógica central de importación VPS → local
    // ----------------------------------------------------------------
    private function importarLineasVPS(array $sincroIds): int
    {
        if (empty($sincroIds) || !$this->modalInventarioId || !$this->modalVpsInventarioId) return 0;

        [$desde, $hasta] = $this->getFechas();

        $colsLocal = \Illuminate\Support\Facades\Schema::connection('localmysql')->getColumnListing('kardexes2');

        $lineas = DB::connection('vpsmysql')
            ->table('kardexes2')
            ->where('inventario', $this->modalVpsInventarioId)
            ->whereIn('sincro_id', $sincroIds)
            ->get();

        $importados = 0;
        foreach ($lineas as $l) {
            $arr = (array) $l;

            // Mapear inventario VPS → inventario local
            $arr['inventario'] = $this->modalInventarioId;

            // Columna id_vps si existe
            if (in_array('id_vps', $colsLocal)) {
                $arr['id_vps'] = $l->id;
            }

            // Quitar columnas que no existen en local
            $arr = array_intersect_key($arr, array_flip($colsLocal));

            // No insertar el id (auto-increment local)
            unset($arr['id']);

            // Sanear timestamps vacíos
            foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion'] as $ts) {
                if (array_key_exists($ts, $arr) && ($arr[$ts] === '?' || $arr[$ts] === '')) {
                    $arr[$ts] = null;
                }
            }

            // Saltar si ya existe por sincro_id
            if (!empty($arr['sincro_id']) && DB::table('kardexes2')->where('sincro_id', $arr['sincro_id'])->exists()) {
                continue;
            }

            DB::table('kardexes2')->insert($arr);
            $importados++;
        }

        // Recalcular saldos del inventario completo (no solo el período para no romper saldos)
        if ($importados > 0) {
            $this->recalcularSaldosCompleto($this->modalInventarioId);
        }

        return $importados;
    }

    // ----------------------------------------------------------------
    //  Recalcula saldoCantidad de TODAS las líneas del inventario (orden cronológico)
    // ----------------------------------------------------------------
    private function recalcularSaldosCompleto(int $inventarioId): void
    {
        $lineas = DB::table('kardexes2')
            ->where('inventario', $inventarioId)
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get(['id', 'ingresoCantidad', 'egresoCantidad']);

        $saldo = 0.0;
        foreach ($lineas as $l) {
            $saldo = $saldo + (float) $l->ingresoCantidad - (float) $l->egresoCantidad;
            DB::table('kardexes2')->where('id', $l->id)->update([
                'saldoCantidad' => $saldo,
                'updated_at'    => now(),
            ]);
        }
    }

    // ----------------------------------------------------------------
    //  Refresca modal VPS + conteos después de importar
    // ----------------------------------------------------------------
    private function refrescarDespuesDeImportar(): void
    {
        // Re-cargar sincroIds en local
        $sincroIds = array_filter(array_column($this->modalVpsLineas, 'sincro_id'));
        $enLocal = DB::table('kardexes2')
            ->whereIn('sincro_id', $sincroIds)
            ->pluck('sincro_id')
            ->flip()
            ->toArray();
        $this->modalVpsEnLocal = $enLocal;
        $this->modalVpsKeyModal++;

        // Actualizar conteo local en el resultado de la tabla
        [$desde, $hasta] = $this->getFechas();
        foreach ($this->resultados as &$r) {
            if ($r['inventario_id'] == $this->modalInventarioId) {
                $r['total'] = DB::table('kardexes2')
                    ->where('inventario', $this->modalInventarioId)
                    ->whereBetween('fecha', [$desde, $hasta])
                    ->count();
                $r['vps_diff'] = ($r['vps_total'] ?? 0) - $r['total'];
                break;
            }
        }
        unset($r);
    }

    private function validarPerfil()
    {
        abort_unless(Auth::user()->can('KardexAuditor_Index'), 403);
    }
}
