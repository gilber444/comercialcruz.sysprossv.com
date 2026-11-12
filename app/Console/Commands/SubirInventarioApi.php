<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sube al VPS por API la existencia (inventarios) y el kardex de esta sucursal.
 * Reemplaza la subida por conexion directa (sync:local-vps para esas tablas y
 * sync:existencias-vps).
 *
 * - Existencia: compara cada inventario con la ultima existencia que el VPS ya
 *   confirmo (guardada en storage/app) y manda solo los que cambiaron, suba o
 *   baje, aunque el cambio no haya tocado updated_at. Cada
 *   sync.inventario_completo_min minutos manda todos, por si alla quedo distinta.
 * - Kardex: manda las filas nuevas (por id) y las modificadas (por updated_at).
 */
class SubirInventarioApi extends Command
{
    protected $signature = 'sync:inventario-api
        {--sucursal= : Sucursal (por defecto APP_SUCURSAL_ID)}
        {--limit=500 : Filas por peticion}
        {--lotes=0 : Peticiones maximas por tabla en cada corrida (0 = hasta terminar)}
        {--completo : Manda la existencia de todos los productos aunque no hayan cambiado}
        {--desde=2026-10-01 : En la primera corrida, kardex creado desde esta fecha}
        {--reiniciar : Olvida lo ya enviado y empieza de nuevo}';

    protected $description = 'Sube al VPS por API la existencia (inventarios) y el kardex de esta sucursal';

    private const CONN = 'localmysql';

    private array $estado = [];

    private string $archivo = '';

    private string $url = '';

    private string $token = '';

    private int $sucursal = 0;

    public function handle(): int
    {
        if (config('app.modo', 'local') === 'vps') {
            $this->error('Este comando es para las PC locales (APP_MODO=local).');
            return self::FAILURE;
        }

        $this->token = (string) config('sync.api_token');
        $this->url = rtrim((string) config('sync.api_url'), '/');
        $this->sucursal = (int) ($this->option('sucursal') ?: config('app.sucursal_id', 0));
        if ($this->token === '' || $this->url === '' || $this->sucursal <= 0) {
            $this->error('Falta SYNC_API_TOKEN, SYNC_API_URL o APP_SUCURSAL_ID en el .env.');
            return self::FAILURE;
        }
        $protegidas = array_filter(array_map('trim', explode(',', (string) env('PROTECTED_SUCURSAL_IDS', ''))));
        if (in_array((string) $this->sucursal, $protegidas, true)) {
            $this->error("La sucursal {$this->sucursal} esta protegida (PROTECTED_SUCURSAL_IDS).");
            return self::FAILURE;
        }

        // Una sola corrida a la vez por PC
        $candado = fopen(storage_path("app/api_inventario_{$this->sucursal}.lock"), 'c');
        if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) {
            $this->info('Ya hay otra corrida de sync:inventario-api en esta PC; esta se salta.');
            return self::SUCCESS;
        }

        $this->archivo = storage_path("app/api_inventario_estado_{$this->sucursal}.json");
        $this->estado = !$this->option('reiniciar') && is_file($this->archivo)
            ? (json_decode((string) file_get_contents($this->archivo), true) ?: [])
            : [];

        $ok = true;
        try {
            $this->existencias();
        } catch (\Throwable $e) {
            $ok = false;
            $this->error('inventarios: no se pudo subir: ' . $e->getMessage());
        }

