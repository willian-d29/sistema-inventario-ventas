<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Vendedor Juan',
            'email' => 'vendedor@empresa.com',
            'password' => Hash::make('password'),
            'role' => 'vendedor',
        ]);

        User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@correo.com',
            'password' => Hash::make('password'),
            'role' => 'cliente',
        ]);
    }
}
