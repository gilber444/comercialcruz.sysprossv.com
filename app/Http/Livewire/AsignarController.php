<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\WithPagination;
use DB;

class AsignarController extends Component
{
    use WithPagination;

    public $role,
        $search,
        $old_permissions = [],
        $permisosSelected = [],
        $componentName;
    private $pagination = 10;

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function mount()
    {
        $this->role = 'Elegir';
        $this->componentName = 'Asignar Permisos';
    }

    public function render()
    {
        $permisos = Permission::select('name', 'id', DB::raw('0 as checked'))
            ->where('name', 'like', '%' . $this->search . '%') // Agregar filtro de búsqueda
            ->orderBy('name', 'asc')
            ->paginate($this->pagination);

        if ($this->role != 'Elegir') {
            $list = Permission::join('role_has_permissions as rp', 'rp.permission_id', 'permissions.id')
                ->where('role_id', $this->role)
                ->pluck('permissions.id')
                ->toArray();
            $this->old_permissions = $list;
        }

        if ($this->role != 'Elegir') {
            foreach ($permisos as $permiso) {
                $role = Role::find($this->role);
                $tienePermiso = $role->hasPermissionTo($permiso->name);
                if ($tienePermiso) {
                    $permiso->checked = 1;
                }
            }
        }

        $roles = Role::orderBy('name', 'asc')->get();
        if (auth()->user()->profile != 'Super') {
            $roles = $roles->filter(function ($role) {
                return $role->name != 'Super';
            });
        }

        return view('livewire.asignar.asignar', [
            'roles' => $roles,
            'permisos' => $permisos,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public $listeners = ['revokeall' => 'RemoveAll'];

    public function RemoveAll()
    {
        if ($this->role == 'Elegir') {
            $this->emit('item-error', 'Selecciona un rol vacio');
            return;
        }

        $role = Role::find($this->role);
        $role->syncPermissions([0]);
        $this->emit('item-added', "Se revocaron todos los permisos al rol $role->name");
    }

    public function SyncAll()
    {
        if ($this->role == 'Elegir') {
            $this->emit('item-error', 'Selecciona un rol vacio');
            return;
        }

        $role = Role::find($this->role);
        $permisos = Permission::pluck('id')->toArray();
        $role->syncPermissions($permisos);
        $this->emit('item-added', "Se sincronizaron todos los permisos al rol $role->name");
    }

    public function SyncPermiso($state, $permisoName)
    {
        if ($this->role != 'Elegir') {
            $roleName = Role::find($this->role);

            if ($state) {
                $roleName->givePermissionTo($permisoName);
                $this->emit('item-added', 'Permiso Asignado Correctamente');
            } else {
                $roleName->revokePermissionTo($permisoName);
                $this->emit('item-added', 'Permiso Revocado Correctamente');
            }
        } else {
            $this->emit('item-error', 'Elige un rol valido');
        }
    }
}
