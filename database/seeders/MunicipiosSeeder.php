<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipios = [
            ['codigo' => '1', 'municipio' => 'AHUACHAPAN NORTE', 'departamento' => 1, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'AHUACHAPAN CENTRO', 'departamento' => 1, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'AHUACHAPAN SUR', 'departamento' => 1, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'CABAÑAS ESTE', 'departamento' => 9, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'CABAÑAS OESTE', 'departamento' => 9, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'CHALATENANGO NORTE', 'departamento' => 4, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'CHALATENANGO CENTRO', 'departamento' => 4, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'CHALATENANGO SUR', 'departamento' => 4, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'CUSCATLAN NORTE', 'departamento' => 7, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'CUSCATLAN SUR', 'departamento' => 7, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'LA LIBERTAD NORTE', 'departamento' => 5, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'LA LIBERTAD CENTRO', 'departamento' => 5, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'LA LIBERTAD OESTE', 'departamento' => 5, 'status' => 'Activo'],
            ['codigo' => '4', 'municipio' => 'LA LIBERTAD ESTE', 'departamento' => 5, 'status' => 'Activo'],
            ['codigo' => '5', 'municipio' => 'LA LIBERTAD COSTA', 'departamento' => 5, 'status' => 'Activo'],
            ['codigo' => '6', 'municipio' => 'LA LIBERTAD SUR', 'departamento' => 5, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'LA PAZ OESTE', 'departamento' => 8, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'LA PAZ CENTRO', 'departamento' => 8, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'LA PAZ ESTE', 'departamento' => 8, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'LA UNION NORTE', 'departamento' => 14, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'LA UNION SUR', 'departamento' => 14, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'MORAZAN NORTE', 'departamento' => 13, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'MORAZAN SUR', 'departamento' => 13, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'SAN MIGUEL NORTE', 'departamento' => 12, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'SAN MIGUEL CENTRO', 'departamento' => 12, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'SAN MIGUEL OESTE', 'departamento' => 12, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'SAN SALVADOR NORTE', 'departamento' => 6, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'SAN SALVADOR OESTE', 'departamento' => 6, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'SAN SALVADOR ESTE', 'departamento' => 6, 'status' => 'Activo'],
            ['codigo' => '4', 'municipio' => 'SAN SALVADOR CENTRO', 'departamento' => 6, 'status' => 'Activo'],
            ['codigo' => '5', 'municipio' => 'SAN SALVADOR SUR', 'departamento' => 6, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'SAN VICENTE NORTE', 'departamento' => 10, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'SAN VICENTE SUR', 'departamento' => 10, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'SANTA ANA NORTE', 'departamento' => 2, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'SANTA ANA CENTRO', 'departamento' => 2, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'SANTA ANA ESTE', 'departamento' => 2, 'status' => 'Activo'],
            ['codigo' => '4', 'municipio' => 'SANTA ANA OESTE', 'departamento' => 2, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'SONSONATE NORTE', 'departamento' => 3, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'SONSONATE CENTRO', 'departamento' => 3, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'SONSONATE ESTE', 'departamento' => 3, 'status' => 'Activo'],
            ['codigo' => '4', 'municipio' => 'SONSONATE OESTE', 'departamento' => 3, 'status' => 'Activo'],
            ['codigo' => '1', 'municipio' => 'USULUTAN NORTE', 'departamento' => 11, 'status' => 'Activo'],
            ['codigo' => '2', 'municipio' => 'USULUTAN ESTE', 'departamento' => 11, 'status' => 'Activo'],
            ['codigo' => '3', 'municipio' => 'USULUTAN OESTE', 'departamento' => 11, 'status' => 'Activo'],
        ];

        foreach ($municipios as $municipio) {
            DB::table('municipios')->updateOrInsert(
                ['codigo' => $municipio['codigo'], 'departamento' => $municipio['departamento']],
                ['municipio' => $municipio['municipio'], 'status' => $municipio['status']]
            );
        }
    }
}
