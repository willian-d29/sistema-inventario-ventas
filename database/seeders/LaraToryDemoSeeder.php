<?php

namespace Database\Seeders;

use App\Enums\CashRegister\CashDifferenceStatusEnum;
use App\Enums\CashRegister\CashMovementDirectionEnum;
use App\Enums\CashRegister\CashMovementTypeEnum;
use App\Enums\CashRegister\CashRegisterStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\StockMovementTypeEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class LaraToryDemoSeeder extends Seeder
{
    private array $documentCounters = [
        'receipt' => 0,
        'invoice' => 0,
    ];

    public function run(): void
    {
        $this->wipeDemoData();

        $admin = $this->createUser('Willan Administrador', 'willan.a@laratory.pe', 'admin');
        $cashiers = [
            $this->createUser('Maria Cajera', 'maria.c@laratory.pe', 'cajero'),
            $this->createUser('Luis Cajero', 'luis.c@laratory.pe', 'cajero'),
        ];

        $this->createEmployees($cashiers);

        $categories = $this->createCategories();
        $unitTypes = $this->createUnitTypes();
        $suppliers = $this->createSuppliers();
        $products = $this->createProducts($categories, $unitTypes, $suppliers, $admin);

        $this->createSalesDataset($admin, $cashiers, $products);
        $this->syncDocumentSequences();
    }

    private function wipeDemoData(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->tablesToClear() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->delete();
            $this->resetAutoIncrement($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    private function tablesToClear(): array
    {
        return [
            'document_print_logs',
            'cash_movements',
            'payments',
            'sale_items',
            'sales',
            'carts',
            'stock_movements',
            'order_items',
            'transactions',
            'orders',
            'expenses',
            'salaries',
            'cash_registers',
            'employees',
            'customers',
            'products',
            'suppliers',
            'categories',
            'unit_types',
            'document_sequences',
            'password_reset_tokens',
            'sessions',
            'personal_access_tokens',
            'users',
        ];
    }

    private function resetAutoIncrement(string $table): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        } catch (Throwable) {
            // Some support tables do not have an auto-increment id.
        }
    }

    private function createUser(string $name, string $email, string $role): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => $role,
            'preferences' => [
                'language' => 'es',
                'shortcuts_enabled' => true,
            ],
        ]);
    }

    private function createEmployees(array $cashiers): void
    {
        $employees = [
            [
                'user' => $cashiers[0],
                'phone' => '987654321',
                'address' => 'Av. Arequipa 1480, Lima',
                'salary' => 1650,
                'nid' => '45678912',
            ],
            [
                'user' => $cashiers[1],
                'phone' => '976543210',
                'address' => 'Av. La Marina 2350, San Miguel',
                'salary' => 1700,
                'nid' => '46789123',
            ],
        ];

        foreach ($employees as $employee) {
            Employee::query()->create([
                'user_id' => $employee['user']->id,
                'name' => $employee['user']->name,
                'email' => $employee['user']->email,
                'phone' => $employee['phone'],
                'designation' => 'Cajero de tienda',
                'address' => $employee['address'],
                'salary' => $employee['salary'],
                'photo' => null,
                'nid' => $employee['nid'],
                'joining_date' => now()->subMonths(5)->toDateString(),
            ]);
        }
    }

    private function createCategories(): array
    {
        $names = [
            'Abarrotes',
            'Bebidas',
            'Lacteos y huevos',
            'Frutas y verduras',
            'Carnes y pescados',
            'Limpieza del hogar',
            'Higiene personal',
            'Belleza y cuidado',
            'Tecnologia',
            'Electrohogar',
            'Muebles y organizacion',
            'Deportes y outdoor',
            'Bebes y ninos',
            'Mascotas',
            'Panaderia y pasteleria',
            'Congelados',
            'Cocina y menaje',
            'Ferreteria basica',
            'Libreria y oficina',
            'Snacks y confiteria',
        ];

        return collect($names)
            ->mapWithKeys(fn (string $name) => [$name => Category::query()->create(['name' => $name])])
            ->all();
    }

    private function createUnitTypes(): array
    {
        $units = [
            ['name' => 'Unidad', 'symbol' => 'und'],
            ['name' => 'Paquete', 'symbol' => 'paq'],
            ['name' => 'Bolsa', 'symbol' => 'bol'],
            ['name' => 'Botella', 'symbol' => 'bot'],
            ['name' => 'Litro', 'symbol' => 'lt'],
            ['name' => 'Kilo', 'symbol' => 'kg'],
            ['name' => 'Caja', 'symbol' => 'caja'],
            ['name' => 'Pack', 'symbol' => 'pack'],
            ['name' => 'Lata', 'symbol' => 'lata'],
            ['name' => 'Frasco', 'symbol' => 'frasco'],
        ];

        return collect($units)
            ->mapWithKeys(fn (array $unit) => [$unit['name'] => UnitType::query()->create($unit)])
            ->all();
    }

    private function createSuppliers(): array
    {
        $suppliers = [
            ['Alicorp Distribucion', 'ventas@alicorp-demo.pe', '014801200', 'Av. Argentina 4793, Callao', 'Alicorp Mayorista'],
            ['Gloria Alimentos', 'pedidos@gloria-demo.pe', '014802300', 'Av. Republica de Panama 2461, Lima', 'Gloria'],
            ['Backus Bebidas', 'comercial@backus-demo.pe', '014803400', 'Av. Nicolas Ayllon 3986, Ate', 'Backus'],
            ['Nestle Peru', 'contacto@nestle-demo.pe', '014804500', 'Av. Los Castillos 185, Ate', 'Nestle'],
            ['Procter & Gamble Peru', 'mayorista@pg-demo.pe', '014805600', 'Av. Javier Prado 4200, Surco', 'P&G'],
            ['Unilever Peru', 'logistica@unilever-demo.pe', '014806700', 'Av. El Derby 254, Surco', 'Unilever'],
            ['San Fernando Comercial', 'ventas@sanfernando-demo.pe', '014807800', 'Av. Republica de Panama 4295, Surquillo', 'San Fernando'],
            ['Distribuidora Hogar y Bazar', 'pedidos@hogarbazar-demo.pe', '014808900', 'Av. Industrial 1550, Independencia', 'Hogar y Bazar'],
        ];

        return collect($suppliers)
            ->map(fn (array $supplier) => Supplier::query()->create([
                'name' => $supplier[0],
                'email' => $supplier[1],
                'phone' => $supplier[2],
                'address' => $supplier[3],
                'shop_name' => $supplier[4],
                'photo' => null,
            ]))
            ->values()
            ->all();
    }

    private function createProducts(array $categories, array $unitTypes, array $suppliers, User $admin): array
    {
        $products = [];
        $index = 1;

        foreach ($this->catalogGroups() as $group) {
            foreach ($group['items'] as $item) {
                [$name, $price] = $item;
                $quantity = 36 + ($index % 67);
                $supplier = $suppliers[($index - 1) % count($suppliers)];
                $unitType = $unitTypes[$item[2] ?? $group['unit'] ?? 'Unidad'];
                $cost = round($price * (0.68 + (($index % 7) * 0.015)), 2);

                $product = Product::query()->create([
                    'category_id' => $categories[$group['category']]->id,
                    'supplier_id' => $supplier->id,
                    'name' => $name,
                    'product_number' => 'LT-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'description' => $this->productDescription($name, $group),
                    'product_code' => 'TECH-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'barcode' => $index === 1 ? '7896004009582' : (string) (7750000000000 + $index),
                    'root' => Str::slug(Str::ascii($name)),
                    'buying_price' => $cost,
                    'selling_price' => $price,
                    'buying_date' => now()->subDays(40 - ($index % 30)),
                    'unit_type_id' => $unitType->id,
                    'quantity' => $quantity,
                    'photo' => $this->productImagePath($group['image'], $name, $index),
                    'status' => ProductStatusEnum::ACTIVE->value,
                ]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $admin->id,
                    'type' => StockMovementTypeEnum::ADJUSTMENT_IN->value,
                    'quantity' => $quantity,
                    'previous_quantity' => 0,
                    'new_quantity' => $quantity,
                    'reason' => 'Carga inicial de inventario demo',
                ]);

                $products[] = $product;
                $index++;
            }
        }

        return $products;
    }

    private function productDescription(string $name, array $group): string
    {
        return "{$name}. {$group['description']} Presentacion lista para venta en tienda, con precio actualizado, unidad comercial definida y control por codigo de barras.";
    }

    private function productImageUrl(string $imageKeyword, string $name, int $index): string
    {
        $words = collect($this->productImageKeywords($name, $imageKeyword))
            ->flatMap(fn (string $word) => preg_split('/\s+/', $word) ?: [])
            ->map(fn (string $word) => preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($word)))
            ->filter()
            ->unique()
            ->take(6)
            ->implode(',');

        return "https://loremflickr.com/320/320/{$words}?lock=".(4200 + $index);
    }

    private function productImagePath(string $imageKeyword, string $name, int $index): string
    {
        $override = $this->localProductImageOverride($index);

        if ($override && Storage::disk('public')->exists(Product::PHOTO_PATH.'/'.$override)) {
            return $override;
        }

        foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $extension) {
            $fileName = Str::slug(Str::ascii($name)).'-'.$index.'.'.$extension;

            if (Storage::disk('public')->exists(Product::PHOTO_PATH.'/'.$fileName)) {
                return $fileName;
            }
        }

        return $this->productImageUrl($imageKeyword, $name, $index);
    }

    private function localProductImageOverride(int $index): ?string
    {
        return [
            1 => 'real-arroz-extra-costeno.jpg',
            2 => 'real-azucar-rubia-paramonga.jpg',
            3 => 'real-aceite-vegetal-cocinero.jpg',
            4 => 'real-tostadas-integrales-bimbo.jpg',
            5 => 'real-cereal-hojuelas-de-maiz-angel.jpg',
            6 => 'real-atun-trozos-en-aceite-florida.jpg',
            7 => 'real-leche-evaporada-gloria.jpg',
            8 => 'real-arroz-extra-faraon.jpg',
            9 => 'real-mayonesa-bells.jpg',
            10 => 'real-cereal-hojuelas-de-maiz-kelloggs.jpg',
            11 => 'real-gaseosa-cola-coca-cola.jpg',
            12 => 'real-te-helado-fuze-tea.jpg',
            13 => 'real-agua-sin-gas-cielo.jpg',
            14 => 'catalog-bebidas.svg',
            15 => 'real-te-helado-fuze-tea.jpg',
            16 => 'real-bebida-rehidratante-gatorade.jpg',
            17 => 'real-energizante-red-bull.jpg',
            18 => 'catalog-bebidas.svg',
            19 => 'real-te-helado-fuze-tea.jpg',
            20 => 'real-yogurt-bebible-fresa-laive.jpg',
            21 => 'real-leche-evaporada-gloria.jpg',
            22 => 'real-yogurt-bebible-fresa-gloria.jpg',
            23 => 'real-queso-fresco-gloria.jpg',
            24 => 'real-mantequilla-con-sal-laive.jpg',
            25 => 'real-queso-fresco-laive.jpg',
            26 => 'catalog-lacteos.svg',
            28 => 'real-mantequilla-con-sal-laive.jpg',
            31 => 'catalog-frutas-y-verduras.svg',
            33 => 'tomate-italiano-por-kg-35.jpg',
            34 => 'manzana-royal-gala-por-kg-32.jpg',
        ][$index] ?? null;
    }

    private function productImageKeywords(string $name, string $fallback): array
    {
        $normalized = Str::lower(Str::ascii($name));
        $priorityRules = [
            'atun' => ['canned tuna', 'can', 'grocery', 'product'],
            'shampoo canino' => ['dog shampoo bottle', 'product'],
        ];

        foreach ($priorityRules as $needle => $keywords) {
            if (str_contains($normalized, $needle)) {
                return $keywords;
            }
        }

        $rules = [
            'arroz' => ['rice', 'bag', 'supermarket', 'product'],
            'azucar' => ['sugar', 'bag', 'grocery', 'product'],
            'aceite' => ['cooking oil', 'bottle', 'grocery', 'product'],
            'fideo' => ['spaghetti', 'pasta', 'package', 'product'],
            'spaghetti' => ['spaghetti', 'pasta', 'package', 'product'],
            'harina' => ['flour', 'bag', 'grocery', 'product'],
            'atun' => ['canned tuna', 'can', 'grocery', 'product'],
            'leche evaporada' => ['evaporated milk', 'can', 'grocery', 'product'],
            'lenteja' => ['lentils', 'bag', 'grocery', 'product'],
            'salsa roja' => ['tomato sauce', 'jar', 'grocery', 'product'],
            'avena' => ['oats', 'bag', 'grocery', 'product'],
            'coca-cola' => ['cola bottle', 'soda', 'product'],
            'inca kola' => ['yellow soda', 'bottle', 'product'],
            'agua' => ['water bottle', 'beverage', 'product'],
            'frugos' => ['juice carton', 'beverage', 'product'],
            'sporade' => ['sports drink', 'bottle', 'product'],
            'volt' => ['energy drink', 'can', 'product'],
            'malta' => ['malt drink', 'bottle', 'product'],
            'free tea' => ['iced tea', 'bottle', 'product'],
            'leche uht' => ['milk carton', 'dairy', 'product'],
            'yogurt' => ['yogurt bottle', 'dairy', 'product'],
            'queso' => ['cheese package', 'dairy', 'product'],
            'mantequilla' => ['butter package', 'dairy', 'product'],
            'huevos' => ['egg carton', 'dairy', 'product'],
            'manjar' => ['caramel spread', 'jar', 'product'],
            'crema de leche' => ['cream carton', 'dairy', 'product'],
            'platano' => ['banana', 'fruit', 'supermarket'],
            'manzana' => ['apple', 'fruit', 'supermarket'],
            'naranja' => ['orange', 'fruit', 'supermarket'],
            'palta' => ['avocado', 'fruit', 'supermarket'],
            'tomate' => ['tomato', 'vegetable', 'supermarket'],
            'papa' => ['potato', 'vegetable', 'supermarket'],
            'zanahoria' => ['carrot', 'vegetable', 'supermarket'],
            'cebolla' => ['red onion', 'vegetable', 'supermarket'],
            'lechuga' => ['lettuce', 'vegetable', 'supermarket'],
            'brocoli' => ['broccoli', 'vegetable', 'supermarket'],
            'pechuga' => ['chicken breast', 'meat', 'supermarket'],
            'pierna' => ['chicken legs', 'meat', 'supermarket'],
            'carne molida' => ['ground beef', 'meat', 'supermarket'],
            'bistec' => ['beef steak', 'meat', 'supermarket'],
            'chuleta' => ['pork chop', 'meat', 'supermarket'],
            'tilapia' => ['fish fillet', 'seafood', 'supermarket'],
            'trucha' => ['trout fish', 'seafood', 'supermarket'],
            'hamburguesa' => ['burger patties', 'frozen food', 'product'],
            'hot dog' => ['hot dog package', 'meat', 'product'],
            'chorizo' => ['chorizo sausage', 'meat', 'product'],
            'detergente' => ['laundry detergent', 'cleaning', 'product'],
            'lavavajilla' => ['dish soap', 'cleaning', 'product'],
            'lejia' => ['bleach bottle', 'cleaning', 'product'],
            'limpiador' => ['cleaner bottle', 'cleaning', 'product'],
            'limpia vidrios' => ['glass cleaner', 'spray bottle', 'product'],
            'suavizante' => ['fabric softener', 'bottle', 'product'],
            'esponja' => ['cleaning sponge', 'package', 'product'],
            'papel toalla' => ['paper towel', 'roll', 'product'],
            'bolsa de basura' => ['trash bags', 'package', 'product'],
            'colgate' => ['toothpaste', 'tube', 'product'],
            'cepillo dental' => ['toothbrush', 'package', 'product'],
            'jabon' => ['soap bar', 'bath', 'product'],
            'shampoo' => ['shampoo bottle', 'product'],
            'desodorante' => ['deodorant spray', 'product'],
            'gel de ducha' => ['shower gel', 'bottle', 'product'],
            'toallas higienicas' => ['sanitary pads', 'package', 'product'],
            'protectores diarios' => ['panty liners', 'package', 'product'],
            'papel higienico' => ['toilet paper', 'package', 'product'],
            'listerine' => ['mouthwash bottle', 'product'],
            'crema corporal' => ['body lotion', 'bottle', 'product'],
            'agua micelar' => ['micellar water', 'bottle', 'product'],
            'mascara' => ['mascara makeup', 'cosmetics', 'product'],
            'elvive' => ['hair treatment', 'bottle', 'product'],
            'protector solar' => ['sunscreen bottle', 'product'],
            'crema peinar' => ['hair cream', 'jar', 'product'],
            'ampollas' => ['hair ampoules', 'beauty', 'product'],
            'esmalte' => ['nail polish', 'cosmetics', 'product'],
            'gillette' => ['razor package', 'product'],
            'eucerin' => ['skin lotion', 'bottle', 'product'],
            'galaxy' => ['smartphone', 'mobile phone', 'product'],
            'redmi' => ['smartphone', 'mobile phone', 'product'],
            'buds' => ['wireless earbuds', 'product'],
            'mouse' => ['wireless mouse', 'product'],
            'datatraveler' => ['usb flash drive', 'product'],
            'adaptador wifi' => ['wifi adapter', 'product'],
            'tab m9' => ['tablet', 'product'],
            'parlante' => ['bluetooth speaker', 'product'],
            'cargador' => ['phone charger', 'product'],
            'cable hdmi' => ['hdmi cable', 'product'],
            'ups' => ['ups battery backup', 'product'],
            'licuadora' => ['blender', 'home appliance', 'product'],
            'airfryer' => ['air fryer', 'home appliance', 'product'],
            'microondas' => ['microwave oven', 'home appliance', 'product'],
            'hervidor' => ['electric kettle', 'home appliance', 'product'],
            'cafetera' => ['coffee maker', 'home appliance', 'product'],
            'batidora' => ['hand blender', 'home appliance', 'product'],
            'aspiradora' => ['vacuum cleaner', 'home appliance', 'product'],
            'plancha vapor' => ['steam iron', 'home appliance', 'product'],
            'olla arrocera' => ['rice cooker', 'home appliance', 'product'],
            'ventilador' => ['tower fan', 'home appliance', 'product'],
            'repisa' => ['floating shelf', 'furniture', 'product'],
            'organizador' => ['plastic organizer', 'storage', 'product'],
            'silla plegable' => ['folding chair', 'furniture', 'product'],
            'mesa auxiliar' => ['side table', 'furniture', 'product'],
            'zapatera' => ['shoe rack', 'furniture', 'product'],
            'caja organizadora' => ['storage box', 'product'],
            'perchero' => ['coat rack', 'furniture', 'product'],
            'canasta' => ['storage basket', 'product'],
            'estante' => ['shelving unit', 'furniture', 'product'],
            'cesto de ropa' => ['laundry basket', 'product'],
            'balon' => ['soccer ball', 'sports', 'product'],
            'yoga' => ['yoga mat', 'sports', 'product'],
            'mancuerna' => ['dumbbell', 'sports', 'product'],
            'tomatodo' => ['sports bottle', 'product'],
            'cuerda saltar' => ['jump rope', 'sports', 'product'],
            'guantes fitness' => ['fitness gloves', 'sports', 'product'],
            'pilates' => ['pilates ball', 'sports', 'product'],
            'bandas elasticas' => ['resistance bands', 'sports', 'product'],
            'cooler' => ['portable cooler', 'outdoor', 'product'],
            'silla camping' => ['camping chair', 'outdoor', 'product'],
            'pampers' => ['diapers package', 'baby', 'product'],
            'huggies' => ['baby wipes', 'package', 'product'],
            'formula' => ['baby formula can', 'product'],
            'compota' => ['baby food jar', 'product'],
            'baby shampoo' => ['baby shampoo bottle', 'product'],
            'panal' => ['diapers package', 'baby', 'product'],
            'biberon' => ['baby bottle', 'product'],
            'vaso entrenador' => ['baby training cup', 'product'],
            'play-doh' => ['play dough', 'toy', 'product'],
            'hot wheels' => ['toy car', 'product'],
            'ricocan' => ['dog food bag', 'product'],
            'ricocat' => ['cat food bag', 'product'],
            'mimaskot' => ['dog food bag', 'product'],
            'pedigree' => ['dog food bag', 'product'],
            'whiskas' => ['cat food pouch', 'product'],
            'arena sanitaria' => ['cat litter bag', 'product'],
            'shampoo canino' => ['dog shampoo bottle', 'product'],
            'correa' => ['dog leash', 'product'],
            'plato acero' => ['pet bowl', 'product'],
            'galletas caninas' => ['dog treats', 'product'],
            'pan frances' => ['bread rolls', 'bakery', 'product'],
            'pan molde' => ['sliced bread', 'bakery', 'product'],
            'croissant' => ['croissant', 'bakery', 'product'],
            'empanada' => ['empanada', 'bakery', 'product'],
            'keke' => ['vanilla cake', 'bakery', 'product'],
            'torta' => ['chocolate cake slice', 'bakery', 'product'],
            'ciabatta' => ['ciabatta bread', 'bakery', 'product'],
            'muffin' => ['blueberry muffin', 'bakery', 'product'],
            'pionono' => ['swiss roll cake', 'bakery', 'product'],
            'galleta artesanal' => ['oat cookies', 'bakery', 'product'],
            'papas prefritas' => ['frozen french fries', 'product'],
            'nuggets' => ['chicken nuggets', 'frozen food', 'product'],
            'verduras congeladas' => ['frozen vegetables', 'product'],
            'helado' => ['ice cream tub', 'product'],
            'pizza' => ['frozen pizza', 'product'],
            'basa' => ['frozen fish fillet', 'product'],
            'tequenos' => ['frozen cheese sticks', 'product'],
            'frutos rojos' => ['frozen berries', 'product'],
            'lasagna' => ['frozen lasagna', 'product'],
            'sarten' => ['frying pan', 'kitchenware', 'product'],
            'olla acero' => ['stainless steel pot', 'product'],
            'tabla picar' => ['cutting board', 'product'],
            'cuchillos' => ['knife set', 'product'],
            'taper' => ['glass food container', 'product'],
            'jarra medidora' => ['measuring jug', 'product'],
            'vaso vidrio' => ['drinking glasses set', 'product'],
            'plato tendido' => ['ceramic plate', 'product'],
            'escurridor' => ['dish rack', 'product'],
            'termo' => ['stainless steel thermos', 'product'],
            'martillo' => ['hammer', 'tool', 'product'],
            'destornillador' => ['screwdriver', 'tool', 'product'],
            'cinta aislante' => ['electrical tape', 'product'],
            'cinta doble' => ['double sided tape', 'product'],
            'candado' => ['padlock', 'product'],
            'foco led' => ['led bulb', 'product'],
            'extension electrica' => ['power extension cord', 'product'],
            'pila' => ['aa batteries', 'product'],
            'silicona' => ['silicone sealant', 'product'],
            'guantes nitrilo' => ['work gloves', 'product'],
            'cuaderno' => ['notebook', 'stationery', 'product'],
            'lapicero' => ['blue pen', 'stationery', 'product'],
            'resaltador' => ['highlighter pen', 'stationery', 'product'],
            'papel bond' => ['copy paper ream', 'product'],
            'archivador' => ['binder folder', 'product'],
            'post-it' => ['sticky notes', 'product'],
            'tijera' => ['school scissors', 'product'],
            'goma en barra' => ['glue stick', 'product'],
            'folder' => ['manila folders', 'product'],
            'calculadora' => ['calculator', 'product'],
            'lays' => ['potato chips bag', 'snack', 'product'],
            'doritos' => ['tortilla chips bag', 'snack', 'product'],
            'chizitos' => ['cheese puffs bag', 'snack', 'product'],
            'sublime' => ['chocolate bar', 'product'],
            'princesa' => ['chocolate bar', 'product'],
            'oreo' => ['oreo cookies package', 'product'],
            'casino' => ['sandwich cookies package', 'product'],
            'soda crackers' => ['crackers package', 'product'],
            'm&m' => ['candy bag', 'product'],
            'halls' => ['cough drops package', 'product'],
        ];

        foreach ($rules as $needle => $keywords) {
            if (str_contains($normalized, $needle)) {
                return $keywords;
            }
        }

        return collect(explode(' ', Str::ascii($fallback.' '.$name)))
            ->map(fn (string $word) => preg_replace('/[^A-Za-z0-9]/', '', $word))
            ->filter()
            ->take(5)
            ->values()
            ->all();
    }

    private function createSalesDataset(User $admin, array $cashiers, array $products): void
    {
        $methods = [
            PaymentMethodEnum::CASH->value,
            PaymentMethodEnum::YAPE->value,
            PaymentMethodEnum::PLIN->value,
            PaymentMethodEnum::CARD->value,
            PaymentMethodEnum::TRANSFER->value,
        ];

        $saleIndex = 1;

        for ($day = 18; $day >= 1; $day--) {
            foreach ($cashiers as $cashierIndex => $cashier) {
                $openedAt = now()->subDays($day)->setTime(8 + $cashierIndex, 0);
                $closedAt = (clone $openedAt)->setTime(17, 30);
                $register = $this->openRegister($cashier, 300 + ($cashierIndex * 50), $openedAt, 'Jornada demo cerrada');
                $totals = $this->emptyMethodTotals((float) $register->opening_amount);

                for ($i = 0; $i < 3; $i++) {
                    $soldAt = (clone $openedAt)->addHours(1 + ($i * 2))->addMinutes(($saleIndex * 7) % 50);
                    $method = $methods[$saleIndex % count($methods)];
                    $this->createSale($register, $cashier, $products, $method, $saleIndex, $soldAt, $totals);
                    $saleIndex++;
                }

                $this->closeRegister($register, $admin, $totals, $closedAt);
            }
        }

        foreach ($cashiers as $cashierIndex => $cashier) {
            $openedAt = now()->startOfDay()->addHours(8 + $cashierIndex);
            $register = $this->openRegister($cashier, 350 + ($cashierIndex * 50), $openedAt, 'Caja demo abierta');
            $totals = $this->emptyMethodTotals((float) $register->opening_amount);

            for ($i = 0; $i < 4; $i++) {
                $soldAt = (clone $openedAt)->addMinutes(35 + ($i * 55));
                $method = $methods[($saleIndex + $i) % count($methods)];
                $this->createSale($register, $cashier, $products, $method, $saleIndex, $soldAt, $totals);
                $saleIndex++;
            }
        }
    }

    private function emptyMethodTotals(float $openingAmount = 0): array
    {
        return [
            PaymentMethodEnum::CASH->value => $openingAmount,
            PaymentMethodEnum::YAPE->value => 0,
            PaymentMethodEnum::PLIN->value => 0,
            PaymentMethodEnum::CARD->value => 0,
            PaymentMethodEnum::TRANSFER->value => 0,
        ];
    }

    private function openRegister(User $cashier, float $openingAmount, Carbon $openedAt, string $notes): CashRegister
    {
        $register = CashRegister::query()->create([
            'user_id' => $cashier->id,
            'opening_amount' => $openingAmount,
            'opened_at' => $openedAt,
            'status' => CashRegisterStatusEnum::OPEN->value,
            'notes' => $notes,
            'created_at' => $openedAt,
            'updated_at' => $openedAt,
        ]);

        CashMovement::query()->create([
            'cash_register_id' => $register->id,
            'user_id' => $cashier->id,
            'type' => CashMovementTypeEnum::OPENING->value,
            'direction' => CashMovementDirectionEnum::INCOME->value,
            'payment_method' => PaymentMethodEnum::CASH->value,
            'amount' => $openingAmount,
            'description' => 'Monto inicial de caja',
            'occurred_at' => $openedAt,
            'created_at' => $openedAt,
            'updated_at' => $openedAt,
        ]);

        return $register;
    }

    private function createSale(
        CashRegister $register,
        User $cashier,
        array $products,
        string $paymentMethod,
        int $saleIndex,
        Carbon $soldAt,
        array &$methodTotals
    ): void {
        $documentType = $saleIndex % 5 === 0 ? 'invoice' : 'receipt';
        $sequence = $this->nextDocument($documentType);
        $selectedItems = $this->saleItemsFor($products, $saleIndex);
        $subtotal = round(collect($selectedItems)->sum('subtotal'), 2);
        $taxableAmount = $subtotal;
        $igv = round($taxableAmount * 0.18, 2);
        $total = round($taxableAmount + $igv, 2);

        $sale = Sale::query()->create([
            'cash_register_id' => $register->id,
            'cashier_id' => $cashier->id,
            'customer_id' => null,
            'document_type' => $documentType,
            'document_series' => $sequence['series'],
            'document_number' => $sequence['number'],
            'full_document_number' => $sequence['full_number'],
            'issue_status' => 'pending',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'taxable_amount' => $taxableAmount,
            'igv' => $igv,
            'total' => $total,
            'status' => 'paid',
            'sold_at' => $soldAt,
            'created_at' => $soldAt,
            'updated_at' => $soldAt,
        ]);

        foreach ($selectedItems as $item) {
            $product = $item['product'];
            $unitCost = round((float) $product->buying_price, 2);
            $costSubtotal = round($unitCost * $item['quantity'], 2);

            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'product_code_snapshot' => $product->barcode ?: $product->product_code,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => 0,
                'subtotal' => $item['subtotal'],
                'unit_cost' => $unitCost,
                'cost_subtotal' => $costSubtotal,
                'gross_profit' => round($item['subtotal'] - $costSubtotal, 2),
                'cost_is_estimated' => false,
                'created_at' => $soldAt,
                'updated_at' => $soldAt,
            ]);

            $previousQuantity = (float) $product->quantity;
            $newQuantity = max($previousQuantity - $item['quantity'], 0);
            $product->forceFill(['quantity' => $newQuantity])->save();

            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $cashier->id,
                'type' => StockMovementTypeEnum::SALE->value,
                'quantity' => -$item['quantity'],
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'reference_type' => $sale->getMorphClass(),
                'reference_id' => $sale->id,
                'reason' => 'Venta '.$sale->full_document_number,
                'created_at' => $soldAt,
                'updated_at' => $soldAt,
            ]);
        }

        $payment = $this->createPayment($sale, $paymentMethod, $total, $saleIndex, $soldAt);
        $methodTotals[$paymentMethod] = round($methodTotals[$paymentMethod] + $total, 2);

        CashMovement::query()->create([
            'cash_register_id' => $register->id,
            'user_id' => $cashier->id,
            'sale_id' => $sale->id,
            'payment_id' => $payment->id,
            'type' => CashMovementTypeEnum::SALE->value,
            'direction' => CashMovementDirectionEnum::INCOME->value,
            'payment_method' => $paymentMethod,
            'amount' => $total,
            'reference' => $payment->operation_number,
            'description' => 'Venta '.$sale->full_document_number,
            'metadata' => [
                'sale_document' => $sale->full_document_number,
                'bank_name' => $payment->bank_name,
            ],
            'occurred_at' => $soldAt,
            'created_at' => $soldAt,
            'updated_at' => $soldAt,
        ]);
    }

    private function saleItemsFor(array $products, int $saleIndex): array
    {
        $lineCount = 1 + ($saleIndex % 3);
        $items = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $product = $products[($saleIndex * 7 + $i * 13) % count($products)];
            $quantity = 1 + (($saleIndex + $i) % 2);
            $unitPrice = round((float) $product->selling_price, 2);

            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $quantity, 2),
            ];
        }

        return $items;
    }

    private function createPayment(Sale $sale, string $method, float $total, int $saleIndex, Carbon $soldAt): Payment
    {
        $received = null;
        $change = null;
        $operationNumber = null;
        $bankName = null;

        if ($method === PaymentMethodEnum::CASH->value) {
            $received = ceil($total / 10) * 10;
            $change = round($received - $total, 2);
        } elseif ($method === PaymentMethodEnum::CARD->value) {
            $operationNumber = 'POS-'.str_pad((string) $saleIndex, 6, '0', STR_PAD_LEFT);
            $bankName = 'Visa POS';
        } elseif ($method === PaymentMethodEnum::TRANSFER->value) {
            $operationNumber = 'BCP-'.str_pad((string) $saleIndex, 6, '0', STR_PAD_LEFT);
            $bankName = 'BCP';
        } else {
            $operationNumber = strtoupper($method).'-'.str_pad((string) $saleIndex, 6, '0', STR_PAD_LEFT);
        }

        return Payment::query()->create([
            'sale_id' => $sale->id,
            'payment_method' => $method,
            'amount' => $total,
            'received_amount' => $received,
            'change_amount' => $change,
            'operation_number' => $operationNumber,
            'bank_name' => $bankName,
            'notes' => null,
            'metadata' => [
                'seeded_demo' => true,
            ],
            'created_at' => $soldAt,
            'updated_at' => $soldAt,
        ]);
    }

    private function closeRegister(CashRegister $register, User $admin, array $totals, Carbon $closedAt): void
    {
        $zeroDifferences = collect($totals)->map(fn () => 0)->all();

        $register->update([
            'closing_amount' => $totals[PaymentMethodEnum::CASH->value],
            'expected_amount' => $totals[PaymentMethodEnum::CASH->value],
            'declared_amounts' => $totals,
            'system_amounts' => $totals,
            'differences' => $zeroDifferences,
            'total_difference' => 0,
            'difference_status' => CashDifferenceStatusEnum::BALANCED->value,
            'reviewed_by' => $admin->id,
            'reviewed_at' => $closedAt->copy()->addMinutes(15),
            'review_notes' => 'Revision demo sin diferencias',
            'closing_notes' => 'Cierre demo generado automaticamente',
            'closed_at' => $closedAt,
            'status' => CashRegisterStatusEnum::REVIEWED->value,
            'updated_at' => $closedAt,
        ]);
    }

    private function nextDocument(string $documentType): array
    {
        $this->documentCounters[$documentType]++;
        $series = $documentType === 'invoice' ? 'F001' : 'B001';
        $number = $this->documentCounters[$documentType];

        return [
            'series' => $series,
            'number' => $number,
            'full_number' => $series.'-'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
        ];
    }

    private function syncDocumentSequences(): void
    {
        $rows = [
            ['document_type' => 'receipt', 'series' => 'B001', 'current_number' => $this->documentCounters['receipt']],
            ['document_type' => 'invoice', 'series' => 'F001', 'current_number' => $this->documentCounters['invoice']],
        ];

        foreach ($rows as $row) {
            DB::table('document_sequences')->insert([
                ...$row,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function catalogGroups(): array
    {
        return [
            [
                'category' => 'Abarrotes',
                'unit' => 'Bolsa',
                'image' => 'grocery pantry rice pasta beans',
                'description' => 'Producto de despensa para compras frecuentes y reposicion familiar.',
                'items' => [
                    ['Costeno Arroz Extra 5 kg', 24.90, 'Bolsa'],
                    ['Paisana Azucar Rubia 1 kg', 4.80, 'Bolsa'],
                    ['Primor Aceite Vegetal 900 ml', 10.90, 'Botella'],
                    ['Lavaggi Fideo Spaghetti 500 g', 4.20, 'Paquete'],
                    ['Nicolini Harina Preparada 1 kg', 6.50, 'Bolsa'],
                    ['Florida Atun Trozos en Aceite 170 g', 7.90, 'Lata'],
                    ['Gloria Leche Evaporada Entera 400 g', 4.60, 'Lata'],
                    ['Costeno Lenteja Bebita 500 g', 6.20, 'Bolsa'],
                    ['Molitalia Salsa Roja Completa 400 g', 5.90, 'Frasco'],
                    ['Bells Avena Tradicional 900 g', 8.50, 'Bolsa'],
                ],
            ],
            [
                'category' => 'Bebidas',
                'unit' => 'Botella',
                'image' => 'supermarket beverages soda water juice',
                'description' => 'Bebida para consumo diario, lonchera, reuniones o reposicion de frio.',
                'items' => [
                    ['Coca-Cola Original 1.5 L', 7.90, 'Botella'],
                    ['Inca Kola 1.5 L', 7.80, 'Botella'],
                    ['San Luis Agua sin Gas 2.5 L', 4.20, 'Botella'],
                    ['Cielo Agua Mineral 625 ml', 1.80, 'Botella'],
                    ['Frugos Durazno 1 L', 5.90, 'Litro'],
                    ['Sporade Blueberry 500 ml', 3.20, 'Botella'],
                    ['Volt Energy Drink 300 ml', 2.70, 'Lata'],
                    ['Cusquena Malta 330 ml', 3.90, 'Botella'],
                    ['Free Tea Verde Limon 475 ml', 3.50, 'Botella'],
                    ['Laive Yogurt Bebible Fresa 1 L', 8.90, 'Litro'],
                ],
            ],
            [
                'category' => 'Lacteos y huevos',
                'unit' => 'Unidad',
                'image' => 'dairy milk cheese eggs yogurt',
                'description' => 'Producto refrigerado o de consumo fresco para desayuno y cocina diaria.',
                'items' => [
                    ['Gloria Leche UHT Entera 1 L', 5.20, 'Litro'],
                    ['Laive Yogurt Griego Natural 500 g', 12.90, 'Frasco'],
                    ['Bonle Queso Edam Tajado 180 g', 11.50, 'Paquete'],
                    ['Manty Mantequilla con Sal 200 g', 8.90, 'Unidad'],
                    ['Laive Queso Fresco 400 g', 13.90, 'Unidad'],
                    ['Avinka Huevos Pardos Bandeja 15 und', 14.90, 'Pack'],
                    ['Gloria Yogurt Pro Fresa 1 kg', 10.80, 'Frasco'],
                    ['Milkito Manjar Blanco 250 g', 6.90, 'Frasco'],
                    ['Bonle Mozzarella Rallada 200 g', 12.40, 'Paquete'],
                    ['Laive Crema de Leche 200 ml', 5.60, 'Unidad'],
                ],
            ],
            [
                'category' => 'Frutas y verduras',
                'unit' => 'Kilo',
                'image' => 'fresh fruits vegetables supermarket',
                'description' => 'Producto fresco para canasta saludable y preparacion de comidas.',
                'items' => [
                    ['Platano de Seda por kg', 4.20, 'Kilo'],
                    ['Manzana Royal Gala por kg', 7.90, 'Kilo'],
                    ['Naranja de Jugo por kg', 3.80, 'Kilo'],
                    ['Palta Fuerte por kg', 9.90, 'Kilo'],
                    ['Tomate Italiano por kg', 5.50, 'Kilo'],
                    ['Papa Blanca Seleccionada por kg', 3.20, 'Kilo'],
                    ['Zanahoria Lavada por kg', 3.90, 'Kilo'],
                    ['Cebolla Roja por kg', 4.40, 'Kilo'],
                    ['Lechuga Americana unidad', 3.50, 'Unidad'],
                    ['Brocoli Fresco unidad', 5.90, 'Unidad'],
                ],
            ],
            [
                'category' => 'Carnes y pescados',
                'unit' => 'Kilo',
                'image' => 'meat fish chicken supermarket',
                'description' => 'Producto fresco o empacado para parrilla, cocina diaria y preparaciones familiares.',
                'items' => [
                    ['Pechuga de Pollo Fresca por kg', 16.90, 'Kilo'],
                    ['Pierna con Encuentro de Pollo por kg', 10.90, 'Kilo'],
                    ['Carne Molida Premium por kg', 24.90, 'Kilo'],
                    ['Bistec de Res Nacional por kg', 32.90, 'Kilo'],
                    ['Chuleta de Cerdo por kg', 22.50, 'Kilo'],
                    ['Filete de Tilapia por kg', 27.90, 'Kilo'],
                    ['Trucha Entera Limpia por kg', 24.90, 'Kilo'],
                    ['Hamburguesa de Res Pack 4 und', 18.90, 'Pack'],
                    ['Hot Dog Otto Kunz 500 g', 16.50, 'Paquete'],
                    ['Chorizo Parrillero Braedt 500 g', 21.90, 'Paquete'],
                ],
            ],
            [
                'category' => 'Limpieza del hogar',
                'unit' => 'Unidad',
                'image' => 'cleaning products detergent disinfectant',
                'description' => 'Articulo para limpieza, lavanderia, desinfeccion y mantenimiento del hogar.',
                'items' => [
                    ['Ariel Detergente Liquido 1.8 L', 24.90],
                    ['Bolivar Detergente en Polvo 2.6 kg', 22.50],
                    ['Sapolio Lavavajilla Limon 900 ml', 8.90],
                    ['Clorox Lejia Tradicional 1 L', 4.90],
                    ['Poett Limpiador Lavanda 900 ml', 5.50],
                    ['Mr Musculo Limpia Vidrios 500 ml', 10.90],
                    ['Suavitel Suavizante Primavera 850 ml', 7.90],
                    ['Virutex Esponja Multiuso Pack 3', 6.50],
                    ['Elite Papel Toalla Doble Hoja Pack 2', 9.90],
                    ['Bolsa de Basura Reforzada 75 L Pack 10', 8.50],
                ],
            ],
            [
                'category' => 'Higiene personal',
                'unit' => 'Unidad',
                'image' => 'personal hygiene soap shampoo toothpaste',
                'description' => 'Producto de aseo personal para cuidado diario y reposicion familiar.',
                'items' => [
                    ['Colgate Triple Accion 90 g', 5.90],
                    ['Oral-B Cepillo Dental Pro Salud', 6.90],
                    ['Dove Jabon Barra Original 90 g', 4.90],
                    ['Head and Shoulders Shampoo Limpieza 375 ml', 19.90],
                    ['Rexona Desodorante Invisible 150 ml', 13.90],
                    ['Nivea Men Gel de Ducha 250 ml', 15.90],
                    ['Kotex Toallas Higienicas Nocturnas Pack 10', 8.90],
                    ['Nosotras Protectores Diarios Pack 60', 12.90],
                    ['Elite Papel Higienico Triple Hoja Pack 12', 24.90],
                    ['Listerine Cool Mint 500 ml', 18.90],
                ],
            ],
            [
                'category' => 'Belleza y cuidado',
                'unit' => 'Frasco',
                'image' => 'beauty cosmetics skincare',
                'description' => 'Producto de belleza, cuidado de piel o arreglo personal para uso frecuente.',
                'items' => [
                    ['Nivea Crema Corporal Milk 400 ml', 22.90],
                    ['Garnier Agua Micelar 400 ml', 24.90],
                    ['Maybelline Mascara Lash Sensational', 39.90],
                    ['L Oreal Elvive Oleo Extraordinario 200 ml', 26.90],
                    ['Neutrogena Protector Solar FPS 50 120 ml', 49.90],
                    ['Dove Crema Peinar Rizos 300 ml', 18.90],
                    ['Pantene Ampollas Restauracion Pack 3', 14.90],
                    ['Revlon Esmalte Unas Rojo 14 ml', 16.90],
                    ['Gillette Venus Maquina Depilar Pack 2', 21.90],
                    ['Eucerin pH5 Locion 400 ml', 69.90],
                ],
            ],
            [
                'category' => 'Tecnologia',
                'unit' => 'Unidad',
                'image' => 'consumer electronics smartphone headphones',
                'description' => 'Accesorio o dispositivo tecnologico para conectividad, trabajo y entretenimiento.',
                'items' => [
                    ['Samsung Galaxy A25 5G 128 GB', 999],
                    ['Xiaomi Redmi Buds 5', 179],
                    ['Logitech M185 Mouse Inalambrico', 59],
                    ['Kingston DataTraveler 128 GB', 39],
                    ['TP-Link Archer T3U Adaptador WiFi', 79],
                    ['Lenovo Tab M9 64 GB', 599],
                    ['JBL Go 4 Parlante Bluetooth', 189],
                    ['Baseus Cargador USB-C 30W', 89],
                    ['Cable HDMI 2.0 2 m', 29],
                    ['Forza UPS 750VA', 249],
                ],
            ],
            [
                'category' => 'Electrohogar',
                'unit' => 'Unidad',
                'image' => 'home appliance blender microwave',
                'description' => 'Electrodomestico para cocina, limpieza o comodidad del hogar.',
                'items' => [
                    ['Oster Licuadora Xpert Series', 299],
                    ['Philips Airfryer Essential 4.1 L', 499],
                    ['Samsung Microondas 23 L', 349],
                    ['Imaco Hervidor Electrico 1.7 L', 79],
                    ['Thomas Cafetera Programable 12 Tazas', 189],
                    ['Bosch Batidora de Inmersion 600W', 199],
                    ['Midea Aspiradora Vertical 2 en 1', 329],
                    ['Oster Plancha Vapor Ceramica', 119],
                    ['Recco Olla Arrocera 1.8 L', 129],
                    ['Midea Ventilador Torre 32', 299],
                ],
            ],
            [
                'category' => 'Muebles y organizacion',
                'unit' => 'Unidad',
                'image' => 'home furniture storage organizer',
                'description' => 'Articulo para ordenar, amoblar o mejorar espacios del hogar.',
                'items' => [
                    ['Repisa Flotante Melamina Blanca 80 cm', 69],
                    ['Organizador Plastico 4 Niveles', 89],
                    ['Silla Plegable Negra', 59],
                    ['Mesa Auxiliar Redonda Roble', 129],
                    ['Zapatera Tela 6 Niveles', 79],
                    ['Caja Organizadora 50 L Transparente', 49],
                    ['Perchero Metalico de Pie', 99],
                    ['Canasta Rattan Rectangular', 39],
                    ['Estante Multiuso 5 Niveles', 179],
                    ['Cesto de Ropa con Tapa 60 L', 69],
                ],
            ],
            [
                'category' => 'Deportes y outdoor',
                'unit' => 'Unidad',
                'image' => 'sports fitness outdoor',
                'description' => 'Articulo deportivo para entrenamiento, recreacion o actividades al aire libre.',
                'items' => [
                    ['Balon Adidas Club Numero 5', 89],
                    ['Mat Yoga Antideslizante 6 mm', 49],
                    ['Mancuerna Neopreno 5 kg', 59],
                    ['Tomatodo Deportivo 1 L', 29],
                    ['Cuerda Saltar con Rodajes', 25],
                    ['Guantes Fitness Talla M', 39],
                    ['Pelota Pilates 65 cm', 55],
                    ['Set Bandas Elasticas Resistencia', 45],
                    ['Cooler Portatil 12 L', 79],
                    ['Silla Camping Plegable Azul', 89],
                ],
            ],
            [
                'category' => 'Bebes y ninos',
                'unit' => 'Paquete',
                'image' => 'baby products diapers toys',
                'description' => 'Producto para cuidado infantil, alimentacion, higiene o entretenimiento.',
                'items' => [
                    ['Pampers Confort Sec Talla M Pack 52', 49.90, 'Paquete'],
                    ['Huggies Natural Care Panitos Pack 80', 14.90, 'Paquete'],
                    ['Ninet Leche Formula Etapa 3 800 g', 69.90, 'Lata'],
                    ['Gerber Compota Manzana 113 g', 4.90, 'Frasco'],
                    ['Johnson Baby Shampoo 400 ml', 18.90, 'Frasco'],
                    ['Babysec Panal Premium Talla G Pack 46', 45.90, 'Paquete'],
                    ['Biberon Avent Natural 260 ml', 42.90, 'Unidad'],
                    ['Nuby Vaso Entrenador 240 ml', 24.90, 'Unidad'],
                    ['Play-Doh Set Colores Basico', 29.90, 'Caja'],
                    ['Hot Wheels Auto Basico Unidad', 9.90, 'Unidad'],
                ],
            ],
            [
                'category' => 'Mascotas',
                'unit' => 'Bolsa',
                'image' => 'pet food dog cat supermarket',
                'description' => 'Producto para alimentacion, higiene o cuidado de mascotas del hogar.',
                'items' => [
                    ['Ricocan Adulto Carne y Cereales 3 kg', 32.90, 'Bolsa'],
                    ['Ricocat Pescado Adulto 1 kg', 14.90, 'Bolsa'],
                    ['Mimaskot Cachorro 2 kg', 28.90, 'Bolsa'],
                    ['Pedigree Adulto Carne 2 kg', 34.90, 'Bolsa'],
                    ['Whiskas Carne Sachet 85 g', 3.20, 'Unidad'],
                    ['Arena Sanitaria Super Cat 4 kg', 18.90, 'Bolsa'],
                    ['Shampoo Canino Antipulgas 250 ml', 16.90, 'Frasco'],
                    ['Correa Nylon Mascota Mediana', 19.90, 'Unidad'],
                    ['Plato Acero Mascota 450 ml', 14.90, 'Unidad'],
                    ['Galletas Caninas Dentales Pack 7', 12.90, 'Pack'],
                ],
            ],
            [
                'category' => 'Panaderia y pasteleria',
                'unit' => 'Unidad',
                'image' => 'bakery bread pastry supermarket',
                'description' => 'Producto horneado para desayuno, lonchera, merienda o consumo familiar.',
                'items' => [
                    ['Pan Frances Bolsa 10 und', 4.90],
                    ['Pan Molde Integral Bimbo 600 g', 10.90],
                    ['Croissant Mantequilla Unidad', 3.50],
                    ['Empanada de Carne Horneada', 5.90],
                    ['Keke Vainilla Familiar 450 g', 12.90],
                    ['Torta Chocolate Porcion', 8.90],
                    ['Pan Ciabatta Pack 6 und', 6.90],
                    ['Muffin Arandanos Unidad', 4.90],
                    ['Pionono Manjar Blanco 500 g', 15.90],
                    ['Galleta Artesanal Avena 6 und', 7.90],
                ],
            ],
            [
                'category' => 'Congelados',
                'unit' => 'Bolsa',
                'image' => 'frozen food supermarket',
                'description' => 'Producto congelado para preparacion rapida y conservacion prolongada.',
                'items' => [
                    ['McCain Papas Prefritas 1 kg', 18.90, 'Bolsa'],
                    ['San Fernando Nuggets Pollo 400 g', 16.90, 'Bolsa'],
                    ['Tottus Mix Verduras Congeladas 500 g', 8.90, 'Bolsa'],
                    ['D Onofrio Helado Vainilla 1 L', 18.90, 'Litro'],
                    ['Pizza Familiar Americana Congelada', 21.90, 'Unidad'],
                    ['Filete Basa Congelado 500 g', 14.90, 'Bolsa'],
                    ['Hamburguesa Parrillera Congelada Pack 6', 24.90, 'Pack'],
                    ['Wong Tequenos Queso 500 g', 19.90, 'Bolsa'],
                    ['Frutos Rojos Congelados 400 g', 15.90, 'Bolsa'],
                    ['Lasagna Bolognesa Congelada 700 g', 22.90, 'Unidad'],
                ],
            ],
            [
                'category' => 'Cocina y menaje',
                'unit' => 'Unidad',
                'image' => 'kitchenware cookware dishes',
                'description' => 'Articulo de cocina para preparacion, servicio o conservacion de alimentos.',
                'items' => [
                    ['Sarten Antiadherente 24 cm', 69],
                    ['Olla Acero Inoxidable 5 L', 129],
                    ['Tabla Picar Bambu 35 cm', 39],
                    ['Set Cuchillos Cocina 5 Piezas', 89],
                    ['Taper Hermetico Vidrio 1 L', 29],
                    ['Jarra Medidora Plastica 1 L', 12],
                    ['Vaso Vidrio Alto Pack 6', 35],
                    ['Plato Tendido Ceramica Blanco', 14],
                    ['Escurridor Vajilla Compacto', 49],
                    ['Termo Acero Inoxidable 750 ml', 59],
                ],
            ],
            [
                'category' => 'Ferreteria basica',
                'unit' => 'Unidad',
                'image' => 'hardware tools home repair',
                'description' => 'Articulo basico para reparaciones, mantenimiento y mejoras del hogar.',
                'items' => [
                    ['Truper Martillo Una 16 oz', 39],
                    ['Stanley Destornillador Phillips 6', 18],
                    ['3M Cinta Aislante Negra', 8],
                    ['Tesa Cinta Doble Contacto 5 m', 16],
                    ['Candado Yale 40 mm', 29],
                    ['Foco LED Philips 9W Luz Calida', 9],
                    ['Extension Electrica 3 Tomas 5 m', 35],
                    ['Pila Duracell AA Pack 4', 18],
                    ['Silicona Transparente Tekbond 280 ml', 17],
                    ['Guantes Nitrilo Trabajo Par', 12],
                ],
            ],
            [
                'category' => 'Libreria y oficina',
                'unit' => 'Unidad',
                'image' => 'stationery office school supplies',
                'description' => 'Producto para estudio, oficina, impresion y organizacion documental.',
                'items' => [
                    ['Cuaderno Standford A4 100 Hojas', 12.90],
                    ['Lapicero Pilot BP-1 Azul', 2.50],
                    ['Resaltador Stabilo Boss Amarillo', 5.90],
                    ['Papel Bond A4 75 g Millar', 32.90],
                    ['Archivador Lomo Ancho Oficio', 9.90],
                    ['Post-it Notas Adhesivas 3x3', 8.90],
                    ['Tijera Escolar Maped 13 cm', 6.90],
                    ['Goma en Barra UHU 21 g', 5.90],
                    ['Folder Manila A4 Pack 25', 14.90],
                    ['Calculadora Casio MX-12B', 39.90],
                ],
            ],
            [
                'category' => 'Snacks y confiteria',
                'unit' => 'Paquete',
                'image' => 'snacks candy cookies supermarket',
                'description' => 'Producto de impulso para lonchera, antojo, reunion o consumo rapido.',
                'items' => [
                    ['Lays Papas Clasicas 140 g', 7.90],
                    ['Doritos Queso Atrevido 150 g', 8.50],
                    ['Chizitos Queso 190 g', 7.20],
                    ['Sublime Chocolate Clasico 30 g', 2.20],
                    ['Princesa Chocolate 30 g', 2.00],
                    ['Oreo Galletas Original 126 g', 4.90],
                    ['Casino Menta Pack 6', 5.50],
                    ['Field Soda Crackers 204 g', 4.80],
                    ['M&M Mani 45 g', 4.50],
                    ['Halls Miel Limon 25 g', 2.50],
                ],
            ],
        ];
    }
}
