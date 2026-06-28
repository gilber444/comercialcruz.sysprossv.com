<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadMedidasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidadMedidas = [
            ['codigo' => '01', 'valor' => 'METRO'],
            ['codigo' => '02', 'valor' => 'YARDA'],
            ['codigo' => '03', 'valor' => 'VARA'],
            ['codigo' => '04', 'valor' => 'PIE'],
            ['codigo' => '05', 'valor' => 'PULGADA'],
            ['codigo' => '06', 'valor' => 'MILIMETRO'],
            ['codigo' => '08', 'valor' => 'MILLA CUADRADA'],
            ['codigo' => '09', 'valor' => 'KILOMETRO CUADRADO'],
            ['codigo' => '10', 'valor' => 'HECTAREA'],
            ['codigo' => '11', 'valor' => 'MANZANA'],
            ['codigo' => '12', 'valor' => 'ACRE'],
            ['codigo' => '13', 'valor' => 'METRO CUADRADO'],
            ['codigo' => '14', 'valor' => 'YARDA CUADRADA'],
            ['codigo' => '15', 'valor' => 'VARA CUADRADA'],
            ['codigo' => '16', 'valor' => 'PIE CUADRADO'],
            ['codigo' => '17', 'valor' => 'PULGADA CUADRADA'],
            ['codigo' => '18', 'valor' => 'METRO CUBICO'],
            ['codigo' => '19', 'valor' => 'YARDA CUBICA'],
            ['codigo' => '20', 'valor' => 'BARRIL'],
            ['codigo' => '21', 'valor' => 'PIE CUBICO'],
            ['codigo' => '22', 'valor' => 'GALON'],
            ['codigo' => '23', 'valor' => 'LITRO'],
            ['codigo' => '24', 'valor' => 'BOTELLA'],
            ['codigo' => '25', 'valor' => 'PULGADA CUBICA'],
            ['codigo' => '26', 'valor' => 'MILILITRO'],
            ['codigo' => '27', 'valor' => 'ONZA FLUIDA'],
            ['codigo' => '29', 'valor' => 'TONELADA METRICA'],
            ['codigo' => '30', 'valor' => 'TONELADA'],
            ['codigo' => '31', 'valor' => 'QUINTAL METRICO'],
            ['codigo' => '32', 'valor' => 'QUINTAL'],
            ['codigo' => '33', 'valor' => 'ARROBA'],
            ['codigo' => '34', 'valor' => 'KILOGRAMO'],
            ['codigo' => '35', 'valor' => 'LIBRA TROY'],
            ['codigo' => '36', 'valor' => 'LIBRA'],
            ['codigo' => '37', 'valor' => 'ONZA TROY'],
            ['codigo' => '38', 'valor' => 'ONZA'],
            ['codigo' => '39', 'valor' => 'GRAMO'],
            ['codigo' => '40', 'valor' => 'MILIGRAMO'],
            ['codigo' => '42', 'valor' => 'MEGAWATT'],
            ['codigo' => '43', 'valor' => 'KILOWATT'],
            ['codigo' => '44', 'valor' => 'WATT'],
            ['codigo' => '45', 'valor' => 'MEGAVOLTIO-AMPERIO'],
            ['codigo' => '46', 'valor' => 'KILOVOLTIO-AMPERIO'],
            ['codigo' => '47', 'valor' => 'VOLTIO-AMPERIO'],
            ['codigo' => '49', 'valor' => 'GIGAWATT-HORA'],
            ['codigo' => '50', 'valor' => 'MEGAWATT-HORA'],
            ['codigo' => '51', 'valor' => 'KILOWATT-HORA'],
            ['codigo' => '52', 'valor' => 'WATT-HORA'],
            ['codigo' => '53', 'valor' => 'KILOVOLTIO'],
            ['codigo' => '54', 'valor' => 'VOLTIO'],
            ['codigo' => '55', 'valor' => 'MILLAR'],
            ['codigo' => '56', 'valor' => 'MEDIO MILLAR'],
            ['codigo' => '57', 'valor' => 'CIENTO'],
            ['codigo' => '58', 'valor' => 'DOCENA'],
            ['codigo' => '59', 'valor' => 'UNIDAD'],
            ['codigo' => '99', 'valor' => 'OTRA'],
        ];

        DB::table('unidad_medidas')->insert($unidadMedidas);
    }
}
