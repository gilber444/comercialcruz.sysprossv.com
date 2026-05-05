<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// [2026-03-31] Limpieza semanal: vacía tabla firmadors y purga recepcion_dtes sin sello
class LimpiarTablasFirmador extends Command
{
    protected $signature   = 'firmador:limpiar';
    protected $description = 'Vacía la tabla firmadors y elimina recepcion_dtes sin selloRecibido';

    public function handle()
    {
        // ── firmadors ─────────────────────────────────────────────────────
        $totalFirmadors = DB::table('firmadors')->count();
        DB::table('firmadors')->truncate();
        $this->info("✓ firmadors vaciada ({$totalFirmadors} registros eliminados)");

        // ── recepcion_dtes sin sello ──────────────────────────────────────
        $eliminados = DB::table('recepcion_dtes')
            ->whereNull('selloRecibido')
            ->delete();
        $this->info("✓ recepcion_dtes: {$eliminados} registros sin selloRecibido eliminados");

        $this->info('Limpieza completada: ' . now()->toDateTimeString());
        return self::SUCCESS;
    }
}
