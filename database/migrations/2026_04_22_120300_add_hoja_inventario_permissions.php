<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $print = Permission::firstOrCreate([
            'name' => 'hojaInventario_Print',
            'guard_name' => 'web',
        ]);

        $validar = Permission::firstOrCreate([
            'name' => 'hojaInventario_Validar',
            'guard_name' => 'web',
        ]);

        foreach (['Super', 'Administrador', 'GERENTE', 'BODEGA', 'Cajeros'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo([$print, $validar]);
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['hojaInventario_Print', 'hojaInventario_Validar'])->delete();
    }
};
