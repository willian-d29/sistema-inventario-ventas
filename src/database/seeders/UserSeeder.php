<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        //  Admins
        for ($i = 1; $i <= 2; $i++) {
            User::updateOrCreate(
                ['email' => "admin{$i}@sistema.com"],
                [
                    'name'     => "Administrador {$i}",
                    'email'    => "admin{$i}@sistema.com",
                    'password' => Hash::make('password'),
                    'role'     => 'admin',
                    'photo'    => 'admin.jpg',
                    'address'  => $faker->address,
                    'phone'    => $faker->phoneNumber,
                ]
            );
        }

        // Vendedores
        for ($i = 1; $i <= 5; $i++) {
            User::updateOrCreate(
                ['email' => "vendedor{$i}@empresa.com"],
                [
                    'name'     => "Vendedor {$i}",
                    'email'    => "vendedor{$i}@empresa.com",
                    'password' => Hash::make('password'),
                    'role'     => 'vendedor',
                    'photo'    => 'vendedor.jpg',
                    'address'  => $faker->address,
                    'phone'    => $faker->phoneNumber,
                ]
            );
        }

        //  Clientes (muchos)
        for ($i = 1; $i <= 100; $i++) {
            $nombre = $faker->name;
            $correo = "cliente{$i}@correo.com";

            User::updateOrCreate(
                ['email' => $correo],
                [
                    'name'     => $nombre,
                    'email'    => $correo,
                    'password' => Hash::make('123456789'), // o algo genérico para pruebas
                    'role'     => 'cliente',
                    'photo'    => 'cliente.jpg',
                    'address'  => $faker->address,
                    'phone'    => $faker->phoneNumber,
                ]
            );
        }

        $this->command->info("Usuarios insertados: 2 admins, 5 vendedores, 100 clientes.");
    }
}
