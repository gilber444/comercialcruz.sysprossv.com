<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sube al VPS por API solo los DTE PROCESADOS (con sello) de esta sucursal,
 * cada uno con su venta, detalles, caja, resumen, firma y recepcion.
 *
 * 1) Pregunta al VPS cuales de un lote no tiene (o tiene sin ese sello).
 * 2) Manda solo esos. El VPS guarda por sincro_id (igual que sync:local-vps) y
 *    devuelve sus ids, que se anotan en id_vps de esta PC.
 * Avanza por los DTE con una marca guardada en storage/app, asi cada corrida
 * sigue donde quedo la anterior; un DTE que recibe su sello despues vuelve a
 * entrar porque cambia su updated_at.
 */
class SubirDtesProcesadosApi extends Command
{
    use \App\Console\Concerns\ReportaLatido;

    protected $signature = 'sync:dtes-procesados-api
        {--limit=100 : DTE a revisar por lote}
        {--lotes=10 : Lotes por corrida}
        {--sucursal= : Sucursal (por defecto APP_SUCURSAL_ID)}
        {--desde=2026-10-01 : Solo DTE emitidos desde esta fecha}
        {--reiniciar : Vuelve a revisar desde el principio}';

    protected $description = 'Sube al VPS por API los DTE PROCESADOS con sello (venta, detalles, caja, resumen, firma y recepcion)';

    private const CONN = 'localmysql';

    protected function ejecutar(): int
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

