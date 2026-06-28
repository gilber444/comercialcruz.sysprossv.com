<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1) Crear permiso para ver costos en el reporte de utilidades detallado
        $permiso = Permission::firstOrCreate([
            'name' => 'Utilidad_Costo',
            'guard_name' => 'web',
        ]);

        // 2) Asignar a los roles que ya tienen el permiso equivalente del reporte sintetizado
        $rolesConPermiso = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'UtilidadSin_Costo');
        })->get();

        foreach ($rolesConPermiso as $role) {
            $role->givePermissionTo($permiso);
        }

        // 3) Registrar el feature de esta versión (sin marcar aún como producción)
        DB::table('features')->insert([
            'version'     => '1.0.5',
            'descripcion' => 'Mejoras reportes utilidades, PDF streaming, Excel, spinners y corrección costos POS',
            'activo'      => true,
            'produccion'  => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Permission::where('name', 'Utilidad_Costo')->where('guard_name', 'web')->delete();

        DB::table('features')
            ->where('version', '1.0.5')
            ->where('descripcion', 'Mejoras reportes utilidades, PDF streaming, Excel, spinners y corrección costos POS')
            ->delete();
    }
};
