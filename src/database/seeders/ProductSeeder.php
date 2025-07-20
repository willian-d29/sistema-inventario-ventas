<?php

namespace Database\Seeders;

use App\Enums\Product\ProductFieldsEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all();
        $unitTypes = UnitType::all();
        $categories = Category::all();

        $totalMax = 300;   // Limitar a 300 productos
        $totalInserted = 0;

        foreach ($categories as $category) {
            $slug = Str::slug($category->name);

            for ($skip = 0; $skip <= 90; $skip += 30) {
                $response = Http::get("https://dummyjson.com/products/category/{$slug}?limit=30&skip={$skip}");
                if (!$response->successful()) break;

                $products = $response->object()->products ?? [];

                if (empty($products)) break;

                foreach ($products as $product) {
                    if ($totalInserted >= $totalMax) break 2;

                    try {
                        $imageUrl = $product->images[0] ?? null;
                        if (!$imageUrl) continue;

                        $imageName = basename($imageUrl);
                        $imagePath = 'products/' . $imageName;
                        Storage::put($imagePath, Http::retry(2)->get($imageUrl)->body());

                        Product::create([
                            ProductFieldsEnum::CATEGORY_ID->value    => $category->id,
                            ProductFieldsEnum::SUPPLIER_ID->value    => $suppliers->random()->id,
                            ProductFieldsEnum::NAME->value           => $product->title,
                            ProductFieldsEnum::PRODUCT_NUMBER->value => 'P-' . strtoupper(Str::random(6)),
                            ProductFieldsEnum::DESCRIPTION->value    => $product->description,
                            ProductFieldsEnum::PRODUCT_CODE->value   => strtoupper(Str::random(3)),
                            ProductFieldsEnum::ROOT->value           => strtoupper(Str::random(3)),
                            ProductFieldsEnum::BUYING_PRICE->value   => $product->price,
                            ProductFieldsEnum::SELLING_PRICE->value  => $product->price + rand(10, 100),
                            ProductFieldsEnum::BUYING_DATE->value    => now()->subDays(rand(20, 500)),
                            ProductFieldsEnum::UNIT_TYPE_ID->value   => $unitTypes->random()->id,
                            ProductFieldsEnum::QUANTITY->value       => $product->stock,
                            ProductFieldsEnum::PHOTO->value          => $imageName,
                            ProductFieldsEnum::STATUS->value         => ProductStatusEnum::ACTIVE->value,
                            ProductFieldsEnum::CREATED_AT->value     => now(),
                            'updated_at'                              => now(),
                        ]);

                        $totalInserted++;
                    } catch (\Throwable $e) {
                        $this->command->warn("❌ Error con '{$product->title}': " . $e->getMessage());
                    }
                }
            }
        }

        $this->command->info("✅ Se insertaron $totalInserted productos reales.");
    }
}
