<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Admin Principal',
        'password' => Hash::make('password'),
        'role' => 'admin',
    ]
);

User::firstOrCreate(
    ['email' => 'vendedor@empresa.com'],
    [
        'name' => 'Vendedor Juan',
        'password' => Hash::make('password'),
        'role' => 'vendedor',
    ]
);

User::firstOrCreate(
    ['email' => 'cliente@correo.com'],
    [
        'name' => 'Cliente Demo',
        'password' => Hash::make('password'),
        'role' => 'cliente',
    ]
);

    }
}
