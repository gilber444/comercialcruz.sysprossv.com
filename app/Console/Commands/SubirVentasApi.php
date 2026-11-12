<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\SyncTablasVentasController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sube al VPS por API, por lotes, las filas nuevas o cambiadas de esta sucursal
 * en: ventas, cajas, ventas_detalles, tockens, dtes, resumen_dtes, firmadors y
 * recepcion_dtes (en ese orden, padres antes que hijos).
 *
 * Mismas reglas que sync:local-vps (upsert por sincro_id, llaves traducidas por
 * el sincro_id del padre, sin borrados logicos y sin DTE RECHAZADO), pero manda
 * cientos de filas por peticion. Cada tabla avanza con su propia marca
 * (updated_at, id) guardada en storage/app.
 */
class SubirVentasApi extends Command
{
    protected $signature = 'sync:ventas-api
        {--limit=500 : Filas por peticion}
        {--lotes=0 : Peticiones maximas por tabla en cada corrida (0 = hasta terminar)}
        {--sucursal= : Sucursal (por defecto APP_SUCURSAL_ID)}
        {--tablas= : Solo estas tablas, separadas por coma}
        {--desde=2026-10-01 : En la primera corrida, solo filas cambiadas desde esta fecha}
        {--reiniciar : Borra las marcas y vuelve a revisar desde --desde}';

    protected $description = 'Sube al VPS por API ventas, cajas, detalles, tockens, dtes, resumen, firmadors y recepcion de esta sucursal';

    private const CONN = 'localmysql';

    /** Tope de filas por peticion en tablas con JSON grandes (limite de tamano del POST). */
    private const TOPE = ['dtes' => 200, 'resumen_dtes' => 300, 'firmadors' => 100, 'recepcion_dtes' => 200];

    public function handle(): int
    {
        if (config('app.modo', 'local') === 'vps') {
            $this->error('Este comando es para las PC locales (APP_MODO=local).');
            return self::FAILURE;
        }

        $token = (string) config('sync.api_token');
        $url = rtrim((string) config('sync.api_url'), '/');
        $sucursal = (int) ($this->option('sucursal') ?: config('app.sucursal_id', 0));
        if ($token === '' || $url === '' || $sucursal <= 0) {
            $this->error('Falta SYNC_API_TOKEN, SYNC_API_URL o APP_SUCURSAL_ID en el .env.');
            return self::FAILURE;
        }

        // Una sola corrida a la vez por PC (la manual y la programada no se cruzan)
        $candado = fopen(storage_path("app/api_ventas_{$sucursal}.lock"), 'c');
        if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) {
            $this->info('Ya hay otra corrida de sync:ventas-api en esta PC; esta se salta.');
            return self::SUCCESS;
        }

        $marcaArchivo = storage_path("app/api_ventas_marcas_{$sucursal}.json");
        $marcas = !$this->option('reiniciar') && is_file($marcaArchivo)
            ? (json_decode((string) file_get_contents($marcaArchivo), true) ?: [])
            : [];

        $tablas = array_keys(SyncTablasVentasController::PLANES);
        if ($this->option('tablas')) {
            $pedidas = array_map('trim', explode(',', (string) $this->option('tablas')));
            $tablas = array_values(array_intersect($tablas, $pedidas));
        }

        // Un mismo corte para todas las tablas: un hijo anterior al corte tiene a su
        // padre tambien antes del corte, asi que el padre sube primero.
        $this->corte = now()->subSeconds(2)->toDateTimeString();
        $lotesMax = (int) $this->option('lotes');
        $limit = max(1, (int) $this->option('limit'));
        $huboError = false;

        foreach ($tablas as $tabla) {
            if (!Schema::connection(self::CONN)->hasTable($tabla)) {
                continue;
            }
            $subidas = $errores = $esperando = 0;
            try {
                for ($lote = 0; $lotesMax <= 0 || $lote < $lotesMax; $lote++) {
                    $filas = $this->siguientes($tabla, $sucursal, $marcas[$tabla] ?? null, min($limit, self::TOPE[$tabla] ?? $limit));
                    if ($filas->isEmpty()) {
                        break;
                    }

                    $r = $this->post($url . '/api/sync/tablas-ventas', $token, [
                        'tabla' => $tabla,
                        'sucursal' => $sucursal,
                        'filas' => $filas->map(fn ($f) => $this->item($tabla, $f))->values()->all(),
                    ]);

                    $subidas += (int) ($r['guardados'] ?? 0);
                    foreach ((array) ($r['errores'] ?? []) as $e) {
                        $errores++;
                        if ($errores <= 10) {
                            $this->warn('  ' . $e);
                        }
                    }
                    $this->anotarIdsVps($tabla, (array) ($r['ids'] ?? []));

                    // Filas cuyo padre aun no esta en el VPS: la marca se queda antes de
                    // la primera (si es reciente) para reintentarla en la siguiente corrida.
                    // Las de mas de un dia se dan por perdidas (padre borrado) y se saltan.
                    $pendientes = array_flip((array) ($r['pendientes'] ?? []));
                    $esperando += count($pendientes);
                    $hasta = $filas->last();
                    $detener = false;
                    if ($pendientes) {
                        $anterior = null;
                        foreach ($filas as $f) {
                            if (isset($pendientes[$f->sincro_id]) && (string) $f->updated_at >= now()->subDay()->toDateTimeString()) {
                                $hasta = $anterior;
                                $detener = true;
                                break;
                            }
                            $anterior = $f;
                        }
                    }
                    if ($hasta) {
                        $marcas[$tabla] = ['ts' => (string) $hasta->updated_at, 'id' => (int) $hasta->id];
                        file_put_contents($marcaArchivo, json_encode($marcas));
                    }
                    if ($detener) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                $huboError = true;
                $this->error("{$tabla}: no se pudo subir: " . $e->getMessage());
            }
            $this->info("{$tabla}: subidas {$subidas}" . ($errores ? ", con error {$errores}" : '') . ($esperando ? ", esperando a su padre {$esperando}" : '') . '.');
        }

        return $huboError ? self::FAILURE : self::SUCCESS;
    }

