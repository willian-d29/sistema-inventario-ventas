<?php

namespace Database\Seeders;

use App\Enums\Supplier\SupplierFieldsEnum;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = Http::get("https://api.escuelajs.co/api/v1/users");
        if ($response->successful()) {
            $suppliers = $response->object();

            foreach ($suppliers as $supplier) {
                // Download the image
                $imageContent = Http::get($supplier->avatar)->body();
                $imageName = basename($supplier->avatar);
                $imagePath = 'suppliers/' . $imageName;
                Storage::put($imagePath, $imageContent);

                $suppliersPayload = [
                    SupplierFieldsEnum::NAME->value       => fake()->name,
                    SupplierFieldsEnum::EMAIL->value      => fake()->email,
                    SupplierFieldsEnum::PHONE->value      => fake()->phoneNumber,
                    SupplierFieldsEnum::ADDRESS->value    => fake()->address,
                    SupplierFieldsEnum::SHOP_NAME->value  => fake()->company,
                    SupplierFieldsEnum::CREATED_AT->value => now(),
                    "updated_at"                          => now(),
                ];

                Supplier::query()->updateOrCreate(
                    [SupplierFieldsEnum::PHOTO->value => $imageName],
                    $suppliersPayload
                );
            }
        } else {
            $this->command->error('Failed to seed supplier data.');
        }
    }
}
