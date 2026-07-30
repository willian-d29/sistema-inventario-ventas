<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images
        {--limit=0 : Maximo de productos a procesar. 0 procesa todos}
        {--force : Reemplaza fotos existentes}
        {--skip-existing-urls : Ignora URLs externas guardadas y busca imagenes nuevas en Open Facts}
        {--dry-run : Simula la busqueda sin descargar ni guardar}';

    protected $description = 'Descarga imagenes reales de productos, las optimiza y las asigna al inventario local.';

    private const USER_AGENT = 'LaraTory/1.0 (demo product image enrichment; contact=local)';
    private const IMAGE_SIZE = 420;
    private const SEARCH_PAGE_SIZE = 8;

    public function handle(): int
    {
        Storage::disk('public')->makeDirectory(Product::PHOTO_PATH);

        $query = Product::query()
            ->with('category:id,name')
            ->orderBy('id');

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('No hay productos en la base de datos actual. Ejecuta primero el seeder o crea productos.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $attribution = [];
        $pools = null;
        $poolOffsets = [];

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            if (! $this->shouldReplace($product) && ! $this->option('force')) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $image = $this->option('skip-existing-urls') ? null : $this->existingExternalImage($product);

            if (! $image) {
                $pools ??= $this->buildImagePools($products);
                $image = $this->nextPooledImage($product, $pools, $poolOffsets) ?? $this->findImageFor($product);
            }

            if (! $image) {
                $failed++;
                $bar->advance();
                continue;
            }

            if ($this->option('dry-run')) {
                $this->newLine();
                $this->line("{$product->id}. {$product->name} -> {$image['url']}");
                $updated++;
                $bar->advance();
                continue;
            }

            $fileName = $this->downloadAndOptimize($product, $image['url']);

            if (! $fileName) {
                $failed++;
                $bar->advance();
                continue;
            }

            $this->deleteLocalPhoto($product->getRawOriginal('photo'));
            $product->forceFill(['photo' => $fileName])->save();
            $attribution[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'file' => Product::PHOTO_PATH.'/'.$fileName,
                'source' => $image['source'] ?? 'Open Facts',
                'source_url' => $image['product_url'] ?? $image['url'],
                'matched_name' => $image['name'] ?? null,
                'brand' => $image['brand'] ?? null,
                'license_note' => 'Open Facts product images are published under CC BY-SA according to Open Food Facts API documentation.',
            ];
            $updated++;
            $bar->advance();
        }

        if (! $this->option('dry-run') && $attribution !== []) {
            Storage::disk('public')->put(
                Product::PHOTO_PATH.'/open-facts-attribution.json',
                json_encode($attribution, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Imagenes asignadas: {$updated}");
        $this->line("Omitidos: {$skipped}");

        if ($failed > 0) {
            $this->warn("Sin imagen encontrada o descargable: {$failed}");
        }

        return self::SUCCESS;
    }

    private function shouldReplace(Product $product): bool
    {
        $photo = trim((string) $product->getRawOriginal('photo'));

        return $photo === ''
            || $photo === 'default-image.jpg'
            || str_starts_with($photo, 'http://')
            || str_starts_with($photo, 'https://')
            || ! Storage::disk('public')->exists(Product::PHOTO_PATH.'/'.$photo);
    }

    private function existingExternalImage(Product $product): ?array
    {
        $photo = trim((string) $product->getRawOriginal('photo'));

        if (! filter_var($photo, FILTER_VALIDATE_URL)) {
            return null;
        }

        return [
            'url' => $photo,
            'source' => str_contains($photo, 'openfoodfacts') ? 'Open Facts' : 'External seed image',
            'product_url' => $photo,
            'name' => $product->name,
            'brand' => null,
        ];
    }

    private function findImageFor(Product $product): ?array
    {
        foreach ($this->candidateQueries($product) as $query) {
            foreach ($this->candidateHosts($product) as $host) {
                $result = $this->searchOpenFactsImages($host, $query, self::SEARCH_PAGE_SIZE)[0] ?? null;

                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    private function buildImagePools($products): array
    {
        $pools = [];

        foreach ($products->groupBy(fn (Product $product) => $product->category?->name ?? 'General') as $category => $categoryProducts) {
            $probe = $categoryProducts->first();
            $target = min(max($categoryProducts->count() * 2, 16), 80);
            $images = [];

            foreach ($this->poolQueriesForCategory($category) as $query) {
                foreach ($this->candidateHosts($probe) as $host) {
                    $images = array_merge($images, $this->searchOpenFactsImages($host, $query, $target));
                    $images = $this->uniqueImages($images);

                    if (count($images) >= $categoryProducts->count()) {
                        break 2;
                    }
                }
            }

            $pools[$category] = $images;
        }

        return $pools;
    }

    private function nextPooledImage(Product $product, array $pools, array &$offsets): ?array
    {
        $category = $product->category?->name ?? 'General';
        $pool = $pools[$category] ?? [];

        if ($pool === []) {
            return null;
        }

        $offsets[$category] ??= 0;
        $image = $pool[$offsets[$category] % count($pool)];
        $offsets[$category]++;

        return $image;
    }

    private function poolQueriesForCategory(string $category): array
    {
        $key = Str::lower(Str::ascii($category));

        $map = [
            'abarrotes' => ['rice pasta beans cereal oil supermarket', 'pantry grocery'],
            'bebidas' => ['soda water juice beverage supermarket', 'soft drink bottle'],
            'lacteos y huevos' => ['milk cheese yogurt eggs dairy', 'dairy product'],
            'frutas y verduras' => ['fruit vegetables fresh produce supermarket', 'fresh produce'],
            'carnes y pescados' => ['meat fish chicken supermarket', 'packaged meat'],
            'limpieza del hogar' => ['detergent disinfectant cleaning product', 'household cleaning'],
            'higiene personal' => ['shampoo toothpaste soap deodorant', 'personal care product'],
            'belleza y cuidado' => ['cosmetics skincare cream beauty', 'beauty care product'],
            'tecnologia' => ['smartphone headphones electronics', 'consumer electronics'],
            'electrohogar' => ['blender microwave appliance kettle', 'home appliance'],
            'muebles y organizacion' => ['storage organizer furniture home', 'home organization'],
            'deportes y outdoor' => ['sports fitness bottle yoga ball', 'fitness product'],
            'bebes y ninos' => ['baby diapers wipes toys', 'baby product'],
            'mascotas' => ['dog food cat food pet product', 'pet food'],
            'panaderia y pasteleria' => ['bread cookies pastry bakery', 'bakery product'],
            'congelados' => ['frozen food pizza ice cream', 'frozen product'],
            'cocina y menaje' => ['cookware kitchenware dishes pan', 'kitchen product'],
            'ferreteria basica' => ['tools tape batteries hardware', 'home repair product'],
            'libreria y oficina' => ['notebook pen paper office supplies', 'stationery product'],
            'snacks y confiteria' => ['chips chocolate candy cookies snack', 'snack product'],
        ];

        return collect([
            ...($map[$key] ?? []),
            $this->cleanSearchText($category),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function candidateQueries(Product $product): array
    {
        $name = $this->cleanSearchText($product->name);
        $category = $this->cleanSearchText($product->category?->name ?? '');
        $withoutSize = trim((string) preg_replace('/\b\d+([.,]\d+)?\s?(kg|g|gr|ml|l|lt|und|pack|pzas|piezas|cm|mm|w|gb|tb)\b/i', '', $name));
        $words = collect(explode(' ', $withoutSize))
            ->filter(fn (string $word) => mb_strlen($word) > 2)
            ->take(5)
            ->implode(' ');

        return collect([
            $name,
            $withoutSize,
            "{$withoutSize} {$category}",
            $words,
            $category,
        ])
            ->map(fn (string $value) => trim(preg_replace('/\s+/', ' ', $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function candidateHosts(Product $product): array
    {
        $category = Str::lower(Str::ascii($product->category?->name ?? ''));

        $food = 'https://world.openfoodfacts.org';
        $beauty = 'https://world.openbeautyfacts.org';
        $pet = 'https://world.openpetfoodfacts.org';
        $products = 'https://world.openproductsfacts.org';

        if (str_contains($category, 'belleza') || str_contains($category, 'higiene')) {
            return [$beauty, $food, $products];
        }

        if (str_contains($category, 'mascota')) {
            return [$pet, $food, $products];
        }

        if (str_contains($category, 'tecnologia')
            || str_contains($category, 'electro')
            || str_contains($category, 'mueble')
            || str_contains($category, 'deporte')
            || str_contains($category, 'cocina')
            || str_contains($category, 'ferreteria')
            || str_contains($category, 'libreria')
        ) {
            return [$products, $food, $beauty];
        }

        return [$food, $products, $beauty];
    }

    private function searchOpenFactsImages(string $host, string $terms, int $pageSize): array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->retry(2, 250)
                ->get("{$host}/cgi/search.pl", [
                    'search_terms' => $terms,
                    'search_simple' => 1,
                    'action' => 'process',
                    'json' => 1,
                    'page_size' => $pageSize,
                    'fields' => 'code,product_name,brands,image_front_small_url,image_small_url,image_url',
                ]);
        } catch (Throwable) {
            return [];
        }

        if (! $response->ok()) {
            return [];
        }

        $products = $response->json('products') ?? [];
        $images = [];

        foreach ($products as $item) {
            $url = $item['image_front_small_url']
                ?? $item['image_small_url']
                ?? $item['image_url']
                ?? null;

            if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                $code = $item['code'] ?? null;
                $images[] = [
                    'url' => $url,
                    'source' => Str::of($host)->after('https://')->before('.org')->replace('world.', 'Open ')->headline()->toString(),
                    'product_url' => $code ? "{$host}/product/{$code}" : $url,
                    'name' => $item['product_name'] ?? null,
                    'brand' => $item['brands'] ?? null,
                ];
            }
        }

        return $this->uniqueImages($images);
    }

    private function uniqueImages(array $images): array
    {
        return collect($images)
            ->unique('url')
            ->values()
            ->all();
    }

    private function downloadAndOptimize(Product $product, string $url): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->connectTimeout(4)
                ->timeout(8)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->ok() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        $binary = $this->normalizeImage($response->body());
        if (! $binary) {
            return null;
        }

        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';
        $fileName = Str::slug(Str::ascii($product->name)).'-'.$product->id.'.'.$extension;

        Storage::disk('public')->put(Product::PHOTO_PATH.'/'.$fileName, $binary);

        return $fileName;
    }

    private function normalizeImage(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $contents;
        }

        $source = @imagecreatefromstring($contents);
        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);
            return null;
        }

        $canvas = imagecreatetruecolor(self::IMAGE_SIZE, self::IMAGE_SIZE);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $scale = min(self::IMAGE_SIZE / $width, self::IMAGE_SIZE / $height);
        $targetWidth = max(1, (int) floor($width * $scale));
        $targetHeight = max(1, (int) floor($height * $scale));
        $targetX = (int) floor((self::IMAGE_SIZE - $targetWidth) / 2);
        $targetY = (int) floor((self::IMAGE_SIZE - $targetHeight) / 2);

        imagecopyresampled($canvas, $source, $targetX, $targetY, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($canvas, null, 78);
        } else {
            imagejpeg($canvas, null, 82);
        }
        $binary = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $binary ?: null;
    }

    private function deleteLocalPhoto(?string $photo): void
    {
        $photo = trim((string) $photo);

        if ($photo === ''
            || $photo === 'default-image.jpg'
            || str_starts_with($photo, 'http://')
            || str_starts_with($photo, 'https://')
        ) {
            return;
        }

        Storage::disk('public')->delete(Product::PHOTO_PATH.'/'.$photo);
    }

    private function cleanSearchText(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9\s]/', ' ', Str::ascii($value)));
    }
}
