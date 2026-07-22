<?php

namespace App\Http\Middleware;

use App\Services\BusinessSettingsService;
use App\Models\CashRegister;
use App\Services\UserPreferenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(app(UserPreferenceService::class)->forUser($request->user())['locale'] ?? 'es');

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $preferences = fn () => app(UserPreferenceService::class)->forUser($request->user());

        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'photo' => $request->user()->photo,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'preferences' => $preferences(),
                ] : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'isSuccess' => fn () => $request->session()->get('flash')['isSuccess'] ?? true,
                'message' => fn () => $request->session()->get('flash')['message'] ?? null,
            ],
            'businessSettings' => fn () => app(BusinessSettingsService::class)->public(),
            'userPreferences' => $preferences,
            'currency' => fn () => app(BusinessSettingsService::class)->getCurrencySymbol(),
            'decimal_point' => fn () => app(BusinessSettingsService::class)->getDecimalPoint(),
            'currentCashRegister' => fn () => $request->user()
                ? CashRegister::query()
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'open')
                    ->latest('opened_at')
                    ->first(['id', 'opened_at', 'opening_amount', 'status'])
                : null,
        ];
    }
}
