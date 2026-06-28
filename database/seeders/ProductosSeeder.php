<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('productos')->insert([
            [
                'codebar1' => null,
                'codebar2' => null,
                'codebar3' => 'ABONO001',
                'codealternativo' => null,
                'nombreProducto' => 'ABONO A CREDITO',
                'categoria' => 1, // ID de la categoría 'CREDITOS'
                'familia' => 1,   // ID de la familia 'Sin Familia'
                'medida' => 1,    // ID de la medida 'UNIDAD'
                'proveedor1' => null,
                'proveedor2' => null,
                'proveedor3' => null,
                'activo' => 'SI',
                'exento' => 'NO',
                'caja' => null,
                'fraccionario' => null,
                'medidamh' => 55,  // ID de la medida 'UNIDAD'
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codebar1' => null,
                'codebar2' => null,
                'codebar3' => 'PAGO001',
                'codealternativo' => null,
                'nombreProducto' => 'PAGO DE CREDITO',
                'categoria' => 1, // ID de la categoría 'CREDITOS'
                'familia' => 1,   // ID de la familia 'Sin Familia'
                'medida' => 1,    // ID de la medida 'UNIDAD'
                'proveedor1' => null,
                'proveedor2' => null,
                'proveedor3' => null,
                'activo' => 'SI',
                'exento' => 'NO',
                'caja' => null,
                'fraccionario' => null,
                'medidamh' => 55,  // ID de la medida 'UNIDAD'
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
