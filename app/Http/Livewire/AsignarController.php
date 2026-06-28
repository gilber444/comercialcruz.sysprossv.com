<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

// [2026-03-29] Rediseño completo — permisos agrupados, clonación de roles, sin paginación
class AsignarController extends Component
{
    public $role         = 'Elegir';
    public $search       = '';
    public $roleClonar   = 'Elegir';
    public $componentName = 'Asignar Permisos';

    protected $listeners = ['revokeall' => 'RemoveAll'];

    public function mount()
    {
        $this->componentName = 'Asignar Permisos';
    }

    public function render()
    {
        // Todos los permisos filtrados por búsqueda, agrupados por módulo
        $allPermisos = Permission::select('id', 'name')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();

        // Agrupar: todo antes del último guión bajo = módulo
        // KardexAuditor_Index → KardexAuditor | Dashboard_Admin_View → Dashboard_Admin
        $grupos = $allPermisos->groupBy(function ($p) {
            $parts = explode('_', $p->name);
            if (count($parts) > 1) {
                array_pop($parts);
                return implode('_', $parts);
            }
            return $p->name;
        })->sortKeys();

        // Permisos actuales del rol seleccionado
        $permisosDelRol = [];
        $rolActual      = null;
        $rolStats       = ['total' => 0, 'count' => 0, 'pct' => 0];

        if ($this->role !== 'Elegir') {
            $rolActual = Role::with('permissions')->find($this->role);
            if ($rolActual) {
                $permisosDelRol  = $rolActual->permissions->pluck('id')->toArray();
                $total           = Permission::count();
                $rolStats = [
                    'total' => $total,
                    'count' => count($permisosDelRol),
                    'pct'   => $total > 0 ? round(count($permisosDelRol) / $total * 100) : 0,
                ];
            }
        }

        // Roles disponibles
        $roles = Role::orderBy('name')->get();
        if (auth()->user()->profile !== 'Super') {
            $roles = $roles->filter(fn($r) => $r->name !== 'Super')->values();
        }

        // Stats de todos los roles para el selector de clonación
        $totalPermisos = Permission::count();
        $rolesStats = $roles->map(fn($r) => [
            'id'    => $r->id,
            'name'  => $r->name,
            'count' => DB::table('role_has_permissions')->where('role_id', $r->id)->count(),
            'total' => $totalPermisos,
        ]);

        return view('livewire.asignar.asignar', compact(
            'grupos', 'roles', 'rolesStats', 'permisosDelRol', 'rolActual', 'rolStats', 'totalPermisos'
        ))->extends('layouts.theme.app')->section('content');
    }

    // ── Toggle individual ─────────────────────────────────────────────────
    public function SyncPermiso($state, $permisoName)
    {
        if ($this->role === 'Elegir') {
            $this->emit('item-error', 'Selecciona un rol primero');
            return;
        }

        $rol = Role::find($this->role);

        if (filter_var($state, FILTER_VALIDATE_BOOLEAN)) {
            $rol->givePermissionTo($permisoName);
        } else {
            $rol->revokePermissionTo($permisoName);
        }
        // Sin emit — el toggle es inmediato y silencioso
    }

    // ── Sync/Revoke todo el grupo ─────────────────────────────────────────
    public function SyncGrupo($modulo, $activar)
    {
        if ($this->role === 'Elegir') {
            $this->emit('item-error', 'Selecciona un rol primero');
            return;
        }

        $rol = Role::find($this->role);
        $ids = Permission::where('name', 'like', $modulo . '_%')
            ->orWhere('name', $modulo)
            ->pluck('name')->toArray();

        if ($activar) {
            $rol->givePermissionTo($ids);
            $this->emit('item-added', "Grupo «{$modulo}» activado");
        } else {
            $rol->revokePermissionTo($ids);
            $this->emit('item-added', "Grupo «{$modulo}» desactivado");
        }
    }

    // ── Asignar todos los permisos ────────────────────────────────────────
    public function SyncAll()
    {
        if ($this->role === 'Elegir') {
            $this->emit('item-error', 'Selecciona un rol primero');
            return;
        }

        $rol = Role::find($this->role);
        $rol->syncPermissions(Permission::pluck('id')->toArray());
        $this->emit('item-added', "Todos los permisos asignados a «{$rol->name}»");
    }

    // ── Revocar todos ─────────────────────────────────────────────────────
    public function RemoveAll()
    {
        if ($this->role === 'Elegir') {
            $this->emit('item-error', 'Selecciona un rol primero');
            return;
        }

        $rol = Role::find($this->role);
        $rol->syncPermissions([]);
        $this->emit('item-added', "Todos los permisos revocados de «{$rol->name}»");
    }

    // ── Clonar permisos de un rol origen al rol seleccionado ──────────────
    public function ClonarPermisos()
    {
        if ($this->role === 'Elegir' || $this->roleClonar === 'Elegir') {
            $this->emit('item-error', 'Selecciona el rol destino y el rol origen');
            return;
        }

        if ($this->role === $this->roleClonar) {
            $this->emit('item-error', 'El rol origen y destino no pueden ser el mismo');
            return;
        }

        $origen  = Role::with('permissions')->find($this->roleClonar);
        $destino = Role::find($this->role);

        $destino->syncPermissions($origen->permissions->pluck('id')->toArray());

        $this->roleClonar = 'Elegir';
        $this->emit('close-modal-clonar');
        $this->emit('item-added', "Permisos de «{$origen->name}» clonados a «{$destino->name}» correctamente");
    }
}
