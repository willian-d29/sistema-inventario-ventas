<?php

namespace Database\Seeders;

use App\Enums\Category\CategoryFieldsEnum;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public static function minimarketCategories(): array
    {
        return [
            'Abarrotes',
            'Bebidas',
            'Lacteos',
            'Snacks y golosinas',
            'Panaderia',
            'Limpieza del hogar',
            'Cuidado personal',
            'Congelados y refrigerados',
            'Bebes',
            'Mascotas',
            'Frutas y verduras',
            'Farmacia basica',
        ];
    }

    public function run(): void
    {
        foreach (self::minimarketCategories() as $category) {
            Category::query()->updateOrCreate([
                CategoryFieldsEnum::NAME->value => $category,
            ]);
        }
    }
}
