<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copia esquema_dtes del VPS a esta PC, con el JSON de cada esquema.
 *
 * Los esquemas se cargan solo en el VPS (pantalla Esquemas DTE). Sin esta copia,
 * la revision antes de firmar en las cajas no tiene contra que validar.
 * El VPS manda: lo que alli esta Activo queda Activo aqui, y una fila local que
 * el VPS no tiene se desactiva.
 */
class BajarEsquemasDesdeVPS extends Command
{
    protected $signature = 'esquemas:bajar-desde-vps {--dry-run : Solo muestra lo que cambiaria}';

    protected $description = 'Copia los esquemas JSON de Hacienda (esquema_dtes) del VPS a esta PC';

    private const CONN_LOCAL = 'localmysql';
    private const CONN_VPS = 'vpsmysql';

    /** Columnas que se copian, si existen en ambos lados. */
    private const COLUMNAS = ['nombre', 'archivo', 'ruta', 'hash', 'contenido', 'publicado', 'status', 'deleted_at', 'created_at', 'updated_at'];

    public function handle(): int
    {
        if (config('app.modo', 'local') === 'vps') {
            $this->error('Este comando es para las PC locales (APP_MODO=local).');
            return self::FAILURE;
        }

        try {
            $vps = DB::connection(self::CONN_VPS);
            $local = DB::connection(self::CONN_LOCAL);
            $vps->getPdo();
            $local->getPdo();
        } catch (\Throwable $e) {
            $this->error('Sin conexion: ' . $e->getMessage());
            return self::FAILURE;
        }

        foreach ([self::CONN_VPS => 'VPS', self::CONN_LOCAL => 'esta PC'] as $conn => $donde) {
            if (!Schema::connection($conn)->hasColumn('esquema_dtes', 'contenido')) {
                $this->error("Falta la migracion de esquema_dtes (columna contenido) en {$donde}. Corra php artisan migrate.");
                return self::FAILURE;
            }
        }

        $columnas = array_values(array_intersect(
            self::COLUMNAS,
            Schema::connection(self::CONN_VPS)->getColumnListing('esquema_dtes'),
            Schema::connection(self::CONN_LOCAL)->getColumnListing('esquema_dtes')
        ));

        // Primero sin el contenido (pesa decenas de KB por esquema): solo se baja
        // el de los esquemas nuevos o que cambiaron.
        $ligeras = array_values(array_diff($columnas, ['contenido']));
        $remotas = $vps->table('esquema_dtes')
            ->selectRaw("id, tipoDte, version, (contenido IS NULL OR contenido = '') as vacio")
            ->addSelect($ligeras)
            ->get();
        $locales = $local->table('esquema_dtes')
            ->selectRaw("id, tipoDte, version, hash, status, deleted_at, (contenido IS NULL OR contenido = '') as vacio")
            ->get()
            ->keyBy(fn ($f) => $f->tipoDte . '|' . $f->version);

        $dry = (bool) $this->option('dry-run');
        $nuevos = $actualizados = $iguales = $desactivados = $sinContenido = 0;

        $local->transaction(function () use ($vps, $local, $remotas, $locales, $ligeras, $dry, &$nuevos, &$actualizados, &$iguales, &$desactivados, &$sinContenido) {
            $vistas = [];

            foreach ($remotas as $r) {
                $clave = $r->tipoDte . '|' . $r->version;
                $vistas[$clave] = true;
                $datos = [];
                foreach ($ligeras as $c) {
                    $datos[$c] = $r->{$c};
                }

                if ($r->vacio && $r->status === 'Activo' && $r->deleted_at === null) {
                    $sinContenido++;
                    $this->warn("  {$r->tipoDte} v{$r->version}: el VPS no tiene el contenido; vuelva a subir el esquema en el VPS.");
                }

                $l = $locales[$clave] ?? null;
                $igual = $l !== null && $l->hash === $r->hash && $l->status === $r->status
                    && $l->deleted_at == $r->deleted_at && ($r->vacio || !$l->vacio);
                if ($igual) {
                    $iguales++;
                    continue;
                }

                if (!$r->vacio) {
                    $datos['contenido'] = $vps->table('esquema_dtes')->where('id', $r->id)->value('contenido');
                }

                if ($l === null) {
                    $nuevos++;
                    $this->line("  + {$r->tipoDte} v{$r->version} ({$r->status})");
                    if (!$dry) {
                        $local->table('esquema_dtes')->insert(array_merge(['tipoDte' => $r->tipoDte, 'version' => $r->version], $datos));
                    }
                    continue;
                }

                $actualizados++;
                $this->line("  ~ {$r->tipoDte} v{$r->version} ({$l->status} -> {$r->status})");
                if (!$dry) {
                    $local->table('esquema_dtes')->where('id', $l->id)->update($datos);
                }
            }

            foreach ($locales as $clave => $l) {
                if (!isset($vistas[$clave]) && $l->status === 'Activo') {
                    $desactivados++;
                    $this->line("  - {$l->tipoDte} v{$l->version}: no esta en el VPS, se desactiva");
                    if (!$dry) {
                        $local->table('esquema_dtes')->where('id', $l->id)->update(['status' => 'Desactivado', 'updated_at' => now()]);
                    }
                }
            }
        });

        $this->info(($dry ? '[dry-run] ' : '') . "Esquemas: {$nuevos} nuevos, {$actualizados} actualizados, {$iguales} iguales, {$desactivados} desactivados"
            . ($sinContenido ? ", {$sinContenido} sin contenido en el VPS" : '') . '.');

        return self::SUCCESS;
    }
}
