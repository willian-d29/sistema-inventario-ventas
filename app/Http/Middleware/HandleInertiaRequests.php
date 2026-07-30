<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use App\Models\Product;
use App\Services\BusinessSettingsService;
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
        $preferences = null;
        $preferencesResolver = function () use ($request, &$preferences): array {
            if ($preferences === null) {
                $preferences = app(UserPreferenceService::class)->forUser($request->user());
            }

            return $preferences;
        };

        $businessSettings = null;
        $businessSettingsResolver = function () use (&$businessSettings): array {
            if ($businessSettings === null) {
                $businessSettings = app(BusinessSettingsService::class)->public();
            }

            return $businessSettings;
        };

        $currentCashRegister = null;
        $currentCashRegisterResolved = false;
        $currentCashRegisterResolver = function () use ($request, &$currentCashRegister, &$currentCashRegisterResolved): ?CashRegister {
            if (! $request->user()) {
                return null;
            }

            if (! $currentCashRegisterResolved) {
                $currentCashRegister = CashRegister::query()
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'open')
                    ->latest('opened_at')
                    ->first(['id', 'user_id', 'opened_at', 'opening_amount', 'status']);
                $currentCashRegisterResolved = true;
            }

            return $currentCashRegister;
        };

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
                    'preferences' => $preferencesResolver(),
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
            'businessSettings' => $businessSettingsResolver,
            'userPreferences' => $preferencesResolver,
            'currency' => fn () => $businessSettingsResolver()['currency_symbol'] ?? app(BusinessSettingsService::class)->getCurrencySymbol(),
            'decimal_point' => fn () => $businessSettingsResolver()['decimal_point'] ?? app(BusinessSettingsService::class)->getDecimalPoint(),
            'currentCashRegister' => $currentCashRegisterResolver,
            'appNotifications' => fn () => $this->notificationsFor($request, $currentCashRegisterResolver()),
        ];
    }

    private function notificationsFor(Request $request, ?CashRegister $currentCashRegister = null): array
    {
        $user = $request->user();

        if (! $user) {
            return ['items' => [], 'count' => 0];
        }

        $items = [];
        if ($user->role === 'cajero') {
            if (! $currentCashRegister) {
                $items[] = [
                    'id' => 'cash-closed',
                    'tone' => 'warning',
                    'icon' => 'fa-lock',
                    'title_key' => 'notifications.cash_closed_title',
                    'body_key' => 'notifications.cash_closed_body',
                    'route_name' => 'cash-registers.index',
                    'action_key' => 'notifications.open_cash',
                ];
            }

            $pendingDifference = CashRegister::query()
                ->where('user_id', $user->id)
                ->where('status', 'closed')
                ->whereNotNull('total_difference')
                ->where('total_difference', '!=', 0)
                ->latest('closed_at')
                ->first(['id', 'total_difference']);

            if ($pendingDifference) {
                $items[] = [
                    'id' => 'cash-difference-'.$pendingDifference->id,
                    'tone' => 'danger',
                    'icon' => 'fa-balance-scale',
                    'title_key' => 'notifications.cash_difference_title',
                    'body_key' => 'notifications.cash_difference_body',
                    'body_params' => ['amount' => number_format((float) $pendingDifference->total_difference, 2)],
                    'route_name' => 'cash-registers.index',
                    'action_key' => 'notifications.review_cash',
                ];
            }
        }

        $stockCounters = Product::query()
            ->where('status', 'active')
            ->selectRaw('
                SUM(CASE WHEN quantity > 0 AND quantity < 10 THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_count
            ')
            ->first();
        $lowStockCount = (int) ($stockCounters->low_stock_count ?? 0);
        $outOfStockCount = (int) ($stockCounters->out_of_stock_count ?? 0);

        if ($outOfStockCount > 0) {
            $items[] = [
                'id' => 'out-of-stock',
                'tone' => 'danger',
                'icon' => 'fa-times-circle',
                'title_key' => 'notifications.out_stock_title',
                'body_key' => 'notifications.out_stock_body',
                'body_params' => ['count' => $outOfStockCount],
                'route_name' => 'products.index',
                'route_params' => ['quantities' => [0, 0]],
                'action_key' => 'notifications.view_products',
            ];
        }

        if ($lowStockCount > 0) {
            $items[] = [
                'id' => 'low-stock',
                'tone' => 'warning',
                'icon' => 'fa-exclamation-triangle',
                'title_key' => 'notifications.low_stock_title',
                'body_key' => 'notifications.low_stock_body',
                'body_params' => ['count' => $lowStockCount],
                'route_name' => 'products.index',
                'route_params' => ['quantities' => [1, 9]],
                'action_key' => 'notifications.view_products',
            ];
        }

        if ($user->role === 'admin') {
            $pendingDifferencesCount = CashRegister::query()
                ->where('status', 'closed')
                ->whereNotNull('total_difference')
                ->where('total_difference', '!=', 0)
                ->count();

            if ($pendingDifferencesCount > 0) {
                $items[] = [
                    'id' => 'cash-differences',
                    'tone' => 'danger',
                    'icon' => 'fa-balance-scale',
                    'title_key' => 'notifications.cash_differences_title',
                    'body_key' => 'notifications.cash_differences_body',
                    'body_params' => ['count' => $pendingDifferencesCount],
                    'route_name' => 'cash-registers.index',
                    'route_params' => ['status' => 'closed'],
                    'action_key' => 'notifications.review_cash',
                ];
            }

            $withoutCostCount = Product::query()
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('buying_price')->orWhere('buying_price', '<=', 0);
                })
                ->count();

            if ($withoutCostCount > 0) {
                $items[] = [
                    'id' => 'products-without-cost',
                    'tone' => 'info',
                    'icon' => 'fa-tags',
                    'title_key' => 'notifications.products_without_cost_title',
                    'body_key' => 'notifications.products_without_cost_body',
                    'body_params' => ['count' => $withoutCostCount],
                    'route_name' => 'products.index',
                    'action_key' => 'notifications.view_products',
                ];
            }
        }

        return [
            'items' => array_values($items),
            'count' => count($items),
        ];
    }
}
