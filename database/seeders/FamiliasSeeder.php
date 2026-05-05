<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FamiliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('familias')->insert([
            [
                'familia' => 'Sin Familia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
