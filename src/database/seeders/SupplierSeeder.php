<?php

namespace Database\Seeders;

use App\Enums\Supplier\SupplierFieldsEnum;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Faker\Factory as Faker;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        $imageFolder = public_path('assets/img/seeder/suppliers');
        $files = File::exists($imageFolder) ? File::files($imageFolder) : [];

        $maxSuppliers = 20; // ajustable
        $createdCount = 0;

        if (Supplier::count() >= $maxSuppliers) {
            $this->command->warn(" Ya hay más de $maxSuppliers proveedores. Seeder cancelado.");
            return;
        }

        // Si hay imágenes: un proveedor por imagen
        foreach ($files as $file) {
            $imageName = $file->getBasename();
            $imagePath = 'suppliers/' . $imageName;

            Storage::put($imagePath, $file->getContents());

            $suppliersPayload = [
                SupplierFieldsEnum::NAME->value       => $faker->name,
                SupplierFieldsEnum::EMAIL->value      => $faker->unique()->safeEmail,
                SupplierFieldsEnum::PHONE->value      => $faker->phoneNumber,
                SupplierFieldsEnum::ADDRESS->value    => $faker->address,
                SupplierFieldsEnum::SHOP_NAME->value  => $faker->company,
                SupplierFieldsEnum::PHOTO->value      => $imageName,
                SupplierFieldsEnum::CREATED_AT->value => now(),
                "updated_at"                          => now(),
            ];

            Supplier::updateOrCreate(
                [SupplierFieldsEnum::PHOTO->value => $imageName],
                $suppliersPayload
            );

            $createdCount++;
        }

        // Si hay menos imágenes que proveedores deseados
        $faltan = $maxSuppliers - $createdCount;
        for ($i = 0; $i < $faltan; $i++) {
            $suppliersPayload = [
                SupplierFieldsEnum::NAME->value       => $faker->name,
                SupplierFieldsEnum::EMAIL->value      => $faker->unique()->safeEmail,
                SupplierFieldsEnum::PHONE->value      => $faker->phoneNumber,
                SupplierFieldsEnum::ADDRESS->value    => $faker->address,
                SupplierFieldsEnum::SHOP_NAME->value  => $faker->company,
                SupplierFieldsEnum::PHOTO->value      => 'default.jpg', // imagen genérica
                SupplierFieldsEnum::CREATED_AT->value => now(),
                "updated_at"                          => now(),
            ];

            Supplier::create($suppliersPayload);
            $createdCount++;
        }

        $this->command->info(" Se insertaron $createdCount proveedores (con y sin imagen).");
    }
}
