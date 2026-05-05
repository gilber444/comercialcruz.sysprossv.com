<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedidasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('medidas')->insert([
            [
                'unidad' => 'UNIDAD',
                'simbolo' => 'U',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
