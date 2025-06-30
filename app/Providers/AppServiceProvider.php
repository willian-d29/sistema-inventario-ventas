<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Inertia\Inertia;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    



public function boot(): void
{
    Inertia::share([
        'flash' => function () {
            return [
                'message' => session('flash.message'),
                'isSuccess' => session('flash.isSuccess'),
                'order_id' => session('order_id'), //  aquí lo expones a Vue
            ];
        },
    ]);
}


}
