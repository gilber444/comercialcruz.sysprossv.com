<?php
namespace App\Console\Commands;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Throwable;
class SyncDesdeVPS extends Command
{
    protected $signature = 'sync:vps-local
        {--stage= : Etapa (acl|compras|solicitudes|base|productos|precios|actores|users)}
        {--tables= : Lista de tablas separadas por coma (tiene prioridad sobre --stage)}
        {--sucursales= : IDs de sucursal (uno o varios separados por coma). Si se omite, usa env(SUCURSAL_ID)}
        {--acl-mode=merge : Modo ACL: snapshot|merge (snapshot = TRUNCATE+INSERT)}';
    protected $description = 'Sincroniza datos del VPS hacia la base de datos local (incremental con watermark por etapas)';
    // Etapas en orden topológico (prioridad solicitada primero)
    protected array $stages = [
        'acl' => ['roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions', 'users'],
        'compras'     => ['compras', 'compras_detalles'],
        'solicitudes' => ['solicitudes', 'solicitudes_detalles'],
        // base ahora incluye empresa → sucursales → parametros (en ese orden),
        // y además medidas, categorias, familias
        'base' => ['empresas', 'sucursales', 'parametros', 'medidas', 'categorias', 'familias'],
        // NUEVO
        'ajustes'   => ['ajustes', 'ajustes_detalles'],
        'productos' => ['productos'],
        'precios'   => ['precios', 'descuentos'],
        'actores'   => ['clientes', 'proveedores'],
        'users'     => ['users'],
    ];
    // Orden por defecto: procesamos etapa por etapa en orden y dentro de base se respeta empresa→sucursales→parametros
    protected array $defaultOrder = [
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'users',
        // compras / solicitudes
        'compras',
        'compras_detalles',
        'solicitudes',
        'solicitudes_detalles',
        // inventario
        'inventarios',
        // base
        'empresas',
        'sucursales',
        'parametros',
        'medidas',
        'categorias',
        'familias',
        // ajustes
        'ajustes',
        'ajustes_detalles',
        // resto
        'productos',
        'precios',
        'descuentos',
        'clientes',
        'proveedores',
        'users',
        'creditos',
    ];
    // 🔒 Se calcula en runtime desde --sucursales o APP_SUCURSAL_ID del .env
    protected array $sucursalesPermitidas = [];
    // Tablas a las que aplica filtro por sucursal/destino
    protected array $tablasConFiltroSucursal = [
        'compras',
        'compras_detalles',
        'solicitudes',
        'solicitudes_detalles',
        // NUEVO
        'ajustes',
        'ajustes_detalles',
        // inventario y resumen de ventas
        'ventas_resumen',
        'inventarios',
    ];
    // Aliases posibles de columna sucursal (ampliado)
    protected array $candidatosColSucursal = ['sucursal_id', 'id_sucursal', 'sucursal', 'idsucursal', 'idSucursal', 'SucursalId'];
    protected array $colSucursalOverrides = [
        'solicitudes' => ['destino', 'id_destino', 'Destino', 'DestinoId'],
    ];
    protected array $fkCandidatas = [
        'compras_detalles'     => ['compra_id', 'compra'],
        'solicitudes_detalles' => ['solicitud_id', 'solicitud'],
        // NUEVO
        'ajustes_detalles'     => ['ajuste_id', 'ajuste'],
    ];
    // En estas tablas, si el sincro_id ya existe: NO actualizar (ni insertar) → evita rebote
    protected array $tablasEvitarUpdateSiSidExiste = [
		'compras',
		'compras_detalles',
		//'ajustes',
		//'ajustes_detalles'
	];
    // ACL tables
    protected array $aclTables = ['roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions', 'users'];
    protected ?string $wmAjustesAtBefore    = null;
    protected int     $wmAjustesIdBefore    = 0;
    protected ?string $wmAjustesDetAtBefore = null;
    protected int     $wmAjustesDetIdBefore = 0;
    // Parámetros
    protected int    $chunk         = 1000;
    protected string $direction     = 'vps_to_local';
    protected int    $maxWindow     = 600; // minutos (fallback)
    protected int    $bufferMinutes = 2;
    protected string $connRemote    = 'vpsmysql';
    protected string $connLocal     = 'localmysql';
    protected string $sucursal      = '';
    protected string $nodeId        = 'local-unknown';
    protected array  $ajustesSidBefore = [];
    public function handle()
    {
        if (env('APP_MODO', 'local') === 'vps') {
            $this->error('❌ sync:vps-local NO debe ejecutarse en VPS (APP_MODO=vps). Abortando.');
            return self::FAILURE;
        }
        // ✅ Valida conexiones
        try {
            DB::connection($this->connRemote)->getPdo();
            DB::connection($this->connLocal)->getPdo();
        } catch (Throwable $e) {
            $this->error('❌ Conexión fallida: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->nodeId = env('NODE_ID', 'local-unknown');
        // ✅ Carga sucursales (CLI / APP_SUCURSAL_ID del .env)
        $resolved = $this->resolverSucursales();
        $this->sucursalesPermitidas = $resolved;
        // Label para bitácora: dinámico según sucursal resuelta
        $ids = implode(',', $this->sucursalesPermitidas);
        $this->sucursal = $ids ? "SERVIDOR -> SUCURSAL(ES) #{$ids}" : 'SERVIDOR -> (sin sucursal)';
        $tablesOpt = trim((string) ($this->option('tables') ?? ''));
        $stageOpt  = trim((string) ($this->option('stage') ?? ''));
        $aclMode   = (string) ($this->option('acl-mode') ?? 'merge');
        $tablas = [];
        if ($tablesOpt !== '') {
            $tablas = array_values(array_filter(array_map('trim', explode(',', $tablesOpt))));
        } elseif ($stageOpt !== '') {
            if (!isset($this->stages[$stageOpt])) {
                $this->error("Etapa inválida: {$stageOpt}. Usa: " . implode('|', array_keys($this->stages)));
                return self::INVALID;
            }
            $tablas = $this->stages[$stageOpt];
        } else {
            foreach ($this->defaultOrder as $t) {
                $tablas[] = $t;
            }
        }
        $tablas    = array_values(array_unique($tablas));
        $tablasSet = array_flip($tablas); // para consultas rápidas
        $this->info('== INICIO SYNC VPS → LOCAL == ' . now()->toDateTimeString());
        $this->line('Tablas: ' . implode(', ', $tablas));
        if (!empty($this->sucursalesPermitidas)) {
            $this->line('Filtro de sucursales: ' . implode(',', $this->sucursalesPermitidas) . ' (aplica a compras/solicitudes/ajustes y detalles; inventarios/ventas_resumen invertido)');
        } else {
            $this->warn('⚠ Sin filtro de sucursales (se traerán TODAS). Define --sucursales o variables .env.');
        }
        // === ACL primero ===
        if ($stageOpt === 'acl') {
            $this->sincronizarACL($aclMode);
            $this->info('== FIN SYNC VPS → LOCAL (solo ACL) == ' . now()->toDateTimeString());
            return self::SUCCESS;
        }
        $this->sincronizarACL($aclMode);
        $tablas    = array_values(array_filter($tablas, fn($t) => !in_array($t, $this->aclTables, true)));
        $tablasSet = array_flip($tablas);
        // === Capturar watermarks de AJUSTES/DETALLES ANTES del sync (solo si se tocan en esta corrida) ===
        if (isset($tablasSet['ajustes']) || isset($tablasSet['ajustes_detalles']) || $stageOpt === '') {
            [$this->wmAjustesAtBefore,    $this->wmAjustesIdBefore]    = $this->getWatermark('ajustes');
            [$this->wmAjustesDetAtBefore, $this->wmAjustesDetIdBefore] = $this->getWatermark('ajustes_detalles');
            // ⬅️ NUEVO: tomar foto de los sincro_id locales antes del sync
            $this->ajustesSidBefore = DB::connection($this->connLocal)
                ->table('ajustes')
                ->whereNotNull('sincro_id')
                ->where('sincro_id', '!=', '')   // ← evita vacíos
                ->pluck('sincro_id')
                ->filter()
                ->values()
                ->all();
        }
        // === Sync por tabla ===
        foreach ($tablas as $tabla) {
            $this->sincronizarTabla($tabla, $this->sucursal);
        }
        // === Remapear FK creditos.venta de ID_VPS a ID_LOCAL ===
        if (isset($tablasSet['creditos']) || $stageOpt === '') {
            $this->remapearFKCreditos();
        }
        // === Remapear FK ajustes_detalles.ajuste de ID_VPS a ID_LOCAL ===
        if (isset($tablasSet['ajustes_detalles']) || $stageOpt === '') {
            $this->remapearFKAjustesDetalles();
        }
        // === Aplicar SOLO los ajustes nuevos que llegaron en esta corrida ===
        if (isset($tablasSet['ajustes']) || isset($tablasSet['ajustes_detalles']) || $stageOpt === '') {
            $this->procesarAjustesIncrementales(); // ⬅️ reemplaza a procesarAjustesPendientes()
        }
        $this->info('== FIN SYNC VPS → LOCAL == ' . now()->toDateTimeString());
        return self::SUCCESS;
    }
    /* =========================
     * ACL (Spatie)
     * ========================= */
    protected function sincronizarACL(string $mode = 'merge'): void
    {
        $mode = strtolower($mode);
        if (!in_array($mode, ['snapshot', 'merge'], true)) {
            $mode = 'merge';
        }
        $this->info("→ ACL ({$mode})");
        // Tablas Spatie
        $pivots  = ['model_has_permissions', 'model_has_roles', 'role_has_permissions'];
        $masters = ['permissions', 'roles']; // users SIEMPRE MERGE
        $allAcl  = array_merge($pivots, $masters, ['users']);
        // Validar existencia
        foreach ($allAcl as $t) {
            if (!Schema::connection($this->connRemote)->hasTable($t) || !Schema::connection($this->connLocal)->hasTable($t)) {
                $this->warn("  - {$t}: no existe en alguna conexión. Saltando.");
            }
        }
        // 0) Stats por tabla
        $stats     = [];
        $initStats = fn() => ['insert' => 0, 'update' => 0, 'delete' => 0, 'truncate' => false];
        foreach ($allAcl as $t) {
            $stats[$t] = $initStats();
        }
        // 1) Traer datasets del VPS (pequeños)
        $datasets = [];
        foreach ($allAcl as $t) {
            if (Schema::connection($this->connRemote)->hasTable($t)) {
                $colsRemote = Schema::connection($this->connRemote)->getColumnListing($t);
                $colsLocal  = Schema::connection($this->connLocal)->hasTable($t)
                    ? Schema::connection($this->connLocal)->getColumnListing($t)
                    : [];
                $cols       = array_values(array_intersect($colsRemote, $colsLocal));
                if (!empty($cols)) {
                    $datasets[$t] = DB::connection($this->connRemote)->table($t)->select($cols)->get();
                } else {
                    $datasets[$t] = collect();
                }
            } else {
                $datasets[$t] = collect();
            }
        }
        // 2) Si snapshot: limpiar PIVOTES -> MAESTRAS (no tocamos users)
        if ($mode === 'snapshot') {
            DB::connection($this->connLocal)->statement('SET FOREIGN_KEY_CHECKS=0');
            // Pivotes
            foreach ($pivots as $t) {
                if (!Schema::connection($this->connLocal)->hasTable($t)) continue;
                try {
                    DB::connection($this->connLocal)->table($t)->truncate();
                    $stats[$t]['truncate'] = true;
                } catch (\Throwable $e) {
                    $deleted              = DB::connection($this->connLocal)->table($t)->delete();
                    $stats[$t]['delete'] += $deleted;
                }
            }
            // Maestras (permissions, roles)
            foreach ($masters as $t) {
                if (!Schema::connection($this->connLocal)->hasTable($t)) continue;
                try {
                    DB::connection($this->connLocal)->table($t)->truncate();
                    $stats[$t]['truncate'] = true;
                } catch (\Throwable $e) {
                    $deleted              = DB::connection($this->connLocal)->table($t)->delete();
                    $stats[$t]['delete'] += $deleted;
                }
            }
            // USERS también en snapshot
            if (Schema::connection($this->connLocal)->hasTable('users')) {
                try {
                    DB::connection($this->connLocal)->table('users')->truncate();
                    $stats['users']['truncate'] = true;
                } catch (\Throwable $e) {
                    $deleted                   = DB::connection($this->connLocal)->table('users')->delete();
                    $stats['users']['delete'] += $deleted;
                }
            }
            DB::connection($this->connLocal)->statement('SET FOREIGN_KEY_CHECKS=1');
        }
        // 3) USERS (merge siempre)
        if (Schema::connection($this->connLocal)->hasTable('users')) {
            $rows = $datasets['users'] ?? collect();
            if ($rows->isNotEmpty()) {
                $colsLocalUsers = Schema::connection($this->connLocal)->getColumnListing('users');
                $hasId          = in_array('id', $colsLocalUsers, true);
                $updatable      = array_values(array_diff($colsLocalUsers, ['id', 'created_at']));
                DB::connection($this->connLocal)->transaction(function () use ($rows, $hasId, $updatable, &$stats) {
                    foreach ($rows as $r) {
                        $data = (array) $r;
                        foreach (['created_at', 'updated_at', 'deleted_at', 'email_verified_at'] as $ts) {
                            if (array_key_exists($ts, $data) && ($data[$ts] === '?' || $data[$ts] === '')) {
                                $data[$ts] = null;
                            }
                        }
                        if ($hasId && isset($data['id'])) {
                            $exists = DB::connection($this->connLocal)->table('users')->where('id', $data['id'])->exists();
                            if ($exists) {
                                $affected = DB::connection($this->connLocal)
                                    ->table('users')
                                    ->where('id', $data['id'])
                                    ->update(array_intersect_key($data, array_flip($updatable)));
                                if ($affected > 0) {
                                    $stats['users']['update'] += 1;
                                }
                            } else {
                                DB::connection($this->connLocal)->table('users')->insert($data);
                                $stats['users']['insert'] += 1;
                            }
                        } else {
                            DB::connection($this->connLocal)->table('users')->insert($data);
                            $stats['users']['insert'] += 1;
                        }
                    }
                });
            }
        }
        // 4) MAESTRAS (permissions, roles)
        foreach ($masters as $t) {
            if (!Schema::connection($this->connLocal)->hasTable($t)) {
                continue;
            }
            $rows = $datasets[$t] ?? collect();
            if ($rows->isEmpty()) {
                continue;
            }
            $colsLocal = Schema::connection($this->connLocal)->getColumnListing($t);
            $hasId     = in_array('id', $colsLocal, true);
            $updatable = array_values(array_diff($colsLocal, ['id', 'created_at']));
            DB::connection($this->connLocal)->transaction(function () use ($t, $rows, $hasId, $updatable, $mode, &$stats) {
                foreach ($rows as $r) {
                    $data = (array) $r;
                    foreach (['created_at', 'updated_at', 'deleted_at'] as $ts) {
                        if (array_key_exists($ts, $data) && ($data[$ts] === '?' || $data[$ts] === '')) {
                            $data[$ts] = null;
                        }
                    }
                    if ($mode === 'snapshot') {
                        DB::connection($this->connLocal)->table($t)->insert($data);
                        $stats[$t]['insert'] += 1;
                    } else {
                        // merge
                        if ($hasId && isset($data['id']) && DB::connection($this->connLocal)->table($t)->where('id', $data['id'])->exists()) {
                            $affected = DB::connection($this->connLocal)
                                ->table($t)
                                ->where('id', $data['id'])
                                ->update(array_intersect_key($data, array_flip($updatable)));
                            if ($affected > 0) {
                                $stats[$t]['update'] += 1;
                            }
                        } else {
                            DB::connection($this->connLocal)->table($t)->insert($data);
                            $stats[$t]['insert'] += 1;
                        }
                    }
                }
            });
        }
        // 5) PIVOTES (role_has_permissions, model_has_roles, model_has_permissions)
        foreach ($pivots as $t) {
            if (!Schema::connection($this->connLocal)->hasTable($t)) {
                continue;
            }
            $rows = $datasets[$t] ?? collect();
            if ($rows->isEmpty()) {
                continue;
            }
            $colsLocal = Schema::connection($this->connLocal)->getColumnListing($t);
            DB::connection($this->connLocal)->transaction(function () use ($t, $rows, $colsLocal, $mode, &$stats) {
                if ($mode === 'merge') {
                    foreach ($rows as $r) {
                        $data = (array) $r;
                        // Borramos coincidencia exacta (por todas las columnas presentes)
                        $q = DB::connection($this->connLocal)->table($t);
                        foreach ($colsLocal as $c) {
                            if (array_key_exists($c, $data)) {
                                $q->where($c, $data[$c]);
                            }
                        }
                        $deleted              = $q->delete();
                        if ($deleted > 0) {
                            $stats[$t]['delete'] += $deleted;
                        }
                        DB::connection($this->connLocal)->table($t)->insert($data);
                        $stats[$t]['insert'] += 1;
                    }
                } else {
                    // snapshot: solo insert, ya truncamos
                    foreach ($rows as $r) {
                        DB::connection($this->connLocal)->table($t)->insert((array) $r);
                        $stats[$t]['insert'] += 1;
                    }
                }
            });
        }
        // 6) Resumen por tabla (consola) + totales
        $totalInsert = $totalUpdate = $totalDelete = 0;
        foreach (['users', ...$masters, ...$pivots] as $t) {
            $s          = $stats[$t];
            $totalInsert += $s['insert'];
            $totalUpdate += $s['update'];
            $totalDelete += $s['delete'];
            $this->line(sprintf(
                '   • %-24s %s%s%s  +%d ins  ~%d upd  ×%d del',
                '[' . $t . ']',
                $s['truncate'] ? 'TRUNCATE ' : '',
                !$s['truncate'] && $s['delete'] > 0 ? 'DELETE ' : '',
                $s['truncate'] || $s['delete'] > 0 ? '→ ' : '',
                $s['insert'],
                $s['update'],
                $s['delete']
            ));
        }
        $this->info(sprintf('   ✔ ACL total: +%d ins / ~%d upd / ×%d del', $totalInsert, $totalUpdate, $totalDelete));
        // 7) Bitácora con mensaje detallado
        if (Schema::connection($this->connLocal)->hasTable('bitacora_sincronizacions')) {
            // Construir mensaje compacto
            $parts = [];
            foreach (['users', ...$masters, ...$pivots] as $t) {
                $s        = $stats[$t];
                $parts[] = "{$t}(ins={$s['insert']},upd={$s['update']},del={$s['delete']}" . ($s['truncate'] ? ',trunc=1' : '') . ')';
            }
            $mensaje = "ACL {$mode} | " . implode(' | ', $parts);
            DB::connection($this->connLocal)
                ->table('bitacora_sincronizacions')
                ->insert([
                    'sincro_id'   => (string) Str::uuid(),
                    'tabla'       => 'ACL',
                    'origen'      => 'vps',
                    'destino'     => 'local',
                    'sucursal'    => $this->sucursal,
                    'registros'   => $totalInsert + $totalUpdate, // “movimientos” efectivos
                    'insertados'  => $totalInsert,
                    'actualizados'=> $totalUpdate,
                    'estado'      => 'OK',
                    'mensaje'     => $mensaje,
                    'fecha'       => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
        }
        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info('   ↻ Cache de permisos Spatie limpiada.');
        }
    }
    /* =========================
     * Sincronización tabla por tabla
     * ========================= */
    /* =========================
     * Genérico por tabla
     * ========================= */
    protected function sincronizarTabla(string $tabla, string $sucursal)
    {
        if (!Schema::connection($this->connRemote)->hasTable($tabla)) {
            $this->warn("Tabla remota {$tabla} no existe. Saltando.");
            return;
        }
        if (!Schema::connection($this->connLocal)->hasTable($tabla)) {
            $this->warn("Tabla local {$tabla} no existe. Saltando.");
            return;
        }
        $colsRemote = Schema::connection($this->connRemote)->getColumnListing($tabla);
        $colsLocal  = Schema::connection($this->connLocal)->getColumnListing($tabla);
        $cols       = array_values(array_intersect($colsRemote, $colsLocal));
        foreach (['id', 'updated_at', 'sincro_id'] as $req) {
            if (!in_array($req, $cols, true)) {
                $this->warn("{$tabla} no tiene '{$req}'. Saltando.");
                return;
            }
        }
        $hasIdVpsLocal = Schema::connection($this->connLocal)->hasColumn($tabla, 'id_vps');
        [$wmAt, $wmId] = $this->getWatermark($tabla);
        $this->line("→ {$tabla} desde watermark: updated_at=[" . ($wmAt ?? '2020-01-01 00:00:00') . '] id=[' . ($wmId ?? 0) . ']');
        $query = DB::connection($this->connRemote)
            ->table($tabla)
            ->select($cols)
            ->whereNotNull('sincro_id')
            ->where('sincro_id', '!=', '');
        // (Opcional futuro) anti-eco por last_writer_node si existiera esa columna
        if (Schema::connection($this->connRemote)->hasColumn($tabla, 'last_writer_node')) {
            $query->where(function ($q) {
                $q->whereNull('last_writer_node')
                    ->orWhere('last_writer_node', '!=', $this->nodeId);
            });
        }
        $requiereFiltroSucursal = in_array($tabla, $this->tablasConFiltroSucursal, true)
            && !empty($this->sucursalesPermitidas);
        if ($requiereFiltroSucursal) {
            $aplicado = $this->aplicarFiltroSucursal($tabla, $query, $colsRemote);
            if (!$aplicado) {
                $this->warn("⚠ No se pudo aplicar filtro por sucursales en [{$tabla}]. Se omite la tabla.");
                return;
            }
        }
        $query = $query
            ->when(
                $wmAt,
                function ($q) use ($wmAt, $wmId) {
                    $q->where(function ($q2) use ($wmAt, $wmId) {
                        $q2->where('updated_at', '>', $wmAt)
                            ->orWhere(function ($q3) use ($wmAt, $wmId) {
                                $q3->where('updated_at', '=', $wmAt)
                                    ->where('id', '>', $wmId);
                            });
                    });
                },
                function ($q) use ($tabla) {
                    $window = $this->computeWindowMinutesFromBitacora($tabla);
                    $desde  = now()->subMinutes($window + $this->bufferMinutes);
                    $q->where('updated_at', '>=', $desde);
                },
            )
            ->orderBy('updated_at')
            ->orderBy('id');
        $insertados  = 0;
        $actualizados = 0;
        $estado      = 'OK';
        $mensaje     = null;
        $maxAt       = $wmAt;
        $maxId       = $wmId;
        $procesadas  = 0;
        $deshabilitarFK = Str::endsWith($tabla, '_detalles') || $tabla === 'creditos';
        if ($deshabilitarFK) {
            DB::connection($this->connLocal)->statement('SET FOREIGN_KEY_CHECKS=0');
        }
        try {
            $query->chunk($this->chunk, function ($rows) use ($tabla, $cols, $colsRemote, $hasIdVpsLocal, &$insertados, &$actualizados, &$maxAt, &$maxId, &$procesadas) {
                if ($rows->isEmpty()) {
                    return;
                }
                $lote = [];
                $sids = [];
                foreach ($rows as $r) {
                    $arr = (array) $r;
                    if (empty($arr['sincro_id'])) {
                        continue;
                    }
                    foreach (['created_at', 'updated_at', 'deleted_at', 'sincronizacion', 'fechaVencimiento'] as $ts) {
                        if (array_key_exists($ts, $arr) && ($arr[$ts] === '?' || $arr[$ts] === '')) {
                            $arr[$ts] = null;
                        }
                    }
                    if (in_array('sincronizacion', $cols, true)) {
                        $arr['sincronizacion'] = Carbon::now();
                    }
                    if ($hasIdVpsLocal) {
                        $arr['id_vps'] = $r->id;
                    }
                    $lote[] = $arr;
                    $sids[] = $arr['sincro_id'];
                    $maxAt = $r->updated_at;
                    $maxId = $r->id;
                }
                if (empty($lote)) {
                    return;
                }
                // creditos: solo actualizar campo 'estado' de créditos de la sucursal propia
                if ($tabla === 'creditos') {
                    DB::connection($this->connLocal)->transaction(function () use ($lote, &$actualizados) {
                        foreach ($lote as $row) {
                            // Solo créditos de la sucursal propia
                            if (!in_array($row['sucursal'] ?? null, $this->sucursalesPermitidas, true)) {
                                continue;
                            }
                            $existing = DB::connection($this->connLocal)
                                ->table('creditos')
                                ->where('sincro_id', $row['sincro_id'])
                                ->first();
                            if ($existing && isset($row['estado'])) {
                                DB::connection($this->connLocal)
                                    ->table('creditos')
                                    ->where('id', $existing->id)
                                    ->update(['estado' => $row['estado'], 'updated_at' => $row['updated_at']]);
                                $actualizados++;
                            }
                        }
                    });
                    $procesadas += count($lote);
                    return;
                }
                // inventarios: upsert directo por producto+sucursal, sin reconciliación de IDs
                // 🔒 CRÍTICO: NUNCA sobreescribir existencia local ni restaurar/borrar con deleted_at
                if ($tabla === 'inventarios') {
                    $updateCols = ['sincro_id', 'id_vps', 'sincronizacion', 'updated_at'];
                    // Solo incluir columnas que realmente existan en ambas bases
                    $updateCols = array_values(array_intersect($updateCols, $cols));
                    DB::connection($this->connLocal)->transaction(function () use ($lote, $updateCols, &$insertados, &$actualizados) {
                        foreach ($lote as $row) {
                            $existing = DB::connection($this->connLocal)
                                ->table('inventarios')
                                ->where('producto', $row['producto'])
                                ->where('sucursal', $row['sucursal'])
                                ->first();
                            if ($existing) {
                                $data = array_intersect_key($row, array_flip($updateCols));
                                if (!empty($data)) {
                                    DB::connection($this->connLocal)->table('inventarios')->where('id', $existing->id)->update($data);
                                }
                                $actualizados++;
                            } else {
                                $insertRow = $row;
                                unset($insertRow['id']);
                                // 🔒 Nunca insertar con existencia del VPS; si es nuevo, va a 0
                                if (array_key_exists('existencia', $insertRow)) {
                                    $insertRow['existencia'] = 0;
                                }
                                DB::connection($this->connLocal)->table('inventarios')->insert($insertRow);
                                $insertados++;
                            }
                        }
                    });
                    $procesadas += count($lote);
                    return; // siguiente chunk
                }
                // Reconciliar colisiones id ↔ sincro_id
                $ids = array_column($lote, 'id');
                if (!empty($ids)) {
                    $conflicts = DB::connection($this->connLocal)
                        ->table($tabla)
                        ->whereIn('id', $ids)
                        ->whereNotIn('sincro_id', $sids)
                        ->count();
                    if ($conflicts > 0) {
                        $fixed = $this->reconciliarIdVsSincro($tabla, $ids, $sids, $this->connLocal, $this->connRemote);
                        $conflicts2 = DB::connection($this->connLocal)
                            ->table($tabla)
                            ->whereIn('id', $ids)
                            ->whereNotIn('sincro_id', $sids)
                            ->count();
                        if ($conflicts2 > 0) {
                            throw new \RuntimeException("Conflicto de IDs en {$tabla}: {$conflicts2} fila(s) con mismo 'id' pero distinto 'sincro_id'.");
                        } elseif ($fixed > 0) {
                            $this->warn("ℹ [$tabla] Conflictos detectados: {$conflicts}. Reconciliados: {$fixed}.");
                        }
                    }
                }
                // Split por existencia previa de sids
                $existentes = DB::connection($this->connLocal)
                    ->table($tabla)
                    ->whereIn('sincro_id', $sids)
                    ->pluck('sincro_id')
                    ->all();
                $ya = array_flip($existentes);
                $toUpdate = [];
                $toInsert = [];
                foreach ($lote as $row) {
                    if (isset($ya[$row['sincro_id']])) {
                        // Evitar rebote
                        if (in_array($tabla, $this->tablasEvitarUpdateSiSidExiste, true)) {
                            continue; // ignorar: ni update ni insert
                        }
                        $toUpdate[] = $row;
                    } else {
                        $toInsert[] = $row;
                    }
                }
                // Columnas a actualizar
                $updateCols = array_values(array_diff($cols, ['id', 'created_at']));
                if ($hasIdVpsLocal) {
                    $updateCols[] = 'id_vps';
                }
                $updateCols = array_values(array_unique($updateCols));
                DB::connection($this->connLocal)->transaction(function () use ($tabla, $toUpdate, $toInsert, $updateCols, &$insertados, &$actualizados) {
                    // UPDATE por sincro_id
                    if (!empty($toUpdate)) {
                        foreach ($toUpdate as $row) {
                            $data = array_intersect_key($row, array_flip($updateCols));
                            // Proteger: si solicitud ya está Finalizada en local, no pisar el estado
                            if ($tabla === 'solicitudes' && isset($data['estado'])) {
                                $estadoLocal = DB::connection($this->connLocal)
                                    ->table($tabla)
                                    ->where('sincro_id', $row['sincro_id'])
                                    ->value('estado');
                                if ($estadoLocal === 'Finalizado') {
                                    unset($data['estado']);
                                }
                            }
                            // DTEs: si en VPS está Procesado, actualizar local con estado y sello del VPS
                            if ($tabla === 'dtes' && isset($row['estado']) && $row['estado'] === 'Procesado') {
                                $estadoLocal = DB::connection($this->connLocal)
                                    ->table('dtes')
                                    ->where('sincro_id', $row['sincro_id'])
                                    ->value('estado');
                                if ($estadoLocal !== 'Procesado') {
                                    $data['estado'] = 'Procesado';
                                    $data['sello']  = $row['sello'] ?? $data['sello'] ?? null;
                                }
                            }
                            // Proteger: si ajuste ya fue aplicado localmente, no pisar el status
                            if ($tabla === 'ajustes' && isset($data['status'])) {
                                $ajusteLocal = DB::connection($this->connLocal)
                                    ->table($tabla)
                                    ->where('sincro_id', $row['sincro_id'])
                                    ->first(['status', 'aplicado_local']);
                                if ($ajusteLocal && ($ajusteLocal->status === 'Finalizado' || !empty($ajusteLocal->aplicado_local))) {
                                    unset($data['status']);
                                }
                            }
                            DB::connection($this->connLocal)
                                ->table($tabla)
                                ->where('sincro_id', $row['sincro_id'])
                                ->update($data);
                        }
                        $actualizados += count($toUpdate);
                    }
                    // INSERT nuevos
                    if (!empty($toInsert)) {
                        DB::connection($this->connLocal)->table($tabla)->insert($toInsert);
                        $insertados += count($toInsert);
                    }
                });
                $procesadas += count($toInsert) + count($toUpdate);
            });
        } catch (Throwable $e) {
            $estado  = 'ERROR';
            $mensaje = $e->getMessage();
            $this->error("✘ Error en [$tabla]: $mensaje");
        } finally {
            if ($deshabilitarFK) {
                DB::connection($this->connLocal)->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
        if ($procesadas > 0 && ($estado ?? 'OK') === 'OK') {
            $this->putWatermark($tabla, $maxAt, (int) $maxId);
            $this->info("   ✔ {$tabla}: {$procesadas} filas. Nuevo watermark: {$maxAt} / {$maxId}");
        } else {
            $this->line("   • {$tabla}: sin cambios.");
        }
        $regTotal = $insertados + $actualizados;
        if (Schema::connection($this->connLocal)->hasTable('bitacora_sincronizacions')) {
            if (($estado ?? 'OK') !== 'OK' || $regTotal > 0) {
                DB::connection($this->connLocal)
                    ->table('bitacora_sincronizacions')
                    ->insert([
                        'sincro_id'    => (string) Str::uuid(),
                        'tabla'        => $tabla,
                        'origen'       => 'vps',
                        'destino'      => 'local',
                        'sucursal'     => $this->sucursal,
                        'registros'    => $regTotal,
                        'insertados'   => $insertados,
                        'actualizados' => $actualizados,
                        'estado'       => $estado ?? 'OK',
                        'mensaje'      => $mensaje ?? null,
                        'fecha'        => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
            }
        }
    }
    protected function aplicarFiltroSucursal(string $tabla, Builder $query, array $colsRemotos): bool
    {
        $colFiltroPara = function (string $t, array $cols) {
            $cand = $this->colSucursalOverrides[$t] ?? $this->candidatosColSucursal;
            return $this->primeraColumnaExistente($cand, $cols);
        };
        // 🔹 Tablas "padre"
        if (in_array($tabla, ['compras', 'solicitudes', 'ajustes', 'ventas_resumen', 'inventarios'], true)) {
            $col = $colFiltroPara($tabla, $colsRemotos);
            if (!$col) {
                return false;
            }
            // inventarios y ventas_resumen: TODAS menos las sucursalesPermitidas (ej. todas menos 3)
            if (in_array($tabla, ['ventas_resumen', 'inventarios'], true)) {
                $query->whereNotIn($col, $this->sucursalesPermitidas);
            } else {
                // compras / solicitudes / ajustes: SOLO sucursalesPermitidas (ej. solo 3)
                $query->whereIn($col, $this->sucursalesPermitidas);
            }
            return true;
        }
        // 🔹 Tablas de detalle (siguen al padre): compras_detalles, solicitudes_detalles, ajustes_detalles
        if (in_array($tabla, ['compras_detalles', 'solicitudes_detalles', 'ajustes_detalles'], true)) {
            $fkCands = $this->fkCandidatas[$tabla] ?? [];
            if ($tabla === 'ajustes_detalles' && empty($fkCands)) {
                $fkCands = ['ajuste_id', 'ajuste'];
            }
            $fkCol = $this->primeraColumnaExistente($fkCands, $colsRemotos);
            if (!$fkCol) {
                return false;
            }
            $parent = $tabla === 'compras_detalles'
                ? 'compras'
                : ($tabla === 'solicitudes_detalles' ? 'solicitudes' : 'ajustes');
            if (!Schema::connection($this->connRemote)->hasTable($parent)) {
                return false;
            }
            $colsParent        = Schema::connection($this->connRemote)->getColumnListing($parent);
            $colSucursalParent = $colFiltroPara($parent, $colsParent);
            if (!$colSucursalParent) {
                return false;
            }
            // Para padres (compras / solicitudes / ajustes) solo queremos sucursalesPermitidas (ej. 3)
            $sub = DB::connection($this->connRemote)
                ->table($parent)
                ->select('id')
                ->whereIn($colSucursalParent, $this->sucursalesPermitidas);
            $query->whereIn($fkCol, $sub);
            return true;
        }
        return false;
    }
    protected function primeraColumnaExistente(array $candidatas, array $colsTabla): ?string
    {
        foreach ($candidatas as $c) {
            if (in_array($c, $colsTabla, true)) {
                return $c;
            }
        }
        return null;
    }
    /* ========= Watermark ========= */
    protected function getWatermark(string $tabla): array
    {
        if (!Schema::connection($this->connLocal)->hasTable('sync_states')) {
            return [null, 0];
        }
        $row = DB::connection($this->connLocal)
            ->table('sync_states')
            ->where('direction', $this->direction)
            ->where('table', $tabla)
            ->first();
        return [$row?->watermark_updated_at ?? null, (int) ($row?->watermark_id ?? 0)];
    }
    protected function putWatermark(string $tabla, ?string $wmAt, int $wmId): void
    {
        if (!Schema::connection($this->connLocal)->hasTable('sync_states')) {
            return;
        }
        DB::connection($this->connLocal)
            ->table('sync_states')
            ->updateOrInsert(
                ['direction' => $this->direction, 'table' => $tabla],
                [
                    'watermark_updated_at' => $wmAt,
                    'watermark_id'         => $wmId,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ],
            );
    }
    protected function computeWindowMinutesFromBitacora(string $tabla): int
    {
        $base = 5;
        if (!Schema::connection($this->connLocal)->hasTable('bitacora_sincronizacions')) {
            return 30;
        }
        $lastOk = DB::connection($this->connLocal)
            ->table('bitacora_sincronizacions')
            ->where('tabla', $tabla)
            ->where('estado', 'OK')
            ->orderByDesc('id')
            ->value('created_at');
        if (!$lastOk) {
            return max($base, 30);
        }
        $minsSinceOk = now()->diffInMinutes(Carbon::parse($lastOk));
        return min(max($base, $minsSinceOk), $this->maxWindow);
    }
    /* ========= Reconciliar id ↔ sincro_id (anti-colisión) ========= */
    /**
     * Tablas hijas con FK hacia una tabla padre dada.
     * Se usan para reparar el FK cuando renombramos el id del padre.
     */
    protected array $childFkMap = [
        'compras'     => [
            ['table' => 'compras_detalles', 'col' => 'compra'],
            ['table' => 'cuentas_pagars',   'col' => 'compra'],
        ],
        'solicitudes' => [
            ['table' => 'solicitudes_detalles', 'col' => 'solicitud'],
        ],
        'ajustes'     => [
            ['table' => 'ajustes_detalles', 'col' => 'ajuste'],
        ],
    ];
    protected function reconciliarIdVsSincro(string $tabla, array $ids, array $sidsRemotos, string $connLocal, string $connRemote): int
    {
        $remotos = DB::connection($connRemote)
            ->table($tabla)
            ->select('id', 'sincro_id', 'updated_at')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
        if ($remotos->isEmpty()) {
            return 0;
        }
        $porActualizar = DB::connection($connLocal)
            ->table($tabla)
            ->select(['id', 'sincro_id'])
            ->whereIn('id', $ids)
            ->get()
            ->filter(function ($loc) use ($remotos) {
                $rem = $remotos->get($loc->id);
                return $rem && $loc->sincro_id !== $rem->sincro_id && !empty($rem->sincro_id);
            });
        if ($porActualizar->isEmpty()) {
            return 0;
        }
        $total = 0;
        DB::connection($connLocal)->transaction(function () use ($tabla, $porActualizar, $remotos, &$total, $connLocal) {
            foreach ($porActualizar as $loc) {
                $rem = $remotos->get($loc->id);
                if (!$rem || empty($rem->sincro_id)) {
                    continue;
                }
                // Cuando id coincide pero sincro_id difiere, son registros GENUINAMENTE DISTINTOS
                // (auto-increment independiente en cada base). El sincro_id es la identidad del
                // registro y NUNCA debe cambiarse. Renombramos el id local a un valor libre para
                // que el registro del VPS pueda insertarse limpiamente con su id original.
                $newId    = (int) DB::connection($connLocal)->table($tabla)->max('id') + 1;
                $children = $this->childFkMap[$tabla] ?? [];
                DB::connection($connLocal)->statement('SET FOREIGN_KEY_CHECKS=0');
                // Actualizar FKs en tablas hijas antes de renombrar el padre
                foreach ($children as $child) {
                    if (Schema::connection($connLocal)->hasTable($child['table'])
                        && Schema::connection($connLocal)->hasColumn($child['table'], $child['col'])) {
                        DB::connection($connLocal)
                            ->table($child['table'])
                            ->where($child['col'], $loc->id)
                            ->update([$child['col'] => $newId]);
                    }
                }
                // Renombrar el registro local al nuevo ID (conserva sincro_id)
                DB::connection($connLocal)
                    ->table($tabla)
                    ->where('id', $loc->id)
                    ->update(['id' => $newId]);
                DB::connection($connLocal)->statement('SET FOREIGN_KEY_CHECKS=1');
                $this->warn("   ↳ [{$tabla}] Conflicto id={$loc->id}: local sincro_id={$loc->sincro_id} renombrado a id={$newId}. VPS sincro_id={$rem->sincro_id} se insertará con id={$loc->id}.");
                $total++;
            }
        });
        return $total;
    }
    /* ========= Helpers sucursal ========= */
    protected function resolverSucursales(): array
    {
        $opt      = (string) ($this->option('sucursales') ?? '');
        $envSingle = env('APP_SUCURSAL_ID') ?? env('SUCURSAL_ID') ?? config('app.sucursal_id');
        $envList  = env('SUCURSALES_IDS');
        $raw = $opt !== '' ? $opt : ($envList ?: $envSingle ?? '');
        $vals = array_values(
            array_filter(
                array_map(function ($v) {
                    $v = trim((string) $v);
                    if ($v === '') {
                        return null;
                    }
                    return ctype_digit($v) ? (int) $v : null;
                }, explode(',', (string) $raw)),
            ),
        );
        return array_values(array_unique($vals));
    }
    protected function procesarAjustesPendientes(): void
    {
        $kdxTable = $this->getKardexTable();
        if (
            !Schema::connection($this->connLocal)->hasTable('ajustes') ||
            !Schema::connection($this->connLocal)->hasTable('ajustes_detalles') ||
            !Schema::connection($this->connLocal)->hasTable('inventarios') ||
            !Schema::connection($this->connLocal)->hasTable($kdxTable)
        ) {
            $this->warn('Ajustes: faltan tablas locales (ajustes/ajustes_detalles/inventarios/kardexes2). Saltando.');
            return;
        }
        $this->info('→ Aplicando ajustes pendientes a inventario/kardex…');
        // Columnas disponibles
        $ajCols  = Schema::connection($this->connLocal)->getColumnListing('ajustes');
        $detCols = Schema::connection($this->connLocal)->getColumnListing('ajustes_detalles');
        $kdxCols = Schema::connection($this->connLocal)->getColumnListing($kdxTable);
        $tieneAplicadoLocal = in_array('aplicado_local', $ajCols, true);
        $tieneStatus        = in_array('status', $ajCols, true);
        $tieneTipo          = in_array('tipo', $ajCols, true);
        // Detectar si hay alguna columna de usuario en kardexes
        $userCol = $this->primeraColumnaExistente(['user', 'usuario', 'user_id', 'id_user', 'idUsuario'], $kdxCols);
        // Base query: ajustes de sucursales permitidas
        $ajustesQ = DB::connection($this->connLocal)->table('ajustes');
        // Filtro por sucursal si existe la columna
        $colSuc = $this->primeraColumnaExistente($this->candidatosColSucursal, $ajCols) ?? 'sucursal';
        if (in_array($colSuc, $ajCols, true) && !empty($this->sucursalesPermitidas)) {
            $ajustesQ->whereIn($colSuc, $this->sucursalesPermitidas);
        }
        // Solo status=Finalizado si existe
        if ($tieneStatus) {
            $ajustesQ->whereIn('status', ['Ingresado', 'Realizado']);
        }
        // Idempotencia adicional: si existe aplicado_local, solo NULL
        if ($tieneAplicadoLocal) {
            $ajustesQ->whereNull('aplicado_local');
        }
        $ajustes   = $ajustesQ->orderByDesc('updated_at')->limit(1000)->get();
        $aplicados = 0;
        foreach ($ajustes as $aj) {
            $ajusteId = $aj->id;
            $tipo     = $tieneTipo ? ($aj->tipo ?? 'Ingreso') : 'Ingreso';
            $sucursal = in_array($colSuc, $ajCols, true) ? ($aj->{$colSuc}) : null;
            $fecha    = $aj->fecha ?? now()->toDateString();
            $detalle  = $aj->detalle ?? '';
            // Omitir ajustes de sucursales VPS — VPS aplica su propio kardex.
            // Consultar en connRemote (VPS) como fuente de verdad del modo,
            // porque en local puede estar desactualizado y causar doble aplicación.
            if ($sucursal !== null) {
                $modoSuc = DB::connection($this->connRemote)
                    ->table('sucursales')->where('id', $sucursal)->value('modo');
                if ($modoSuc !== 'local') continue;
            }
            // Idempotencia por Kardex: ya existe asiento de este ajuste
            if ($this->existeKardexAplicado($ajusteId)) {
                if ($tieneAplicadoLocal) {
                    $updateData = ['aplicado_local' => now(), 'updated_at' => now()];
                    if ($tieneStatus) {
                        $updateData['status'] = 'Finalizado';
                    }
                    DB::connection($this->connLocal)
                        ->table('ajustes')
                        ->where('id', $ajusteId)
                        ->update($updateData);
                }
                continue;
            }
            $detalles = $this->obtenerDetallesAjuste($ajusteId, $detCols);
            if ($detalles->isEmpty()) continue;
            DB::connection($this->connLocal)->transaction(function () use ($detalles, $ajusteId, $tipo, $sucursal, $fecha, $detalle, $userCol, $kdxTable) {
                foreach ($detalles as $it) {
                    $producto   = $it->producto;
                    $medida     = $it->medida ?? null;
                    $ingresoQty = (float) ($it->ingreso ?? $it->cantidad ?? 0);
                    $costoUnit  = (float) ($it->costo ?? 0);
                    $totalVal   = (float) ($it->total ?? ($costoUnit * $ingresoQty));
                    // 1) Garantizar inventario
                    $inv = DB::connection($this->connLocal)
                        ->table('inventarios')
                        ->where('producto', $producto)
                        ->when($sucursal !== null, fn($q) => $q->where('sucursal', $sucursal))
                        ->first();
                    if (!$inv) {
                        $empresa = null;
                        if ($sucursal !== null && Schema::connection($this->connLocal)->hasTable('sucursales')) {
                            $empresa = DB::connection($this->connLocal)
                                ->table('sucursales')
                                ->where('id', $sucursal)
                                ->value('empresa');
                        }
                        $invId = DB::connection($this->connLocal)
                            ->table('inventarios')
                            ->insertGetId([
                                'producto'   => $producto,
                                'empresa'    => $empresa,
                                'sucursal'   => $sucursal,
                                'existencia' => 0.00,
                                'sincro_id'  => (string) Str::uuid(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $inv   = DB::connection($this->connLocal)
                            ->table('inventarios')
                            ->where('id', $invId)
                            ->first();
                    }
                    // 2) Calcular nueva existencia
                    $existAct   = (float) $inv->existencia;
                    $esIngreso  = (stripos($tipo, 'Ingreso') !== false);
                    $nuevaExist = $esIngreso ? ($existAct + $ingresoQty) : max(0.00, $existAct - $ingresoQty);
                    // 3) Último saldoValor en Kardex
                    $ulti = DB::connection($this->connLocal)
                        ->table($kdxTable)
                        ->where('producto', $producto)
                        ->where('inventario', $inv->id)
                        ->orderByDesc('id')
                        ->first();
                    $saldoCantidad = $nuevaExist;
                    if ($esIngreso) {
                        $saldoValor = $ulti ? (float) $ulti->saldoValor + $totalVal : $totalVal;
                        $ingC       = $ingresoQty;
                        $ingV       = $totalVal;
                        $egrC       = 0.00;
                        $egrV       = 0.00;
                        $desc       = 'Ingreso por Ajuste #' . $ajusteId . ' (sync) ' . $detalle;
                    } else {
                        $saldoValor = $ulti ? max(0.00, (float) $ulti->saldoValor - $totalVal) : 0.00;
                        $ingC       = 0.00;
                        $ingV       = 0.00;
                        $egrC       = $ingresoQty;
                        $egrV       = $totalVal;
                        $desc       = 'Egreso por Ajuste #' . $ajusteId . ' (sync) ' . $detalle;
                    }
                    // 4) Actualizar inventario
                    DB::connection($this->connLocal)
                        ->table('inventarios')
                        ->where('id', $inv->id)
                        ->update(['existencia' => $nuevaExist, 'updated_at' => now()]);
                    // 5) Insertar Kardex (sin columna de usuario si no existe)
                    $dataKardex = [
                        'producto'        => $producto,
                        'inventario'      => $inv->id,
                        'descripcion'     => $desc,
                        'fecha'           => $fecha ?: now()->toDateString(),
                        'hora'            => now()->format('H:i:s'),
                        'ingresoCantidad' => $ingC,
                        'ingresoValor'    => $ingV,
                        'egresoCantidad'  => $egrC,
                        'egresoValor'     => $egrV,
                        'saldoCantidad'   => $saldoCantidad,
                        'saldoValor'      => $saldoValor,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                    // Agregar sucursal sólo si existe esa columna
                    if (in_array('sucursal', $kdxCols, true)) {
                        $dataKardex['sucursal'] = $sucursal;
                    }
                    // Agregar user/usuario sólo si existe esa columna
                    if ($userCol) {
                        $dataKardex[$userCol] = $ajusteId; // marcador del origen
                    }
                    DB::connection($this->connLocal)
                        ->table($kdxTable)
                        ->insert($dataKardex);
                }
            });
            if ($tieneAplicadoLocal) {
                $updateData = ['aplicado_local' => now(), 'updated_at' => now()];
                if ($tieneStatus) {
                    $updateData['status'] = 'Finalizado';
                }
                DB::connection($this->connLocal)
                    ->table('ajustes')
                    ->where('id', $ajusteId)
                    ->update($updateData);
            }
            $aplicados++;
        }
        $this->info("   ✔ Ajustes aplicados: {$aplicados}");
    }
    /** Devuelve true si ya existe un Kardex generado por este sync para el ajuste (idempotencia). */
    protected function existeKardexAplicado(int $ajusteId): bool
    {
        $kdxTable = $this->getKardexTable();
        if (!Schema::connection($this->connLocal)->hasTable($kdxTable)) {
            return false;
        }
        // [2026-03-24] Buscar cualquier kardex con #ID — con o sin "(sync)" para detectar los creados por AprobacionAjuste directo
        $existe = DB::connection($this->connLocal)
            ->table($kdxTable)
            ->where(function ($q) use ($ajusteId) {
                $q->where('descripcion', 'like', 'Ingreso por Ajuste #' . $ajusteId . '%')
                  ->orWhere('descripcion', 'like', 'Egreso por Ajuste #' . $ajusteId . '%');
            })
            ->exists();
        return $existe;
    }
    /** Lee detalles del ajuste con columnas habituales (tolerante a esquemas). */
    protected function obtenerDetallesAjuste(int $ajusteId, array $detCols)
    {
        $fk = $this->primeraColumnaExistente(['ajuste_id', 'ajuste'], $detCols) ?? 'ajuste';
        $colsDeseadas = array_values(array_intersect($detCols, [
            'id', 'sincro_id', 'ajuste', 'ajuste_id', 'producto', 'inventario',
            'medida', 'cantidad', 'ingreso', 'costo', 'total', 'created_at', 'updated_at'
        ]));
        // Buscar por ID local primero; si no hay resultados, buscar por id_vps como fallback
        $result = DB::connection($this->connLocal)
            ->table('ajustes_detalles')
            ->select($colsDeseadas ?: ['*'])
            ->where($fk, $ajusteId)
            ->get();
        if ($result->isEmpty()) {
            $idVps = DB::connection($this->connLocal)
                ->table('ajustes')
                ->where('id', $ajusteId)
                ->value('id_vps');
            if ($idVps && $idVps != $ajusteId) {
                $result = DB::connection($this->connLocal)
                    ->table('ajustes_detalles')
                    ->select($colsDeseadas ?: ['*'])
                    ->where($fk, $idVps)
                    ->get();
            }
        }
        return $result;
    }
    /**
     * Remapea creditos.venta de ID_VPS → ID_LOCAL.
     * La venta que llega en el crédito es el id del VPS; localmente ventas.id_vps lo mapea.
     */
    protected function remapearFKCreditos(): void
    {
        if (!Schema::connection($this->connLocal)->hasTable('creditos') ||
            !Schema::connection($this->connLocal)->hasTable('ventas')) {
            return;
        }
        if (!Schema::connection($this->connLocal)->hasColumn('ventas', 'id_vps')) {
            $this->warn('   ⚠ ventas.id_vps no existe — no se puede remapear creditos.venta.');
            return;
        }
        // Créditos cuyo campo venta coincide con el id_vps de una venta local (no con el id local)
        $creditos = DB::connection($this->connLocal)
            ->table('creditos as cr')
            ->join('ventas as v', 'v.id_vps', '=', 'cr.venta')
            ->whereColumn('cr.venta', '!=', 'v.id')
            ->select('cr.id as credito_id', 'cr.venta as venta_vps', 'v.id as venta_local')
            ->get();
        $remapeados = 0;
        foreach ($creditos as $cr) {
            DB::connection($this->connLocal)->statement('SET FOREIGN_KEY_CHECKS=0');
            DB::connection($this->connLocal)
                ->table('creditos')
                ->where('id', $cr->credito_id)
                ->update(['venta' => $cr->venta_local]);
            DB::connection($this->connLocal)->statement('SET FOREIGN_KEY_CHECKS=1');
            $remapeados++;
        }
        if ($remapeados > 0) {
            $this->info("   ✔ creditos: {$remapeados} FK(s) de venta remapeadas de id_vps a id_local.");
        }
    }
    /**
     * Remapea ajustes_detalles.ajuste de ID_VPS a ID_LOCAL.
     * Necesario cuando el ajuste fue reconciliado y su id local difiere del id en VPS.
     */
    protected function remapearFKAjustesDetalles(): void
    {
        if (!Schema::connection($this->connLocal)->hasTable('ajustes') ||
            !Schema::connection($this->connLocal)->hasTable('ajustes_detalles')) {
            return;
        }
        $detCols = Schema::connection($this->connLocal)->getColumnListing('ajustes_detalles');
        $fkCol   = $this->primeraColumnaExistente(['ajuste_id', 'ajuste'], $detCols) ?? 'ajuste';
        // Ajustes cuyo id_vps difiere del id local (fueron reconciliados)
        $ajustes = DB::connection($this->connLocal)
            ->table('ajustes')
            ->whereNotNull('id_vps')
            ->whereColumn('id', '!=', 'id_vps')
            ->get(['id', 'id_vps']);
        $remapeados = 0;
        foreach ($ajustes as $aj) {
            $affected = DB::connection($this->connLocal)
                ->table('ajustes_detalles')
                ->where($fkCol, $aj->id_vps)
                ->update([$fkCol => $aj->id]);
            $remapeados += $affected;
        }
        if ($remapeados > 0) {
            $this->info("   ✔ ajustes_detalles: {$remapeados} FK(s) remapeadas de id_vps a id_local.");
        }
    }
    /**
     * Aplica SOLO los ajustes nuevos/actualizados en esta corrida,
     * comparando contra los watermarks capturados ANTES del sync.
     */
    protected function procesarAjustesIncrementales(): void
    {
        if (
            !Schema::connection($this->connLocal)->hasTable('ajustes') ||
            !Schema::connection($this->connLocal)->hasTable('ajustes_detalles')
        ) {
            $this->warn('Ajustes: faltan tablas locales (ajustes/ajustes_detalles). Saltando incrementales.');
            return;
        }
        $this->info('→ Aplicando ajustes NUEVOS (por created_at y sincro_id no existente antes del sync)…');
        $ajCols        = Schema::connection($this->connLocal)->getColumnListing('ajustes');
        $tieneStatus   = in_array('status', $ajCols, true);
        $tieneAplicadoLoc = in_array('aplicado_local', $ajCols, true);
        $colSuc        = $this->primeraColumnaExistente($this->candidatosColSucursal, $ajCols) ?? 'sucursal';
        // Watermark capturado ANTES del sync (pero lo usamos con CREATED_AT)
        $wmAt = $this->wmAjustesAtBefore;
        $wmId = $this->wmAjustesIdBefore;
        $ajustesQ = DB::connection($this->connLocal)
            ->table('ajustes')
            ->select('id', 'sincro_id')
            ->whereNotNull('sincro_id');
        // Solo ajustes realmente nuevos por created_at
        if ($wmAt) {
            $ajustesQ->where(function ($q) use ($wmAt, $wmId) {
                $q->where('updated_at', '>', $wmAt)
                    ->orWhere(function ($q2) use ($wmAt, $wmId) {
                        $q2->where('updated_at', '=', $wmAt)
                            ->where('id', '>', $wmId);
                    });
            });
        }
        if ($tieneStatus) {
            $ajustesQ->whereIn('status', ['Ingresado', 'Realizado']);
        }
        if ($tieneAplicadoLoc) {
            $ajustesQ->whereNull('aplicado_local');
        }
        if (in_array($colSuc, $ajCols, true) && !empty($this->sucursalesPermitidas)) {
            $ajustesQ->whereIn($colSuc, $this->sucursalesPermitidas);
        }
        // ⬅️ CLAVE: excluir los sincro_id que YA existían antes del sync
        if (!empty($this->ajustesSidBefore)) {
            $ajustesQ->whereNotIn('sincro_id', $this->ajustesSidBefore);
        }
        $ids = $ajustesQ->pluck('id')->all();
        if (empty($ids)) {
            $this->line('   • No hay ajustes nuevos que aplicar.');
            return;
        }
        $aplicados = 0;
        foreach (array_chunk($ids, 100) as $chunk) {
            $aplicados += $this->aplicarAjustesPorIds($chunk);
        }
        $this->info("   ✔ Ajustes aplicados (incremental): {$aplicados}");
    }
    /**
     * Aplica la lógica de inventario+kardex para una lista de IDs de ajustes.
     * Reutiliza internamente la misma lógica de procesarAjustesPendientes pero focalizada.
     */
    protected function getKardexTable(): string
    {
        if (Schema::connection($this->connLocal)->hasTable('kardexes2')) {
            return 'kardexes2';
        }
        if (Schema::connection($this->connLocal)->hasTable('kardexes')) {
            return 'kardexes';
        }
        return 'kardexes2';
    }
    protected function aplicarAjustesPorIds(array $ajusteIds): int
    {
        if (empty($ajusteIds)) return 0;
        $ajCols  = Schema::connection($this->connLocal)->getColumnListing('ajustes');
        $detCols = Schema::connection($this->connLocal)->getColumnListing('ajustes_detalles');
        // Usaremos SIEMPRE kardexes2 (como dijiste que es la correcta)
        $kdxTable = $this->getKardexTable();
        if (!Schema::connection($this->connLocal)->hasTable($kdxTable)) {
            $this->error("❌ No existe la tabla {$kdxTable} en la conexión local.");
            return 0;
        }
        $kdxCols            = Schema::connection($this->connLocal)->getColumnListing($kdxTable);
        $tieneAplicadoLocal = in_array('aplicado_local', $ajCols, true);
        $tieneStatus        = in_array('status', $ajCols, true);
        $tieneTipo          = in_array('tipo', $ajCols, true);
        $colSuc             = $this->primeraColumnaExistente($this->candidatosColSucursal, $ajCols) ?? 'sucursal';
        $userCol            = $this->primeraColumnaExistente(['user', 'usuario', 'user_id', 'id_user', 'idUsuario'], $kdxCols);
        $fkDet = $this->primeraColumnaExistente(['ajuste_id', 'ajuste'], $detCols) ?? 'ajuste';
        $aplicados = 0;
        $ajustes = DB::connection($this->connLocal)
            ->table('ajustes')
            ->whereIn('id', $ajusteIds)
            ->get();
        foreach ($ajustes as $aj) {
            $ajusteId = (int) $aj->id;
            $tipo     = $tieneTipo ? ($aj->tipo ?? 'Ingreso') : 'Ingreso';
            $sucursal = in_array($colSuc, $ajCols, true) ? ($aj->{$colSuc}) : null;
            $fecha    = $aj->fecha ?? now()->toDateString();
            $detalle  = $aj->detalle ?? '';
            // Omitir ajustes de sucursales VPS — VPS ya aplicó el kardex directamente.
            // Consultar en connRemote (VPS) como fuente de verdad del modo,
            // porque en local puede estar desactualizado y causar doble aplicación.
            if ($sucursal !== null) {
                $modoSuc = DB::connection($this->connRemote)
                    ->table('sucursales')
                    ->where('id', $sucursal)
                    ->value('modo');
                if ($modoSuc !== 'local') {
                    continue;
                }
            }
            // [2026-03-24] Idempotencia: detectar kardex con #ID creado por AprobacionAjuste directo o por sync
            $yaExiste = DB::connection($this->connLocal)
                ->table($kdxTable)
                ->where(function ($q) use ($ajusteId) {
                    $q->where('descripcion', 'like', "Ingreso por Ajuste #{$ajusteId}%")
                      ->orWhere('descripcion', 'like', "Egreso por Ajuste #{$ajusteId}%");
                })
                ->exists();
            if ($yaExiste) {
                if ($tieneAplicadoLocal) {
                    $updateData = ['aplicado_local' => now(), 'updated_at' => now()];
                    if ($tieneStatus) {
                        $updateData['status'] = 'Finalizado';
                    }
                    DB::connection($this->connLocal)
                        ->table('ajustes')
                        ->where('id', $ajusteId)
                        ->update($updateData);
                }
                continue;
            }
            $detalles = DB::connection($this->connLocal)
                ->table('ajustes_detalles')
                ->where($fkDet, $ajusteId)
                ->get();
            if ($detalles->isEmpty()) continue;
            DB::connection($this->connLocal)->transaction(function () use ($detalles, $ajusteId, $tipo, $sucursal, $fecha, $detalle, $userCol, $kdxTable, $kdxCols) {
                foreach ($detalles as $it) {
                    $producto   = (int) $it->producto;
                    $ingresoQty = (float) ($it->ingreso ?? $it->cantidad ?? 0);
                    $costoUnit  = (float) ($it->costo ?? 0);
                    $totalVal   = (float) ($it->total ?? ($costoUnit * $ingresoQty));
                    // Inventario (crear si no existe)
                    $inv = DB::connection($this->connLocal)
                        ->table('inventarios')
                        ->where('producto', $producto)
                        ->when($sucursal !== null, fn($q) => $q->where('sucursal', $sucursal))
                        ->first();
                    if (!$inv) {
                        $empresa = null;
                        if ($sucursal !== null && Schema::connection($this->connLocal)->hasTable('sucursales')) {
                            $empresa = DB::connection($this->connLocal)
                                ->table('sucursales')
                                ->where('id', $sucursal)
                                ->value('empresa');
                        }
                        $invId = DB::connection($this->connLocal)
                            ->table('inventarios')
                            ->insertGetId([
                                'producto'   => $producto,
                                'empresa'    => $empresa,
                                'sucursal'   => $sucursal,
                                'existencia' => 0.00,
                                'sincro_id'  => (string) Str::uuid(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $inv   = DB::connection($this->connLocal)
                            ->table('inventarios')
                            ->where('id', $invId)
                            ->first();
                    }
                    $existAct   = (float) $inv->existencia;
                    $esIngreso  = (stripos($tipo, 'Ingreso') !== false);
                    $nuevaExist = $esIngreso ? ($existAct + $ingresoQty) : max(0.00, $existAct - $ingresoQty);
                    $ulti = DB::connection($this->connLocal)
                        ->table($kdxTable)
                        ->where('producto', $producto)
                        ->where('inventario', $inv->id)
                        ->orderByDesc('id')
                        ->first();
                    if ($esIngreso) {
                        $saldoValor = $ulti ? (float) $ulti->saldoValor + $totalVal : $totalVal;
                        $ingC       = $ingresoQty;
                        $ingV       = $totalVal;
                        $egrC       = 0.00;
                        $egrV       = 0.00;
                        $desc       = 'Ingreso por Ajuste #' . $ajusteId . ' (sync) ' . $detalle .' Aplicado en PC Local';
                    } else {
                        $saldoValor = $ulti ? max(0.00, (float) $ulti->saldoValor - $totalVal) : 0.00;
                        $ingC       = 0.00;
                        $ingV       = 0.00;
                        $egrC       = $ingresoQty;
                        $egrV       = $totalVal;
                        $desc       = 'Egreso por Ajuste #' . $ajusteId . ' (sync) ' . $detalle .' Aplicado en PC Local';
                    }
                    // Actualiza inventario
                    DB::connection($this->connLocal)
                        ->table('inventarios')
                        ->where('id', $inv->id)
                        ->update(['existencia' => $nuevaExist, 'updated_at' => now()]);
                    // Build INSERT para kardexes2
                    $dataKardex = [
                        'producto'        => $producto,
                        'inventario'      => $inv->id,
                        'descripcion'     => $desc,
                        'fecha'           => $fecha ?: now()->toDateString(),
                        'hora'            => now()->format('H:i:s'),
                        'ingresoCantidad' => $ingC,
                        'ingresoValor'    => $ingV,
                        'egresoCantidad'  => $egrC,
                        'egresoValor'     => $egrV,
                        'saldoCantidad'   => $nuevaExist,
                        'saldoValor'      => $saldoValor,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                    //  OBLIGATORIO: añade sincro_id si existe esa columna (evita el 1364)
                    if (in_array('sincro_id', $kdxCols, true)) {
                        $dataKardex['sincro_id'] = (string) Str::uuid();
                    }
                    // opcional: sincronizacion
                    if (in_array('sincronizacion', $kdxCols, true)) {
                        $dataKardex['sincronizacion'] = now();
                    }
                    // opcional: sucursal
                    if (in_array('sucursal', $kdxCols, true)) {
                        $dataKardex['sucursal'] = $sucursal;
                    }
                    // opcional: user/usuario
                    if ($userCol) {
                        // guarda el id del ajuste como referencia, o cambia por Auth::id() si prefieres
                        $dataKardex[$userCol] = $ajusteId;
                    }
                    DB::connection($this->connLocal)
                        ->table($kdxTable)
                        ->insert($dataKardex);
                }
            });
            if ($tieneAplicadoLocal) {
                $updateData = ['aplicado_local' => now(), 'updated_at' => now()];
                if ($tieneStatus) {
                    $updateData['status'] = 'Finalizado';
                }
                DB::connection($this->connLocal)
                    ->table('ajustes')
                    ->where('id', $ajusteId)
                    ->update($updateData);
            }
            $aplicados++;
        }
        return $aplicados;
    }
}
