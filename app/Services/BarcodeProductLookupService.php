<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BarcodeProductLookupService
{
    public function lookup(string $barcode): array
    {
        $barcode = trim($barcode);

        if (! preg_match('/^\d{6,32}$/', $barcode)) {
            return $this->emptyResult($barcode);
        }

        try {
            $response = Http::acceptJson()
                ->withUserAgent('LaraTory/1.0 (local-pos@example.com)')
                ->timeout(4)
                ->retry(1, 200)
                ->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}.json", [
                    'fields' => 'code,status,product_name,generic_name,brands,categories_tags,image_front_url,image_url,quantity',
                ]);

            if (! $response->ok() || (int) $response->json('status') !== 1) {
                return $this->emptyResult($barcode);
            }

            $product = $response->json('product', []);
            $name = trim((string) ($product['product_name'] ?? $product['generic_name'] ?? ''));

            if ($name === '') {
                return $this->emptyResult($barcode);
            }

            $brand = trim((string) ($product['brands'] ?? ''));

            return [
                'found' => true,
                'source' => 'Open Food Facts',
                'barcode' => $barcode,
                'name' => Str::limit($name, 180, ''),
                'brand' => Str::limit($brand, 120, ''),
                'description' => trim(collect([$brand, $product['quantity'] ?? null])->filter()->join(' · ')),
                'image_url' => $product['image_front_url'] ?? $product['image_url'] ?? null,
                'suggested_category' => $this->cleanCategory($product['categories_tags'] ?? []),
            ];
        } catch (Throwable $exception) {
            Log::warning('Barcode lookup failed.', [
                'barcode' => $barcode,
                'message' => $exception->getMessage(),
            ]);

            return $this->emptyResult($barcode);
        }
    }

    private function emptyResult(string $barcode): array
    {
        return [
            'found' => false,
            'source' => null,
            'barcode' => $barcode,
            'name' => null,
            'brand' => null,
            'description' => null,
            'image_url' => null,
            'suggested_category' => null,
        ];
    }

    private function cleanCategory(array $tags): ?string
    {
        $tag = collect($tags)->last();

        if (! is_string($tag) || $tag === '') {
            return null;
        }

        return Str::of($tag)
            ->after(':')
            ->replace('-', ' ')
            ->title()
            ->toString();
    }
}
