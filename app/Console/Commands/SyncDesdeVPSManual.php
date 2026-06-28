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

class SyncDesdeVPSManual extends Command
{
    protected $signature = 'sync:vps-local-manual
        {--stage= : Etapa (acl|compras|solicitudes|base|productos|precios|actores|users)}
        {--tables= : Lista de tablas separadas por coma (tiene prioridad sobre --stage)}
        {--sucursales= : IDs de sucursal (uno o varios separados por coma). Si se omite, usa env(SUCURSAL_ID)}
        {--acl-mode=merge : Modo ACL: snapshot|merge (snapshot = TRUNCATE+INSERT)}
        {--lookback=120 : Minutos a retroceder desde el watermark (manual)}';

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
        'dtes'      => ['tockens', 'dtes'],
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
        'tockens',
        'dtes',
    ];

    // 🔒 Se calcula en runtime desde --sucursales o .env; si nada viene, conserva este default
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
        'dtes',
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
        'ajustes',
        'ajustes_detalles'
    ];

    // ACL tables
    protected array $aclTables = ['roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions', 'users'];

    protected ?string $wmAjustesAtBefore    = null;
    protected int     $wmAjustesIdBefore    = 0;

    protected ?string $wmAjustesDetAtBefore = null;
    protected int     $wmAjustesDetIdBefore = 0;

    // Parámetros
    protected int    $manualLookbackMinutes = 120;
    protected int    $chunk         = 1000;
    protected string $direction     = 'vps_to_local';
    protected int    $maxWindow     = 600; // minutos (fallback)
    protected int    $bufferMinutes = 2;
    protected string $connRemote    = 'vpsmysql';
    protected string $connLocal     = 'localmysql';
    protected string $sucursal      = 'SERVIDOR -> TIENDA LOCAL';

    protected string $nodeId        = 'local-unknown';
    protected array  $ajustesSidBefore = [];

    public function handle()
    {
        // ✅ Valida conexiones
        try {
            DB::connection($this->connRemote)->getPdo();
            DB::connection($this->connLocal)->getPdo();
        } catch (Throwable $e) {
            $this->error('❌ Conexión fallida: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->nodeId = env('NODE_ID', 'local-unknown');

        $this->manualLookbackMinutes = max(0, (int) ($this->option('lookback') ?? 120));

        // ✅ Carga sucursales (CLI/.env) y conserva el default si viene vacío
        $resolved = $this->resolverSucursales();
        if (empty($resolved)) {
            $resolved = $this->sucursalesPermitidas; // mantiene default
        }
        $this->sucursalesPermitidas = $resolved;

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
                    'actualizados' => $totalUpdate,
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
        if ($tabla === 'dtes' && in_array('estado', $colsRemote, true)) {
            $query->whereRaw("UPPER(TRIM(estado)) = 'PROCESADO'");
        }

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
                    $wmAtAdj = Carbon::parse($wmAt)->subMinutes($this->manualLookbackMinutes);
                    $q->where(function ($q2) use ($wmAtAdj, $wmId) {
                        $q2->where('updated_at', '>', $wmAtAdj)
                            ->orWhere(function ($q3) use ($wmAtAdj, $wmId) {
                                $q3->where('updated_at', '=', $wmAtAdj)
                                    ->where('id', '>', $wmId);
                            });
                    });
                },
                function ($q) use ($tabla) {
                    //$window = $this->computeWindowMinutesFromBitacora($tabla);
                    $desde  = now()->subMinutes($this->manualLookbackMinutes);
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

        $deshabilitarFK = Str::endsWith($tabla, '_detalles');
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
                    if ($tabla === 'dtes' && !$this->prepararDteParaLocal($arr)) {
                        continue;
                    }

                    $lote[] = $arr;
                    $sids[] = $arr['sincro_id'];

                    $maxAt = $r->updated_at;
                    $maxId = $r->id;
                }

                if (empty($lote)) {
                    return;
                }

                // 🔒 PROTECCIÓN AJUSTES: si ajuste/detalle ya está Finalizado/aplicado localmente, ignorarlo completamente
                if ($tabla === 'ajustes' || $tabla === 'ajustes_detalles') {
                    $sidsAplicados = [];
                    if ($tabla === 'ajustes') {
                        $sidsAplicados = DB::connection($this->connLocal)
                            ->table('ajustes')
                            ->whereIn('sincro_id', $sids)
                            ->where(function ($q) {
                                $q->where('status', 'Finalizado')
                                  ->orWhereNotNull('aplicado_local');
                            })
                            ->pluck('sincro_id')
                            ->all();
                    } else {
                        // Para detalles, verificar si el detalle YA está aplicado localmente
                        $detColsLocal = Schema::connection($this->connLocal)->getColumnListing('ajustes_detalles');
                        $tieneDetStatus = in_array('status', $detColsLocal, true);
                        $tieneDetAplicado = in_array('aplicado_local', $detColsLocal, true);
                        if ($tieneDetStatus || $tieneDetAplicado) {
                            $q = DB::connection($this->connLocal)
                                ->table('ajustes_detalles')
                                ->whereIn('sincro_id', $sids);
                            $q->where(function ($q2) use ($tieneDetStatus, $tieneDetAplicado) {
                                if ($tieneDetStatus) {
                                    $q2->orWhere('status', 'Finalizado');
                                }
                                if ($tieneDetAplicado) {
                                    $q2->orWhereNotNull('aplicado_local');
                                }
                            });
                            $sidsAplicados = $q->pluck('sincro_id')->all();
                        }
                        // 🔒 Protección adicional: si el detalle no tiene status/aplicado_local (dato antiguo),
                        // verificar si su ajuste padre está Finalizado
                        $fkDetCol = $this->primeraColumnaExistente(['ajuste_id', 'ajuste'], $detColsLocal) ?? 'ajuste';
                        $detallesConPadreFinalizado = DB::connection($this->connLocal)
                            ->table('ajustes_detalles as ad')
                            ->join('ajustes as a', 'a.id', '=', 'ad.' . $fkDetCol)
                            ->whereIn('ad.sincro_id', $sids)
                            ->where(function ($q) {
                                $q->where('a.status', 'Finalizado')
                                  ->orWhereNotNull('a.aplicado_local');
                            })
                            ->pluck('ad.sincro_id')
                            ->all();
                        $sidsAplicados = array_values(array_unique(array_merge($sidsAplicados, $detallesConPadreFinalizado)));
                    }
                    if (!empty($sidsAplicados)) {
                        $sidsAplicadosFlip = array_flip($sidsAplicados);
                        $loteFiltrado = [];
                        $sidsFiltrados = [];
                        foreach ($lote as $row) {
                            if (!isset($sidsAplicadosFlip[$row['sincro_id']])) {
                                $loteFiltrado[] = $row;
                                $sidsFiltrados[] = $row['sincro_id'];
                            }
                        }
                        $omitidos = count($lote) - count($loteFiltrado);
                        if ($omitidos > 0) {
                            $this->line("   ⏭ [{$tabla}] {$omitidos} registro(s) ya aplicado(s) localmente. Omitidos.");
                        }
                        $lote = $loteFiltrado;
                        $sids = $sidsFiltrados;
                        if (empty($lote)) {
                            return;
                        }
                    }
                }

                // 🔒 CRÍTICO: inventarios — upsert por producto+sucursal, NUNCA tocar existencia local ni deleted_at
                if ($tabla === 'inventarios') {
                    $updateCols = ['sincro_id', 'id_vps', 'sincronizacion', 'updated_at'];
                    $updateCols = array_values(array_intersect($updateCols, $cols));

                    DB::connection($this->connLocal)->transaction(function () use ($lote, $updateCols, &$insertados, &$actualizados) {
                        foreach ($lote as $row) {
                            $existing = DB::connection($this->connLocal)
                                ->table('inventarios')
                                ->where('producto', $row['producto'])
                                ->where('sucursal', $row['sucursal'])
                                ->first();

                            $duplicateBySincro = null;
                            if (!empty($row['sincro_id'])) {
                                $duplicateQuery = DB::connection($this->connLocal)
                                    ->table('inventarios')
                                    ->where('sincro_id', $row['sincro_id']);
                                if ($existing) {
                                    $duplicateQuery->where('id', '<>', $existing->id);
                                }
                                $duplicateBySincro = $duplicateQuery->first(['id', 'sucursal']);
                            }

                            if ($duplicateBySincro) {
                                if ($this->inventarioEsDeSucursalLocal($duplicateBySincro->sucursal)) {
                                    $this->line("   ⏭ inventarios: sincro_id={$row['sincro_id']} ya existe en sucursal local. Registro VPS omitido.");
                                    continue;
                                }

                                DB::connection($this->connLocal)
                                    ->table('inventarios')
                                    ->where('id', $duplicateBySincro->id)
                                    ->delete();
                            }

                            if ($existing) {
                                $data = array_intersect_key($row, array_flip($updateCols));
                                if (!empty($data)) {
                                    DB::connection($this->connLocal)->table('inventarios')->where('id', $existing->id)->update($data);
                                }
                                $actualizados++;
                            } else {
                                $insertRow = $row;
                                unset($insertRow['id']);
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

                $sidsSolicitudesFinalizadas = [];
				$tablasProtegerFinalizado = ['solicitudes', 'solicitudes_detalles', 'ajustes', 'ajustes_detalles'];
				if (in_array($tabla, $tablasProtegerFinalizado, true)) {
					$colsLocalTabla = Schema::connection($this->connLocal)->getColumnListing($tabla);
					$colStatus      = $this->primeraColumnaExistente(['status', 'estado', 'estatus'], $colsLocalTabla);
					if ($colStatus) {
						$sidsSolicitudesFinalizadas = DB::connection($this->connLocal)
							->table($tabla)
							->whereIn('sincro_id', $sids)
							->where($colStatus, 'Finalizado')
							->pluck('sincro_id')
							->flip()
							->all();
					}
				}


                foreach ($lote as $row) {
                    if (isset($ya[$row['sincro_id']])) {
                        // Evitar rebote
                        if (in_array($tabla, $this->tablasEvitarUpdateSiSidExiste, true)) {
							continue;
						}
						if (in_array($tabla, $tablasProtegerFinalizado, true)
							&& isset($sidsSolicitudesFinalizadas[$row['sincro_id']])) {
							$this->line("   ⏭ [{$tabla}] sincro_id={$row['sincro_id']} ya Finalizado localmente. Omitido.");
							continue;
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
                            if ($tabla === 'dtes' && $this->estadoEsProcesado($row['estado'] ?? null)) {
                                $data['estado'] = 'Procesado';
                                $data['sello'] = $row['sello'] ?? $data['sello'] ?? null;
                            }
                            // Proteger: si detalle de ajuste ya fue aplicado localmente, no pisar datos
                            if ($tabla === 'ajustes_detalles') {
                                $detalleLocal = DB::connection($this->connLocal)
                                    ->table($tabla)
                                    ->where('sincro_id', $row['sincro_id'])
                                    ->first(['status', 'aplicado_local']);
                                if ($detalleLocal && ($detalleLocal->status === 'Finalizado' || !empty($detalleLocal->aplicado_local))) {
                                    foreach (['cantidad', 'ingreso', 'costo', 'total'] as $campoProtegido) {
                                        unset($data[$campoProtegido]);
                                    }
                                }
                            }
                            DB::connection($this->connLocal)
                                ->table($tabla)
                                ->where('sincro_id', $row['sincro_id'])
                                ->update($data);
                            if ($tabla === 'dtes' && $this->estadoEsProcesado($row['estado'] ?? null)) {
                                $this->propagarDteProcesado($row);
                            }
                        }
                        $actualizados += count($toUpdate);
                    }

                    // INSERT nuevos
                    if (!empty($toInsert)) {
                        DB::connection($this->connLocal)->table($tabla)->insert($toInsert);
                        if ($tabla === 'dtes') {
                            foreach ($toInsert as $row) {
                                if ($this->estadoEsProcesado($row['estado'] ?? null)) {
                                    $this->propagarDteProcesado($row);
                                }
                            }
                        }
                        $insertados += count($toInsert);
                    }
                });

                $procesadas += count($toInsert) + count($toUpdate);
            });
        } catch (Throwable $e) {
            $estado  = 'ERROR';
            $mensaje = $this->limpiarMensajeBitacora($e->getMessage());
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
                        'mensaje'      => $this->limpiarMensajeBitacora($mensaje ?? null),
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
        if (in_array($tabla, ['compras', 'solicitudes', 'ajustes', 'ventas_resumen', 'inventarios', 'dtes'], true)) {
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

        if (!$row) {
            DB::connection($this->connLocal)
                ->table('sync_states')
                ->updateOrInsert(
                    ['direction' => $this->direction, 'table' => $tabla],
                    [
                        'watermark_updated_at' => null,
                        'watermark_id'         => 0,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ],
                );

            return [null, 0];
        }

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
            ->select('id', 'sincro_id')
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

                // ¿El sincro_id objetivo ya lo tiene otra fila local?
                $otra = DB::connection($connLocal)
                    ->table($tabla)
                    ->select('id', 'sincro_id')
                    ->where('sincro_id', $rem->sincro_id)
                    ->where('id', '!=', $loc->id)
                    ->first();

                if ($otra) {
                    // El sincro_id correcto de "esa otra" según VPS (si existe), o UUID nuevo
                    $remOtra             = $remotos->get($otra->id);
                    $nuevoSincroParaOtra = $remOtra && !empty($remOtra->sincro_id)
                        ? $remOtra->sincro_id
                        : (string) Str::uuid();

                    DB::connection($connLocal)
                        ->table($tabla)
                        ->where('id', $otra->id)
                        ->update([
                            'sincro_id'  => $nuevoSincroParaOtra,
                            'updated_at' => now(),
                        ]);
                }

                DB::connection($connLocal)
                    ->table($tabla)
                    ->where('id', $loc->id)
                    ->update([
                        'sincro_id'  => $rem->sincro_id,
                        'updated_at' => $rem->updated_at ?? now(),
                    ]);

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

            // [2026-03-24] Omitir ajustes de sucursales VPS — VPS aplica su propio kardex.
            if ($sucursal !== null) {
                $modoSuc = DB::connection($this->connLocal)
                    ->table('sucursales')->where('id', $sucursal)->value('modo');
                if ($modoSuc !== 'local') continue;
            }

            // Idempotencia por Kardex: ya existe asiento de este ajuste
            if ($this->existeKardexAplicado($ajusteId)) {
                if ($tieneAplicadoLocal) {
                    DB::connection($this->connLocal)
                        ->table('ajustes')
                        ->where('id', $ajusteId)
                        ->update(['aplicado_local' => now(), 'updated_at' => now()]);
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
                    $esIngreso  = (strcasecmp($tipo, 'Ingreso') === 0);
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
                DB::connection($this->connLocal)
                    ->table('ajustes')
                    ->where('id', $ajusteId)
                    ->update(['aplicado_local' => now(), 'updated_at' => now()]);
            }
            // También marcar los detalles como aplicados
            $tieneDetStatus = in_array('status', $detCols, true);
            $tieneDetAplicado = in_array('aplicado_local', $detCols, true);
            if ($tieneDetAplicado || $tieneDetStatus) {
                $fkDet = $this->primeraColumnaExistente(['ajuste_id', 'ajuste'], $detCols) ?? 'ajuste';
                $detUpdate = ['updated_at' => now()];
                if ($tieneDetStatus) {
                    $detUpdate['status'] = 'Finalizado';
                }
                if ($tieneDetAplicado) {
                    $detUpdate['aplicado_local'] = now();
                }
                DB::connection($this->connLocal)
                    ->table('ajustes_detalles')
                    ->where($fkDet, $ajusteId)
                    ->update($detUpdate);
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
            'id',
            'sincro_id',
            'ajuste',
            'ajuste_id',
            'producto',
            'inventario',
            'medida',
            'cantidad',
            'ingreso',
            'costo',
            'total',
            'created_at',
            'updated_at'
        ]));

        return DB::connection($this->connLocal)
            ->table('ajustes_detalles')
            ->select($colsDeseadas ?: ['*'])
            ->where($fk, $ajusteId)
            ->get();
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

            // [2026-03-24] Omitir ajustes de sucursales VPS — VPS aplica su propio kardex.
            if ($sucursal !== null) {
                $modoSuc = DB::connection($this->connLocal)
                    ->table('sucursales')->where('id', $sucursal)->value('modo');
                if ($modoSuc !== 'local') continue;
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
                    DB::connection($this->connLocal)
                        ->table('ajustes')
                        ->where('id', $ajusteId)
                        ->update(['aplicado_local' => now(), 'updated_at' => now()]);
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
                    $esIngreso  = (strcasecmp($tipo, 'Ingreso') === 0);
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
                        $desc       = 'Ingreso por Ajuste #' . $ajusteId . ' (sync) ' . $detalle . ' Aplicado en PC Local';
                    } else {
                        $saldoValor = $ulti ? max(0.00, (float) $ulti->saldoValor - $totalVal) : 0.00;
                        $ingC       = 0.00;
                        $ingV       = 0.00;
                        $egrC       = $ingresoQty;
                        $egrV       = $totalVal;
                        $desc       = 'Egreso por Ajuste #' . $ajusteId . ' (sync) ' . $detalle . ' Aplicado en PC Local';
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
                DB::connection($this->connLocal)
                    ->table('ajustes')
                    ->where('id', $ajusteId)
                    ->update(['aplicado_local' => now(), 'updated_at' => now()]);
            }
            // También marcar los detalles como aplicados
            $tieneDetStatus = in_array('status', $detCols, true);
            $tieneDetAplicado = in_array('aplicado_local', $detCols, true);
            if ($tieneDetAplicado || $tieneDetStatus) {
                $detUpdate = ['updated_at' => now()];
                if ($tieneDetStatus) {
                    $detUpdate['status'] = 'Finalizado';
                }
                if ($tieneDetAplicado) {
                    $detUpdate['aplicado_local'] = now();
                }
                DB::connection($this->connLocal)
                    ->table('ajustes_detalles')
                    ->where($fkDet, $ajusteId)
                    ->update($detUpdate);
            }

            $aplicados++;
        }

        return $aplicados;
    }

    protected function estadoEsProcesado($estado): bool
    {
        return strtoupper(trim((string) $estado)) === 'PROCESADO';
    }

    protected function inventarioEsDeSucursalLocal($sucursal): bool
    {
        if (empty($this->sucursalesPermitidas)) {
            return true;
        }

        $locales = array_map('intval', $this->sucursalesPermitidas);

        return in_array((int) $sucursal, $locales, true);
    }

    protected function prepararDteParaLocal(array &$row): bool
    {
        // Solo sincronizar DTEs que en VPS estén PROCESADOS
        if (!$this->estadoEsProcesado($row['estado'] ?? null)) {
            return false;
        }

        // Solo procesar si el DTE local está en estado Rechazado
        // (el update posterior cambiará a Procesado y propagará el sello)
        $dteLocal = DB::connection($this->connLocal)
            ->table('dtes')
            ->where('sincro_id', $row['sincro_id'])
            ->value('estado');

        if (strtoupper(trim((string) $dteLocal)) !== 'RECHAZADO') {
            return false;
        }

        // Resolver venta local para propagar sello a ventas/cajas
        if (!array_key_exists('venta', $row) || blank($row['venta'])) {
            return true;
        }

        $ventaLocalId = $this->resolverVentaLocalDte(null, $row['venta']);
        if (!$ventaLocalId) {
            $this->warn("   ⚠ dtes: sincro_id={$row['sincro_id']} omitido; venta VPS {$row['venta']} no existe en local.");
            return false;
        }

        $row['venta'] = $ventaLocalId;

        // NO validar token: solo se necesita el sello para propagar a ventas/cajas/dtes
        if (array_key_exists('tocken', $row) && !blank($row['tocken'])) {
            $tockenLocalId = $this->resolverTockenLocal($row['tocken']);
            if ($tockenLocalId) {
                $row['tocken'] = $tockenLocalId;
            }
            // Si no existe token local, no fallar: dejar el valor VPS o null
        }

        return true;
    }

    protected function resolverTockenLocal($tockenVps): ?int
    {
        if (!$tockenVps || !Schema::connection($this->connLocal)->hasTable('tockens')) {
            return null;
        }

        if (Schema::connection($this->connLocal)->hasColumn('tockens', 'id_vps')) {
            $local = DB::connection($this->connLocal)
                ->table('tockens')
                ->where('id_vps', $tockenVps)
                ->value('id');

            if ($local) {
                return (int) $local;
            }
        }

        if (DB::connection($this->connLocal)->table('tockens')->where('id', $tockenVps)->exists()) {
            return (int) $tockenVps;
        }

        if (!Schema::connection($this->connRemote)->hasTable('tockens')) {
            return null;
        }

        $remote = DB::connection($this->connRemote)
            ->table('tockens')
            ->where('id', $tockenVps)
            ->first();

        if (!$remote) {
            return null;
        }

        $payload = (array) $remote;
        $localCols = Schema::connection($this->connLocal)->getColumnListing('tockens');
        unset($payload['id']);
        $payload = array_intersect_key($payload, array_flip($localCols));

        if (in_array('id_vps', $localCols, true)) {
            $payload['id_vps'] = (int) $tockenVps;
        }
        if (in_array('sincronizacion', $localCols, true)) {
            $payload['sincronizacion'] = now();
        }
        if (in_array('sincro_id', $localCols, true) && empty($payload['sincro_id'])) {
            $payload['sincro_id'] = (string) Str::uuid();
        }
        $payload['created_at'] = $payload['created_at'] ?? now();
        $payload['updated_at'] = $payload['updated_at'] ?? now();

        if (!empty($payload['sincro_id']) && in_array('sincro_id', $localCols, true)) {
            $existing = DB::connection($this->connLocal)
                ->table('tockens')
                ->where('sincro_id', $payload['sincro_id'])
                ->first(['id']);

            if ($existing) {
                DB::connection($this->connLocal)
                    ->table('tockens')
                    ->where('id', $existing->id)
                    ->update($payload);

                return (int) $existing->id;
            }
        }

        return (int) DB::connection($this->connLocal)
            ->table('tockens')
            ->insertGetId($payload);
    }

    protected function limpiarMensajeBitacora(?string $mensaje): ?string
    {
        if ($mensaje === null) {
            return null;
        }

        $mensaje = preg_replace('/jsonDte\s*=\s*.*?(, `caja`|, `sucursal`| where `sincro_id`)/s', 'jsonDte = [omitido]$1', $mensaje) ?? $mensaje;
        $mensaje = @iconv('UTF-8', 'UTF-8//IGNORE', $mensaje) ?: $mensaje;

        return Str::limit($mensaje, 1000);
    }

    protected function propagarDteProcesado(array $row): void
    {
        $sello = $row['sello'] ?? null;
        if (blank($sello) || empty($row['sincro_id'])) {
            return;
        }

        $dteLocal = DB::connection($this->connLocal)
            ->table('dtes')
            ->where('sincro_id', $row['sincro_id'])
            ->first(['id', 'venta', 'estado']);

        if (!$dteLocal) {
            return;
        }

        $ventaLocalId = $this->resolverVentaLocalDte($dteLocal->venta ?? null, $row['venta'] ?? null);
        if ($ventaLocalId) {
            DB::connection($this->connLocal)
                ->table('ventas')
                ->where('id', $ventaLocalId)
                ->update(['sello' => $sello, 'updated_at' => now()]);

            DB::connection($this->connLocal)
                ->table('cajas')
                ->where('venta', $ventaLocalId)
                ->update(['sello' => $sello, 'updated_at' => now()]);
        }

        DB::connection($this->connLocal)
            ->table('dtes')
            ->where('id', $dteLocal->id)
            ->update(['estado' => 'Procesado', 'sello' => $sello, 'updated_at' => now()]);
    }

    protected function resolverVentaLocalDte($ventaLocalActual, $ventaVps): ?int
    {
        if ($ventaLocalActual && DB::connection($this->connLocal)->table('ventas')->where('id', $ventaLocalActual)->exists()) {
            return (int) $ventaLocalActual;
        }

        if ($ventaVps && Schema::connection($this->connLocal)->hasColumn('ventas', 'id_vps')) {
            $local = DB::connection($this->connLocal)
                ->table('ventas')
                ->where('id_vps', $ventaVps)
                ->value('id');

            if ($local) {
                return (int) $local;
            }
        }

        return null;
    }
}
