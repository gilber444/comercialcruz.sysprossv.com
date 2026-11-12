<?php

namespace App\Console\Concerns;

use App\Support\SyncApi;

/**
 * Para los comandos de sync por API de la caja: al terminar manda al VPS un
 * "latido" con el resultado y lo que imprimio (pantalla monitor-sync-api).
 *
 * El comando escribe su logica en ejecutar() en vez de handle(). Si una corrida
 * se salta (por ejemplo, porque ya hay otra), pone $this->omitirLatido = true
 * para no tapar el resumen de la corrida buena.
 */
trait ReportaLatido
{
    private array $lineasLatido = [];

    private bool $huboErrorLatido = false;

    protected bool $omitirLatido = false;

    public function handle(): int
    {
        $codigo = (int) $this->ejecutar();

        if (!$this->omitirLatido) {
            $sucursal = $this->hasOption('sucursal') && $this->option('sucursal')
                ? (int) $this->option('sucursal')
                : (int) config('app.sucursal_id', 0);
            SyncApi::latido((string) $this->getName(), $sucursal, $codigo === 0 && !$this->huboErrorLatido, $this->lineasLatido);
        }

        return $codigo;
    }

    public function info($string, $verbosity = null)
    {
        $this->lineasLatido[] = (string) $string;
        parent::info($string, $verbosity);
    }

    public function warn($string, $verbosity = null)
    {
        $this->lineasLatido[] = '⚠ ' . $string;
        parent::warn($string, $verbosity);
    }

    public function error($string, $verbosity = null)
    {
        $this->huboErrorLatido = true;
        $this->lineasLatido[] = '✘ ' . $string;
        parent::error($string, $verbosity);
    }
}