        // Una sola corrida a la vez por PC (la manual y la programada no se cruzan).
        // flock lo suelta el sistema aunque el proceso se cierre de golpe.
        $candado = fopen(storage_path("app/api_dtes_procesados_{$sucursal}.lock"), 'c');
        if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) {
            $this->omitirLatido = true;
            $this->info('Ya hay otra corrida subiendo DTE en esta PC; esta se salta.');
            return self::SUCCESS;
        }

        $marcaArchivo = storage_path("app/api_dtes_procesados_{$sucursal}.json");
        $marca = !$this->option('reiniciar') && is_file($marcaArchivo)
            ? (json_decode((string) file_get_contents($marcaArchivo), true) ?: [])
            : [];

        $subidos = $revisados = 0;
        $errores = [];

        try {
            $this->subirLotes($url, $token, $sucursal, $marca, $marcaArchivo, $subidos, $revisados, $errores);
        } catch (\Throwable $e) {
            // La marca se guarda por lote: la siguiente corrida sigue donde quedo
            $this->error('No se pudo subir: ' . $e->getMessage());
            $this->info("Antes del error: revisados {$revisados}, subidos {$subidos}.");
            return self::FAILURE;
        }

        foreach (array_slice($errores, 0, 20) as $e) {
            $this->warn('  ' . $e);
        }
        $this->info("DTE procesados revisados: {$revisados}, subidos al VPS: {$subidos}, con error: " . count($errores) . '.');

        return self::SUCCESS;
    }

    private function subirLotes(string $url, string $token, int $sucursal, array $marca, string $marcaArchivo, int &$subidos, int &$revisados, array &$errores): void
    {
        for ($lote = 0; $lote < max(1, (int) $this->option('lotes')); $lote++) {
            $dtes = $this->siguientes($sucursal, $marca, max(1, (int) $this->option('limit')));
            if ($dtes->isEmpty()) {
                break;
            }
            $revisados += $dtes->count();

            foreach ($dtes as $d) {
                $this->asegurarSincro('dtes', $d);
            }

            // 1) Cuales le faltan al VPS
            $resp = $this->post($url . '/api/sync/dtes-procesados/faltantes', $token, [
                'sucursal' => $sucursal,
                'items' => $dtes->map(fn ($d) => ['sincro_id' => $d->sincro_id, 'sello' => $d->sello])->values(),
            ]);
            $faltan = array_flip((array) ($resp['faltantes'] ?? []));

            // 2) Arma y manda solo esos
            $paquetes = [];
            foreach ($dtes as $d) {
                if (isset($faltan[$d->sincro_id]) && ($p = $this->paquete($d))) {
                    $paquetes[] = $p;
                }
            }

            if ($paquetes) {
                $r = $this->post($url . '/api/sync/dtes-procesados', $token, ['sucursal' => $sucursal, 'paquetes' => $paquetes]);
                $subidos += (int) ($r['guardados'] ?? 0);
                $errores = array_merge($errores, (array) ($r['errores'] ?? []));
                $this->anotarIdsVps((array) ($r['ids'] ?? []));
            }

            $ultimo = $dtes->last();
            $marca = ['ts' => (string) $ultimo->updated_at, 'id' => (int) $ultimo->id];
            file_put_contents($marcaArchivo, json_encode($marca));
        }
    }

    /** Siguiente lote de DTE PROCESADOS con sello, factura (01) y CCF (03), de esta sucursal. */
    private function siguientes(int $sucursal, array $marca, int $limit)
    {
        $q = DB::connection(self::CONN)->table('dtes')
            ->join('tipo_documentos as td', 'td.id', '=', 'dtes.tipoDte')
            ->whereIn('td.codigo', ['01', '1', '03', '3'])
            ->where('dtes.sucursal', $sucursal)
            ->whereRaw('UPPER(dtes.estado) = ?', ['PROCESADO'])
            ->whereNotNull('dtes.sello')->where('dtes.sello', '!=', '')
            ->where('dtes.fecEmi', '>=', $this->option('desde'))
            ->select('dtes.*');

        if (!empty($marca['ts'])) {
            $q->where(function ($w) use ($marca) {
                $w->where('dtes.updated_at', '>', $marca['ts'])
                    ->orWhere(function ($w2) use ($marca) {
                        $w2->where('dtes.updated_at', $marca['ts'])->where('dtes.id', '>', (int) ($marca['id'] ?? 0));
                    });
            });
        }

        return $q->orderBy('dtes.updated_at')->orderBy('dtes.id')->limit($limit)->get();
    }

    /** Paquete de un DTE con sus filas relacionadas y el sincro_id de cada padre. */
    private function paquete(object $d): ?array
    {
        $db = DB::connection(self::CONN);
        $venta = $db->table('ventas')->where('id', $d->venta)->first();
        if (!$venta) {
            return null;
        }
        $this->asegurarSincro('ventas', $venta);

        $item = fn (string $tabla, object $fila, array $fk = []) => [
            'fila' => (array) $this->asegurarSincro($tabla, $fila),
            'fk' => $fk,
        ];

        $detalles = $db->table('ventas_detalles')->where('venta', $venta->id)->get()
            ->map(fn ($f) => $item('ventas_detalles', $f, ['producto' => $this->sincroDe('productos', $f->producto ?? null)]))->all();
        $cajas = $db->table('cajas')->where('venta', $venta->id)->get()
            ->map(fn ($f) => $item('cajas', $f, ['corte' => $this->sincroDe('cortes', $f->corte ?? null)]))->all();

        $paquete = [
            'venta' => $item('ventas', $venta),
            'detalles' => $detalles,
            'cajas' => $cajas,
            'dte' => $item('dtes', $d, ['tocken' => $this->sincroDe('tockens', $d->tocken ?? null)]),
        ];
        foreach (['resumen_dtes', 'firmadors', 'recepcion_dtes'] as $tabla) {
            $paquete[$tabla] = $db->table($tabla)->where('dte', $d->id)->get()->map(fn ($f) => $item($tabla, $f))->all();
        }

        return $paquete;
    }

    /** Garantiza sincro_id en la fila local (lo genera y guarda si falta). */
    private function asegurarSincro(string $tabla, object $fila): object
    {
        if (empty($fila->sincro_id) && property_exists($fila, 'sincro_id')) {
            $fila->sincro_id = (string) Str::uuid();
            DB::connection(self::CONN)->table($tabla)->where('id', $fila->id)->update(['sincro_id' => $fila->sincro_id]);
        }

        return $fila;
    }

    private function sincroDe(string $tabla, $id): ?string
    {
        if (!$id) {
            return null;
        }
        try {
            return DB::connection(self::CONN)->table($tabla)->where('id', $id)->value('sincro_id');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Anota en id_vps de esta PC los ids que devolvio el VPS. */
    private function anotarIdsVps(array $listas): void
    {
        foreach ($listas as $ids) {
            foreach ((array) $ids as $tabla => $porSincro) {
                foreach ((array) $porSincro as $sincro => $idVps) {
                    try {
                        DB::connection(self::CONN)->table($tabla)->where('sincro_id', $sincro)->update(['id_vps' => (int) $idVps]);
                    } catch (\Throwable $e) {
                        // La tabla puede no tener id_vps: no impide la subida
                    }
                }
            }
        }
    }

    private function post(string $url, string $token, array $datos): array
    {
        $r = Http::withHeaders(['X-Sync-Token' => $token, 'Accept' => 'application/json'])
            ->timeout(120)->retry(2, 2000, throw: false)
            ->post($url, $datos);

        if (!$r->successful()) {
            throw new \RuntimeException("El VPS respondio HTTP {$r->status()}: " . mb_substr($r->body(), 0, 300));
        }

        return (array) $r->json();
    }
}
