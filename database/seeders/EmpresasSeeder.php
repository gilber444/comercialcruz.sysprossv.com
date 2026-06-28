<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresa = [
            'empresa' => 'Empresa Ejemplo',
            'razon' => 'Razón Social Ejemplo',
            'direccion' => 'Dirección Ejemplo',
            'telefono' => '1234567890',
            'responsable' => 'Responsable Ejemplo',
            'registro' => 'Registro Ejemplo',
            'giro' => 'Giro Ejemplo',
            'nit' => 'NIT Ejemplo',
            'tipoContribuyente' => 'Tipo Ejemplo',
            'actividad' => 1, // Asegúrate de que el ID 1 exista en la tabla actividad_economicas
            'desActividad' => 'Descripción de Actividad Ejemplo',
            'correo' => 'correo@example.com',
            'apiPassword' => 'password123',
            'departamento' => 1, // Asegúrate de que el ID 1 exista en la tabla departamentos
            'municipio' => 1, // Asegúrate de que el ID 1 exista en la tabla municipios
            'distrito' => 1, // Asegúrate de que el ID 1 exista en la tabla distritos
            'plan' => 1,
            'image' => 'imagen.jpg',
        ];

        DB::table('empresas')->insert($empresa);
    }
}
