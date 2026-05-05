<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super = User::create([
            'name' => 'Admin User',
            'user' => 'admin',
            'phone' => '12345678',
            'email' => 'admin@example.com',
            'profile' => 'Super',
            'status'=>'ACTIVE',
            'password' => Hash::make('password'),
            'password2' => 'password2',
            'image' => 'default.png',
            'remember_token' => Str::random(10),
            'empresa' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $super->syncRoles('Super');
    }
}
