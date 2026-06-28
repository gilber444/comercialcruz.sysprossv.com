<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepararAjustesDetallesEnCero extends Command
{
    protected $signature = 'ajustes:reparar-detalles-cero
        {--confirm : Ejecutar la reparacion (sin esto solo muestra lo que encontraria)}
        {--sucursal= : ID de sucursal para filtrar ajustes}
        {--todos : Comparar TODOS los detalles de ajustes finalizados, no solo los en 0}
        {--por-conteo : Buscar ajustes donde falten detalles (conteo local != conteo VPS)}';

    protected $description = 'Repara detalles de ajustes locales que quedaron en 0 o faltan comparando con el VPS';

    protected string $connRemote = 'vpsmysql';
    protected string $connLocal  = 'localmysql';

    public function handle()
    {
        $confirm     = $this->option('confirm');
        $sucursal    = $this->option('sucursal');
        $todos       = $this->option('todos');
        $porConteo   = $this->option('por-conteo');

        if (!Schema::connection($this->connLocal)->hasTable('ajustes')
            || !Schema::connection($this->connLocal)->hasTable('ajustes_detalles')) {
            $this->error('Faltan tablas locales (ajustes / ajustes_detalles)');
            return self::FAILURE;
        }

        // Verificar conexion al VPS
        try {
            DB::connection($this->connRemote)->getPdo();
        } catch (\Throwable $e) {
            $this->error('No se puede conectar al VPS: ' . $e->getMessage());
            return self::FAILURE;
        }

        // =====================================================================
        // MODO POR CONTEO: Buscar ajustes donde falten detalles
        // =====================================================================
        if ($porConteo) {
            return $this->repararPorConteo($confirm, $sucursal);
        }

        // =====================================================================
        // MODO NORMAL: Buscar detalles con valores en 0
        // =====================================================================
        if ($todos) {
            $this->info('Modo TODOS: Comparando TODOS los detalles de ajustes finalizados con el VPS...');
        } else {
            $this->info('Buscando detalles de ajustes locales con cantidad/ingreso en 0 o null...');
        }
        $this->newLine();

        // Buscar detalles locales
        $query = DB::connection($this->connLocal)
            ->table('ajustes_detalles as ad')
            ->join('ajustes as a', 'a.id', '=', 'ad.ajuste')
            ->select([
                'ad.id as detalle_local_id',
                'ad.sincro_id as detalle_sincro_id',
                'ad.ajuste as ajuste_local_id',
                'ad.producto',
                'ad.cantidad',
                'ad.ingreso',
                'ad.costo',
                'ad.total',
                'a.sincro_id as ajuste_sincro_id',
                'a.sucursal',
                'a.status',
                'a.aplicado_local',
                'a.tipo',
            ])
            ->whereNotNull('ad.sincro_id')
            ->where('ad.sincro_id', '!=', '')
            ->when($sucursal, fn($q) => $q->where('a.sucursal', $sucursal));

        if (!$todos) {
            // Modo normal: solo los que estan en 0 o null
            $query->where(function ($q) {
                $q->where('ad.cantidad', 0)
                  ->orWhere('ad.cantidad', 0.00)
                  ->orWhereNull('ad.cantidad')
                  ->orWhere('ad.ingreso', 0)
                  ->orWhere('ad.ingreso', 0.00)
                  ->orWhereNull('ad.ingreso')
                  ->orWhere('ad.producto', 0)
                  ->orWhereNull('ad.producto')
                  ->orWhere('ad.total', 0)
                  ->orWhere('ad.total', 0.00)
                  ->orWhereNull('ad.total');
            });
        } else {
            // Modo todos: solo ajustes finalizados
            $query->where(function ($q) {
                $q->where('a.status', 'Finalizado')
                  ->orWhereNotNull('a.aplicado_local');
            });
        }

        $detallesEnCero = $query->get();

        if ($detallesEnCero->isEmpty()) {
            $this->info('No se encontraron detalles para revisar. Todo limpio.');
            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$detallesEnCero->count()} detalles locales para revisar:");
        $this->newLine();

        return $this->procesarReparacion($detallesEnCero, $confirm);
    }

    /**
     * Busca ajustes donde el conteo de detalles local difiera del VPS
     */
    protected function repararPorConteo(bool $confirm, ?string $sucursal): int
    {
        $this->info('Modo POR CONTEO: Buscando ajustes donde falten detalles...');
        $this->newLine();

        // 1. Traer ajustes finalizados locales con sus conteos
        $ajustesLocales = DB::connection($this->connLocal)
            ->table('ajustes as a')
            ->selectRaw('a.id, a.sincro_id, a.sucursal, a.status, COUNT(ad.id) as total_detalles')
            ->leftJoin('ajustes_detalles as ad', 'ad.ajuste', '=', 'a.id')
            ->whereNotNull('a.sincro_id')
            ->where('a.sincro_id', '!=', '')
            ->where(function ($q) {
                $q->where('a.status', 'Finalizado')
                  ->orWhereNotNull('a.aplicado_local');
            })
            ->when($sucursal, fn($q) => $q->where('a.sucursal', $sucursal))
            ->groupBy('a.id', 'a.sincro_id', 'a.sucursal', 'a.status')
            ->get();

        if ($ajustesLocales->isEmpty()) {
            $this->info('No se encontraron ajustes finalizados para revisar.');
            return self::SUCCESS;
        }

        $this->info("Revisando {$ajustesLocales->count()} ajustes locales...");
        $this->newLine();
        
        // Debug: mostrar algunos ajustes con 0 detalles
        $ajustesConCeroDetalles = array_filter($ajustesLocales->toArray(), fn($a) => $a->total_detalles == 0);
        if (!empty($ajustesConCeroDetalles)) {
            $this->warn('Ajustes con 0 detalles en local: ' . count($ajustesConCeroDetalles));
            foreach (array_slice($ajustesConCeroDetalles, 0, 5) as $a) {
                $this->line("  - Ajuste #{$a->id}, sincro_id=" . substr($a->sincro_id, 0, 8) . "..., status={$a->status}");
            }
            $this->newLine();
        }

        // 2. Para cada ajuste, comparar conteo con VPS
        $ajustesConDiferencia = [];

        foreach ($ajustesLocales as $ajusteLocal) {
            // Obtener los sincro_id de los detalles locales de este ajuste
            $detallesLocalSids = DB::connection($this->connLocal)
                ->table('ajustes_detalles')
                ->where('ajuste', $ajusteLocal->id)
                ->whereNotNull('sincro_id')
                ->where('sincro_id', '!=', '')
                ->pluck('sincro_id')
                ->all();

            // Buscar en el VPS cuantos detalles con esos sincro_id existen
            $conteoVps = 0;
            if (!empty($detallesLocalSids)) {
                $conteoVps = DB::connection($this->connRemote)
                    ->table('ajustes_detalles')
                    ->whereIn('sincro_id', $detallesLocalSids)
                    ->count();
            }

            // Si el conteo VPS es menor que el local, algo raro paso (no deberia)
            // Si es igual, todo bien
            // Si es mayor, faltan detalles en local (pero esto no deberia pasar con sincro_id)
            // La logica real: si hay detalles en el VPS que NO estan en local

            // Buscar TODOS los detalles del ajuste en VPS (por ajuste_id_vps o por sincro_id del padre)
            $ajusteVps = DB::connection($this->connRemote)
                ->table('ajustes')
                ->where('sincro_id', $ajusteLocal->sincro_id)
                ->first(['id']);

            $todosDetallesVps = [];
            if ($ajusteVps) {
                $todosDetallesVps = DB::connection($this->connRemote)
                    ->table('ajustes_detalles')
                    ->where('ajuste', $ajusteVps->id)
                    ->whereNotNull('sincro_id')
                    ->where('sincro_id', '!=', '')
                    ->get(['sincro_id', 'producto', 'cantidad', 'ingreso', 'costo', 'total'])
                    ->all();
            }

            $conteoVpsTotal = count($todosDetallesVps);

            if ($conteoVpsTotal > $ajusteLocal->total_detalles) {
                $ajustesConDiferencia[] = [
                    'ajuste_id'       => $ajusteLocal->id,
                    'sincro_id'       => $ajusteLocal->sincro_id,
                    'sucursal'        => $ajusteLocal->sucursal,
                    'status'          => $ajusteLocal->status,
                    'local_detalles'  => $ajusteLocal->total_detalles,
                    'vps_detalles'    => $conteoVpsTotal,
                    'faltan'          => $conteoVpsTotal - $ajusteLocal->total_detalles,
                    'detalles_vps'    => $todosDetallesVps,
                    'detalles_local_sids' => $detallesLocalSids,
                ];
            }
        }

        if (empty($ajustesConDiferencia)) {
            $this->info('No se encontraron ajustes con diferencia de conteo. Todo limpio.');
            return self::SUCCESS;
        }

        $this->warn('Se encontraron ' . count($ajustesConDiferencia) . ' ajustes con detalles faltantes:');
        $this->newLine();

        $headers = ['Ajuste ID', 'Sincro ID', 'Sucursal', 'Local Det', 'VPS Det', 'Faltan'];
        $rows = [];
        foreach ($ajustesConDiferencia as $a) {
            $rows[] = [
                $a['ajuste_id'],
                substr($a['sincro_id'], 0, 8) . '...',
                $a['sucursal'],
                $a['local_detalles'],
                $a['vps_detalles'],
                $a['faltan'],
            ];
        }
        $this->table($headers, $rows);

        if (!$confirm) {
            $this->newLine();
            $this->warn('Esto fue solo una vista previa. Para reparar usa:');
            $this->line('  php artisan ajustes:reparar-detalles-cero --por-conteo --confirm');
            return self::SUCCESS;
        }

        // Reparar: insertar los detalles faltantes
        $reparados = 0;
        foreach ($ajustesConDiferencia as $ajusteInfo) {
            $this->info("Reparando ajuste #{$ajusteInfo['ajuste_id']}...");

            // Usar los detalles del VPS que ya obtuvimos
            foreach ($ajusteInfo['detalles_vps'] as $detVps) {
                // Verificar si ya existe en local por sincro_id
                $existeLocal = in_array($detVps->sincro_id, $ajusteInfo['detalles_local_sids']);

                if (!$existeLocal) {
                    // Traer el detalle completo del VPS
                    $detalleVpsCompleto = DB::connection($this->connRemote)
                        ->table('ajustes_detalles')
                        ->where('sincro_id', $detVps->sincro_id)
                        ->first();

                    if ($detalleVpsCompleto) {
                        // Verificar si el sincro_id ya existe en local (pero con otro ajuste_id)
                        $detalleExistente = DB::connection($this->connLocal)
                            ->table('ajustes_detalles')
                            ->where('sincro_id', $detVps->sincro_id)
                            ->first();

                        if ($detalleExistente) {
                            // Resolver inventario local
                            $inventarioLocalId = $this->resolverInventarioLocal((int)$detalleVpsCompleto->inventario, $ajusteInfo['sucursal']);

                            // Actualizar el detalle existente (cambiar ajuste y valores)
                            DB::connection($this->connLocal)
                                ->table('ajustes_detalles')
                                ->where('id', $detalleExistente->id)
                                ->update([
                                    'ajuste'         => $ajusteInfo['ajuste_id'],
                                    'producto'       => $detalleVpsCompleto->producto,
                                    'inventario'     => $inventarioLocalId ?? $detalleExistente->inventario,
                                    'medida'         => $detalleVpsCompleto->medida,
                                    'cantidad'       => $detalleVpsCompleto->cantidad,
                                    'ingreso'        => $detalleVpsCompleto->ingreso,
                                    'costo'          => $detalleVpsCompleto->costo,
                                    'total'          => $detalleVpsCompleto->total,
                                    'status'         => 'Finalizado',
                                    'aplicado_local' => now(),
                                    'updated_at'     => now(),
                                ]);
                            $this->line("  ~ Detalle actualizado: producto={$detVps->producto}, cant={$detVps->cantidad}, ing={$detVps->ingreso}");
                            $reparados++;
                        } else {
                            // Resolver inventario local
                            $inventarioLocalId = $this->resolverInventarioLocal((int)$detalleVpsCompleto->inventario, $ajusteInfo['sucursal']);

                            // Insertar el detalle faltante
                            $data = (array) $detalleVpsCompleto;
                            unset($data['id']);
                            $data['ajuste'] = $ajusteInfo['ajuste_id'];
                            $data['inventario'] = $inventarioLocalId ?? $data['inventario'];
                            $data['status'] = 'Finalizado';
                            $data['aplicado_local'] = now();
                            $data['created_at'] = $data['created_at'] ?? now();
                            $data['updated_at'] = now();

                            DB::connection($this->connLocal)->table('ajustes_detalles')->insert($data);
                            $this->line("  + Detalle insertado: producto={$detVps->producto}, cant={$detVps->cantidad}, ing={$detVps->ingreso}");
                            $reparados++;
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->info("✔ Reparacion por conteo completa: {$reparados} detalles insertados.");
        return self::SUCCESS;
    }

    /**
     * Procesa la reparacion de detalles con valores en 0
     */
    protected function procesarReparacion($detallesEnCero, bool $confirm): int
    {
        $aReparar = [];
        $yaCorrectos = 0;
        $noEncontrados = 0;
        $vpsTambienEnCero = 0;

        foreach ($detallesEnCero as $det) {
            // Buscar el detalle en el VPS por sincro_id
            $detalleVps = DB::connection($this->connRemote)
                ->table('ajustes_detalles')
                ->where('sincro_id', $det->detalle_sincro_id)
                ->first(['id', 'producto', 'cantidad', 'ingreso', 'costo', 'total', 'ajuste']);

            if (!$detalleVps) {
                $noEncontrados++;
                continue;
            }

            $localCantidad = (float) ($det->cantidad ?? 0);
            $localIngreso  = (float) ($det->ingreso ?? 0);
            $localCosto    = (float) ($det->costo ?? 0);
            $localTotal    = (float) ($det->total ?? 0);

            $vpsCantidad = (float) ($detalleVps->cantidad ?? 0);
            $vpsIngreso  = (float) ($detalleVps->ingreso ?? 0);
            $vpsCosto    = (float) ($detalleVps->costo ?? 0);
            $vpsTotal    = (float) ($detalleVps->total ?? 0);

            // Si ya esta correcto, saltar
            if (abs($localCantidad - $vpsCantidad) < 0.01
                && abs($localIngreso - $vpsIngreso) < 0.01
                && abs($localCosto - $vpsCosto) < 0.01
                && abs($localTotal - $vpsTotal) < 0.01
                && (int)$det->producto == (int)$detalleVps->producto) {
                $yaCorrectos++;
                continue;
            }

            // Si el VPS tambien tiene 0 en todo, no se puede reparar
            if ($vpsCantidad == 0 && $vpsIngreso == 0 && $vpsTotal == 0 && (int)$detalleVps->producto == 0) {
                $vpsTambienEnCero++;
                $this->line("  ⚠ sincro_id={$det->detalle_sincro_id}: VPS tambien en 0 (prod={$detalleVps->producto}, cant={$vpsCantidad}, ing={$vpsIngreso})");
                continue;
            }

            $difCantidad = $vpsCantidad - $localCantidad;
            $difIngreso  = $vpsIngreso - $localIngreso;

            $aReparar[] = [
                'detalle_local_id' => $det->detalle_local_id,
                'ajuste_local_id'  => $det->ajuste_local_id,
                'sincro_id'        => $det->detalle_sincro_id,
                'producto'         => $det->producto,
                'tipo'             => $det->tipo,
                'local_cantidad'   => $localCantidad,
                'local_ingreso'    => $localIngreso,
                'local_costo'      => $localCosto,
                'local_total'      => $localTotal,
                'vps_cantidad'     => $vpsCantidad,
                'vps_ingreso'      => $vpsIngreso,
                'vps_costo'        => $vpsCosto,
                'vps_total'        => $vpsTotal,
                'dif_cantidad'     => $difCantidad,
                'dif_ingreso'      => $difIngreso,
                'ajuste_status'    => $det->status,
                'aplicado_local'   => $det->aplicado_local,
            ];
        }

        $this->info("Resumen de revision:");
        $this->line("  - Detalles revisados: {$detallesEnCero->count()}");
        $this->line("  - Ya correctos: {$yaCorrectos}");
        $this->line("  - No encontrados en VPS: {$noEncontrados}");
        $this->line("  - VPS tambien en 0: {$vpsTambienEnCero}");
        $this->line("  - A reparar: " . count($aReparar));
        $this->newLine();

        if (empty($aReparar)) {
            $this->info('No hay detalles que necesiten reparacion.');
            return self::SUCCESS;
        }

        // Mostrar tabla de lo que se va a reparar
        $headers = ['Ajuste ID', 'Detalle ID', 'Prod', 'Local Cant', 'Local Ing', 'VPS Cant', 'VPS Ing', 'Dif Cant', 'Dif Ing', 'Status'];
        $rows = [];
        foreach ($aReparar as $r) {
            $rows[] = [
                $r['ajuste_local_id'],
                $r['detalle_local_id'],
                $r['producto'],
                $r['local_cantidad'],
                $r['local_ingreso'],
                $r['vps_cantidad'],
                $r['vps_ingreso'],
                $r['dif_cantidad'],
                $r['dif_ingreso'],
                $r['ajuste_status'] ?? 'N/A',
            ];
        }
        $this->table($headers, $rows);

        $this->newLine();
        $this->warn('Total a reparar: ' . count($aReparar) . ' detalles');

        if (!$confirm) {
            $this->newLine();
            $this->warn('Esto fue solo una vista previa. Para ejecutar la reparacion usa:');
            $this->line('  php artisan ajustes:reparar-detalles-cero --confirm');
            $this->newLine();
            $this->warn('Para revisar TODOS los detalles de ajustes finalizados:');
            $this->line('  php artisan ajustes:reparar-detalles-cero --todos --confirm');
            $this->newLine();
            $this->warn('Para buscar por conteo de detalles faltantes:');
            $this->line('  php artisan ajustes:reparar-detalles-cero --por-conteo --confirm');
            $this->newLine();
            $this->warn('IMPORTANTE: Haz un respaldo de la base de datos antes de ejecutar.');
            return self::SUCCESS;
        }

        if (!$this->confirm('¿Estas seguro de reparar estos detalles? Se actualizaran los valores y el inventario.', false)) {
            $this->line('Operacion cancelada.');
            return self::SUCCESS;
        }

        // Ejecutar reparacion
        $reparados = 0;
        $inventarioAjustado = 0;

        DB::connection($this->connLocal)->beginTransaction();
        try {
            foreach ($aReparar as $item) {
                // 1. Actualizar el detalle local con los valores del VPS
                DB::connection($this->connLocal)
                    ->table('ajustes_detalles')
                    ->where('id', $item['detalle_local_id'])
                    ->update([
                        'cantidad'       => $item['vps_cantidad'],
                        'ingreso'        => $item['vps_ingreso'],
                        'costo'          => $item['vps_costo'],
                        'total'          => $item['vps_total'],
                        'status'         => 'Finalizado',
                        'aplicado_local' => now(),
                        'updated_at'     => now(),
                    ]);

                // 2. Si el ajuste ya fue aplicado al inventario, ajustar el inventario
                if ($item['aplicado_local'] || $item['ajuste_status'] === 'Finalizado') {
                    // Buscar el inventario del producto
                    $ajuste = DB::connection($this->connLocal)
                        ->table('ajustes')
                        ->where('id', $item['ajuste_local_id'])
                        ->first(['sucursal', 'tipo']);

                    if ($ajuste) {
                        $inventario = DB::connection($this->connLocal)
                            ->table('inventarios')
                            ->where('producto', $item['producto'])
                            ->where('sucursal', $ajuste->sucursal)
                            ->first(['id', 'existencia']);

                        if ($inventario) {
                            $esIngreso = stripos($ajuste->tipo ?? '', 'Ingreso') !== false;
                            $difCantidad = $item['dif_cantidad'];
                            $difIngreso  = $item['dif_ingreso'];
                            $difAplicar  = $difIngreso != 0 ? $difIngreso : $difCantidad;

                            if ($difAplicar != 0) {
                                $nuevaExistencia = $esIngreso
                                    ? (float) $inventario->existencia + $difAplicar
                                    : max(0, (float) $inventario->existencia - $difAplicar);

                                DB::connection($this->connLocal)
                                    ->table('inventarios')
                                    ->where('id', $inventario->id)
                                    ->update([
                                        'existencia' => $nuevaExistencia,
                                        'updated_at' => now(),
                                    ]);

                                $this->line("  ✔ Inventario #{$inventario->id} ajustado: {$inventario->existencia} → {$nuevaExistencia} (dif: {$difAplicar})");
                                $inventarioAjustado++;
                            }
                        }
                    }
                }

                $this->line("  ✔ Detalle #{$item['detalle_local_id']} sincro_id={$item['sincro_id']} reparado: cant={$item['local_cantidad']}→{$item['vps_cantidad']}, ing={$item['local_ingreso']}→{$item['vps_ingreso']}");
                $reparados++;
            }

            DB::connection($this->connLocal)->commit();

            $this->newLine();
            $this->info("✔ Reparacion completa: {$reparados} detalles reparados, {$inventarioAjustado} inventarios ajustados.");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::connection($this->connLocal)->rollBack();
            $this->error('Error durante la reparacion: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Busca el inventario local correspondiente a un inventario del VPS.
     * Primero busca por id_vps, luego por producto+sucursal, y si no existe lo crea.
     */
    protected function resolverInventarioLocal(int $inventarioVpsId, int $sucursalId): ?int
    {
        // 1. Buscar en local por id_vps
        $invLocal = DB::connection($this->connLocal)
            ->table('inventarios')
            ->where('id_vps', $inventarioVpsId)
            ->first(['id']);

        if ($invLocal) {
            return (int) $invLocal->id;
        }

        // 2. Traer el inventario del VPS para obtener producto y sucursal
        $invVps = DB::connection($this->connRemote)
            ->table('inventarios')
            ->where('id', $inventarioVpsId)
            ->first(['producto', 'sucursal']);

        if (!$invVps) {
            return null;
        }

        // 3. Buscar en local por producto + sucursal
        $invLocal = DB::connection($this->connLocal)
            ->table('inventarios')
            ->where('producto', $invVps->producto)
            ->where('sucursal', $invVps->sucursal)
            ->first(['id']);

        if ($invLocal) {
            // Guardar id_vps para futuras veces
            DB::connection($this->connLocal)
                ->table('inventarios')
                ->where('id', $invLocal->id)
                ->update(['id_vps' => $inventarioVpsId]);
            return (int) $invLocal->id;
        }

        // 4. Crear el inventario local si no existe
        $empresa = DB::connection($this->connLocal)
            ->table('sucursales')
            ->where('id', $invVps->sucursal)
            ->value('empresa');

        $nuevoId = DB::connection($this->connLocal)
            ->table('inventarios')
            ->insertGetId([
                'producto'   => $invVps->producto,
                'empresa'    => $empresa,
                'sucursal'   => $invVps->sucursal,
                'existencia' => 0.00,
                'id_vps'     => $inventarioVpsId,
                'sincro_id'  => (string) \Illuminate\Support\Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return $nuevoId;
    }
}