        foreach (['kardexes', 'kardexes2'] as $tabla) {
            if (!Schema::connection(self::CONN)->hasTable($tabla)) {
                continue;
            }
            try {
                $this->kardex($tabla);
            } catch (\Throwable $e) {
                $ok = false;
                $this->error("{$tabla}: no se pudo subir: " . $e->getMessage());
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    // ===================== Existencia =====================

    private function existencias(): void
    {
        $db = DB::connection(self::CONN);
        $cols = array_flip(Schema::connection(self::CONN)->getColumnListing('inventarios'));

        $minutos = max(1, (int) config('sync.inventario_completo_min', 60));
        $ultimoCompleto = (string) ($this->estado['completo_at'] ?? '');
        $completo = $this->option('completo') || $ultimoCompleto === '' || $ultimoCompleto <= now()->subMinutes($minutos)->toDateTimeString();
        $inicio = now()->toDateTimeString();

        $q = $db->table('inventarios')->where('sucursal', $this->sucursal);
        if (isset($cols['deleted_at'])) {
            $q->whereNull('deleted_at');
        }
        $snap = (array) ($this->estado['snap'] ?? []);
        $cambiados = $q->orderBy('id')->get()
            ->filter(fn ($i) => $completo || ($snap[$i->id] ?? null) !== $this->norm($i->existencia))
            ->values();

        $subidas = $errores = $esperando = 0;
        $lotesMax = (int) $this->option('lotes');
        // Filas pequenas: el doble por peticion
        foreach ($cambiados->chunk(max(1, (int) $this->option('limit')) * 2) as $n => $lote) {
            if ($lotesMax > 0 && $n >= $lotesMax) {
                $completo = false; // no llego a mandar todos
                break;
            }
            $productos = $db->table('productos')->whereIn('id', $lote->pluck('producto')->unique()->values()->all())->pluck('sincro_id', 'id');
            $items = $lote->map(function ($i) use ($productos) {
                $i->sincro_id = $this->asegurarSincro('inventarios', $i);
                return ['fila' => (array) $i, 'fk' => ['producto' => $productos[$i->producto] ?? null]];
            })->values()->all();

            $r = $this->post('inventarios', $items);
            $subidas += (int) ($r['guardados'] ?? 0);
            $errores += $this->avisar($r);
            $esperando += count((array) ($r['pendientes'] ?? []));

            // Solo lo que el VPS confirmo queda como enviado
            $ids = (array) ($r['ids'] ?? []);
            foreach ($lote as $i) {
                if (isset($ids[$i->sincro_id])) {
                    $snap[$i->id] = $this->norm($i->existencia);
                    if (isset($cols['id_vps']) && (int) ($i->id_vps ?? 0) !== (int) $ids[$i->sincro_id]) {
                        $db->table('inventarios')->where('id', $i->id)->update(['id_vps' => (int) $ids[$i->sincro_id]]);
                    }
                }
            }
            $this->estado['snap'] = $snap;
            $this->guardar();
        }

        if ($completo && $errores === 0) {
            $this->estado['completo_at'] = $inicio;
            $this->guardar();
        }

        $this->info('inventarios: ' . ($completo ? 'revision completa, ' : '') . "subidas {$subidas}"
            . ($errores ? ", con error {$errores}" : '') . ($esperando ? ", producto aun no esta en el VPS {$esperando}" : '') . '.');
    }

    private function norm($valor): string
    {
        return number_format((float) $valor, 4, '.', '');
    }

    // ===================== Kardex =====================

    private function kardex(string $tabla): void
    {
        $db = DB::connection(self::CONN);
        $cols = array_flip(Schema::connection(self::CONN)->getColumnListing($tabla));
        $limit = max(1, (int) $this->option('limit'));
        $lotesMax = (int) $this->option('lotes');
        $corte = now()->subSeconds(2)->toDateTimeString();

        // Primera vez: arranca en el ultimo kardex creado antes de --desde
        if (!isset($this->estado[$tabla])) {
            $previo = $db->table($tabla)->where('created_at', '<', $this->option('desde'))->max('id');
            $this->estado[$tabla] = ['id' => (int) $previo, 'ts' => now()->toDateTimeString(), 'ts_id' => 0];
            $this->guardar();
        }
        $marca = $this->estado[$tabla];
        $idInicial = (int) $marca['id'];
        $subidas = $errores = $esperando = 0;

        // 1) Filas nuevas, por id
        for ($n = 0; $lotesMax <= 0 || $n < $lotesMax; $n++) {
            $filas = $this->base($tabla, $cols)->where('id', '>', (int) $this->estado[$tabla]['id'])
                ->orderBy('id')->limit($limit)->get();
            if ($filas->isEmpty()) {
                break;
            }
            $r = $this->post($tabla, $this->itemsKardex($tabla, $filas));
            $subidas += (int) ($r['guardados'] ?? 0);
            $errores += $this->avisar($r);

            // Si el producto o su inventario aun no llegan al VPS, la marca se queda antes
            // de la primera fila reciente que falta. Las de mas de un dia se saltan.
            $pendientes = array_flip((array) ($r['pendientes'] ?? []));
            $esperando += count($pendientes);
            $hasta = (int) $filas->last()->id;
            $detener = false;
            foreach ($filas as $f) {
                if (isset($pendientes[$f->sincro_id]) && (string) ($f->created_at ?? $corte) >= now()->subDay()->toDateTimeString()) {
                    $hasta = (int) $f->id - 1;
                    $detener = true;
                    break;
                }
            }
            $this->estado[$tabla]['id'] = max((int) $this->estado[$tabla]['id'], $hasta);
            $this->guardar();
            if ($detener) {
                break;
            }
        }

        // 2) Filas ya enviadas que se modificaron despues, por updated_at
        for ($n = 0; $lotesMax <= 0 || $n < $lotesMax; $n++) {
            $ts = (string) $this->estado[$tabla]['ts'];
            $tsId = (int) ($this->estado[$tabla]['ts_id'] ?? 0);
            $filas = $this->base($tabla, $cols)->where('id', '<=', $idInicial)
                ->where(fn ($w) => $w->where('updated_at', '>', $ts)->orWhere(fn ($w2) => $w2->where('updated_at', $ts)->where('id', '>', $tsId)))
                ->where('updated_at', '<=', $corte)
                ->orderBy('updated_at')->orderBy('id')->limit($limit)->get();
            if ($filas->isEmpty()) {
                break;
            }
            $r = $this->post($tabla, $this->itemsKardex($tabla, $filas));
            $subidas += (int) ($r['guardados'] ?? 0);
            $errores += $this->avisar($r);
            $esperando += count((array) ($r['pendientes'] ?? []));
            $ultima = $filas->last();
            $this->estado[$tabla]['ts'] = (string) $ultima->updated_at;
            $this->estado[$tabla]['ts_id'] = (int) $ultima->id;
            $this->guardar();
        }

        $this->info("{$tabla}: subidas {$subidas}" . ($errores ? ", con error {$errores}" : '') . ($esperando ? ", esperando producto o inventario {$esperando}" : '') . '.');
    }

    /** Kardex de esta sucursal (por su columna sucursal o por la de su inventario). */
    private function base(string $tabla, array $cols)
    {
        $q = DB::connection(self::CONN)->table($tabla);
        if (isset($cols['sucursal'])) {
            $q->where('sucursal', $this->sucursal);
        } else {
            $q->whereIn('inventario', fn ($s) => $s->select('id')->from('inventarios')->where('sucursal', $this->sucursal));
        }
        if (isset($cols['deleted_at'])) {
            $q->whereNull('deleted_at');
        }

        return $q;
    }

    private function itemsKardex(string $tabla, $filas): array
    {
        $db = DB::connection(self::CONN);
        $productos = $db->table('productos')->whereIn('id', $filas->pluck('producto')->filter()->unique()->values()->all())->pluck('sincro_id', 'id');
        $inventarios = $db->table('inventarios')->whereIn('id', $filas->pluck('inventario')->filter()->unique()->values()->all())->get(['id', 'sincro_id', 'sucursal'])->keyBy('id');

        return $filas->map(function ($f) use ($tabla, $productos, $inventarios) {
            $f->sincro_id = $this->asegurarSincro($tabla, $f);
            $inv = $inventarios[$f->inventario ?? 0] ?? null;
            if ($inv && empty($inv->sincro_id) && (int) $inv->sucursal === $this->sucursal) {
                $inv->sincro_id = $this->asegurarSincro('inventarios', $inv);
            }

            return ['fila' => (array) $f, 'fk' => [
                'producto' => $productos[$f->producto ?? 0] ?? null,
                // Solo el inventario de esta sucursal; si no, el VPS lo busca por producto
                'inventario' => $inv && (int) $inv->sucursal === $this->sucursal ? $inv->sincro_id : null,
            ]];
        })->values()->all();
    }

    // ===================== Comunes =====================

    /** sincro_id de la fila; si esta vacio lo pone solo si sigue vacio y relee. */
    private function asegurarSincro(string $tabla, object $fila): ?string
    {
        if (!empty($fila->sincro_id)) {
            return $fila->sincro_id;
        }
        DB::connection(self::CONN)->table($tabla)->where('id', $fila->id)
            ->where(fn ($w) => $w->whereNull('sincro_id')->orWhere('sincro_id', ''))
            ->update(['sincro_id' => (string) Str::uuid()]);

        return DB::connection(self::CONN)->table($tabla)->where('id', $fila->id)->value('sincro_id') ?: null;
    }

    private function avisar(array $r): int
    {
        $errores = (array) ($r['errores'] ?? []);
        foreach (array_slice($errores, 0, 10) as $e) {
            $this->warn('  ' . $e);
        }

        return count($errores);
    }

    private function guardar(): void
    {
        file_put_contents($this->archivo, json_encode($this->estado));
    }

    private function post(string $tabla, array $filas): array
    {
        $r = Http::withHeaders(['X-Sync-Token' => $this->token, 'Accept' => 'application/json'])
            ->timeout(180)->retry(2, 3000, throw: false)
            ->post($this->url . '/api/sync/inventario', ['tabla' => $tabla, 'sucursal' => $this->sucursal, 'filas' => $filas]);

        if (!$r->successful()) {
            throw new \RuntimeException("El VPS respondio HTTP {$r->status()}: " . mb_substr($r->body(), 0, 300));
        }

        return (array) $r->json();
    }
}
