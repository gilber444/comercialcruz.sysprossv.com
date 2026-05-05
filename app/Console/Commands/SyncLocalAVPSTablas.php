<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
class SyncLocalAVPSTablas extends Command
{
    protected $signature = 'sync:local-vps
        {--tables= : Lista de tablas separadas por coma (si se omite, usa el orden por defecto)}
        {--limit=1000 : Máximo de filas por tabla}
        {--sucursal-inventarios= : Sucursal local que puede subir inventario/compras/solicitudes/DTE}
        {--local=localmysql : Conexión origen}
        {--vps=vpsmysql : Conexión destino}
        {--direction=local_to_vps : Dirección para sync_states}';
    protected $description = 'Sincroniza datos desde LOCAL hacia VPS resolviendo IDs con sincro_id + id_vps';
    protected string $syncStatesTable = 'sync_states';
    /**
     * Plan por tabla:
     * - ts: columna timestamp para watermark
     * - fk: [ 'columna_fk' => 'tabla_referenciada' ]
     * - required: columnas fk que son obligatorias (si no se mapean, se salta la fila)
     */
    protected array $plans = [
        // Flujos de caja
        'remesas'    => ['ts' => 'updated_at', 'fk' => []],
        'aperturas'  => ['ts' => 'updated_at', 'fk' => []],
        'cortes'     => ['ts' => 'updated_at', 'fk' => []],
        'clientes'   => ['ts' => 'updated_at', 'fk' => []],
        // Catálogo
        'productos'  => ['ts' => 'updated_at', 'fk' => []], 
        // Padres
        'compras'      => ['ts' => 'updated_at', 'fk' => []],
        // 👇 NUEVO: Cuentas por pagar (depende de compras)
        'cuentas_pagars' => [
            'ts' => 'updated_at',
            'fk' => [
                'compra' => 'compras', // <- FK a compras.id (ajusta el nombre si tu columna difiere)
            ],
            'required' => ['compra'],   // exige que la compra exista en VPS
        ],
        // 👇 NUEVO: Pagos (depende de cuentas_pagars)
        'pagos' => [
            'ts' => 'updated_at',
            'fk' => [
                'cuenta_pagar' => 'cuentas_pagars', // <- FK a cuentas_pagars.id (ajústalo si tu columna difiere)
            ],
            'required' => ['cuenta_pagar'],        // exige que la cuenta exista en VPS
        ],
        'solicitudes'  => ['ts' => 'updated_at', 'fk' => []],
        'ventas'       => ['ts' => 'updated_at', 'fk' => []],
        // Hijos con FKs
        'cajas' => [
            'ts' => 'updated_at',
            'fk' => [
                'venta' => 'ventas',
                'corte' => 'cortes', // si existe la col
            ],
        ],
        'ventas_detalles' => [
            'ts' => 'updated_at',
            'fk' => [
                'venta'    => 'ventas',
                'producto' => 'productos',
            ],
            // 'required' => ['venta'], // habilita si te conviene
        ],
        'inventarios' => [
            'ts' => 'updated_at',
            'fk' => [
                'producto' => 'productos',
            ],
        ],
        'kardexes' => [
            'ts' => 'updated_at',
            'fk' => [
                'producto'   => 'productos',
                'inventario' => 'inventarios',
            ],
        ],
        // Tokens/DTE (según tus tablas)
        'tockens' => ['ts' => 'updated_at', 'fk' => []],
        'dtes' => [
            'ts' => 'updated_at', // o 'sincronizacion' si esa es la que usas
            'fk' => [
                'venta'  => 'ventas',
                'tocken' => 'tockens',
            ],
            // 'required' => ['venta','tocken'], // habilita si ambas deben existir
        ],
        'resumen_dtes' => [
            'ts' => 'updated_at',
            'fk' => [
                'dte' => 'dtes',
            ],
            'required' => ['dte'],
        ],
        'firmadors' => [
            'ts' => 'updated_at',
            'fk' => [
                'dte' => 'dtes',
            ],
            'required' => ['dte'],
        ],
        'recepcion_dtes' => [
            'ts' => 'updated_at',
            'fk' => [
                'dte' => 'dtes',
            ],
            'required' => ['dte'],
        ],
        // Detalles de compras/solicitudes
        'compras_detalles' => [
            'ts' => 'updated_at',
            'fk' => [
                'compra'   => 'compras',   // tu FK se llama 'compra'
                'producto' => 'productos',
            ],
            'required' => ['compra'],      // OBLIGATORIO mapear compra
        ],
        'solicitudes_detalles' => [
            'ts' => 'updated_at',
            'fk' => [
                'solicitud' => 'solicitudes',
                'producto'  => 'productos',
            ],
            // 'required' => ['solicitud'], // habilita si te conviene
        ],
        // Ajustes
        'ajustes' => ['ts' => 'updated_at', 'fk' => []],
        'ajustes_detalles' => [
            'ts' => 'updated_at',
            'fk' => [
                'ajuste'   => 'ajustes',
                'producto' => 'productos',
                'inventario' => 'inventarios',
            ],
            'required' => ['ajuste', 'inventario'],
        ],
        // ===== Inventario (aperturas + hojas) =====
        // PADRE
        'aperturas_inventario' => [
            'ts' => 'updated_at',
            'fk' => [
                // si tu tabla tiene estas columnas, se mapearán (si no existen, no pasa nada)
                'responsable'  => 'users',
                'user'         => 'users',
            ],
            // 'required' => ['empresa','sucursal'], // habilítalo si de verdad son obligatorias
        ],
        // HIJA (depende de aperturas_inventario)
        'hoja_inventarios' => [
            'ts' => 'updated_at',
            'fk' => [
                // normalmente la hoja apunta a la apertura por 'apertura' o 'apertura_id'
                'apertura'     => 'aperturas_inventario',
                'apertura_id'  => 'aperturas_inventario',
                // opcional
                'user'         => 'users',
                'responsable'  => 'users',
            ],
            // si tu hoja SIEMPRE debe tener apertura, dejá requerido el que realmente exista
            // 'required' => ['apertura'], // o ['apertura_id']
        ],
        // NIETA (depende de hoja_inventarios + productos + inventarios)
        'hoja_inventario_detalles' => [
            'ts' => 'updated_at',
            'fk' => [
                // normalmente el detalle apunta a hoja por 'hoja' o 'hoja_id'
                'hoja'     => 'hoja_inventarios',
                'hoja_id'  => 'hoja_inventarios',
                'producto'   => 'productos',
                'inventario' => 'inventarios',
            ],
            // obligatorios (según tu diseño)
            // 'required' => ['hoja','producto'], // o ['hoja_id','producto']
        ],
    ];
    public function handle()
    {
        $connLocal = (string) $this->option('local');
        $connVps   = (string) $this->option('vps');
        $limit     = (int) $this->option('limit');
        $direction = (string) $this->option('direction');
        $sucursalBitacora = 'Nueva Belen'; // etiqueta para bitácora
        // Orden por defecto (padres→hijos)
        $defaultOrder = [
            'remesas',
            'aperturas',
            'cortes',
            'clientes',
            'productos',
            'compras',
            'cuentas_pagars', // 👈 NUEVO: primero la cuenta por pagar (hija directa de compras)
            'pagos',          // 👈 NUEVO: luego los pagos (hijo de cuentas_pagars)
            'solicitudes',
            'ventas',
            'cajas',
            'tockens',
            'dtes',
            'resumen_dtes',
            'firmadors',
            'recepcion_dtes',
            'compras_detalles',
            'solicitudes_detalles',
            'ventas_detalles',
            'inventarios',
            'kardexes',
            'kardexes2', // si existe
            'ajustes',
            'ajustes_detalles',
            'aperturas_inventario',
            'hoja_inventarios',
            'hoja_inventario_detalles',
        ];
        // Filtrado manual por --tables
        $tablesOpt = $this->option('tables');
        $tables = $tablesOpt
            ? array_map('trim', explode(',', $tablesOpt))
            : $defaultOrder;
        // Normaliza variantes de tablas de detalle si se dieran nombres alternos
        $tables = $this->normalizeDetailTableList($tables, $connLocal);
        foreach ($tables as $table) {
            if (!$this->tableExists($connLocal, $table)) {
                $this->warn("→ Tabla local '{$table}' no existe. Saltando.");
                continue;
            }
            if (!$this->tableExists($connVps, $table)) {
                $this->warn("→ Tabla VPS '{$table}' no existe. Saltando.");
                continue;
            }
            $this->info("→ Tabla: {$table}");
            try {
                $res = $this->syncTable($table, $limit, $connLocal, $connVps, $direction);
                $this->line("   • {$table}: {$res['count']} filas (ins: {$res['ins']}, upd: {$res['upd']}). WM → {$res['wm_ts']} / {$res['wm_id']}");
                // Bitácora OK
                if (Schema::connection($connLocal)->hasTable('bitacora_sincronizacions')) {
                    $regTotal = (int)$res['ins'] + (int)$res['upd'];
                    if ($regTotal > 0) {
                        DB::connection($connLocal)->table('bitacora_sincronizacions')->insert([
                            'sincro_id'    => (string) Str::uuid(),
                            'tabla'        => $table,
                            'origen'       => 'local',
                            'destino'      => 'vps',
                            'sucursal'     => $sucursalBitacora,
                            'registros'    => $regTotal,
                            'insertados'   => (int)$res['ins'],
                            'actualizados' => (int)$res['upd'],
                            'estado'       => 'OK',
                            'mensaje'      => null,
                            'fecha'        => now(),
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                    }
                }
            } catch (Throwable $e) {
                $this->error("   ✘ Error en {$table}: " . $e->getMessage());
                // Bitácora ERROR
                if (Schema::connection($connLocal)->hasTable('bitacora_sincronizacions')) {
                    DB::connection($connLocal)->table('bitacora_sincronizacions')->insert([
                        'sincro_id'    => (string) Str::uuid(),
                        'tabla'        => $table,
                        'origen'       => 'local',
                        'destino'      => 'vps',
                        'sucursal'     => $sucursalBitacora,
                        'registros'    => 0,
                        'insertados'   => 0,
                        'actualizados' => 0,
                        'estado'       => 'ERROR',
                        'mensaje'      => $e->getMessage(),
                        'fecha'        => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        }
        $this->info('✔ Sincronización finalizada.');
        return self::SUCCESS;
    }
    // ========== Núcleo por tabla ==========
    protected function syncTable(string $table, int $limit, string $connLocal, string $connVps, string $direction): array
    {
        $plan  = $this->plans[$table] ?? ['ts' => 'updated_at', 'fk' => []];
        $tsCol = $plan['ts'];
        // 1) Watermark vigente
        $wm   = $this->getWatermark($direction, $table);
        $wmTs = $wm['watermark_updated_at'] ?? null;
        $wmId = isset($wm['watermark_id']) ? (int)$wm['watermark_id'] : 0;
        // 2) Query incremental
        $cutoff = now();
        $q = DB::connection($connLocal)->table($table);
        if ($wmTs) {
            // pre-check rápido
            $hasChanges = DB::connection($connLocal)->table($table)
                ->whereRaw("( {$tsCol}, id ) > (?, ?)", [$wmTs, $wmId])
                ->where($tsCol, '<=', $cutoff)
                ->limit(1)
                ->exists();
            if (!$hasChanges) {
                return [
                    'count' => 0,
                    'ins' => 0,
                    'upd' => 0,
                    'wm_ts' => $wmTs ?? 'NULL',
                    'wm_id' => $wmId ?? 0,
                ];
            }
            $q->whereRaw("( {$tsCol}, id ) > (?, ?)", [$wmTs, $wmId]);
        }
        // (Opcional) ignorar soft-deletes:
        if (Schema::connection($connLocal)->hasColumn($table, 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        $q->where($tsCol, '<=', $cutoff);
        if ($table === 'ajustes' && Schema::connection($connLocal)->hasColumn('ajustes', 'aplicado_local')) {
            $q->whereNotNull('aplicado_local');
        }
        $rows = $q->orderBy($tsCol)->orderBy('id')->limit($limit)->get();
        if ($rows->isEmpty()) {
            return [
                'count' => 0,
                'ins' => 0,
                'upd' => 0,
                'wm_ts' => $wmTs ?? 'NULL',
                'wm_id' => $wmId ?? 0,
            ];
        }
        $ins = 0;
        $upd = 0;
        $lastTs = $wmTs;
        $lastId = $wmId;
        foreach ($rows as $r) {
            $payload = (array) $r;
            // 3) Asegurar sincro_id
            if (empty($payload['sincro_id'])) {
                $payload['sincro_id'] = (string) Str::uuid();
                DB::connection($connLocal)->table($table)
                    ->where('id', $r->id)
                    ->update(['sincro_id' => $payload['sincro_id']]);
            }
            // 3.1 Normaliza timestamps con valores raros
            foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
                if (isset($payload[$ts]) && ($payload[$ts] === '?' || $payload[$ts] === '')) {
                    $payload[$ts] = null;
                }
            }
            // 4) Mapear FKs y exigir las 'required' (si no mapea, saltar la fila)
            $required = array_flip($plan['required'] ?? []);
            foreach (($plan['fk'] ?? []) as $col => $refTable) {
                if (array_key_exists($col, $payload)) {
                    $mapped = $this->mapFk($refTable, $payload[$col], $connLocal, $connVps);
                    if (isset($required[$col]) && is_null($mapped)) {
                        $this->warn("   ⚠ Saltado {$table} id={$r->id} por FK requerida '{$col}' sin mapear (local={$payload[$col]})");
                        continue 2; // siguiente fila
                    }
                    $payload[$col] = $mapped; // puede quedar null si NO es requerida
                }
            }
            // 5) Upsert en VPS por sincro_id (nunca forzar PK)
            unset($payload['id'], $payload['id_vps']); // 🔒 nunca empujar PK local
            $before   = $this->findBySincro($connVps, $table, $payload['sincro_id']);
            $remoteId = $this->upsertBySincro($connVps, $table, $payload);
            // contador ins/upd
            if ($before) {
                $upd++;
            } else {
                $ins++;
            }
            // 6) Grabar id_vps en LOCAL
            DB::connection($connLocal)->table($table)
                ->where('id', $r->id)
                ->update(['id_vps' => $remoteId]);
            // 7) Avanzar WM local
            $lastTs = $r->{$tsCol};
            $lastId = $r->id;
        }
        // 8) Guardar WM
        $this->saveWatermark($direction, $table, $lastTs, (int)$lastId);
        return [
            'count' => ($ins + $upd),
            'ins'   => $ins,
            'upd'   => $upd,
            'wm_ts' => (string)$lastTs,
            'wm_id' => (int)$lastId,
        ];
    }
    // ========== Utilidades DB ==========
    protected function tableExists(string $conn, string $table): bool
    {
        try {
            DB::connection($conn)->table($table)->limit(1)->get();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    protected function normalizeDetailTableList(array $tables, string $connLocal): array
    {
        $hasDetalle = in_array('detalle_ventas', $tables, true);
        $hasVD      = in_array('ventas_detalle', $tables, true);
        if ($hasDetalle && $hasVD) {
            // deja sólo la que exista en LOCAL
            $detalleExists = $this->tableExists($connLocal, 'detalle_ventas');
            $vdExists      = $this->tableExists($connLocal, 'ventas_detalle');
            if ($detalleExists && !$vdExists) {
                return array_values(array_diff($tables, ['ventas_detalle']));
            }
            if ($vdExists && !$detalleExists) {
                return array_values(array_diff($tables, ['detalle_ventas']));
            }
        }
        return $tables;
    }
    protected function findBySincro(string $conn, string $table, string $sincroId): ?object
    {
        return DB::connection($conn)->table($table)
            ->where('sincro_id', $sincroId)->first();
    }
    protected function upsertBySincro(string $connVps, string $table, array $payload): int
    {
        $sincro = $payload['sincro_id'] ?? null;
        if (!$sincro) {
            throw new \RuntimeException("Falta sincro_id para {$table}");
        }
        // normaliza timestamps vacíos
        foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
            if (isset($payload[$ts]) && ($payload[$ts] === '?' || $payload[$ts] === '')) {
                $payload[$ts] = null;
            }
        }
        $existing = DB::connection($connVps)->table($table)
            ->where('sincro_id', $sincro)->first();
        $payload['updated_at'] = $payload['updated_at'] ?? now();
        if ($existing) {
            DB::connection($connVps)->table($table)
                ->where('sincro_id', $sincro)
                ->update($payload);
            return (int) $existing->id;
        }
        $payload['created_at'] = $payload['created_at'] ?? now();
        return (int) DB::connection($connVps)->table($table)->insertGetId($payload);
    }
    /**
     * Traduce una FK local (id) a id remota del VPS, usando id_vps o sincro_id.
     * Si no existe aún en el VPS, intenta subir el padre "al vuelo" para obtener su id_vps.
     * NUNCA hace fallback al id local.
     */
    protected function mapFk(string $refTable, $localId, string $connLocal, string $connVps): ?int
    {
        if (!$localId) return null;
        // 1) ¿Ya tenemos id_vps/sincro_id del padre en LOCAL?
        $ref = DB::connection($connLocal)->table($refTable)
            ->select('id_vps', 'sincro_id')
            ->where('id', $localId)->first();
        // 2) Si ya hay id_vps, úsalo
        if ($ref && !empty($ref->id_vps)) {
            return (int) $ref->id_vps;
        }
        // 3) Si hay sincro_id, intenta resolverlo en VPS
        if ($ref && !empty($ref->sincro_id)) {
            $row = DB::connection($connVps)->table($refTable)
                ->select('id')->where('sincro_id', $ref->sincro_id)->first();
            if ($row) {
                // guardar id_vps en local para futuras veces
                DB::connection($connLocal)->table($refTable)
                    ->where('id', $localId)->update(['id_vps' => (int)$row->id]);
                return (int) $row->id;
            }
        }
        // 4) Último intento: subir el padre al vuelo
        $remoteId = $this->ensureParentSynced($refTable, (int)$localId, $connLocal, $connVps);
        return $remoteId ?: null; // nunca devolver id local
    }
    /**
     * Sube el padre al VPS si no existe y devuelve su id_vps.
     */
    protected function ensureParentSynced(string $refTable, int $localId, string $connLocal, string $connVps): ?int
    {
        $ref = DB::connection($connLocal)->table($refTable)->where('id', $localId)->first();
        if (!$ref) return null;
        // Si ya tiene id_vps, úsalo
        if (!empty($ref->id_vps)) {
            return (int) $ref->id_vps;
        }
        // Garantiza sincro_id
        $payload = (array) $ref;
        if (empty($payload['sincro_id'])) {
            $payload['sincro_id'] = (string) Str::uuid();
            DB::connection($connLocal)->table($refTable)
                ->where('id', $localId)->update(['sincro_id' => $payload['sincro_id']]);
        }
        // No empujar PK local
        unset($payload['id'], $payload['id_vps']);
        // Normaliza timestamps vacíos/raros
        foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
            if (isset($payload[$ts]) && ($payload[$ts] === '?' || $payload[$ts] === '')) {
                $payload[$ts] = null;
            }
        }
        // Upsert por sincro_id
        $existing = DB::connection($connVps)->table($refTable)
            ->where('sincro_id', $payload['sincro_id'])->first();
        $payload['updated_at'] = $payload['updated_at'] ?? now();
        if ($existing) {
            DB::connection($connVps)->table($refTable)
                ->where('sincro_id', $payload['sincro_id'])->update($payload);
            $remoteId = (int) $existing->id;
        } else {
            $payload['created_at'] = $payload['created_at'] ?? now();
            $remoteId = (int) DB::connection($connVps)->table($refTable)->insertGetId($payload);
        }
        // Graba id_vps en LOCAL
        DB::connection($connLocal)->table($refTable)
            ->where('id', $localId)->update(['id_vps' => $remoteId]);
        return $remoteId;
    }
    // ========== Watermark en sync_states ==========
    protected function getWatermark(string $direction, string $table): array
    {
        $row = DB::table($this->syncStatesTable)
            ->where('direction', $direction)
            ->where('table', $table)
            ->first();
        return $row ? [
            'watermark_updated_at' => $row->watermark_updated_at,
            'watermark_id'         => (int) $row->watermark_id,
        ] : [];
    }
    protected function saveWatermark(string $direction, string $table, ?string $ts, int $id): void
    {
        $now  = now();
        $tsDb = $ts ?: null;
        $exists = DB::table($this->syncStatesTable)
            ->where('direction', $direction)
            ->where('table', $table)->first();
        if ($exists) {
            DB::table($this->syncStatesTable)
                ->where('direction', $direction)
                ->where('table', $table)
                ->update([
                    'watermark_updated_at' => $tsDb,
                    'watermark_id'         => $id,
                    'updated_at'           => $now,
                ]);
        } else {
            DB::table($this->syncStatesTable)
                ->insert([
                    'direction'            => $direction,
                    'table'                => $table,
                    'watermark_updated_at' => $tsDb,
                    'watermark_id'         => $id,
                    'meta'                 => null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);
        }
    }
}
