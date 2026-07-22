<?php

namespace App\Services\DocumentLookup;

use App\Contracts\DocumentLookupServiceInterface;
use App\Data\DocumentLookupResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HttpDocumentLookupService implements DocumentLookupServiceInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function lookupDni(string $dni): DocumentLookupResult
    {
        return $this->lookup('dni', $dni);
    }

    public function lookupRuc(string $ruc): DocumentLookupResult
    {
        return $this->lookup('ruc', $ruc);
    }

    private function lookup(string $type, string $number): DocumentLookupResult
    {
        if (! $this->isConfigured()) {
            return DocumentLookupResult::notConfigured();
        }

        try {
            $path = $type === 'dni'
                ? (string) ($this->config['dni_path'] ?? '')
                : (string) ($this->config['ruc_path'] ?? '');

            $url = $this->buildUrl($path, $type, $number);
            $request = Http::acceptJson()->timeout((int) ($this->config['timeout'] ?? 5));

            if (filled($this->config['api_token'] ?? null)) {
                $request = $request->withToken((string) $this->config['api_token']);
            }

            $response = filled($path)
                ? $request->get($url)
                : $request->get($url, ['type' => $type, 'number' => $number]);

            if (! $response->successful()) {
                return DocumentLookupResult::notFound(true);
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                return DocumentLookupResult::notFound(true);
            }

            return $this->mapPayload($type, $number, $payload);
        } catch (ConnectionException) {
            return DocumentLookupResult::notFound(true, 'Error temporal; completa los datos manualmente.');
        } catch (Throwable $exception) {
            Log::warning('Document lookup provider failed.', [
                'type' => $type,
                'exception' => $exception::class,
            ]);

            return DocumentLookupResult::notFound(true, 'Error temporal; completa los datos manualmente.');
        }
    }

    private function isConfigured(): bool
    {
        return filled($this->config['api_url'] ?? null);
    }

    private function buildUrl(string $path, string $type, string $number): string
    {
        $baseUrl = rtrim((string) $this->config['api_url'], '/');
        if (blank($path)) {
            return $baseUrl;
        }

        $path = Str::of($path)
            ->replace('{type}', $type)
            ->replace('{number}', $number)
            ->trim('/')
            ->toString();

        return $baseUrl.'/'.$path;
    }

    private function mapPayload(string $type, string $number, array $payload): DocumentLookupResult
    {
        $data = $payload['data'] ?? $payload;
        if (! is_array($data)) {
            return DocumentLookupResult::notFound(true);
        }

        if ($type === 'dni') {
            $fullName = $data['full_name']
                ?? $data['nombre_completo']
                ?? $data['name']
                ?? null;

            return filled($fullName)
                ? DocumentLookupResult::found('dni', $number, ['full_name' => (string) $fullName])
                : DocumentLookupResult::notFound(true);
        }

        $businessName = $data['business_name']
            ?? $data['razon_social']
            ?? $data['name']
            ?? null;

        return filled($businessName)
            ? DocumentLookupResult::found('ruc', $number, [
                'business_name' => (string) $businessName,
                'trade_name' => $data['trade_name'] ?? $data['nombre_comercial'] ?? null,
                'address' => $data['address'] ?? $data['direccion'] ?? null,
            ])
            : DocumentLookupResult::notFound(true);
    }
}
