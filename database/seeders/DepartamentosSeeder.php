<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departamentos = [
            ['codigo' => '01', 'departamento' => 'Ahuachapán', 'status' => 'Desactivado'],
            ['codigo' => '02', 'departamento' => 'Santa Ana', 'status' => 'Desactivado'],
            ['codigo' => '03', 'departamento' => 'Sonsonate', 'status' => 'Desactivado'],
            ['codigo' => '04', 'departamento' => 'Chalatenango', 'status' => 'Desactivado'],
            ['codigo' => '05', 'departamento' => 'La Libertad', 'status' => 'Desactivado'],
            ['codigo' => '06', 'departamento' => 'San Salvador', 'status' => 'Desactivado'],
            ['codigo' => '07', 'departamento' => 'Cuscatlán', 'status' => 'Desactivado'],
            ['codigo' => '08', 'departamento' => 'La Paz', 'status' => 'Desactivado'],
            ['codigo' => '09', 'departamento' => 'Cabañas', 'status' => 'Desactivado'],
            ['codigo' => '10', 'departamento' => 'San Vicente', 'status' => 'Desactivado'],
            ['codigo' => '11', 'departamento' => 'Usulután', 'status' => 'Desactivado'],
            ['codigo' => '12', 'departamento' => 'San Miguel', 'status' => 'Desactivado'],
            ['codigo' => '13', 'departamento' => 'Morazán', 'status' => 'Desactivado'],
            ['codigo' => '14', 'departamento' => 'La Unión', 'status' => 'Activo'],
        ];

        foreach ($departamentos as $departamento) {
            DB::table('departamentos')->updateOrInsert(
                ['codigo' => $departamento['codigo']],
                ['departamento' => $departamento['departamento'], 'status' => $departamento['status']]
            );
        }
    }
}
