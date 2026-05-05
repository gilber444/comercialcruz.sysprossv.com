<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SyncDesdeLocal extends Command
{ 

    protected $signature = 'sync:bitacora-local-vps
        {--limit= : Máximo de filas por ejecución (por seguridad)}';

    protected $description = 'Sincroniza bitacora_sincronizacions desde LOCAL hacia VPS (incremental sin cambiar esquemas)';

    // Conexiones y tabla
    protected string $connLocal  = 'localmysql';  // ORIGEN
    protected string $connRemote = 'vpsmysql';    // DESTINO
    protected string $tabla      = 'bitacora_sincronizacions';

    // Watermark (lo guardamos en LOCAL.sync_states, si existe)
    protected string $direction  = 'bitacora_local_to_vps';
    protected string $sucursal   = 'La Belen';

    // Lotes
    protected int $chunk   = 1000;
    protected int $defaultLimit = 5000;

    public function handle()
    {
        try {
            DB::connection($this->connLocal)->getPdo();
            DB::connection($this->connRemote)->getPdo();
        } catch (Throwable $e) {
            $this->error('❌ Conexión fallida: ' . $e->getMessage());
            return self::FAILURE;
        }

        // 1) Validar tablas
        if (!Schema::connection($this->connLocal)->hasTable($this->tabla)) {
            $this->warn("Tabla local {$this->tabla} no existe. Abortando.");
            return self::SUCCESS;
        }
        if (!Schema::connection($this->connRemote)->hasTable($this->tabla)) {
            $this->warn("Tabla VPS {$this->tabla} no existe. Abortando.");
            return self::SUCCESS;
        }

        // 2) Columnas y requisitos
        $colsLocal  = Schema::connection($this->connLocal)->getColumnListing($this->tabla);
        $colsRemote = Schema::connection($this->connRemote)->getColumnListing($this->tabla);
        $cols       = array_values(array_intersect($colsLocal, $colsRemote));

        if (!in_array('sincro_id', $cols)) {
            $this->error("La tabla {$this->tabla} debe tener 'sincro_id'.");
            return self::FAILURE;
        }
        // Timestamp para el watermark: prioriza updated_at, luego created_at, y como último 'fecha'
        $tsCol = in_array('updated_at', $cols) ? 'updated_at'
            : (in_array('created_at', $cols) ? 'created_at'
                : (in_array('fecha', $cols) ? 'fecha' : null));
        if (!$tsCol) {
            $this->error("La tabla {$this->tabla} necesita 'updated_at' o 'created_at' o 'fecha'.");
            return self::FAILURE;
        }
        if (!in_array('id', $cols)) {
            $this->error("La tabla {$this->tabla} necesita columna 'id'.");
            return self::FAILURE;
        }

        // 3) Watermark
        [$wmAt, $wmId] = $this->getWatermark();
        $limit = (int)($this->option('limit') ?? $this->defaultLimit);
        if ($limit < 0) $limit = 0;

        $this->info('== SYNC BITÁCORA LOCAL → VPS ==');
        $this->line("Tabla: {$this->tabla} | WM: {$tsCol}=" . ($wmAt ?? 'NULL') . " / id=" . ($wmId ?? 0) . " | Límite: " . ($limit ?: 'sin límite'));

        // 4) Query origen (LOCAL)
        $query = DB::connection($this->connLocal)->table($this->tabla)
            ->select($cols)
            ->when($wmAt, function ($q) use ($tsCol, $wmAt, $wmId) {
                $q->where(function ($qq) use ($tsCol, $wmAt, $wmId) {
                    $qq->where($tsCol, '>', $wmAt)
                        ->orWhere(function ($qq2) use ($tsCol, $wmAt, $wmId) {
                            $qq2->where($tsCol, '=', $wmAt)
                                ->where('id', '>', $wmId);
                        });
                });
            })
            ->orderBy($tsCol)
            ->orderBy('id');

        $procesadas = 0;
        $insertadas = 0;
        $actualizadas = 0;
        $estado = 'OK';
        $mensaje = null;
        $maxAt = $wmAt;
        $maxId = $wmId;

        try {
            $query->chunk($this->chunk, function ($rows) use (&$procesadas, &$insertadas, &$actualizadas, &$estado, &$mensaje, &$maxAt, &$maxId, $limit, $cols, $tsCol) {
                if ($rows->isEmpty()) return false;

                // --- Preparar lote ---
                $lote = [];
                $sids = [];
                foreach ($rows as $r) {
                    if ($limit > 0 && $procesadas >= $limit) {
                        return false; // alcanzó el límite de esta ejecución
                    }

                    $arr = (array)$r;

                    // No mandamos 'id' para evitar choques de PK entre tiendas
                    unset($arr['id']);

                    // Si no hay updated_at en el set, mantenemos el valor original que vino; no tocamos created_at
                    $lote[] = $arr;
                    $sids[] = $r->sincro_id;

                    $maxAt = $r->{$tsCol};
                    $maxId = $r->id;
                    $procesadas++;
                }

                if (empty($lote)) return $limit ? ($procesadas < $limit) : true;

                // --- Deduplicación por app (sin requerir UNIQUE en DB) ---
                // 1) ¿Qué sincro_id ya existen en VPS?
                $existentes = DB::connection($this->connRemote)->table($this->tabla)
                    ->whereIn('sincro_id', $sids)
                    ->pluck('sincro_id')
                    ->all();
                $ya = array_flip($existentes);

                // 2) Separar INSERT vs UPDATE
                $toInsert = [];
                $toUpdate = [];
                foreach ($lote as $row) {
                    if (isset($ya[$row['sincro_id']])) $toUpdate[] = $row;
                    else $toInsert[] = $row;
                }

                DB::connection($this->connRemote)->transaction(function () use ($toInsert, $toUpdate, $cols) {
                    // INSERT (ignoramos created_at/updated_at si no vienen, DB los llenará si tiene timestamps)
                    if (!empty($toInsert)) {
                        DB::connection($this->connRemote)->table($this->tabla)->insert($toInsert);
                    }

                    // UPDATE fila por fila por 'sincro_id' (sin tocar created_at)
                    if (!empty($toUpdate)) {
                        $updateCols = array_values(array_diff($cols, ['id', 'created_at']));
                        foreach ($toUpdate as $row) {
                            $payload = array_intersect_key($row, array_flip($updateCols));
                            DB::connection($this->connRemote)->table($this->tabla)
                                ->where('sincro_id', $row['sincro_id'])
                                ->update($payload);
                        }
                    }
                });

                $insertadas += count($toInsert);
                $actualizadas += count($toUpdate);

                // ¿Seguimos?
                if ($limit > 0 && $procesadas >= $limit) return false;
                return true;
            });
        } catch (Throwable $e) {
            $estado = 'ERROR';
            $mensaje = $e->getMessage();
            $this->error("✘ Error: {$mensaje}");
        }

        // 5) Guardar WM si hubo filas y todo OK
        if ($procesadas > 0 && $estado === 'OK') {
            $this->putWatermark($maxAt, (int)$maxId);
            $this->info("✔ {$this->tabla}: {$procesadas} filas (ins: {$insertadas}, upd: {$actualizadas}). WM → {$maxAt} / {$maxId}");
        } else {
            $this->line("• {$this->tabla}: sin cambios.");
        }

        // Bitácora (solo si existe la tabla) — evitar recursión cuando la tabla objetivo ES la bitácora
        $regTotal = $insertadas + $actualizadas;
        if (
            Schema::connection($this->connLocal)->hasTable('bitacora_sincronizacions')
            && $this->tabla !== 'bitacora_sincronizacions' // <<< CLAVE
        ) {
            if ($estado !== 'OK' || $regTotal > 0) {
                DB::connection($this->connLocal)->table('bitacora_sincronizacions')->insert([
                    'sincro_id'     => (string) Str::uuid(),
                    'tabla'         => $this->tabla,
                    'origen'        => 'local',
                    'destino'       => 'vps',
                    'sucursal'      => $this->sucursal,
                    'registros'     => $regTotal,
                    'insertados'    => $insertadas,
                    'actualizados'  => $actualizadas,
                    'estado'        => $estado,
                    'mensaje'       => $mensaje,
                    'fecha'         => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        return $estado === 'OK' ? self::SUCCESS : self::FAILURE;
    }

    // --- Watermark helpers (guardados en LOCAL.sync_states si existe) ---
    protected function getWatermark(): array
    {
        if (!Schema::connection($this->connLocal)->hasTable('sync_states')) {
            return [null, 0];
        }
        $row = DB::connection($this->connLocal)->table('sync_states')
            ->where('direction', $this->direction)
            ->where('table', $this->tabla)
            ->first();

        return [$row?->watermark_updated_at ?? null, (int)($row?->watermark_id ?? 0)];
    }

    protected function putWatermark(?string $wmAt, int $wmId): void
    {
        if (!Schema::connection($this->connLocal)->hasTable('sync_states')) return;

        DB::connection($this->connLocal)->table('sync_states')->updateOrInsert(
            ['direction' => $this->direction, 'table' => $this->tabla],
            [
                'watermark_updated_at' => $wmAt,
                'watermark_id'         => $wmId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]
        );
    }
}
