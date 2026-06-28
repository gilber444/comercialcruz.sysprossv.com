<?php

namespace App\Http\Livewire;

use App\Models\Sucursales;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConciliacionInventarioController extends Component
{
    public $componentName;
    public $dateFrom;
    public $dateTo;
    public $sucursal = 0;
    public $sucursales = [];
    public $incluirAjustes = true;
    public $incluirCompras = true;
    public $incluirSolicitudes = true;
    public $incluirVentas = true;
    public $soloEnCero = false;
    public $soloDiferencias = true;
    public $rows = [];
    public $summary = [];
    public $manualExistencia = [];

    public function mount()
    { 
        abort_unless(Auth::user()->can('ConciliacionInventario_Index'), 403);

        $this->componentName = 'Conciliacion de Inventario';
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');

        $user = Auth::user();

        if (in_array($user->profile, ['Super', 'Administrador', 'Gerente'], true)) {
            $this->sucursales = Sucursales::orderBy('nombre')->get();
            $this->sucursal = 0;
        } else {
            $this->sucursales = Sucursales::where('id', $user->sucursal)->orderBy('nombre')->get();
            $this->sucursal = (int) $user->sucursal;
        }
    }

    public function render()
    {
        return view('livewire.inventario.conciliacion')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function consultar()
    {
        $this->autorizarAccion('ConciliacionInventario_Consultar');

        $this->validate([
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date|after_or_equal:dateFrom',
        ]);

        $fuenteCompras = $this->incluirCompras ? $this->getFuenteCompras() : [];
        $fuenteAjustes = $this->incluirAjustes ? $this->getFuenteAjustes() : [];
        $fuenteSolicitudes = $this->incluirSolicitudes ? $this->getFuenteSolicitudes() : [];
        $fuenteVentas = $this->incluirVentas ? $this->getFuenteVentas() : [];

        $kardexCompras = $this->incluirCompras ? $this->getKardexNetoByDescripcion(['Compra de productos%']) : [];
        $kardexAjustes = $this->incluirAjustes ? $this->getKardexNetoByDescripcion([
            'Ingreso por Ajuste%',
            'Egreso por Ajuste%',
            'Ingreso por Ajuste #% (sync)%',
            'Egreso por Ajuste #% (sync)%',
        ]) : [];
        $kardexSolicitudes = $this->incluirSolicitudes ? $this->getKardexNetoByDescripcion([
            'Despacho de producto%',
            'Ingreso de producto a la sucursal%',
        ]) : [];
        $kardexVentas = $this->incluirVentas ? $this->getKardexNetoByDescripcion(['Venta con %']) : [];

        $latestKardex = $this->getLatestKardexSaldo();

        $keys = array_values(array_unique(array_merge(
            array_keys($fuenteCompras),
            array_keys($fuenteAjustes),
            array_keys($fuenteSolicitudes),
            array_keys($fuenteVentas),
            array_keys($latestKardex)
        )));

        $inventarios = $this->getInventariosByKeys($keys);
        $productosInfo = $this->getProductosInfoByKeys($keys);

        $rows = [];

        foreach ($keys as $key) {
            [$producto, $sucursal] = explode('|', $key);
            $inventario = $inventarios[$key] ?? null;
            $productoInfo = $productosInfo[$key] ?? null;

            $existenciaActual = (float) ($inventario['existencia'] ?? 0);
            $compras = (float) ($fuenteCompras[$key] ?? 0);
            $ajustesIngreso = (float) ($fuenteAjustes[$key]['ingreso'] ?? 0);
            $ajustesEgreso = (float) ($fuenteAjustes[$key]['egreso'] ?? 0);
            $solicitudesIn = (float) ($fuenteSolicitudes[$key]['in'] ?? 0);
            $solicitudesOut = (float) ($fuenteSolicitudes[$key]['out'] ?? 0);
            $ventas = (float) ($fuenteVentas[$key] ?? 0);

            $ajustes = $ajustesIngreso - $ajustesEgreso;
            $solicitudes = $solicitudesIn - $solicitudesOut;
            $fuentesNeto = $compras + $ajustes + $solicitudes - $ventas;
            $kardexNeto = (float) ($kardexCompras[$key] ?? 0)
                + (float) ($kardexAjustes[$key] ?? 0)
                + (float) ($kardexSolicitudes[$key] ?? 0)
                + (float) ($kardexVentas[$key] ?? 0);

            $diferenciaFuentesKardex = round($fuentesNeto - $kardexNeto, 4);
            $ultimoSaldoKardex = isset($latestKardex[$key]) ? (float) $latestKardex[$key]['saldo'] : null;
            $diferenciaInventarioKardex = $ultimoSaldoKardex !== null
                ? round($ultimoSaldoKardex - $existenciaActual, 4)
                : null;

            if ($this->soloEnCero && abs($existenciaActual) > 0.0001) {
                continue;
            }

            if (
                $this->soloDiferencias &&
                abs($diferenciaFuentesKardex) <= 0.0001 &&
                ($diferenciaInventarioKardex === null || abs($diferenciaInventarioKardex) <= 0.0001)
            ) {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'inventario_id' => $inventario['inventario_id'] ?? null,
                'producto' => (int) $producto,
                'sucursal' => (int) $sucursal,
                'codebar3' => $productoInfo['codebar3'] ?? '',
                'nombreProducto' => $productoInfo['nombreProducto'] ?? ('Producto #' . $producto),
                'existencia_actual' => round($existenciaActual, 4),
                'compras' => round($compras, 4),
                'ajustes_ingreso' => round($ajustesIngreso, 4),
                'ajustes_egreso' => round($ajustesEgreso, 4),
                'ajustes' => round($ajustes, 4),
                'solicitudes_in' => round($solicitudesIn, 4),
                'solicitudes_out' => round($solicitudesOut, 4),
                'solicitudes' => round($solicitudes, 4),
                'ventas' => round($ventas, 4),
                'fuentes_neto' => round($fuentesNeto, 4),
                'kardex_neto' => round($kardexNeto, 4),
                'diferencia_fuentes_kardex' => $diferenciaFuentesKardex,
                'existencia_sugerida_fuentes' => round($existenciaActual + $diferenciaFuentesKardex, 4),
                'ultimo_saldo_kardex' => $ultimoSaldoKardex !== null ? round($ultimoSaldoKardex, 4) : null,
                'diferencia_inventario_kardex' => $diferenciaInventarioKardex,
            ];
        }

        usort($rows, function ($a, $b) {
            $aScore = abs($a['diferencia_fuentes_kardex']) + abs($a['diferencia_inventario_kardex'] ?? 0);
            $bScore = abs($b['diferencia_fuentes_kardex']) + abs($b['diferencia_inventario_kardex'] ?? 0);

            return $bScore <=> $aScore;
        });

        $this->rows = $rows;
        $this->manualExistencia = [];
        foreach ($rows as $row) {
            $this->manualExistencia[$row['key']] = $row['existencia_actual'];
        }
        $this->summary = [
            'total' => count($rows),
            'con_faltante_fuentes' => collect($rows)->filter(fn($row) => abs($row['diferencia_fuentes_kardex']) > 0.0001)->count(),
            'con_diferencia_kardex' => collect($rows)->filter(fn($row) => abs($row['diferencia_inventario_kardex'] ?? 0) > 0.0001)->count(),
            'monto_fuentes' => round(collect($rows)->sum('diferencia_fuentes_kardex'), 4),
        ];
    }

    public function actualizarExistenciaManual(string $key)
    {
        $this->autorizarAccion('ConciliacionInventario_RepararManual');

        $valor = $this->manualExistencia[$key] ?? null;

        if ($valor === null || $valor === '') {
            $this->emit('item-error', 'Debes escribir una existencia.');
            return;
        }

        if (!is_numeric($valor)) {
            $this->emit('item-error', 'La existencia manual debe ser numerica.');
            return;
        }

        $row = collect($this->rows)->firstWhere('key', $key);

        if (!$row) {
            $this->emit('item-error', 'No se encontro la fila a actualizar.');
            return;
        }

        $existencia = (float) $valor;

        DB::transaction(function () use ($row, $existencia) {
            if (!empty($row['inventario_id'])) {
                DB::table('inventarios')
                    ->where('id', $row['inventario_id'])
                    ->update([
                        'existencia' => $existencia,
                        'updated_at' => now(),
                    ]);

                return;
            }

            $empresa = DB::table('sucursales')->where('id', $row['sucursal'])->value('empresa');

            DB::table('inventarios')->insert([
                'producto' => $row['producto'],
                'empresa' => $empresa,
                'sucursal' => $row['sucursal'],
                'existencia' => $existencia,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->consultar();
        $this->emit('item-added', 'Existencia actualizada manualmente.');
    }

    public function aplicarMovimiento(string $key, string $tipo)
    {
        $movimientosSuma = ['compras', 'ajustes_ingreso', 'solicitudes_in'];
        $movimientosResta = ['ajustes_egreso', 'solicitudes_out', 'ventas'];

        if (in_array($tipo, $movimientosSuma, true)) {
            $this->autorizarAccion('ConciliacionInventario_MovimientoMas');
        } elseif (in_array($tipo, $movimientosResta, true)) {
            $this->autorizarAccion('ConciliacionInventario_MovimientoMenos');
        }

        $row = collect($this->rows)->firstWhere('key', $key);

        if (!$row) {
            $this->emit('item-error', 'No se encontro la fila a actualizar.');
            return;
        }

        $map = [
            'compras' => ['cantidad' => (float) ($row['compras'] ?? 0), 'signo' => 1, 'label' => 'compras'],
            'ajustes_ingreso' => ['cantidad' => (float) ($row['ajustes_ingreso'] ?? 0), 'signo' => 1, 'label' => 'ajustes ingreso'],
            'ajustes_egreso' => ['cantidad' => (float) ($row['ajustes_egreso'] ?? 0), 'signo' => -1, 'label' => 'ajustes egreso'],
            'solicitudes_in' => ['cantidad' => (float) ($row['solicitudes_in'] ?? 0), 'signo' => 1, 'label' => 'solicitudes recibidas'],
            'solicitudes_out' => ['cantidad' => (float) ($row['solicitudes_out'] ?? 0), 'signo' => -1, 'label' => 'solicitudes despachadas'],
            'ventas' => ['cantidad' => (float) ($row['ventas'] ?? 0), 'signo' => -1, 'label' => 'ventas'],
        ];

        if (!isset($map[$tipo])) {
            $this->emit('item-error', 'Movimiento no valido.');
            return;
        }

        $cantidad = (float) $map[$tipo]['cantidad'];
        $delta = $cantidad * (float) $map[$tipo]['signo'];

        if (abs($cantidad) <= 0.0001) {
            $this->emit('item-error', 'No hay cantidad para aplicar en ese movimiento.');
            return;
        }

        DB::transaction(function () use ($row, $delta) {
            if (!empty($row['inventario_id'])) {
                DB::table('inventarios')
                    ->where('id', $row['inventario_id'])
                    ->update([
                        'existencia' => DB::raw('existencia + (' . $delta . ')'),
                        'updated_at' => now(),
                    ]);

                return;
            }

            if ($delta < 0) {
                throw new \RuntimeException('No se puede crear inventario nuevo con una resta.');
            }

            $empresa = DB::table('sucursales')->where('id', $row['sucursal'])->value('empresa');

            DB::table('inventarios')->insert([
                'producto' => $row['producto'],
                'empresa' => $empresa,
                'sucursal' => $row['sucursal'],
                'existencia' => $delta,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->consultar();
        $this->emit('item-added', 'Movimiento aplicado: ' . $map[$tipo]['label']);
    }

    public function aplicarSaldoKardex(string $key)
    {
        $this->autorizarAccion('ConciliacionInventario_AplicarSaldoKardex');

        $row = collect($this->rows)->firstWhere('key', $key);

        if (!$row) {
            $this->emit('item-error', 'No se encontro la fila a actualizar.');
            return;
        }

        $saldo = $row['ultimo_saldo_kardex'];
        $diferencia = (float) ($row['diferencia_inventario_kardex'] ?? 0);

        if ($saldo === null) {
            $this->emit('item-error', 'Ese producto no tiene saldo en kardex.');
            return;
        }

        if (abs($diferencia) <= 0.0001) {
            $this->emit('item-error', 'El inventario ya esta igual al saldo del kardex.');
            return;
        }

        DB::transaction(function () use ($row, $saldo, $diferencia) {
            if (!empty($row['inventario_id'])) {
                DB::table('inventarios')
                    ->where('id', $row['inventario_id'])
                    ->update([
                        'existencia' => DB::raw('existencia + (' . $diferencia . ')'),
                        'updated_at' => now(),
                    ]);

                return;
            }

            $empresa = DB::table('sucursales')->where('id', $row['sucursal'])->value('empresa');

            DB::table('inventarios')->insert([
                'producto' => $row['producto'],
                'empresa' => $empresa,
                'sucursal' => $row['sucursal'],
                'existencia' => $saldo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->consultar();
        $this->emit('item-added', 'Saldo de kardex aplicado al inventario.');
    }

    public function aplicarDesdeFuentes()
    {
        $this->autorizarAccion('ConciliacionInventario_AplicarFuentes');

        if (empty($this->rows)) {
            $this->emit('item-error', 'Primero debes consultar.');
            return;
        }

        $actualizados = 0;
        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use (&$actualizados, &$creados, &$omitidos) {
            foreach ($this->rows as $row) {
                $delta = (float) $row['diferencia_fuentes_kardex'];

                if (abs($delta) <= 0.0001) {
                    continue;
                }

                if (!empty($row['inventario_id'])) {
                    DB::table('inventarios')
                        ->where('id', $row['inventario_id'])
                        ->update([
                            'existencia' => DB::raw('existencia + (' . $delta . ')'),
                            'updated_at' => now(),
                        ]);

                    $actualizados++;
                    continue;
                }

                if ($delta < 0) {
                    $omitidos++;
                    continue;
                }

                $empresa = DB::table('sucursales')->where('id', $row['sucursal'])->value('empresa');

                DB::table('inventarios')->insert([
                    'producto' => $row['producto'],
                    'empresa' => $empresa,
                    'sucursal' => $row['sucursal'],
                    'existencia' => $delta,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $creados++;
            }
        });

        $this->consultar();
        $this->emit('item-added', "Aplicado desde fuentes. Actualizados: {$actualizados}, creados: {$creados}, omitidos: {$omitidos}");
    }

    public function igualarAlUltimoKardex()
    {
        $this->autorizarAccion('ConciliacionInventario_IgualarKardex');

        if (empty($this->rows)) {
            $this->emit('item-error', 'Primero debes consultar.');
            return;
        }

        $actualizados = 0;
        $creados = 0;

        DB::transaction(function () use (&$actualizados, &$creados) {
            foreach ($this->rows as $row) {
                $saldo = $row['ultimo_saldo_kardex'];
                $diferencia = (float) ($row['diferencia_inventario_kardex'] ?? 0);

                if ($saldo === null || abs($diferencia) <= 0.0001) {
                    continue;
                }

                if (!empty($row['inventario_id'])) {
                    DB::table('inventarios')
                        ->where('id', $row['inventario_id'])
                        ->update([
                            'existencia' => $saldo,
                            'updated_at' => now(),
                        ]);

                    $actualizados++;
                    continue;
                }

                $empresa = DB::table('sucursales')->where('id', $row['sucursal'])->value('empresa');

                DB::table('inventarios')->insert([
                    'producto' => $row['producto'],
                    'empresa' => $empresa,
                    'sucursal' => $row['sucursal'],
                    'existencia' => $saldo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $creados++;
            }
        });

        $this->consultar();
        $this->emit('item-added', "Inventario igualado al ultimo kardex. Actualizados: {$actualizados}, creados: {$creados}");
    }

    private function getFuenteCompras(): array
    {
        $rows = DB::table('compras as c')
            ->join('compras_detalles as cd', 'cd.compra', '=', 'c.id')
            ->select(
                'cd.producto',
                'c.sucursal',
                DB::raw('SUM(COALESCE(NULLIF(cd.ingreso, 0), cd.cantidad, 0)) as qty')
            )
            ->whereBetween(DB::raw('DATE(c.fecha)'), [$this->dateFrom, $this->dateTo])
            ->whereRaw("TRIM(LOWER(COALESCE(c.estado, ''))) <> 'anulado'")
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('c.sucursal', $this->sucursal))
            ->groupBy('cd.producto', 'c.sucursal')
            ->get();

        return $this->mapByKey($rows);
    }

    private function getFuenteAjustes(): array
    {
        $rows = DB::table('ajustes as a')
            ->join('ajustes_detalles as ad', 'ad.ajuste', '=', 'a.id')
            ->select(
                'ad.producto',
                'a.sucursal',
                DB::raw("
                    SUM(
                        CASE
                            WHEN TRIM(LOWER(COALESCE(a.tipo, ''))) = 'ingreso'
                                THEN COALESCE(NULLIF(ad.ingreso, 0), ad.cantidad, 0)
                            ELSE 0
                        END
                    ) as qty_ingreso
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN TRIM(LOWER(COALESCE(a.tipo, ''))) = 'egreso'
                                THEN COALESCE(NULLIF(ad.ingreso, 0), ad.cantidad, 0)
                            ELSE 0
                        END
                    ) as qty_egreso
                ")
            )
            ->whereBetween(DB::raw('DATE(a.fecha)'), [$this->dateFrom, $this->dateTo])
            ->whereNull('ad.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereRaw("TRIM(LOWER(COALESCE(a.status, ''))) = 'realizado'")
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('a.sucursal', $this->sucursal))
            ->groupBy('ad.producto', 'a.sucursal')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$this->makeKey($row->producto, $row->sucursal)] = [
                'ingreso' => (float) $row->qty_ingreso,
                'egreso' => (float) $row->qty_egreso,
            ];
        }

        return $map;
    }

    private function getFuenteSolicitudes(): array
    {
        $inRows = DB::table('solicitudes as s')
            ->join('solicitudes_detalles as sd', 'sd.solicitud', '=', 's.id')
            ->select(
                'sd.producto',
                'sd.destino as sucursal',
                DB::raw('SUM(COALESCE(NULLIF(sd.descargar, 0), sd.cantidad, 0)) as qty')
            )
            ->whereBetween(DB::raw('DATE(s.fecha)'), [$this->dateFrom, $this->dateTo])
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereRaw("TRIM(LOWER(COALESCE(sd.estado, ''))) = 'finalizado'")
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('sd.destino', $this->sucursal))
            ->groupBy('sd.producto', 'sd.destino')
            ->get();

        $outRows = DB::table('solicitudes as s')
            ->join('solicitudes_detalles as sd', 'sd.solicitud', '=', 's.id')
            ->select(
                'sd.producto',
                'sd.origen as sucursal',
                DB::raw('SUM(COALESCE(NULLIF(sd.descargar, 0), sd.cantidad, 0)) as qty')
            )
            ->whereBetween(DB::raw('DATE(s.fecha)'), [$this->dateFrom, $this->dateTo])
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereRaw("TRIM(LOWER(COALESCE(sd.estado, ''))) IN ('despachado', 'finalizado')")
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('sd.origen', $this->sucursal))
            ->groupBy('sd.producto', 'sd.origen')
            ->get();

        $map = [];

        foreach ($inRows as $row) {
            $key = $this->makeKey($row->producto, $row->sucursal);
            $map[$key]['in'] = (float) (($map[$key]['in'] ?? 0) + (float) $row->qty);
        }

        foreach ($outRows as $row) {
            $key = $this->makeKey($row->producto, $row->sucursal);
            $map[$key]['out'] = (float) (($map[$key]['out'] ?? 0) + (float) $row->qty);
        }

        return $map;
    }

    private function getFuenteVentas(): array
    {
        $rows = DB::table('ventas as v')
            ->join('ventas_detalles as vd', 'vd.venta', '=', 'v.id')
            ->select(
                'vd.producto',
                'v.sucursal',
                DB::raw('SUM(COALESCE(NULLIF(vd.descargar, 0), vd.cantidad, 0)) as qty')
            )
            ->whereBetween(DB::raw('DATE(v.fecha)'), [$this->dateFrom, $this->dateTo])
            ->whereRaw("TRIM(LOWER(COALESCE(v.estado, ''))) NOT IN ('cancelado', 'anulado', 'eliminado')")
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('v.sucursal', $this->sucursal))
            ->groupBy('vd.producto', 'v.sucursal')
            ->get();

        $map = $this->mapByKey($rows);

        return $map;
    }

    private function getKardexNetoByDescripcion(array $patterns): array
    {
        $query = DB::table('kardexes as k')
            ->join('inventarios as i', 'i.id', '=', 'k.inventario')
            ->select(
                'k.producto',
                'i.sucursal',
                DB::raw('SUM(COALESCE(k.ingresoCantidad, 0) - COALESCE(k.egresoCantidad, 0)) as qty')
            )
            ->whereBetween(DB::raw('DATE(k.fecha)'), [$this->dateFrom, $this->dateTo])
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('i.sucursal', $this->sucursal))
            ->where(function ($q) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $q->orWhere('k.descripcion', 'like', $pattern);
                }
            })
            ->groupBy('k.producto', 'i.sucursal');

        return $this->mapByKey($query->get());
    }

    private function getLatestKardexSaldo(): array
    {
        $latestIds = DB::table('kardexes as k')
            ->join('inventarios as i', 'i.id', '=', 'k.inventario')
            ->select(
                'k.producto',
                'i.sucursal',
                DB::raw('MAX(k.id) as kardex_id')
            )
            ->when((int) $this->sucursal > 0, fn($q) => $q->where('i.sucursal', $this->sucursal))
            ->groupBy('k.producto', 'i.sucursal')
            ->get();

        if ($latestIds->isEmpty()) {
            return [];
        }

        $ids = $latestIds->pluck('kardex_id')->all();

        $rows = DB::table('kardexes as k')
            ->join('inventarios as i', 'i.id', '=', 'k.inventario')
            ->select('k.producto', 'i.sucursal', 'k.saldoCantidad')
            ->whereIn('k.id', $ids)
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$this->makeKey($row->producto, $row->sucursal)] = [
                'saldo' => (float) $row->saldoCantidad,
            ];
        }

        return $map;
    }

    private function getInventariosByKeys(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $query = DB::table('inventarios')
            ->select('id as inventario_id', 'producto', 'sucursal', 'existencia');

        $query->where(function ($q) use ($keys) {
            foreach ($keys as $key) {
                [$producto, $sucursal] = explode('|', $key);
                $q->orWhere(function ($sub) use ($producto, $sucursal) {
                    $sub->where('producto', $producto)->where('sucursal', $sucursal);
                });
            }
        });

        $rows = $query->get();
        $map = [];

        foreach ($rows as $row) {
            $map[$this->makeKey($row->producto, $row->sucursal)] = [
                'inventario_id' => $row->inventario_id,
                'existencia' => (float) $row->existencia,
            ];
        }

        return $map;
    }

    private function getProductosInfoByKeys(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $query = DB::table('productos as p')
            ->join('inventarios as i', 'i.producto', '=', 'p.id')
            ->select('p.id as producto', 'i.sucursal', 'p.codebar3', 'p.nombreProducto');

        $query->where(function ($q) use ($keys) {
            foreach ($keys as $key) {
                [$producto, $sucursal] = explode('|', $key);
                $q->orWhere(function ($sub) use ($producto, $sucursal) {
                    $sub->where('p.id', $producto)->where('i.sucursal', $sucursal);
                });
            }
        });

        $rows = $query->get();
        $map = [];

        foreach ($rows as $row) {
            $map[$this->makeKey($row->producto, $row->sucursal)] = [
                'codebar3' => $row->codebar3,
                'nombreProducto' => $row->nombreProducto,
            ];
        }

        return $map;
    }

    private function mapByKey($rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[$this->makeKey($row->producto, $row->sucursal)] = (float) $row->qty;
        }

        return $map;
    }

    private function makeKey($producto, $sucursal): string
    {
        return $producto . '|' . $sucursal;
    }

    private function autorizarAccion(string $permission): void
    {
        abort_unless(Auth::user()->can($permission), 403);
    }
}
