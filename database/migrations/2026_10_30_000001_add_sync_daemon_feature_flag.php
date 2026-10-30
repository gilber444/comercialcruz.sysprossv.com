<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Flag que lee cada PC en el VPS. Para activar el daemon en todas las
        // sucursales: produccion=1 en 'sync_daemon'. Para una sola sucursal:
        // crear 'sync_daemon_suc_{ID}' con activo=1 y produccion=1.
        if (DB::table('features')->where('codigo', 'sync_daemon')->exists()) {
            return;
        }

        $now = now();

        DB::table('features')->insert([
            'version'     => '1.0.23',
            'codigo'      => 'sync_daemon',
            'descripcion' => 'Daemon de sincronización LOCAL <-> VPS (sync:daemon): un solo proceso por PC cada pocos segundos',
            'activo'      => true,
            'produccion'  => false,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('features')->where('codigo', 'sync_daemon')->delete();
    }
};
