<?php

namespace App\Console\Commands;

use App\Models\Dte;
use App\Traits\enviarCorreoDTE;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DteEnviarCorreoWM extends Command
{
    use enviarCorreoDTE;

    protected $signature = 'dte:enviar-correo-wm
        {--limit=30 : Max correos por corrida}
        {--empresa= : Filtrar por empresa (opcional)}';

    protected $description = 'Envía correos de DTE PROCESADOS (receptor != 1) usando watermark sync_states';

    protected string $direction = 'correo';
    protected string $tableKey  = 'dtes';

    public function handle(): int
    {
        if (!Schema::hasTable('sync_states')) {
            $this->error('No existe la tabla sync_states.');
            return self::FAILURE;
        }

        // Watermark
        [$wmAt, $wmId] = $this->getWatermark($this->tableKey);
        $wmAt = $wmAt ?? '2026-02-16 15:50:00';
        $wmId = (int)($wmId ?? 0);

        $limit   = (int)$this->option('limit');
        $empresa = $this->option('empresa');

        // Query incremental por (updated_at, id)
        $q = Dte::query()
            ->select('id', 'updated_at', 'empresa', 'estado', 'receptor')
            ->whereNotNull('updated_at')
            ->where('estado', 'PROCESADO')
            ->whereNotNull('receptor')
            ->where('receptor', '<>', 1)
            ->where(function ($qq) use ($wmAt, $wmId) {
                $qq->where('updated_at', '>', $wmAt)
                   ->orWhere(function ($q2) use ($wmAt, $wmId) {
                       $q2->where('updated_at', '=', $wmAt)
                          ->where('id', '>', $wmId);
                   });
            });

        if (!empty($empresa)) {
            $q->where('empresa', (int)$empresa);
        }

        $rows = $q->orderBy('updated_at')->orderBy('id')->limit($limit)->get();

        if ($rows->isEmpty()) {
            $this->info("Sin nuevos DTE PROCESADOS. WM={$wmAt}/{$wmId}");
            return self::SUCCESS;
        }

        $ok = 0; $fail = 0;
        $lastAt = $wmAt; $lastId = $wmId;

        foreach ($rows as $row) {
            try {
                // Envío por correo (Trait)
                $this->enviarCorreoDTE((int)$row->id);

                $ok++;
                $this->line("✅ Enviado DTE #{$row->id}");
            } catch (Throwable $e) {
                $fail++;
                logger()->error('DTE_CORREO_ERROR', [
                    'dte_id' => (int)$row->id,
                    'msg'    => $e->getMessage(),
                ]);
                $this->error("❌ Error DTE #{$row->id}: ".$e->getMessage());
            }

            // Avanza watermark al último visto
            $lastAt = $row->updated_at;
            $lastId = (int)$row->id;
        }

        // Guardar watermark
        $this->putWatermark($this->tableKey, $lastAt, $lastId);

        $this->info("Listo. OK={$ok} FAIL={$fail}. Nuevo WM={$lastAt}/{$lastId}");
        return self::SUCCESS;
    }

    protected function getWatermark(string $tabla): array
    {
        $row = DB::table('sync_states')
            ->where('direction', $this->direction)
            ->where('table', $tabla)
            ->first();

        return [$row?->watermark_updated_at ?? null, (int)($row?->watermark_id ?? 0)];
    }

    protected function putWatermark(string $tabla, ?string $wmAt, int $wmId): void
    {
        DB::table('sync_states')->updateOrInsert(
            ['direction' => $this->direction, 'table' => $tabla],
            [
                'watermark_updated_at' => $wmAt,
                'watermark_id'         => $wmId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]
        );
    }
}
