<?php

namespace Database\Seeders;

use App\Enums\Product\ProductFieldsEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\UnitType\UnitTypeFieldsEnum;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\UnitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private const TARGET_PRODUCTS = 500;

    public function run(): void
    {
        DB::transaction(function () {
            $this->purgeCurrentCatalog();

            $categories = $this->createCategories();
            $unitTypes = $this->createUnitTypes();
            $supplierIds = Supplier::query()->pluck('id')->values();

            $this->storeCatalogImages();

            $products = [];
            $counter = 1;

            foreach ($this->families() as $family) {
                foreach ($family['brands'] as $brandIndex => $brand) {
                    foreach ($family['sizes'] as $sizeIndex => $size) {
                        if (count($products) >= self::TARGET_PRODUCTS) {
                            break 3;
                        }

                        $sellingPrice = $this->sellingPrice($family['base_price'], $size, $brandIndex, $sizeIndex);
                        $buyingPrice = round($sellingPrice * 0.72, 2);
                        $stock = $this->stockFor($counter, $family['category']);
                        $name = "{$family['name']} {$brand} {$size}";

                        $products[] = [
                            ProductFieldsEnum::CATEGORY_ID->value => $categories[$family['category']],
                            ProductFieldsEnum::SUPPLIER_ID->value => $supplierIds->isNotEmpty() ? $supplierIds[($counter - 1) % $supplierIds->count()] : null,
                            ProductFieldsEnum::NAME->value => $name,
                            ProductFieldsEnum::PRODUCT_NUMBER->value => 'P-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
                            ProductFieldsEnum::DESCRIPTION->value => $this->description($family, $brand, $size),
                            ProductFieldsEnum::PRODUCT_CODE->value => 'SKU-'.Str::upper(Str::slug($family['category'], '')).'-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
                            ProductFieldsEnum::BARCODE->value => '775'.str_pad((string) (1000000000 + $counter), 10, '0', STR_PAD_LEFT),
                            ProductFieldsEnum::ROOT->value => Str::slug($family['name']),
                            ProductFieldsEnum::BUYING_PRICE->value => $buyingPrice,
                            ProductFieldsEnum::SELLING_PRICE->value => $sellingPrice,
                            ProductFieldsEnum::BUYING_DATE->value => now()->subDays(($counter % 45) + 1),
                            ProductFieldsEnum::UNIT_TYPE_ID->value => $unitTypes[$family['unit']],
                            ProductFieldsEnum::QUANTITY->value => $stock,
                            ProductFieldsEnum::PHOTO->value => $this->realProductImageUrl($family, $brand, $counter),
                            ProductFieldsEnum::STATUS->value => ProductStatusEnum::ACTIVE->value,
                            ProductFieldsEnum::CREATED_AT->value => now(),
                            'updated_at' => now(),
                        ];

                        $counter++;
                    }
                }
            }

            Product::query()->insert($products);
        });
    }

    private function purgeCurrentCatalog(): void
    {
        Cart::query()->delete();

        if (class_exists(StockMovement::class) && Schema::hasTable('stock_movements')) {
            StockMovement::query()->delete();
        }

        Product::query()->delete();

        if (Schema::hasTable('categories')) {
            Category::query()->delete();
        }

        if (Schema::hasTable('unit_types')) {
            UnitType::query()->delete();
        }
    }

    private function createCategories(): array
    {
        $categories = [];

        foreach (CategorySeeder::minimarketCategories() as $name) {
            $category = Category::query()->create(['name' => $name]);
            $categories[$name] = $category->id;
        }

        return $categories;
    }

    private function createUnitTypes(): array
    {
        $unitTypes = [];

        foreach (UnitTypeSeeder::minimarketUnitTypes() as $symbol => $name) {
            $unitType = UnitType::query()->create([
                UnitTypeFieldsEnum::NAME->value => $name,
                UnitTypeFieldsEnum::SYMBOL->value => $symbol,
            ]);

            $unitTypes[$symbol] = $unitType->id;
        }

        return $unitTypes;
    }

    private function storeCatalogImages(): void
    {
        foreach ($this->categoryVisuals() as $category => $visual) {
            Storage::disk('public')->put(
                'products/'.$this->categoryImageName($category),
                $this->categorySvg($category, $visual)
            );
        }
    }

    private function realProductImageUrl(array $family, string $brand, int $counter): string
    {
        $keywords = $this->imageKeywords($family, $brand);

        return "https://loremflickr.com/640/480/{$keywords}?lock={$counter}";
    }

    private function imageKeywords(array $family, string $brand): string
    {
        $categoryKeywords = [
            'Abarrotes' => 'grocery,food,supermarket',
            'Bebidas' => 'drink,bottle,beverage',
            'Lacteos' => 'milk,yogurt,dairy',
            'Snacks y golosinas' => 'snack,candy,chocolate',
            'Panaderia' => 'bread,bakery,toast',
            'Limpieza del hogar' => 'cleaning,detergent,household',
            'Cuidado personal' => 'personal-care,toiletries,soap',
            'Congelados y refrigerados' => 'frozen-food,sausage,refrigerated',
            'Bebes' => 'baby,diapers,wipes',
            'Mascotas' => 'pet-food,dog-food,cat-food',
            'Frutas y verduras' => 'fruit,vegetables,produce',
            'Farmacia basica' => 'pharmacy,first-aid,medicine',
        ];

        $familyKeyword = Str::slug($family['name'], ',');
        $brandKeyword = Str::slug($brand, ',');
        $categoryKeyword = $categoryKeywords[$family['category']] ?? 'supermarket,product';

        return "{$familyKeyword},{$brandKeyword},{$categoryKeyword}";
    }

    private function categoryImageName(string $category): string
    {
        return 'catalog-'.Str::slug($category).'.svg';
    }

    private function categorySvg(string $category, array $visual): string
    {
        $title = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
        $letter = htmlspecialchars($visual['letter'], ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480" role="img" aria-label="{$title}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$visual['from']}"/>
      <stop offset="100%" stop-color="{$visual['to']}"/>
    </linearGradient>
  </defs>
  <rect width="640" height="480" rx="40" fill="url(#bg)"/>
  <circle cx="510" cy="92" r="86" fill="rgba(255,255,255,.22)"/>
  <circle cx="94" cy="390" r="112" fill="rgba(255,255,255,.16)"/>
  <rect x="88" y="92" width="464" height="276" rx="34" fill="rgba(255,255,255,.88)"/>
  <rect x="132" y="280" width="376" height="34" rx="17" fill="{$visual['from']}" opacity=".18"/>
  <text x="320" y="232" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="126" font-weight="900" fill="{$visual['accent']}">{$letter}</text>
  <text x="320" y="322" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="800" fill="#172033">{$title}</text>
</svg>
SVG;
    }

    private function categoryVisuals(): array
    {
        return [
            'Abarrotes' => ['letter' => 'AB', 'from' => '#f59e0b', 'to' => '#16a34a', 'accent' => '#b45309'],
            'Bebidas' => ['letter' => 'BE', 'from' => '#0ea5e9', 'to' => '#2563eb', 'accent' => '#0369a1'],
            'Lacteos' => ['letter' => 'LA', 'from' => '#38bdf8', 'to' => '#f8fafc', 'accent' => '#0f766e'],
            'Snacks y golosinas' => ['letter' => 'SN', 'from' => '#ec4899', 'to' => '#f97316', 'accent' => '#be123c'],
            'Panaderia' => ['letter' => 'PA', 'from' => '#d97706', 'to' => '#fde68a', 'accent' => '#92400e'],
            'Limpieza del hogar' => ['letter' => 'LI', 'from' => '#14b8a6', 'to' => '#22c55e', 'accent' => '#0f766e'],
            'Cuidado personal' => ['letter' => 'CP', 'from' => '#8b5cf6', 'to' => '#06b6d4', 'accent' => '#6d28d9'],
            'Congelados y refrigerados' => ['letter' => 'FR', 'from' => '#60a5fa', 'to' => '#a7f3d0', 'accent' => '#1d4ed8'],
            'Bebes' => ['letter' => 'BB', 'from' => '#f9a8d4', 'to' => '#93c5fd', 'accent' => '#be185d'],
            'Mascotas' => ['letter' => 'MA', 'from' => '#84cc16', 'to' => '#facc15', 'accent' => '#4d7c0f'],
            'Frutas y verduras' => ['letter' => 'FV', 'from' => '#22c55e', 'to' => '#fb7185', 'accent' => '#15803d'],
            'Farmacia basica' => ['letter' => 'FB', 'from' => '#ef4444', 'to' => '#f8fafc', 'accent' => '#b91c1c'],
        ];
    }

    private function description(array $family, string $brand, string $size): string
    {
        return "{$family['description']} Presentacion {$size} de la marca {$brand}. Producto pensado para venta rapida, reposicion diaria y control de stock en minimarket.";
    }

    private function sellingPrice(float $basePrice, string $size, int $brandIndex, int $sizeIndex): float
    {
        $multiplier = match (true) {
            str_contains($size, '5 kg') => 4.65,
            str_contains($size, '3 kg') => 2.95,
            str_contains($size, '2 kg') => 1.95,
            str_contains($size, '1 kg') => 1.00,
            str_contains($size, '750 g') => 0.82,
            str_contains($size, '500 g') => 0.58,
            str_contains($size, '250 g') => 0.34,
            str_contains($size, '3 L') => 2.75,
            str_contains($size, '2.5 L') => 2.25,
            str_contains($size, '2 L') => 1.90,
            str_contains($size, '1.5 L') => 1.45,
            str_contains($size, '1 L') => 1.00,
            str_contains($size, '625 ml') => 0.72,
            str_contains($size, '500 ml') => 0.58,
            str_contains($size, '350 ml') => 0.48,
            str_contains($size, '250 ml') => 0.36,
            str_contains($size, 'pack x6') => 5.60,
            str_contains($size, 'pack x4') => 3.75,
            str_contains($size, 'docena') => 10.80,
            str_contains($size, '6 und') => 5.25,
            str_contains($size, '4 und') => 3.50,
            str_contains($size, '2 und') => 1.95,
            default => 1.00,
        };

        return round(($basePrice * $multiplier) + ($brandIndex * 0.35) + ($sizeIndex * 0.10), 2);
    }

    private function stockFor(int $counter, string $category): int
    {
        $base = match ($category) {
            'Bebidas', 'Snacks y golosinas', 'Abarrotes' => 55,
            'Lacteos', 'Congelados y refrigerados', 'Panaderia' => 28,
            'Frutas y verduras' => 18,
            default => 34,
        };

        return $base + (($counter * 7) % 75);
    }

    private function families(): array
    {
        return [
            ['category' => 'Abarrotes', 'unit' => 'kg', 'name' => 'Arroz extra', 'brands' => ['Costeno', 'Paisana', 'Faraon', 'Valle Norte'], 'sizes' => ['750 g', '1 kg', '5 kg'], 'base_price' => 4.20, 'description' => 'Arroz de grano entero para preparaciones familiares y menu diario.'],
            ['category' => 'Abarrotes', 'unit' => 'kg', 'name' => 'Azucar rubia', 'brands' => ['Cartavio', 'Paramonga', 'Dulce Norte'], 'sizes' => ['500 g', '1 kg', '5 kg'], 'base_price' => 3.80, 'description' => 'Azucar rubia granulada para bebidas, postres y cocina diaria.'],
            ['category' => 'Abarrotes', 'unit' => 'l', 'name' => 'Aceite vegetal', 'brands' => ['Primor', 'Cocinero', 'Sao', 'Ideal'], 'sizes' => ['500 ml', '1 L', '3 L'], 'base_price' => 8.90, 'description' => 'Aceite vegetal de uso domestico para frituras y aderezos.'],
            ['category' => 'Abarrotes', 'unit' => 'paq', 'name' => 'Fideo spaghetti', 'brands' => ['Don Vittorio', 'Nicolini', 'Molitalia'], 'sizes' => ['250 g', '500 g', '1 kg'], 'base_price' => 5.20, 'description' => 'Pasta larga de trigo para sopas, tallarines y platos caseros.'],
            ['category' => 'Abarrotes', 'unit' => 'paq', 'name' => 'Fideo canuto', 'brands' => ['Don Vittorio', 'Nicolini', 'Lavaggi'], 'sizes' => ['250 g', '500 g', '1 kg'], 'base_price' => 4.80, 'description' => 'Pasta corta de trigo ideal para guisos, sopas y ensaladas.'],
            ['category' => 'Abarrotes', 'unit' => 'kg', 'name' => 'Lenteja seleccionada', 'brands' => ['Valle Alto', 'Bells', 'Costeno'], 'sizes' => ['500 g', '1 kg'], 'base_price' => 6.20, 'description' => 'Menestra seleccionada para guisos, sopas y preparaciones nutritivas.'],
            ['category' => 'Abarrotes', 'unit' => 'kg', 'name' => 'Frejol canario', 'brands' => ['Costeno', 'Bells', 'Valle Alto'], 'sizes' => ['500 g', '1 kg'], 'base_price' => 7.40, 'description' => 'Frejol canario limpio y seleccionado para cocina criolla.'],
            ['category' => 'Abarrotes', 'unit' => 'kg', 'name' => 'Harina sin preparar', 'brands' => ['Blanca Flor', 'Nicolini', 'Favorita'], 'sizes' => ['500 g', '1 kg'], 'base_price' => 4.50, 'description' => 'Harina de trigo para panes, masas, apanados y reposteria.'],
            ['category' => 'Abarrotes', 'unit' => 'paq', 'name' => 'Avena tradicional', 'brands' => ['Quaker', 'Santa Catalina', 'Tres Ositos'], 'sizes' => ['250 g', '500 g', '1 kg'], 'base_price' => 5.60, 'description' => 'Avena en hojuelas para desayuno, bebidas y recetas saludables.'],
            ['category' => 'Abarrotes', 'unit' => 'lat', 'name' => 'Atun trozos en aceite', 'brands' => ['Florida', 'A-1', 'Campomar', 'Primor'], 'sizes' => ['170 g'], 'base_price' => 6.90, 'description' => 'Conserva de atun lista para ensaladas, arroz y comidas rapidas.'],
            ['category' => 'Abarrotes', 'unit' => 'lat', 'name' => 'Leche evaporada', 'brands' => ['Gloria', 'Ideal', 'Bonle'], 'sizes' => ['400 g', 'pack x6'], 'base_price' => 4.80, 'description' => 'Leche evaporada para consumo familiar, postres y cocina diaria.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Sal de mesa yodada', 'brands' => ['Emsal', 'Marina', 'Lobos'], 'sizes' => ['500 g', '1 kg'], 'base_price' => 2.20, 'description' => 'Sal yodada refinada para sazonar alimentos y preparaciones caseras.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Cafe instantaneo', 'brands' => ['Nescafe', 'Kirma', 'Altomayo'], 'sizes' => ['50 g', '100 g', '200 g'], 'base_price' => 8.50, 'description' => 'Cafe soluble para bebidas calientes de preparacion inmediata.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Salsa de tomate', 'brands' => ['Alacena', 'Don Vittorio', 'Bells'], 'sizes' => ['200 g', '400 g'], 'base_price' => 3.40, 'description' => 'Salsa de tomate lista para pastas, guisos y preparaciones rapidas.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Mayonesa', 'brands' => ['Alacena', 'Hellmanns', 'Bells'], 'sizes' => ['100 g', '250 g', '500 g'], 'base_price' => 5.90, 'description' => 'Mayonesa cremosa para sandwiches, ensaladas y acompanamientos.'],
            ['category' => 'Abarrotes', 'unit' => 'bot', 'name' => 'Vinagre blanco', 'brands' => ['Firme', 'Bells', 'Compass'], 'sizes' => ['500 ml', '1 L'], 'base_price' => 3.20, 'description' => 'Vinagre blanco para ensaladas, limpieza ligera y preparaciones caseras.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Sillao clasico', 'brands' => ['Kikko', 'Umsha', 'Bells'], 'sizes' => ['150 ml', '500 ml', '1 L'], 'base_price' => 4.40, 'description' => 'Salsa de soya para salteados, arroz chaufa y sazon oriental.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Comino molido', 'brands' => ['Sibarita', 'De la Casa', 'Bells'], 'sizes' => ['25 g', '100 g'], 'base_price' => 2.30, 'description' => 'Condimento molido para aderezos, carnes y guisos tradicionales.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Oregano seco', 'brands' => ['Sibarita', 'De la Casa', 'Bells'], 'sizes' => ['15 g', '50 g'], 'base_price' => 2.10, 'description' => 'Hierba seca para pizzas, pastas, sopas y marinados.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Mermelada de fresa', 'brands' => ['Fanny', 'Gloria', 'Bells'], 'sizes' => ['320 g', '1 kg'], 'base_price' => 6.20, 'description' => 'Mermelada dulce de fruta para pan, postres y desayunos.'],
            ['category' => 'Abarrotes', 'unit' => 'und', 'name' => 'Cereal hojuelas de maiz', 'brands' => ['Angel', 'Kelloggs', 'Bells'], 'sizes' => ['250 g', '500 g'], 'base_price' => 7.40, 'description' => 'Cereal crocante para desayuno con leche o yogurt.'],

            ['category' => 'Bebidas', 'unit' => 'bot', 'name' => 'Agua sin gas', 'brands' => ['San Luis', 'Cielo', 'San Mateo'], 'sizes' => ['625 ml', '1 L', '2.5 L'], 'base_price' => 2.20, 'description' => 'Agua embotellada sin gas para hidratacion diaria.'],
            ['category' => 'Bebidas', 'unit' => 'bot', 'name' => 'Gaseosa cola', 'brands' => ['Coca-Cola', 'Pepsi', 'Kola Real'], 'sizes' => ['500 ml', '1 L', '1.5 L', '3 L'], 'base_price' => 3.20, 'description' => 'Bebida gasificada sabor cola para consumo individual o familiar.'],
            ['category' => 'Bebidas', 'unit' => 'bot', 'name' => 'Gaseosa amarilla', 'brands' => ['Inca Kola', 'Oro', 'Viva'], 'sizes' => ['500 ml', '1 L', '1.5 L', '3 L'], 'base_price' => 3.30, 'description' => 'Bebida gasificada dulce de consumo familiar y reuniones.'],
            ['category' => 'Bebidas', 'unit' => 'bot', 'name' => 'Jugo nectar durazno', 'brands' => ['Frugos', 'Pulp', 'Gloria'], 'sizes' => ['250 ml', '1 L'], 'base_price' => 2.60, 'description' => 'Nectar de fruta listo para loncheras, desayuno y consumo al paso.'],
            ['category' => 'Bebidas', 'unit' => 'bot', 'name' => 'Bebida rehidratante', 'brands' => ['Sporade', 'Gatorade', 'Powerade'], 'sizes' => ['500 ml', '1 L'], 'base_price' => 4.20, 'description' => 'Bebida hidratante para actividad fisica y reposicion de energia.'],
            ['category' => 'Bebidas', 'unit' => 'und', 'name' => 'Energizante', 'brands' => ['Red Bull', 'Volt', 'Monster'], 'sizes' => ['250 ml', '473 ml'], 'base_price' => 6.50, 'description' => 'Bebida energizante para consumo ocasional y venta al paso.'],
            ['category' => 'Bebidas', 'unit' => 'bot', 'name' => 'Te helado', 'brands' => ['Free Tea', 'Lipton', 'Fuze Tea'], 'sizes' => ['500 ml', '1 L'], 'base_price' => 3.90, 'description' => 'Bebida de te frio saborizado, lista para consumir.'],

            ['category' => 'Lacteos', 'unit' => 'und', 'name' => 'Yogurt bebible fresa', 'brands' => ['Gloria', 'Laive', 'Yoleit'], 'sizes' => ['180 g', '1 kg'], 'base_price' => 2.80, 'description' => 'Yogurt bebible sabor fresa para desayuno y lonchera.'],
            ['category' => 'Lacteos', 'unit' => 'und', 'name' => 'Yogurt natural', 'brands' => ['Gloria', 'Laive', 'Danlac'], 'sizes' => ['180 g', '1 kg'], 'base_price' => 3.10, 'description' => 'Yogurt natural cremoso para consumo directo o preparaciones.'],
            ['category' => 'Lacteos', 'unit' => 'und', 'name' => 'Queso fresco', 'brands' => ['Laive', 'Gloria', 'Bonle'], 'sizes' => ['250 g', '500 g', '1 kg'], 'base_price' => 9.50, 'description' => 'Queso fresco refrigerado para desayunos, sandwiches y cocina.'],
            ['category' => 'Lacteos', 'unit' => 'und', 'name' => 'Mantequilla con sal', 'brands' => ['Gloria', 'Laive', 'President'], 'sizes' => ['100 g', '200 g'], 'base_price' => 6.20, 'description' => 'Mantequilla con sal para untar, cocinar y hornear.'],
            ['category' => 'Lacteos', 'unit' => 'und', 'name' => 'Leche fresca', 'brands' => ['Gloria', 'Laive', 'Danlac'], 'sizes' => ['1 L'], 'base_price' => 5.80, 'description' => 'Leche fresca refrigerada para consumo familiar.'],

            ['category' => 'Snacks y golosinas', 'unit' => 'paq', 'name' => 'Papas fritas clasicas', 'brands' => ['Lays', 'Pringles', 'Bells'], 'sizes' => ['35 g', '140 g'], 'base_price' => 2.60, 'description' => 'Snack salado de papa crocante para consumo individual o reuniones.'],
            ['category' => 'Snacks y golosinas', 'unit' => 'paq', 'name' => 'Chifles salados', 'brands' => ['Tiyapuy', 'Crickets', 'Bells'], 'sizes' => ['50 g', '150 g'], 'base_price' => 3.20, 'description' => 'Snack de platano frito crocante para piqueo y lonchera.'],
            ['category' => 'Snacks y golosinas', 'unit' => 'paq', 'name' => 'Galleta soda', 'brands' => ['Field', 'San Jorge', 'Costa'], 'sizes' => ['40 g', 'pack x6'], 'base_price' => 1.50, 'description' => 'Galleta salada para acompanamiento de bebidas y comidas ligeras.'],
            ['category' => 'Snacks y golosinas', 'unit' => 'paq', 'name' => 'Galleta vainilla', 'brands' => ['Field', 'Costa', 'Tentacion'], 'sizes' => ['40 g', 'pack x6'], 'base_price' => 1.70, 'description' => 'Galleta dulce de vainilla para loncheras y consumo al paso.'],
            ['category' => 'Snacks y golosinas', 'unit' => 'und', 'name' => 'Chocolate con leche', 'brands' => ['Sublime', 'Princesa', 'Triangulo'], 'sizes' => ['30 g', '90 g'], 'base_price' => 2.40, 'description' => 'Chocolate dulce para antojos, caja y exhibicion de impulso.'],
            ['category' => 'Snacks y golosinas', 'unit' => 'und', 'name' => 'Caramelo surtido', 'brands' => ['Ambrosoli', 'Arcor', 'Sayon'], 'sizes' => ['100 g', '500 g'], 'base_price' => 3.60, 'description' => 'Caramelos surtidos para venta por bolsa o consumo familiar.'],

            ['category' => 'Panaderia', 'unit' => 'und', 'name' => 'Pan molde blanco', 'brands' => ['Bimbo', 'PyC', 'Union'], 'sizes' => ['480 g', '650 g'], 'base_price' => 7.20, 'description' => 'Pan de molde tajado para sandwiches, tostadas y desayuno.'],
            ['category' => 'Panaderia', 'unit' => 'und', 'name' => 'Pan molde integral', 'brands' => ['Bimbo', 'PyC', 'Union'], 'sizes' => ['480 g', '650 g'], 'base_price' => 8.20, 'description' => 'Pan integral tajado con fibra para desayuno y lonchera.'],
            ['category' => 'Panaderia', 'unit' => 'paq', 'name' => 'Tostadas integrales', 'brands' => ['Bimbo', 'San Jorge', 'Bells'], 'sizes' => ['120 g', '250 g'], 'base_price' => 4.10, 'description' => 'Tostadas crocantes para acompanar bebidas calientes y untables.'],

            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Detergente en polvo', 'brands' => ['Bolivar', 'Ariel', 'Sapolio', 'Opal'], 'sizes' => ['250 g', '750 g', '2 kg'], 'base_price' => 4.50, 'description' => 'Detergente en polvo para lavado de ropa a mano o lavadora.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Lavavajilla liquido', 'brands' => ['Ayudin', 'Sapolio', 'Daryza'], 'sizes' => ['500 ml', '1 L'], 'base_price' => 5.40, 'description' => 'Liquido lavavajilla para limpieza de platos, ollas y utensilios.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'bot', 'name' => 'Lejia desinfectante', 'brands' => ['Clorox', 'Sapolio', 'Patito'], 'sizes' => ['1 L', '2 L'], 'base_price' => 4.80, 'description' => 'Lejia para desinfeccion de pisos, banos y superficies lavables.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'bot', 'name' => 'Limpiatodo floral', 'brands' => ['Poett', 'Sapolio', 'Bells'], 'sizes' => ['900 ml', '1.8 L'], 'base_price' => 5.60, 'description' => 'Limpiador perfumado para pisos y superficies del hogar.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Papel higienico', 'brands' => ['Elite', 'Suave', 'Noble'], 'sizes' => ['2 und', '4 und', '6 und'], 'base_price' => 3.50, 'description' => 'Papel higienico suave para uso diario en el hogar.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Servilletas blancas', 'brands' => ['Elite', 'Scott', 'Bells'], 'sizes' => ['100 und', '200 und'], 'base_price' => 3.30, 'description' => 'Servilletas de papel para mesa, cocina y atencion rapida.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Esponja lavavajilla', 'brands' => ['Scotch-Brite', 'Sapolio', 'Bells'], 'sizes' => ['1 und', '3 und'], 'base_price' => 2.60, 'description' => 'Esponja abrasiva para lavado de vajilla y utensilios de cocina.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Bolsa para basura', 'brands' => ['Virutex', 'Sapolio', 'Bells'], 'sizes' => ['10 und', '30 und'], 'base_price' => 5.40, 'description' => 'Bolsas resistentes para residuos domesticos y limpieza general.'],
            ['category' => 'Limpieza del hogar', 'unit' => 'und', 'name' => 'Insecticida aerosol', 'brands' => ['Baygon', 'Raid', 'Sapolio'], 'sizes' => ['360 ml', '500 ml'], 'base_price' => 10.90, 'description' => 'Aerosol insecticida para control domestico de insectos voladores.'],

            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Shampoo cuidado diario', 'brands' => ['Sedal', 'Pantene', 'Head Shoulders'], 'sizes' => ['200 ml', '400 ml'], 'base_price' => 9.80, 'description' => 'Shampoo de uso diario para limpieza y cuidado del cabello.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Jabon de tocador', 'brands' => ['Dove', 'Palmolive', 'Protex', 'Neko'], 'sizes' => ['90 g', '3 und'], 'base_price' => 3.20, 'description' => 'Jabon de tocador para higiene personal diaria.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Pasta dental', 'brands' => ['Colgate', 'Oral-B', 'Dento'], 'sizes' => ['75 ml', '150 ml'], 'base_price' => 5.40, 'description' => 'Crema dental para higiene bucal diaria y proteccion familiar.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Desodorante aerosol', 'brands' => ['Rexona', 'Nivea', 'Axe'], 'sizes' => ['90 g', '150 ml'], 'base_price' => 9.20, 'description' => 'Desodorante en aerosol para proteccion y frescura durante el dia.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Papel facial', 'brands' => ['Elite', 'Kleenex', 'Scott'], 'sizes' => ['75 und', '150 und'], 'base_price' => 4.80, 'description' => 'Papel facial suave para higiene, escritorio y cartera.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Afeitadora descartable', 'brands' => ['Gillette', 'Schick', 'Bic'], 'sizes' => ['2 und', '4 und'], 'base_price' => 5.20, 'description' => 'Afeitadora descartable para cuidado personal y viaje.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Toalla higienica', 'brands' => ['Nosotras', 'Kotex', 'Always'], 'sizes' => ['10 und', '30 und'], 'base_price' => 6.30, 'description' => 'Toallas higienicas para cuidado femenino diario.'],
            ['category' => 'Cuidado personal', 'unit' => 'und', 'name' => 'Gel antibacterial', 'brands' => ['Aval', 'Bells', 'Portugal'], 'sizes' => ['60 ml', '250 ml', '500 ml'], 'base_price' => 4.20, 'description' => 'Gel antibacterial para higiene de manos fuera de casa.'],

            ['category' => 'Congelados y refrigerados', 'unit' => 'und', 'name' => 'Hot dog clasico', 'brands' => ['San Fernando', 'Otto Kunz', 'Braedt'], 'sizes' => ['250 g', '500 g'], 'base_price' => 8.90, 'description' => 'Embutido refrigerado para desayunos, salchipapas y comidas rapidas.'],
            ['category' => 'Congelados y refrigerados', 'unit' => 'und', 'name' => 'Jamonada especial', 'brands' => ['San Fernando', 'La Segoviana', 'Braedt'], 'sizes' => ['200 g', '500 g'], 'base_price' => 7.60, 'description' => 'Jamonada refrigerada para sandwiches, loncheras y piqueos.'],
            ['category' => 'Congelados y refrigerados', 'unit' => 'und', 'name' => 'Hamburguesa congelada', 'brands' => ['San Fernando', 'Redondos', 'Bells'], 'sizes' => ['4 und', '8 und'], 'base_price' => 10.50, 'description' => 'Hamburguesas congeladas listas para cocinar en plancha o sarten.'],
            ['category' => 'Congelados y refrigerados', 'unit' => 'und', 'name' => 'Pollo trozado congelado', 'brands' => ['San Fernando', 'Redondos', 'Avinka'], 'sizes' => ['1 kg', '2 kg'], 'base_price' => 12.90, 'description' => 'Pollo congelado trozado para preparaciones familiares.'],

            ['category' => 'Bebes', 'unit' => 'und', 'name' => 'Panales etapa pequena', 'brands' => ['Huggies', 'Pampers', 'Babysec'], 'sizes' => ['10 und', '30 und'], 'base_price' => 11.90, 'description' => 'Panales descartables para bebe con absorcion de uso diario.'],
            ['category' => 'Bebes', 'unit' => 'und', 'name' => 'Toallitas humedas', 'brands' => ['Huggies', 'Pampers', 'Babysec'], 'sizes' => ['50 und', '100 und'], 'base_price' => 6.80, 'description' => 'Toallitas humedas para limpieza delicada de bebe.'],
            ['category' => 'Bebes', 'unit' => 'und', 'name' => 'Colado de fruta', 'brands' => ['Heinz', 'Gerber', 'Agugu'], 'sizes' => ['113 g', '170 g'], 'base_price' => 5.90, 'description' => 'Alimento infantil listo para consumir bajo supervision adulta.'],

            ['category' => 'Mascotas', 'unit' => 'kg', 'name' => 'Comida para perro adulto', 'brands' => ['Ricocan', 'Dog Chow', 'Mimaskot'], 'sizes' => ['500 g', '1 kg', '3 kg'], 'base_price' => 8.90, 'description' => 'Alimento balanceado seco para perros adultos.'],
            ['category' => 'Mascotas', 'unit' => 'kg', 'name' => 'Comida para gato adulto', 'brands' => ['Ricocat', 'Cat Chow', 'Mimaskot'], 'sizes' => ['500 g', '1 kg'], 'base_price' => 9.60, 'description' => 'Alimento seco para gatos adultos con formula balanceada.'],
            ['category' => 'Mascotas', 'unit' => 'und', 'name' => 'Arena sanitaria para gato', 'brands' => ['Mishicat', 'Bells', 'Super Cat'], 'sizes' => ['2 kg', '5 kg'], 'base_price' => 8.50, 'description' => 'Arena sanitaria absorbente para bandeja de gato.'],

            ['category' => 'Frutas y verduras', 'unit' => 'kg', 'name' => 'Platano de seda', 'brands' => ['Mercado local', 'Seleccion tienda'], 'sizes' => ['1 kg'], 'base_price' => 4.20, 'description' => 'Fruta fresca de alta rotacion para consumo diario.'],
            ['category' => 'Frutas y verduras', 'unit' => 'kg', 'name' => 'Manzana roja', 'brands' => ['Mercado local', 'Seleccion tienda'], 'sizes' => ['1 kg'], 'base_price' => 6.80, 'description' => 'Manzana fresca para lonchera, postres y consumo directo.'],
            ['category' => 'Frutas y verduras', 'unit' => 'kg', 'name' => 'Papa blanca', 'brands' => ['Mercado local', 'Seleccion tienda'], 'sizes' => ['1 kg', '3 kg'], 'base_price' => 3.20, 'description' => 'Papa fresca seleccionada para cocina diaria.'],
            ['category' => 'Frutas y verduras', 'unit' => 'kg', 'name' => 'Cebolla roja', 'brands' => ['Mercado local', 'Seleccion tienda'], 'sizes' => ['1 kg'], 'base_price' => 3.80, 'description' => 'Cebolla roja fresca para aderezos, ensaladas y cocina criolla.'],
            ['category' => 'Frutas y verduras', 'unit' => 'kg', 'name' => 'Tomate italiano', 'brands' => ['Mercado local', 'Seleccion tienda'], 'sizes' => ['1 kg'], 'base_price' => 4.40, 'description' => 'Tomate fresco para ensaladas, salsas y preparaciones calientes.'],

            ['category' => 'Farmacia basica', 'unit' => 'und', 'name' => 'Alcohol medicinal', 'brands' => ['Portugal', 'Medifarma', 'Bells'], 'sizes' => ['120 ml', '500 ml', '1 L'], 'base_price' => 4.50, 'description' => 'Alcohol medicinal para higiene externa y botiquin basico.'],
            ['category' => 'Farmacia basica', 'unit' => 'und', 'name' => 'Agua oxigenada', 'brands' => ['Portugal', 'Medifarma', 'Bells'], 'sizes' => ['120 ml', '500 ml'], 'base_price' => 3.80, 'description' => 'Solucion de botiquin para limpieza externa bajo uso responsable.'],
            ['category' => 'Farmacia basica', 'unit' => 'und', 'name' => 'Curitas adhesivas', 'brands' => ['Nexcare', 'Hansaplast', 'Bells'], 'sizes' => ['10 und', '30 und'], 'base_price' => 3.60, 'description' => 'Bandas adhesivas para cubrir pequenos cortes y raspaduras.'],
            ['category' => 'Farmacia basica', 'unit' => 'und', 'name' => 'Algodon hidrofilo', 'brands' => ['Nexcare', 'Bells', 'Medifarma'], 'sizes' => ['25 g', '100 g'], 'base_price' => 3.20, 'description' => 'Algodon para limpieza, botiquin y cuidado personal.'],
        ];
    }
}
