<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('features')->insertOrIgnore([
            [
                'version'     => '1.0.5',
                'codigo'      => 'reporte_utilidad_detallado',
                'descripcion' => 'Reporte de Utilidades detallado mejorado (PDF streaming, Excel, spinners y cálculos corregidos)',
                'activo'      => true,
                'produccion'  => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'version'     => '1.0.5',
                'codigo'      => 'reporte_utilidad_sintetizado',
                'descripcion' => 'Reporte de Utilidades Sintetizado mejorado (PDF streaming, Excel, spinners y cálculos corregidos)',
                'activo'      => true,
                'produccion'  => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'version'     => '1.0.5',
                'codigo'      => 'pos_utilidad_costos',
                'descripcion' => 'Corrección de cálculo de costo_total y utilidad en ventas_detalles desde el POS',
                'activo'      => true,
                'produccion'  => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('features')
            ->whereIn('codigo', [
                'reporte_utilidad_detallado',
                'reporte_utilidad_sintetizado',
                'pos_utilidad_costos',
            ])
            ->delete();
    }
};
