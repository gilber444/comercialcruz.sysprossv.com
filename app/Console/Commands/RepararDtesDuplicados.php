<?php

namespace App\Console\Commands;

use App\Models\dte;
use App\Services\ConsultaDteHaciendaService;
use App\Traits\NumeroControlTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repara los DTE que quedaron repetidos (p. ej. por correr varias veces a la vez
 * ventas:convertir-fisicos-dte). Por defecto solo muestra lo que encontro; con
 * --aplicar hace los cambios.
 *
 * 1) Misma venta con varios DTE: se queda el que tiene sello (o, si ninguno
 *    tiene, el mas antiguo); los demas sin sello pasan a ELIMINADO. Si dos o mas
 *    tienen sello, solo se reporta (hay que invalidar en Hacienda).
 * 2) Mismo numero de control en varios DTE: se queda el que tiene sello (o el
 *    mas antiguo); a los demas sin sello se les da un numero de control nuevo y
 *    vuelven a Creado para que app:procesar-dtes-pendientes los genere, firme y
 *    envie de nuevo. Antes se consulta a Hacienda: si ya tiene ese DTE con
 *    sello, se toma el sello y no se cambia nada.
 * 3) Venta cuyo(s) DTE estan todos ELIMINADO y sin sello: se reactiva uno con
 *    numero de control nuevo (se habia eliminado a mano por el error).
 */
class RepararDtesDuplicados extends Command
{
    use NumeroControlTrait;

    protected $signature = 'dte:reparar-duplicados
        {--sucursal= : Sucursal (por defecto APP_SUCURSAL_ID)}
        {--desde=2026-10-01 : Solo DTE emitidos desde esta fecha}
        {--aplicar : Hace los cambios (sin esto solo muestra lo que encontro)}';

    protected $description = 'Repara DTE repetidos: misma venta con varios DTE o mismo numero de control';

    private const ELIMINADO = 'ELIMINADO';

    private bool $aplicar = false;

    /** DTE que el paso 1 manda a ELIMINADO (para que la revision sin --aplicar cuadre). */
    private array $sobrantes = [];

