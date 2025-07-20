<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
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
        // Flash global (ya lo tenías)
        Inertia::share([
            'flash' => function () {
                return [
                    'message' => session('message'),
                    'isSuccess' => session('isSuccess'),
                    'order_id' => session('order_id'),
                ];
            },
        ]);

        //  Usuario autenticado compartido globalmente (INCLUYE address y phone)
        Inertia::share('auth', [
            'user' => fn () => Auth::check()
                ? Auth::user()->only([
                    'id',
                    'name',
                    'email',
                    'photo',
                    'address',
                    'phone',
                    'role'
                ])
                : null,
        ]);
    }
}
