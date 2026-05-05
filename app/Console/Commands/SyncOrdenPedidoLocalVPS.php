<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SyncOrdenPedidoLocalVPS extends Command
{
    protected $signature = 'sync:orden-pedido
        {--limit=1000 : Máximo de filas}
        {--local=localmysql : Conexión origen}
        {--vps=vpsmysql : Conexión destino}
        {--direction=local_to_vps : Dirección para sync_states}';

    protected $description = 'Sincroniza orden_pedidos y orden_pedido_detalles desde LOCAL hacia VPS';

    protected string $syncStatesTable = 'sync_states';

    protected array $plans = [
        'orden_pedidos' => [
            'ts'       => 'updated_at',
            'fk'       => [],
            'required' => [],
        ],
        'orden_pedido_detalles' => [
            'ts'       => 'updated_at',
            'fk'       => [
                'orden_pedido_id' => 'orden_pedidos',
            ],
            'required' => ['orden_pedido_id'],
        ],
    ];

    public function handle()
    {
        if (env('APP_MODO', 'local') === 'vps') {
            $this->error('❌ sync:orden-pedido NO debe ejecutarse en VPS (APP_MODO=vps). Abortando.');
            return self::FAILURE;
        }

        $connLocal = $this->option('local');
        $connVps   = $this->option('vps');
        $limit     = (int) $this->option('limit');
        $direction = $this->option('direction');

        foreach (['orden_pedidos', 'orden_pedido_detalles'] as $table) {
            $this->info("→ Sincronizando: {$table}");
            try {
                $res = $this->syncTable($table, $limit, $connLocal, $connVps, $direction);
                $this->line("   ✔ {$table}: {$res['count']} filas (ins: {$res['ins']}, upd: {$res['upd']}, omitidas: {$res['skip']})");
            } catch (Throwable $e) {
                $this->error("   ✘ Error en {$table}: {$e->getMessage()}");
            }
        }

        $this->info('✔ Sincronización de órdenes de pedido finalizada');
        return self::SUCCESS;
    }

    protected function syncTable(string $table, int $limit, string $connLocal, string $connVps, string $direction): array
    {
        $plan  = $this->plans[$table];
        $tsCol = $plan['ts'];

        $wm   = $this->getWatermark($direction, $table);
        $wmTs = $wm['watermark_updated_at'] ?? null;
        $wmId = $wm['watermark_id'] ?? 0;

        $q = DB::connection($connLocal)->table($table);

        if ($wmTs) {
            $q->whereRaw("({$tsCol}, id) > (?, ?)", [$wmTs, $wmId]);
        }

        $rows = $q->orderBy($tsCol)->orderBy('id')->limit($limit)->get();

        if ($rows->isEmpty()) {
            return ['count' => 0, 'ins' => 0, 'upd' => 0, 'skip' => 0, 'wm_ts' => $wmTs, 'wm_id' => $wmId];
        }

        $ins = 0; $upd = 0; $skip = 0;
        $lastTs = $wmTs;
        $lastId = $wmId;

        foreach ($rows as $r) {
            $payload = (array) $r;

            if (empty($payload['sincro_id'])) {
                $payload['sincro_id'] = (string) Str::uuid();
                DB::connection($connLocal)->table($table)
                    ->where('id', $r->id)
                    ->update(['sincro_id' => $payload['sincro_id']]);
            }

            foreach (($plan['fk'] ?? []) as $col => $refTable) {
                $originalValue = $payload[$col] ?? null;

                if (empty($originalValue)) {
                    if (in_array($col, $plan['required'] ?? [], true)) {
                        $skip++;
                        $this->warn("   - Omitido {$table} ID {$r->id}: campo requerido '{$col}' viene vacío.");
                        continue 2;
                    }
                    $payload[$col] = null;
                    continue;
                }

                $mapped = $this->mapFk($refTable, (int) $originalValue, $connLocal, $connVps);

                if (in_array($col, $plan['required'] ?? [], true) && empty($mapped)) {
                    $skip++;
                    $this->warn("   - Omitido {$table} ID {$r->id}: no se pudo mapear FK '{$col}'={$originalValue}.");
                    continue 2;
                }

                $payload[$col] = $mapped;
            }

            unset($payload['id'], $payload['id_vps']);

            $before   = $this->findBySincro($connVps, $table, $payload['sincro_id']);
            $remoteId = $this->upsertBySincro($connVps, $table, $payload);

            $before ? $upd++ : $ins++;

            DB::connection($connLocal)->table($table)
                ->where('id', $r->id)
                ->update(['id_vps' => $remoteId]);

            $lastTs = $r->{$tsCol};
            $lastId = $r->id;
        }

        if ($lastTs !== null) {
            $this->saveWatermark($direction, $table, $lastTs, $lastId);
        }

        return ['count' => $ins + $upd, 'ins' => $ins, 'upd' => $upd, 'skip' => $skip, 'wm_ts' => $lastTs, 'wm_id' => $lastId];
    }

    protected function findBySincro(string $conn, string $table, string $sincroId)
    {
        return DB::connection($conn)->table($table)->where('sincro_id', $sincroId)->first();
    }

    protected function upsertBySincro(string $conn, string $table, array $payload): int
    {
        $existing = DB::connection($conn)->table($table)
            ->where('sincro_id', $payload['sincro_id'])->first();

        $payload['updated_at'] = now();

        if ($existing) {
            // No pisar Recibida/Anulada en VPS si ya está en estado final
            if ($table === 'orden_pedidos' && isset($payload['estado']) && in_array($existing->estado, ['Recibida', 'Anulada'])) {
                unset($payload['estado']);
            }
            DB::connection($conn)->table($table)
                ->where('sincro_id', $payload['sincro_id'])
                ->update($payload);
            return (int) $existing->id;
        }

        $payload['created_at'] = now();
        return (int) DB::connection($conn)->table($table)->insertGetId($payload);
    }

    protected function mapFk(string $refTable, int $localId, string $connLocal, string $connVps): ?int
    {
        $ref = DB::connection($connLocal)->table($refTable)
            ->select('id_vps', 'sincro_id')
            ->where('id', $localId)->first();

        if ($ref?->id_vps) {
            return (int) $ref->id_vps;
        }

        if ($ref?->sincro_id) {
            $row = DB::connection($connVps)->table($refTable)
                ->select('id')->where('sincro_id', $ref->sincro_id)->first();
            if ($row) {
                DB::connection($connLocal)->table($refTable)
                    ->where('id', $localId)->update(['id_vps' => $row->id]);
                return (int) $row->id;
            }
        }

        return null;
    }

    protected function getWatermark(string $direction, string $table): array
    {
        $row = DB::table($this->syncStatesTable)
            ->where('direction', $direction)
            ->where('table', $table)->first();
        return $row ? (array) $row : [];
    }

    protected function saveWatermark(string $direction, string $table, $ts, int $id): void
    {
        DB::table($this->syncStatesTable)->updateOrInsert(
            ['direction' => $direction, 'table' => $table],
            ['watermark_updated_at' => $ts, 'watermark_id' => $id, 'updated_at' => now()]
        );
    }
}
