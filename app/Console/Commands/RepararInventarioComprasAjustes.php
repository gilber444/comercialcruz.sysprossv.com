<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class RepararInventarioComprasAjustes extends Command
{
    protected $signature = 'reparar:inventario-compras-ajustes
        {--desde= : Fecha inicio (Y-m-d), por defecto 27 de junio del año actual}
        {--hasta= : Fecha fin (Y-m-d), por defecto 2 de julio del año actual}
        {--sucursal= : ID de sucursal a reparar (por defecto todas)}
        {--campo-existencia=existencia : Columna de existencia en inventarios (existencia|stock)}
        {--forzar : Resetear existencia a 0 y kardex del período antes de re aplicar}
        {--solo-compras : Solo re aplicar compras}
        {--solo-ajustes : Solo re aplicar ajustes}
        {--debug : Mostrar SQL y resultados parciales para depuración}';

    protected $description = 'Re-aplica compras y ajustes al inventario para reparar existencias en un rango de fechas';

    public function handle()
    {
        $anio = now()->year;
        $desde = $this->option('desde') ?: "{$anio}-06-27";
        $hasta = $this->option('hasta') ?: "{$anio}-07-02";
        $sucursal = $this->option('sucursal') ? (int) $this->option('sucursal') : null;
        $campoExistencia = $this->option('campo-existencia') ?: 'existencia';
        $forzar = $this->option('forzar');
        $soloCompras = $this->option('solo-compras');
        $soloAjustes = $this->option('solo-ajustes');
        $debug = $this->option('debug');

        if (!Schema::hasTable('inventarios') || !Schema::hasColumn('inventarios', $campoExistencia)) {
            $this->error("La columna '{$campoExistencia}' no existe en inventarios.");
            return self::FAILURE;
        }

        $this->info("Reparación de inventario desde {$desde} hasta {$hasta}");
        if ($sucursal) {
            $this->info("Sucursal objetivo: {$sucursal}");
        }
        $this->info("Campo de existencia: {$campoExistencia}");

        DB::beginTransaction();

        try {
            if ($forzar) {
                $this->resetearInventarioYkardex($desde, $hasta, $sucursal, $campoExistencia);
            }

            if (!$soloAjustes) {
                $this->reaplicarCompras($desde, $hasta, $sucursal, $campoExistencia, $forzar, $debug);
            }

            if (!$soloCompras) {
                $this->reaplicarAjustes($desde, $hasta, $sucursal, $campoExistencia, $forzar, $debug);
            }

            DB::commit();
            $this->info('✔ Reparación finalizada.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('✘ Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    protected function resetearInventarioYkardex(string $desde, string $hasta, ?int $sucursal, string $campoExistencia): void
    {
        $this->warn('⚠ Modo forzar: se reseteará existencia a 0 y se eliminarán kardexes del período para la sucursal objetivo.');

        // Resetear existencia de la sucursal (o todas)
        $query = DB::table('inventarios');
        if ($sucursal) {
            $query->where('sucursal', $sucursal);
        }
        $afectados = $query->update([$campoExistencia => 0]);
        $this->info("   ↻ Inventarios reseteados a 0: {$afectados}");

        // Borrar kardexes del período para la sucursal objetivo
        $subQuery = DB::table('kardexes as k')
            ->join('inventarios as i', 'i.id', '=', 'k.inventario')
            ->where('k.fecha', '>=', $desde)
            ->where('k.fecha', '<=', $hasta);

        if ($sucursal) {
            $subQuery->where('i.sucursal', $sucursal);
        }

        $ids = $subQuery->pluck('k.id');
        if ($ids->isNotEmpty()) {
            $eliminados = DB::table('kardexes')->whereIn('id', $ids)->delete();
            $this->info("   ↻ Kardexes eliminados del período: {$eliminados}");
        }
    }

    protected function reaplicarCompras(string $desde, string $hasta, ?int $sucursal, string $campoExistencia, bool $forzar, bool $debug = false): void
    {
        $this->info('→ Re-aplicando compras...');

        $query = DB::table('compras as c')
            ->join('compras_detalles as cd', 'cd.compra', '=', 'c.id')
            ->where('c.fecha', '>=', $desde . ' 00:00:00')
            ->where('c.fecha', '<=', $hasta . ' 23:59:59')
            ->where('c.estado', '!=', 'Anulado')
            ->select(
                'c.id as compra_id',
                'c.sucursal',
                'c.correlativo',
                'c.fecha',
                'cd.id as detalle_id',
                'cd.producto',
                'cd.medida',
                'cd.cantidad',
                'cd.ingreso',
                'cd.costo',
                'cd.total'
            )
            ->orderBy('c.fecha')
            ->orderBy('c.id')
            ->orderBy('cd.id');

        if ($sucursal) {
            $query->where('c.sucursal', $sucursal);
        }

        if ($debug) {
            $this->line('   SQL: ' . $query->toSql());
            $this->line('   Bindings: ' . json_encode($query->getBindings()));
        }

        $detalles = $query->get();
        $this->line("   Encontrados {$detalles->count()} detalles de compra para procesar.");

        if ($debug && $detalles->isEmpty()) {
            $this->warn('   No se encontraron compras. Estados disponibles en el rango:');
            $estados = DB::table('compras')
                ->where('fecha', '>=', $desde . ' 00:00:00')
                ->where('fecha', '<=', $hasta . ' 23:59:59')
                ->when($sucursal, fn($q) => $q->where('sucursal', $sucursal))
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get();
            foreach ($estados as $e) {
                $this->line("     - estado={$e->estado}: {$e->total}");
            }
        }

        $procesados = 0;

        foreach ($detalles as $det) {
            $cantidadIngreso = (float) ($det->ingreso ?: $det->cantidad);

            // Si no es forzar, verificar si ya existe kardex para este detalle de compra
            if (!$forzar) {
                $existe = DB::table('kardexes as k')
                    ->join('inventarios as i', 'i.id', '=', 'k.inventario')
                    ->where('k.producto', $det->producto)
                    ->where('i.sucursal', $det->sucursal)
                    ->where('k.descripcion', 'like', '%Compra de productos a%Factura ' . $det->correlativo . '%')
                    ->exists();

                if ($existe) {
                    continue;
                }
            }

            $inventario = $this->obtenerOcrearInventario($det->producto, $det->sucursal, $campoExistencia);

            $nuevaExistencia = (float) $inventario->{$campoExistencia} + $cantidadIngreso;

            DB::table('inventarios')
                ->where('id', $inventario->id)
                ->update([$campoExistencia => $nuevaExistencia]);

            $this->crearKardex(
                $det->producto,
                $inventario->id,
                $det->sucursal,
                'Compra de productos - Factura ' . $det->correlativo . ' (reparación)',
                $det->fecha,
                $cantidadIngreso,
                (float) $det->costo * $cantidadIngreso,
                0,
                0,
                $nuevaExistencia,
                (float) $det->costo * $nuevaExistencia
            );

            $procesados++;
        }

        $this->info("   ✔ Compras re-aplicadas: {$procesados} detalles");
    }

    protected function reaplicarAjustes(string $desde, string $hasta, ?int $sucursal, string $campoExistencia, bool $forzar, bool $debug = false): void
    {
        $this->info('→ Re-aplicando ajustes...');

        $query = DB::table('ajustes as a')
            ->join('ajustes_detalles as ad', 'ad.ajuste', '=', 'a.id')
            ->where('a.fecha', '>=', $desde)
            ->where('a.fecha', '<=', $hasta)
            ->where('a.status', 'Realizado')
            ->whereNull('a.deleted_at')
            ->select(
                'a.id as ajuste_id',
                'a.sucursal',
                'a.fecha',
                'a.tipo',
                'ad.id as detalle_id',
                'ad.producto',
                'ad.inventario',
                'ad.cantidad',
                'ad.ingreso',
                'ad.costo',
                'ad.total'
            )
            ->orderBy('a.fecha')
            ->orderBy('a.id')
            ->orderBy('ad.id');

        if ($sucursal) {
            $query->where('a.sucursal', $sucursal);
        }

        if ($debug) {
            $this->line('   SQL: ' . $query->toSql());
            $this->line('   Bindings: ' . json_encode($query->getBindings()));
        }

        $detalles = $query->get();
        $this->line("   Encontrados {$detalles->count()} detalles de ajuste para procesar.");

        if ($debug && $detalles->isEmpty()) {
            $this->warn('   No se encontraron ajustes. Status disponibles en el rango:');
            $status = DB::table('ajustes')
                ->where('fecha', '>=', $desde)
                ->where('fecha', '<=', $hasta)
                ->when($sucursal, fn($q) => $q->where('sucursal', $sucursal))
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get();
            foreach ($status as $s) {
                $this->line("     - status={$s->status}: {$s->total}");
            }
        }

        $procesados = 0;

        foreach ($detalles as $det) {
            $cantidadAjuste = (float) ($det->ingreso ?: $det->cantidad);
            $esIngreso = strcasecmp($det->tipo, 'Ingreso') === 0;

            // Si no es forzar, verificar si ya existe kardex para este ajuste
            if (!$forzar) {
                $existe = DB::table('kardexes')
                    ->where('producto', $det->producto)
                    ->where('inventario', $det->inventario)
                    ->where('descripcion', 'like', '%Ajuste #' . $det->ajuste_id . '%')
                    ->exists();

                if ($existe) {
                    continue;
                }
            }

            $inventario = DB::table('inventarios')
                ->where('id', $det->inventario)
                ->where('sucursal', $det->sucursal)
                ->first();

            if (!$inventario) {
                $this->warn("   ⚠ No existe inventario id={$det->inventario} para ajuste detalle {$det->detalle_id}. Saltando.");
                continue;
            }

            $existenciaActual = (float) $inventario->{$campoExistencia};
            $nuevaExistencia = $esIngreso
                ? $existenciaActual + $cantidadAjuste
                : max(0, $existenciaActual - $cantidadAjuste);

            DB::table('inventarios')
                ->where('id', $inventario->id)
                ->update([$campoExistencia => $nuevaExistencia]);

            $ultimoKardex = DB::table('kardexes')
                ->where('producto', $det->producto)
                ->where('inventario', $inventario->id)
                ->latest()
                ->first();

            $valorEgreso = $esIngreso ? 0 : (float) $det->costo * $cantidadAjuste;
            $valorIngreso = $esIngreso ? (float) $det->costo * $cantidadAjuste : 0;
            $saldoValor = $ultimoKardex
                ? max(0, (float) $ultimoKardex->saldoValor + ($esIngreso ? $valorIngreso : -$valorEgreso))
                : (float) $det->costo * $nuevaExistencia;

            $this->crearKardex(
                $det->producto,
                $inventario->id,
                $det->sucursal,
                ($esIngreso ? 'Ingreso' : 'Egreso') . ' por Ajuste #' . $det->ajuste_id . ' (reparación)',
                $det->fecha,
                $esIngreso ? $cantidadAjuste : 0,
                $valorIngreso,
                $esIngreso ? 0 : $cantidadAjuste,
                $valorEgreso,
                $nuevaExistencia,
                $saldoValor
            );

            $procesados++;
        }

        $this->info("   ✔ Ajustes re-aplicados: {$procesados} detalles");
    }

    protected function obtenerOcrearInventario(int $producto, int $sucursal, string $campoExistencia)
    {
        $inventario = DB::table('inventarios')
            ->where('producto', $producto)
            ->where('sucursal', $sucursal)
            ->first();

        if ($inventario) {
            return $inventario;
        }

        $sucursalData = DB::table('sucursales')->where('id', $sucursal)->first(['empresa']);
        $empresa = $sucursalData ? $sucursalData->empresa : null;

        $id = DB::table('inventarios')->insertGetId([
            'producto' => $producto,
            'sucursal' => $sucursal,
            'empresa' => $empresa,
            $campoExistencia => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('inventarios')->where('id', $id)->first();
    }

    protected function crearKardex(
        int $producto,
        int $inventario,
        int $sucursal,
        string $descripcion,
        string $fecha,
        float $ingresoCantidad,
        float $ingresoValor,
        float $egresoCantidad,
        float $egresoValor,
        float $saldoCantidad,
        float $saldoValor
    ): void {
        $campos = [
            'producto' => $producto,
            'inventario' => $inventario,
            'descripcion' => $descripcion,
            'fecha' => $fecha,
            'hora' => now()->format('H:i:s'),
            'ingresoCantidad' => $ingresoCantidad,
            'ingresoValor' => $ingresoValor,
            'egresoCantidad' => $egresoCantidad,
            'egresoValor' => $egresoValor,
            'saldoCantidad' => $saldoCantidad,
            'saldoValor' => $saldoValor,
            'sincro_id'  => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('kardexes', 'sucursal')) {
            $campos['sucursal'] = $sucursal;
        }

        DB::table('kardexes')->insert($campos);
    }
}
