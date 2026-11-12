<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Monitor de las sincronizaciones por API (pantalla monitor-sync-api).
 * - api_sync_registros: cada peticion que recibe el VPS en api/sync/* (la
 *   anota el middleware RegistrarSyncApi).
 * - api_sync_latidos: ultimo resultado de cada comando de sync por API en cada
 *   caja, aunque no haya tenido nada que subir (una fila por sucursal y comando).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('api_sync_registros')) {
            Schema::create('api_sync_registros', function (Blueprint $t) {
                $t->id();
                $t->unsignedInteger('sucursal')->nullable();
                $t->string('servicio', 60);
                $t->string('tabla', 60)->nullable();
                $t->unsignedInteger('recibidas')->default(0);
                $t->unsignedInteger('guardados')->default(0);
                $t->unsignedInteger('errores')->default(0);
                $t->unsignedInteger('pendientes')->default(0);
                $t->unsignedSmallInteger('http')->default(200);
                $t->unsignedInteger('ms')->default(0);
                $t->text('mensaje')->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamp('created_at')->nullable()->index();
                $t->index(['sucursal', 'servicio', 'created_at']);
            });
        }

        if (!Schema::hasTable('api_sync_latidos')) {
            Schema::create('api_sync_latidos', function (Blueprint $t) {
                $t->id();
                $t->unsignedInteger('sucursal');
                $t->string('comando', 100);
                $t->boolean('ok')->default(true);
                $t->unsignedInteger('fallos_seguidos')->default(0);
                $t->text('resumen')->nullable();
                $t->string('version', 20)->nullable();
                $t->string('equipo', 100)->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamp('ultimo_at')->nullable();
                $t->timestamp('ultimo_ok_at')->nullable();
                $t->timestamps();
                $t->unique(['sucursal', 'comando']);
            });
        }

        // Permiso para la pantalla: a los roles que ya administran catalogos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $permiso = Permission::firstOrCreate(['name' => 'MonitorSyncApi_Index', 'guard_name' => 'web']);
        foreach (Role::whereHas('permissions', fn ($q) => $q->where('name', 'AdminCatalagos_Index'))->get() as $rol) {
            $rol->givePermissionTo($permiso);
        }
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Feature en prueba: el owner lo libera poniendo produccion = 1
        DB::table('features')->insertOrIgnore([
            'version' => '1.0.58',
            'codigo' => 'monitor_sync_api',
            'descripcion' => 'Pantalla de monitoreo de las sincronizaciones por API',
            'activo' => true,
            'produccion' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_registros');
        Schema::dropIfExists('api_sync_latidos');
        Permission::where('name', 'MonitorSyncApi_Index')->where('guard_name', 'web')->delete();
        DB::table('features')->where('codigo', 'monitor_sync_api')->delete();
    }
};
