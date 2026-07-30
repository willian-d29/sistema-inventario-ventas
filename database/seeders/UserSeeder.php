<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Willan Administrador',
            'email' => 'willan.a@laratory.pe',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Maria Cajera',
            'email' => 'maria.c@laratory.pe',
            'password' => Hash::make('password'),
            'role' => 'cajero',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Luis Cajero',
            'email' => 'luis.c@laratory.pe',
            'password' => Hash::make('password'),
            'role' => 'cajero',
            'email_verified_at' => now(),
        ]);
    }
}
