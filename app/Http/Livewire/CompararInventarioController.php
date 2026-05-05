<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CompararInventarioController extends Component
{
    use WithPagination;

    public string $componentName = 'Comparar Inventario Local vs VPS';
    public string $pageTitle     = 'Diferencias';

    public int    $sucursal_id   = 0;
    public string $search        = '';
    public string $filtro        = 'diferencias';
    public array  $sucursales    = [];

    public bool   $comparado     = false;
    public string $mensaje       = '';
    public string $tipoMensaje   = 'info';

    public ?int   $pushProductoId = null;
    public string $pushNombre     = '';

    // Push masivo
    public bool   $pushingAll    = false;
    public array  $pushPending   = [];
    public int    $pushTotal     = 0;
    public int    $pushDone      = 0;
    public ?int   $pushStartedAt = null;

    protected string $connLocal = 'localmysql';
    protected string $connVps   = 'vpsmysql';

    private int $pagination = 20;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('CompararInventario_Index'), 403);

        $user = Auth::user();

        if (in_array($user->profile, ['Super', 'Administrador', 'Gerente'])) {
            $this->sucursales = DB::table('sucursales')->orderBy('nombre')->get()
                ->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->toArray();
        } else {
            $this->sucursales = DB::table('sucursales')->where('id', $user->sucursal)->get()
                ->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->toArray();
            $this->sucursal_id = $user->sucursal;
        }
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $filas = $this->comparado ? $this->obtenerDiferencias() : collect([]);

        return view('livewire.comparar_inventario.comparar_inventario', [
            'filas'    => $filas,
            'total'    => $filas->count(),
            'difCount' => $filas->filter(fn($r) => $r['diferente'])->count(),
        ])->extends('layouts.theme.app')->section('content');
    }

    public function comparar(): void
    {
        if (!$this->sucursal_id) {
            $this->mensaje     = 'Selecciona una sucursal.';
            $this->tipoMensaje = 'warning';
            return;
        }
        $this->comparado   = true;
        $this->mensaje     = '';
        $this->resetPage();
    }

    public function prepararPush(int $productoLocalId, string $nombre): void
    {
        abort_unless(Auth::user()->can('CompararInventario_Push'), 403);
        $this->pushProductoId = $productoLocalId;
        $this->pushNombre     = $nombre;
        $this->dispatchBrowserEvent('open-modal-push');
    }

    public function confirmarPush(): void
    {
        abort_unless(Auth::user()->can('CompararInventario_Push'), 403);

        if (!$this->pushProductoId || !$this->sucursal_id) return;

        try {
            $exitCode = Artisan::call('push:inventario-vps', [
                'producto_local_id' => $this->pushProductoId,
                'sucursal_id'       => $this->sucursal_id,
            ]);

            if ($exitCode === 0) {
                $this->mensaje     = "✓ Inventario de \"{$this->pushNombre}\" subido al VPS con éxito.";
                $this->tipoMensaje = 'success';
            } else {
                $this->mensaje     = 'Error al subir inventario. Revisa los logs.';
                $this->tipoMensaje = 'danger';
            }
        } catch (Throwable $e) {
            $this->mensaje     = 'Error: ' . $e->getMessage();
            $this->tipoMensaje = 'danger';
            \Illuminate\Support\Facades\Log::error('confirmarPush FAILED', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        $this->pushProductoId = null;
        $this->pushNombre     = '';
        $this->dispatchBrowserEvent('close-modal-push');
    }

    public function cancelarPush(): void
    {
        $this->pushProductoId = null;
        $this->pushNombre     = '';
    }

    public function iniciarPushTodos(): void
    {
        abort_unless(Auth::user()->can('CompararInventario_Push'), 403);

        $filas = $this->obtenerDiferencias();
        $ids   = $filas->where('diferente', true)->pluck('producto_local_id')->values()->toArray();

        if (empty($ids)) {
            $this->mensaje     = 'No hay productos con diferencias para subir.';
            $this->tipoMensaje = 'warning';
            return;
        }

        $this->pushPending   = $ids;
        $this->pushTotal     = count($ids);
        $this->pushDone      = 0;
        $this->pushStartedAt = now()->timestamp;
        $this->pushingAll    = true;
        $this->mensaje       = '';
    }

    public function procesarSiguiente(): void
    {
        if (!$this->pushingAll || empty($this->pushPending)) {
            $this->pushingAll = false;
            if ($this->pushTotal > 0) {
                $this->mensaje     = "✓ Push masivo completado: {$this->pushDone} de {$this->pushTotal} productos subidos al VPS.";
                $this->tipoMensaje = 'success';
                $this->comparar();
            }
            return;
        }

        $productoId = array_shift($this->pushPending);

        try {
            Artisan::call('push:inventario-vps', [
                'producto_local_id' => $productoId,
                'sucursal_id'       => $this->sucursal_id,
                '--sin-kardex'      => true,
            ]);
        } catch (Throwable $e) {
            // continúa con el siguiente aunque falle uno
        }

        $this->pushDone++;
    }

    public function cancelarPushTodos(): void
    {
        $this->pushingAll    = false;
        $this->pushPending   = [];
        $this->pushTotal     = 0;
        $this->pushDone      = 0;
        $this->pushStartedAt = null;
        $this->mensaje       = 'Push cancelado.';
        $this->tipoMensaje   = 'warning';
    }

    protected function obtenerDiferencias(): \Illuminate\Support\Collection
    {
        try {
            DB::connection($this->connVps)->getPdo();
        } catch (Throwable $e) {
            $this->mensaje     = 'No se pudo conectar al VPS: ' . $e->getMessage();
            $this->tipoMensaje = 'danger';
            $this->comparado   = false;
            return collect([]);
        }

        $localQuery = DB::connection($this->connLocal)
            ->table('inventarios as i')
            ->join('productos as p', 'p.id', '=', 'i.producto')
            ->where('i.sucursal', $this->sucursal_id)
            ->select(
                'i.id as inv_local_id', 'i.producto as producto_local_id',
                'i.existencia as existencia_local', 'i.sincro_id',
                'i.id_vps as inv_vps_id_guardado',
                'p.nombreProducto', 'p.codebar1', 'p.id_vps as producto_vps_id'
            );

        if ($this->search) {
            $s = $this->search;
            $localQuery->where(function ($q) use ($s) {
                $q->where('p.nombreProducto', 'like', "%{$s}%")
                  ->orWhere('p.codebar1', 'like', "%{$s}%");
            });
        }

        $locales = $localQuery->orderBy('p.nombreProducto')->get();

        if ($locales->isEmpty()) return collect([]);

        $sincroIds = $locales->pluck('sincro_id')->filter()->unique()->values()->toArray();
        $vpsMap    = collect();

        if (!empty($sincroIds)) {
            $vpsRows = DB::connection($this->connVps)
                ->table('inventarios')
                ->where('sucursal', $this->sucursal_id)
                ->whereIn('sincro_id', $sincroIds)
                //->whereNull('deleted_at')
                ->select('id', 'sincro_id', 'existencia', 'producto')
                ->get();
            foreach ($vpsRows as $v) {
                $vpsMap[$v->sincro_id] = $v;
            }
        }

        $sinMatch = $locales->filter(fn($l) => !isset($vpsMap[$l->sincro_id]) && $l->producto_vps_id);
        if ($sinMatch->isNotEmpty()) {
            $ids   = $sinMatch->pluck('producto_vps_id')->unique()->toArray();
            $extra = DB::connection($this->connVps)
                ->table('inventarios')
                ->where('sucursal', $this->sucursal_id)
                ->whereIn('producto', $ids)
                //->whereNull('deleted_at')
                ->select('id', 'sincro_id', 'existencia', 'producto')
                ->get()->keyBy('producto');

            foreach ($sinMatch as $l) {
                if (isset($extra[$l->producto_vps_id])) {
                    $vpsMap[$l->sincro_id ?: 'prod_'.$l->producto_local_id] = $extra[$l->producto_vps_id];
                }
            }
        }

        $filas = collect();
        foreach ($locales as $local) {
            $key   = $local->sincro_id ?: 'prod_'.$local->producto_local_id;
            $vps   = $vpsMap[$key] ?? null;
            $exVps = $vps ? (float) $vps->existencia : null;
            $exLoc = (float) $local->existencia_local;
            $dif   = $exVps !== null ? round($exLoc - $exVps, 4) : null;
            $esDif = $exVps === null || abs($dif) > 0.0001;

            if ($this->filtro === 'diferencias' && !$esDif) continue;

            $filas->push([
                'producto_local_id' => $local->producto_local_id,
                'inv_local_id'      => $local->inv_local_id,
                'nombreProducto'    => $local->nombreProducto,
                'codebar1'          => $local->codebar1,
                'existencia_local'  => $exLoc,
                'existencia_vps'    => $exVps,
                'diferencia'        => $dif,
                'diferente'         => $esDif,
                'sin_vps'           => $exVps === null,
            ]);
        }

        return $filas;
    }
}