    public function handle(): int
    {
        $sucursal = (int) ($this->option('sucursal') ?: config('app.sucursal_id', 0));
        if ($sucursal <= 0) {
            $this->error('Falta la sucursal (APP_SUCURSAL_ID o --sucursal).');
            return self::FAILURE;
        }
        $this->aplicar = (bool) $this->option('aplicar');
        $desde = (string) $this->option('desde');

        $this->info(($this->aplicar ? 'APLICANDO' : 'SOLO REVISION (use --aplicar para corregir)') . " | sucursal {$sucursal} | desde {$desde}");

        $base = fn () => dte::where('sucursal', $sucursal)->where('fecEmi', '>=', $desde);
        $tieneSello = fn ($d) => trim((string) $d->sello) !== '';
        $eliminado = fn ($d) => strtoupper(trim((string) $d->estado)) === self::ELIMINADO;

        // ---- 1) Misma venta con varios DTE vivos ----
        $this->line('');
        $this->info('1) Ventas con mas de un DTE:');
        $ventasRepetidas = $base()->whereRaw('UPPER(TRIM(estado)) <> ?', [self::ELIMINADO])
            ->select('venta')->groupBy('venta')->havingRaw('COUNT(*) > 1')->pluck('venta');
        $eliminados = 0;
        foreach ($ventasRepetidas as $ventaId) {
            $grupo = $base()->where('venta', $ventaId)->whereRaw('UPPER(TRIM(estado)) <> ?', [self::ELIMINADO])->orderBy('id')->get();
            $conSello = $grupo->filter($tieneSello);
            if ($conSello->count() > 1) {
                $this->warn("   Venta {$ventaId}: {$conSello->count()} DTE CON SELLO (" . $conSello->pluck('numeroControl')->implode(', ') . ') -> hay que invalidar en Hacienda, no se toca.');
                continue;
            }
            $queda = $conSello->first() ?: $grupo->first();
            foreach ($grupo as $d) {
                if ($d->id === $queda->id || $tieneSello($d)) {
                    continue;
                }
                $this->line("   Venta {$ventaId}: queda DTE {$queda->id} ({$queda->estado}); DTE {$d->id} {$d->numeroControl} ({$d->estado}) -> ELIMINADO");
                $this->sobrantes[$d->id] = true;
                if ($this->aplicar) {
                    dte::whereKey($d->id)->where(fn ($q) => $q->whereNull('sello')->orWhere('sello', ''))
                        ->update(['estado' => self::ELIMINADO]);
                }
                $eliminados++;
            }
        }
        $this->line("   Total: {$ventasRepetidas->count()} ventas, {$eliminados} DTE sobrantes a ELIMINADO.");

        // ---- 2) Mismo numero de control ----
        $this->line('');
        $this->info('2) Numeros de control repetidos:');
        $numeros = $base()->whereRaw('UPPER(TRIM(estado)) <> ?', [self::ELIMINADO])
            ->select('numeroControl')->groupBy('numeroControl')->havingRaw('COUNT(*) > 1')->pluck('numeroControl');
        $renumerados = 0;
        foreach ($numeros as $numero) {
            $grupo = $base()->where('numeroControl', $numero)->whereRaw('UPPER(TRIM(estado)) <> ?', [self::ELIMINADO])->orderBy('id')->get()
                ->reject(fn ($d) => isset($this->sobrantes[$d->id]));
            if ($grupo->count() < 2) {
                continue;
            }
            $conSello = $grupo->filter($tieneSello);
            $queda = $conSello->first() ?: $grupo->first();
            foreach ($grupo as $d) {
                if ($d->id === $queda->id || $tieneSello($d)) {
                    continue;
                }
                $renumerados += $this->renumerar($d, "mismo numero que DTE {$queda->id}") ? 1 : 0;
            }
        }
        $this->line("   Total: {$numeros->count()} numeros repetidos, {$renumerados} DTE con numero nuevo.");

        // ---- 3) Ventas que quedaron sin DTE vivo (eliminados a mano) ----
        $this->line('');
        $this->info('3) Ventas con todos sus DTE en ELIMINADO y sin sello:');
        $ventasEliminadas = $base()->whereRaw('UPPER(TRIM(estado)) = ?', [self::ELIMINADO])
            ->where(fn ($q) => $q->whereNull('sello')->orWhere('sello', ''))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('dtes as d2')
                    ->whereColumn('d2.venta', 'dtes.venta')
                    ->where(fn ($w) => $w->whereRaw('UPPER(TRIM(d2.estado)) <> ?', [self::ELIMINADO])
                        ->orWhere(fn ($w2) => $w2->whereNotNull('d2.sello')->where('d2.sello', '<>', '')));
            })
            ->select('venta')->distinct()->pluck('venta');
        $reactivados = 0;
        foreach ($ventasEliminadas as $ventaId) {
            $d = $base()->where('venta', $ventaId)->orderBy('id')->first();
            $reactivados += $this->renumerar($d, "venta {$ventaId} sin DTE vivo, se reactiva") ? 1 : 0;
        }
        $this->line("   Total: {$ventasEliminadas->count()} ventas, {$reactivados} DTE reactivados con numero nuevo.");

        $this->line('');
        $this->info($this->aplicar
            ? 'Listo. Los DTE en Creado los envia app:procesar-dtes-pendientes.'
            : 'Nada se cambio. Si el resultado esta bien, corra de nuevo con --aplicar.');

        return self::SUCCESS;
    }

    /** Da numero de control nuevo y deja el DTE en Creado (con su venta y caja). */
    private function renumerar(dte $d, string $motivo): bool
    {
        $codigo = str_pad((string) optional($d->RtipoDte()->first())->codigo, 2, '0', STR_PAD_LEFT);
        $facturador = ['01' => 1, '03' => 2, '05' => 4][$codigo] ?? null;
        if (!$facturador) {
            $this->warn("   DTE {$d->id} {$d->numeroControl}: tipo {$codigo} no se renumera aqui.");
            return false;
        }

        if (!$this->aplicar) {
            $this->line("   DTE {$d->id} {$d->numeroControl} ({$d->estado}, venta {$d->venta}): {$motivo} -> numero nuevo y Creado");
            return true;
        }

        // Si Hacienda ya lo tiene con sello, se toma y no se cambia el numero
        try {
            if (app(ConsultaDteHaciendaService::class)->consultarYReconciliar($d) === ConsultaDteHaciendaService::ENCONTRADO) {
                $this->line("   DTE {$d->id} {$d->numeroControl}: Hacienda ya lo tiene, se tomo el sello.");
                return false;
            }
        } catch (\Throwable $e) {
            $this->warn("   DTE {$d->id}: no se pudo consultar a Hacienda ({$e->getMessage()}); no se cambia.");
            return false;
        }

        $nuevo = DB::transaction(function () use ($d, $facturador) {
            // obtenerCodigoDTE toma la sucursal y la caja de la sesion
            session(['sucursal' => $d->sucursal, 'caja' => $d->caja]);
            $numero = $this->obtenerCodigoDTE($facturador);

            $cambio = dte::whereKey($d->id)->where(fn ($q) => $q->whereNull('sello')->orWhere('sello', ''))
                ->update(['numeroControl' => $numero, 'estado' => 'Creado', 'jsonDte' => null, 'updated_at' => now()]);
            if (!$cambio) {
                return null;
            }
            DB::table('ventas')->where('id', $d->venta)->update(['numero' => $numero, 'tipo' => 'DTE', 'codigo' => $d->codigoGeneracion, 'updated_at' => now()]);
            DB::table('cajas')->where('venta', $d->venta)->update(['numero' => $numero, 'codigo' => $d->codigoGeneracion, 'updated_at' => now()]);

            return $numero;
        });

        if ($nuevo) {
            $this->line("   DTE {$d->id}: {$d->numeroControl} -> {$nuevo} (Creado)");
        }

        return (bool) $nuevo;
    }
}
