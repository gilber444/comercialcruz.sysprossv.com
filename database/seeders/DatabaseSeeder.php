<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ActividadEconomicaSeeder::class,
            DepartamentosSeeder::class,
            MunicipiosSeeder::class,
            DistritosSeeder::class,
            EmpresasSeeder::class,
            PermissionsSeeder::class,//Primero, crea los permisos
            RolesSeeder::class,//Luego, crea los roles y asigna permisos
            UsersSeeder::class,//Finalmente, crea los usuarios y asigna roles

            MedidasSeeder::class,
            UnidadMedidasSeeder::class,
            CategoriasSeeder::class,
            FamiliasSeeder::class,
            ProductosSeeder::class,
            PreciosSeeder::class,
        ]);

    }
}
