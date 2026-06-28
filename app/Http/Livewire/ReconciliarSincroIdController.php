<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class ReconciliarSincroIdController extends Component
{
    public string $componentName = 'Reconciliar Sincro ID de Inventarios';
    public int $sucursal = 0;
    public array $sucursales = [];
    public array $rows = [];
    public string $mensaje = '';
    public string $tipoMensaje = 'success'; // success | danger

    protected string $connVps   = 'vpsmysql';
    protected string $connLocal = 'localmysql';

    public function mount(): void
    {
        abort_unless(Auth::user()->can('ReconciliarSincroId_Index'), 403);

        $this->componentName = 'Reconciliar Sincro ID de Inventarios';

        $user = Auth::user();

        if (in_array($user->profile, ['Super', 'Administrador', 'Gerente'], true)) {
            $this->sucursales = DB::table('sucursales')->orderBy('nombre')->get()->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->toArray();
            $this->sucursal = 0;
        } else {
            $this->sucursales = DB::table('sucursales')->where('id', $user->sucursal)->orderBy('nombre')->get()->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->toArray();
            $this->sucursal = (int) $user->sucursal;
        }
    }

    public function render()
    {
        return view('livewire.inventario.reconciliar-sincro')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function consultar(): void
    {
        abort_unless(Auth::user()->can('ReconciliarSincroId_Index'), 403);

        $this->mensaje = '';
        $this->rows = [];

        try {
            DB::connection($this->connVps)->getPdo();
            DB::connection($this->connLocal)->getPdo();
        } catch (Throwable $e) {
            $this->mensaje = 'Error de conexion VPS: ' . $e->getMessage();
            $this->tipoMensaje = 'danger';
            return;
        }

        // 1. Traer inventarios locales con sincro_id del producto
        $query = DB::connection($this->connLocal)
            ->table('inventarios as i')
            ->join('productos as p', 'p.id', '=', 'i.producto')
            ->select(
                'i.id as local_inv_id',
                'i.producto as local_producto_id',
                'i.sucursal',
                'i.sincro_id as inv_sincro_local',
                'i.existencia',
                'p.sincro_id as prod_sincro',
                'p.nombreProducto',
                'p.codebar3'
            )
            ->whereNotNull('p.sincro_id')
            ->where('p.sincro_id', '!=', '');

        if ((int) $this->sucursal > 0) {
            $query->where('i.sucursal', $this->sucursal);
        }

        $localInvs = $query->get();

        if ($localInvs->isEmpty()) {
            $this->mensaje = 'No se encontraron inventarios locales con productos sincronizados.';
            $this->tipoMensaje = 'danger';
            return;
        }

        // 2. Obtener productos VPS por los sincro_ids de los productos locales
        $prodSincros = $localInvs->pluck('prod_sincro')->filter()->unique()->values()->all();

        $vpsProductos = DB::connection($this->connVps)
            ->table('productos')
            ->select('id', 'sincro_id')
            ->whereIn('sincro_id', $prodSincros)
            ->get()
            ->keyBy('sincro_id');

        if ($vpsProductos->isEmpty()) {
            $this->mensaje = 'No se encontraron productos en VPS que coincidan con los locales.';
            $this->tipoMensaje = 'danger';
            return;
        }

        // 3. Traer inventarios VPS para esos productos
        $vpsProductoIds = $vpsProductos->pluck('id')->all();

        $vpsInvQuery = DB::connection($this->connVps)
            ->table('inventarios')
            ->select('id as vps_inv_id', 'producto', 'sucursal', 'sincro_id as inv_sincro_vps', 'existencia as existencia_vps')
            ->whereIn('producto', $vpsProductoIds);

        if ((int) $this->sucursal > 0) {
            $vpsInvQuery->where('sucursal', $this->sucursal);
        }

        $vpsInventarios = $vpsInvQuery->get();

        // Indexar por vpsProdId|sucursal
        $vpsInvMap = [];
        foreach ($vpsInventarios as $vpsInv) {
            $vpsInvMap[$vpsInv->producto . '|' . $vpsInv->sucursal] = $vpsInv;
        }

        // 4. Comparar y detectar diferencias
        $rows = [];
        foreach ($localInvs as $localInv) {
            $vpsProducto = $vpsProductos[$localInv->prod_sincro] ?? null;
            if (!$vpsProducto) {
                continue; // producto no existe en VPS aun
            }

            $key     = $vpsProducto->id . '|' . $localInv->sucursal;
            $vpsInv  = $vpsInvMap[$key] ?? null;

            if (!$vpsInv) {
                continue; // inventario VPS no existe para esa sucursal
            }

            // Solo mostrar si sincro_id difiere
            if ((string) $localInv->inv_sincro_local === (string) $vpsInv->inv_sincro_vps) {
                continue;
            }

            $rows[] = [
                'local_inv_id'    => (int) $localInv->local_inv_id,
                'vps_inv_id'      => (int) $vpsInv->vps_inv_id,
                'local_producto'  => (int) $localInv->local_producto_id,
                'sucursal'        => (int) $localInv->sucursal,
                'codebar3'        => $localInv->codebar3 ?? '',
                'nombreProducto'  => $localInv->nombreProducto ?? ('Producto #' . $localInv->local_producto_id),
                'existencia_local' => (float) $localInv->existencia,
                'existencia_vps'  => (float) $vpsInv->existencia_vps,
                'sincro_local'    => (string) ($localInv->inv_sincro_local ?? '(vacío)'),
                'sincro_vps'      => (string) ($vpsInv->inv_sincro_vps ?? '(vacío)'),
            ];
        }

        $this->rows = $rows;

        if (empty($rows)) {
            $this->mensaje = 'No se encontraron diferencias de sincro_id en inventarios.';
            $this->tipoMensaje = 'success';
        } else {
            $this->mensaje = count($rows) . ' inventario(s) con sincro_id diferente encontrados.';
            $this->tipoMensaje = 'danger';
        }
    }

    /**
     * Actualiza el sincro_id local de UN inventario para que coincida con VPS.
     * NO toca la existencia.
     */
    public function actualizar(int $localInvId): void
    {
        abort_unless(Auth::user()->can('ReconciliarSincroId_Actualizar'), 403);

        $row = collect($this->rows)->firstWhere('local_inv_id', $localInvId);

        if (!$row) {
            $this->emit('item-error', 'No se encontró el registro.');
            return;
        }

        DB::connection($this->connLocal)
            ->table('inventarios')
            ->where('id', $row['local_inv_id'])
            ->update([
                'sincro_id'  => $row['sincro_vps'],
                'id_vps'     => $row['vps_inv_id'],
                'updated_at' => now(),
            ]);

        // Quitar de la lista local
        $this->rows = array_values(
            array_filter($this->rows, fn ($r) => $r['local_inv_id'] !== $localInvId)
        );

        $this->emit('item-added', 'Sincro ID actualizado correctamente.');
    }

    /**
     * Actualiza TODOS los registros de la lista actual.
     */
    public function actualizarTodos(): void
    {
        abort_unless(Auth::user()->can('ReconciliarSincroId_Actualizar'), 403);

        if (empty($this->rows)) {
            $this->emit('item-error', 'No hay registros para actualizar.');
            return;
        }

        $actualizados = 0;

        DB::connection($this->connLocal)->transaction(function () use (&$actualizados) {
            foreach ($this->rows as $row) {
                DB::connection($this->connLocal)
                    ->table('inventarios')
                    ->where('id', $row['local_inv_id'])
                    ->update([
                        'sincro_id'  => $row['sincro_vps'],
                        'id_vps'     => $row['vps_inv_id'],
                        'updated_at' => now(),
                    ]);
                $actualizados++;
            }
        });

        $this->rows = [];
        $this->mensaje = "{$actualizados} sincro_id(s) actualizados correctamente.";
        $this->tipoMensaje = 'success';

        $this->emit('item-added', "{$actualizados} registro(s) actualizados.");
    }
}
