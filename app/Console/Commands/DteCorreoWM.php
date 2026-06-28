<?php

namespace App\Console\Commands;

use App\Models\dte;
use App\Traits\enviarCorreoDTE;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Throwable;

class DteCorreoWM extends Command
{
    use enviarCorreoDTE;

    protected $signature = 'dte:correo-wm
        {--limit=30 : Max correos por corrida}
        {--empresa= : Filtrar por empresa (opcional)}';

    protected $description = 'Envía correos de DTE PROCESADOS (receptor != 1) usando watermark sync_states';

    protected string $direction = 'correo';
    protected string $tableKey  = 'dtes';

    public function handle(): int
    {
        Log::info('===== INICIO DTE_CORREO_WM =====');

        if (!Schema::hasTable('sync_states')) {
            Log::error('No existe tabla sync_states');
            return self::FAILURE;
        }

        [$wmAt, $wmId] = $this->getWatermark($this->tableKey);
        $wmAt = $wmAt ?? '2026-02-16 15:50:00';
        $wmId = (int)($wmId ?? 0);

        $limit   = (int) $this->option('limit');
        $empresa = $this->option('empresa');

        Log::info('Watermark actual', [
            'wm_at'   => $wmAt,
            'wm_id'   => $wmId,
            'limit'   => $limit,
            'empresa' => $empresa,
        ]);

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
            $q->where('empresa', (int) $empresa);
        }

        $rows = $q->orderBy('updated_at')->orderBy('id')->limit($limit)->get();

        Log::info('DTE encontrados', [
            'cantidad' => $rows->count(),
        ]);

        if ($rows->isEmpty()) {
            Log::info('Sin nuevos DTE PROCESADOS');
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $lastAt = $wmAt;
        $lastId = $wmId;

        foreach ($rows as $row) {
            try {
                $this->enviarCorreoDTE((int) $row->id);

                $ok++;

                Log::info('DTE enviado', [
                    'dte_id' => $row->id,
                ]);
            } catch (Throwable $e) {
                $fail++;

                Log::error('DTE_CORREO_ERROR', [
                    'dte_id' => $row->id,
                    'msg'    => $e->getMessage(),
                ]);
            }

            $lastAt = $row->updated_at;
            $lastId = (int) $row->id;
        }

        $this->putWatermark($this->tableKey, $lastAt, $lastId);

        Log::info('===== FIN DTE_CORREO_WM =====', [
            'ok'       => $ok,
            'fail'     => $fail,
            'nuevo_wm' => $lastAt . '/' . $lastId,
        ]);

        return self::SUCCESS;
    }

    protected function getWatermark(string $tabla): array
    {
        $row = DB::table('sync_states')
            ->where('direction', $this->direction)
            ->where('table', $tabla)
            ->first();

        return [$row?->watermark_updated_at ?? null, (int) ($row?->watermark_id ?? 0)];
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
