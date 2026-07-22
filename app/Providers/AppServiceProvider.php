<?php

namespace App\Providers;

use App\Contracts\DocumentLookupServiceInterface;
use App\Services\DocumentLookup\HttpDocumentLookupService;
use App\Services\DocumentLookup\NullDocumentLookupService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentLookupServiceInterface::class, function () {
            $config = config('document_lookup');

            return ($config['provider'] ?? 'null') === 'http'
                ? new HttpDocumentLookupService($config)
                : new NullDocumentLookupService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