    /** Siguiente lote de la tabla, solo de esta sucursal, despues de la marca. */
    private function siguientes(string $tabla, int $sucursal, ?array $marca, int $limit)
    {
        $db = DB::connection(self::CONN);
        $cols = array_flip(Schema::connection(self::CONN)->getColumnListing($tabla));
        $q = $db->table($tabla);

        if ($tabla === 'ventas_detalles') {
            $q->whereIn('venta', fn ($s) => $s->select('id')->from('ventas')->where('sucursal', $sucursal));
        } elseif (isset($cols['sucursal'])) {
            $q->where('sucursal', $sucursal);
        }
        if (isset($cols['deleted_at'])) {
            $q->whereNull('deleted_at');
        }
        if ($tabla === 'dtes' && isset($cols['estado'])) {
            // Igual que sync:local-vps: los RECHAZADO no se suben
            $q->where(fn ($w) => $w->whereNull('estado')->orWhereRaw("UPPER(TRIM(estado)) <> 'RECHAZADO'"));
        }

        if (!empty($marca['ts'])) {
            $q->where(function ($w) use ($marca) {
                $w->where('updated_at', '>', $marca['ts'])
                    ->orWhere(fn ($w2) => $w2->where('updated_at', $marca['ts'])->where('id', '>', (int) ($marca['id'] ?? 0)));
            });
        } else {
            $q->where('updated_at', '>=', $this->option('desde'));
        }

        // Las filas que cambien en este mismo segundo entran en la siguiente corrida
        $q->where('updated_at', '<=', $this->corte);

        return $q->orderBy('updated_at')->orderBy('id')->limit($limit)->get();
    }

    /** Fila + sincro_id de cada padre para que el VPS traduzca las llaves. */
    private function item(string $tabla, object $fila): array
    {
        $fila->sincro_id = $this->asegurarSincro($tabla, $fila);
        $fk = [];
        foreach (SyncTablasVentasController::PLANES[$tabla] as $col => $tablaPadre) {
            $id = $fila->{$col} ?? null;
            if ($id) {
                $fk[$col] = in_array($tablaPadre, ['ventas', 'dtes', 'tockens'], true)
                    ? $this->asegurarSincro($tablaPadre, (object) ['id' => $id])
                    : $this->sincroDe($tablaPadre, $id);
            }
        }

        return ['fila' => (array) $fila, 'fk' => $fk];
    }

    private array $cacheSincro = [];

    private string $corte = '';

    /**
     * Devuelve el sincro_id de la fila; si no tiene, lo genera solo si sigue vacio
     * (UPDATE ... WHERE sincro_id vacio) y relee, asi dos procesos nunca le ponen
     * sincro_id distintos a la misma fila.
     */
    private function asegurarSincro(string $tabla, object $fila): ?string
    {
        $clave = "{$tabla}:{$fila->id}";
        if (isset($this->cacheSincro[$clave])) {
            return $this->cacheSincro[$clave];
        }
        $sincro = property_exists($fila, 'sincro_id')
            ? $fila->sincro_id
            : DB::connection(self::CONN)->table($tabla)->where('id', $fila->id)->value('sincro_id');

        if (empty($sincro)) {
            DB::connection(self::CONN)->table($tabla)->where('id', $fila->id)
                ->where(fn ($w) => $w->whereNull('sincro_id')->orWhere('sincro_id', ''))
                ->update(['sincro_id' => (string) Str::uuid()]);
            $sincro = DB::connection(self::CONN)->table($tabla)->where('id', $fila->id)->value('sincro_id');
        }

        return $this->cacheSincro[$clave] = $sincro ?: null;
    }

    private function sincroDe(string $tabla, $id): ?string
    {
        $clave = "{$tabla}:{$id}";
        if (!array_key_exists($clave, $this->cacheSincro)) {
            try {
                $this->cacheSincro[$clave] = DB::connection(self::CONN)->table($tabla)->where('id', $id)->value('sincro_id');
            } catch (\Throwable $e) {
                $this->cacheSincro[$clave] = null;
            }
        }

        return $this->cacheSincro[$clave];
    }

    /** Anota en id_vps de esta PC los ids que devolvio el VPS. */
    private function anotarIdsVps(string $tabla, array $ids): void
    {
        if (!$ids || !Schema::connection(self::CONN)->hasColumn($tabla, 'id_vps')) {
            return;
        }
        foreach ($ids as $sincro => $idVps) {
            DB::connection(self::CONN)->table($tabla)->where('sincro_id', $sincro)->update(['id_vps' => (int) $idVps]);
        }
    }

    private function post(string $url, string $token, array $datos): array
    {
        $r = Http::withHeaders(['X-Sync-Token' => $token, 'Accept' => 'application/json'])
            ->timeout(180)->retry(2, 3000, throw: false)
            ->post($url, $datos);

        if (!$r->successful()) {
            throw new \RuntimeException("El VPS respondio HTTP {$r->status()}: " . mb_substr($r->body(), 0, 300));
        }

        return (array) $r->json();
    }
}
