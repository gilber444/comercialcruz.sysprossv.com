<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('precios')->insert([
            [
                'producto' => 1, // ID del producto ABONO A CREDITO
                'familia' => 1,  // ID de la familia Sin Familia
                'medida' => 1,   // ID de la medida UNIDAD
                'codebar' => 'ABONO001',
                'cantidad' => 1.00,
                'presentacion' => 1.0000,
                'costosiva' => 1.0000,   // Costo sin IVA
                'costociva' => 1.000,  // Costo con IVA
                'utilidad' => 1.000,    // Utilidad
                'pventasiva' => 1.0000, // Precio de venta con IVA
                'pvventa' => 1.000,    // Precio de venta sin IVA
                'escala' => 'No',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'producto' => 2, // ID del producto PAGO DE CREDITO
                'familia' => 1,  // ID de la familia Sin Familia
                'medida' => 1,   // ID de la medida UNIDAD
                'codebar' => 'PAGO001',
                'cantidad' => 1.00,
                'presentacion' => 1.0000,
                'costosiva' => 1.0000,   // Costo sin IVA
                'costociva' => 1.0000,  // Costo con IVA
                'utilidad' => 3.0000,    // Utilidad
                'pventasiva' => 1.0000, // Precio de venta con IVA
                'pvventa' => 1.0000,    // Precio de venta sin IVA
                'escala' => 'No',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
