<?php

namespace App\Services;

use App\Enums\Expense\ExpenseFieldsEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Helpers\BaseHelper;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function getForUser(User $user, ?string $date = null): array
    {
        $selectedDate = $date ?? date('Y-m');

        if ($user->role === 'cajero') {
            return [
                'dashboardRole' => 'cajero',
                'date' => $selectedDate,
                'cashierDashboard' => $this->cashierData($user),
            ];
        }

        return [
            ...$this->getData($date),
            'dashboardRole' => 'admin',
            'date' => $selectedDate,
        ];
    }

    public function getData(?string $date = null): array
    {
        $selectedDate = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();
        $monthStart = $selectedDate->startOfMonth();
        $monthEnd = $selectedDate->endOfMonth();
        $lastMonthStart = $monthStart->subMonth()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->endOfMonth();
        $todayStart = CarbonImmutable::now()->startOfDay();
        $todayEnd = CarbonImmutable::now()->endOfDay();

        $selectedSales = $this->salesBetween($monthStart, $monthEnd);
        $lastMonthSales = $this->salesBetween($lastMonthStart, $lastMonthEnd);

        $selectedMonthTotalSales = (clone $selectedSales)->count();
        $selectedMonthTotalRevenue = (float) (clone $selectedSales)->sum('total');
        $selectedMonthTotalDiscounts = (float) (clone $selectedSales)->sum('discount_total');
        $selectedMonthSaleItems = $this->saleItemsBetween($monthStart, $monthEnd);
        $selectedMonthCostOfGoodsSold = (float) (clone $selectedMonthSaleItems)->sum('cost_subtotal');
        $selectedMonthGrossProfit = (float) (clone $selectedMonthSaleItems)->sum('gross_profit');

        $lastMonthTotalSales = (clone $lastMonthSales)->count();
        $lastMonthTotalRevenue = (float) (clone $lastMonthSales)->sum('total');
        $lastMonthTotalDiscounts = (float) (clone $lastMonthSales)->sum('discount_total');
        $lastMonthSaleItems = $this->saleItemsBetween($lastMonthStart, $lastMonthEnd);
        $lastMonthCostOfGoodsSold = (float) (clone $lastMonthSaleItems)->sum('cost_subtotal');
        $lastMonthGrossProfit = (float) (clone $lastMonthSaleItems)->sum('gross_profit');

        $selectedMonthTotalExpenses = (float) Expense::query()
            ->whereBetween(ExpenseFieldsEnum::EXPENSE_DATE->value, [$monthStart, $monthEnd])
            ->sum(ExpenseFieldsEnum::AMOUNT->value);

        $lastMonthTotalExpenses = (float) Expense::query()
            ->whereBetween(ExpenseFieldsEnum::EXPENSE_DATE->value, [$lastMonthStart, $lastMonthEnd])
            ->sum(ExpenseFieldsEnum::AMOUNT->value);

        return [
            'total_orders' => $this->metric($selectedMonthTotalSales, $lastMonthTotalSales),
            'total_profit' => $this->metric($selectedMonthTotalRevenue, $lastMonthTotalRevenue),
            'total_loss' => $this->metric($selectedMonthGrossProfit, $lastMonthGrossProfit),
            'total_expense' => $this->metric($selectedMonthTotalExpenses, $lastMonthTotalExpenses),
            'cost_of_goods_sold' => $this->metric($selectedMonthCostOfGoodsSold, $lastMonthCostOfGoodsSold),
            'month_discounts' => $this->metric($selectedMonthTotalDiscounts, $lastMonthTotalDiscounts),
            'operating_result' => [
                'selected' => BaseHelper::numberFormat($selectedMonthGrossProfit - $selectedMonthTotalExpenses),
                'description' => 'Utilidad bruta menos gastos registrados.',
            ],
            'cost_warnings' => [
                'estimated_items' => (clone $selectedMonthSaleItems)->where('cost_is_estimated', true)->count(),
            ],
            'today_sales' => [
                'count' => $this->salesBetween($todayStart, $todayEnd)->count(),
                'total' => (float) $this->salesBetween($todayStart, $todayEnd)->sum('total'),
                'cost' => (float) $this->saleItemsBetween($todayStart, $todayEnd)->sum('cost_subtotal'),
                'gross_profit' => (float) $this->saleItemsBetween($todayStart, $todayEnd)->sum('gross_profit'),
            ],
            'payments_by_method' => $this->paymentsByMethod($monthStart, $monthEnd),
            'top_products' => $this->topProducts($monthStart, $monthEnd),
            'products_with_highest_profit' => $this->productsWithHighestProfit($monthStart, $monthEnd),
            'low_stock_products' => $this->lowStockProducts(),
            'recent_sales' => $this->recentSales(),
            'current_cash_registers' => $this->currentCashRegisters(),
            'open_cash_registers_count' => CashRegister::query()->where('status', 'open')->count(),
            'pending_differences_count' => CashRegister::query()
                ->where('status', 'closed')
                ->whereNotNull('total_difference')
                ->where('total_difference', '!=', 0)
                ->count(),
            'products_without_cost_count' => Product::query()
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->where(function (Builder $query) {
                    $query->whereNull('buying_price')->orWhere('buying_price', '<=', 0);
                })
                ->count(),
            'active_products_count' => Product::query()
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->count(),
            'profit_line_chart' => $this->prepareRevenueLineChart(),
            'orders_bar_chart' => $this->prepareSalesBarChart(),
        ];
    }

    private function cashierData(User $user): array
    {
        $cashRegister = CashRegister::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $salesQuery = Sale::query()
            ->with(['payments'])
            ->where('cashier_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->when(
                $cashRegister,
                fn (Builder $query) => $query->where('cash_register_id', $cashRegister->id),
                fn (Builder $query) => $query->whereBetween('sold_at', [now()->startOfDay(), now()->endOfDay()])
            );

        $sales = (clone $salesQuery)->latest('sold_at')->limit(5)->get();
        $summary = $cashRegister ? app(CashRegisterService::class)->summary($cashRegister) : null;
        $movements = $cashRegister
            ? app(CashRegisterService::class)->timelineItems($cashRegister)->take(6)->values()
            : collect();

        $pendingDifference = CashRegister::query()
            ->where('user_id', $user->id)
            ->where('status', 'closed')
            ->whereNotNull('total_difference')
            ->where('total_difference', '!=', 0)
            ->latest('closed_at')
            ->first(['id', 'total_difference', 'closed_at']);

        return [
            'cash_register' => $cashRegister,
            'cash_summary' => $summary,
            'is_open' => (bool) $cashRegister,
            'opened_at' => $cashRegister?->opened_at?->format('d/m/Y H:i'),
            'expected_cash' => $summary['expected_cash'] ?? 0,
            'sales_count' => (clone $salesQuery)->count(),
            'total_sold' => (float) (clone $salesQuery)->sum('total'),
            'last_sale' => $sales->first(),
            'recent_sales' => $sales->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'document' => $sale->full_document_number,
                'payments' => $sale->payments->pluck('payment_method')->join(' + '),
                'total' => (float) $sale->total,
                'sold_at' => $sale->sold_at?->format('d/m/Y H:i'),
            ])->all(),
            'movements' => $movements->all(),
            'pending_difference' => $pendingDifference,
        ];
    }

    private function salesBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Sale::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereBetween('sold_at', [$start, $end]);
    }

    private function saleItemsBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNotIn('sales.status', ['cancelled', 'refunded'])
            ->whereBetween('sales.sold_at', [$start, $end]);
    }

    private function metric(float|int $current, float|int $previous): array
    {
        $percentageChange = $previous != 0
            ? (($current - $previous) / $previous) * 100
            : 0;

        return [
            'selected' => is_float($current) ? (float) BaseHelper::numberFormat($current) : $current,
            'percentage_change' => abs(BaseHelper::numberFormat($percentageChange)),
            'stateArray' => $percentageChange < 0 ? 'down' : 'up',
        ];
    }

    private function paymentsByMethod(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $labels = collect(PaymentMethodEnum::options())->pluck('label', 'value');

        return Payment::query()
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->selectRaw('payments.payment_method, SUM(payments.amount) as total, COUNT(*) as count')
            ->whereNotIn('sales.status', ['cancelled', 'refunded'])
            ->whereBetween('sales.sold_at', [$start, $end])
            ->groupBy('payments.payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($payment) => [
                'method' => $payment->payment_method,
                'label' => $labels[$payment->payment_method] ?? ucfirst($payment->payment_method),
                'count' => (int) $payment->count,
                'total' => (float) BaseHelper::numberFormat((float) $payment->total),
            ])
            ->values()
            ->all();
    }

    private function topProducts(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('sale_items.product_name_snapshot as name, SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal - sale_items.discount) as total, SUM(sale_items.cost_subtotal) as cost, SUM(sale_items.gross_profit) as gross_profit')
            ->whereNotIn('sales.status', ['cancelled', 'refunded'])
            ->whereBetween('sales.sold_at', [$start, $end])
            ->groupBy('sale_items.product_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'total' => (float) BaseHelper::numberFormat((float) $item->total),
                'cost' => (float) BaseHelper::numberFormat((float) $item->cost),
                'gross_profit' => (float) BaseHelper::numberFormat((float) $item->gross_profit),
            ])
            ->all();
    }

    private function productsWithHighestProfit(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('sale_items.product_name_snapshot as name, SUM(sale_items.quantity) as quantity, SUM(sale_items.gross_profit) as gross_profit')
            ->whereNotIn('sales.status', ['cancelled', 'refunded'])
            ->whereBetween('sales.sold_at', [$start, $end])
            ->groupBy('sale_items.product_name_snapshot')
            ->orderByDesc('gross_profit')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'gross_profit' => (float) BaseHelper::numberFormat((float) $item->gross_profit),
            ])
            ->all();
    }

    private function lowStockProducts(): array
    {
        return Product::query()
            ->where('status', ProductStatusEnum::ACTIVE->value)
            ->where('quantity', '<', 10)
            ->orderBy('quantity')
            ->limit(8)
            ->get(['id', 'name', 'quantity'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => (float) $product->quantity,
            ])
            ->all();
    }

    private function recentSales(): array
    {
        return Sale::query()
            ->with(['cashier:id,name', 'payments'])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->latest('sold_at')
            ->limit(6)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'document' => $sale->full_document_number,
                'cashier' => $sale->cashier?->name ?: 'Sin cajero',
                'payments' => $sale->payments->pluck('payment_method')->join(' + '),
                'total' => (float) $sale->total,
                'sold_at' => $sale->sold_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    private function currentCashRegisters(): array
    {
        return CashRegister::query()
            ->with('user:id,name')
            ->where('status', 'open')
            ->latest('opened_at')
            ->get()
            ->map(fn (CashRegister $cashRegister) => [
                'id' => $cashRegister->id,
                'cashier' => $cashRegister->user?->name ?: 'Sin cajero',
                'opening_amount' => (float) $cashRegister->opening_amount,
                'opened_at' => $cashRegister->opened_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    private function prepareRevenueLineChart(): array
    {
        return $this->monthlyChart(fn (CarbonImmutable $start, CarbonImmutable $end) => (float) $this->salesBetween($start, $end)->sum('total'));
    }

    private function prepareSalesBarChart(): array
    {
        return $this->monthlyChart(fn (CarbonImmutable $start, CarbonImmutable $end) => $this->salesBetween($start, $end)->count());
    }

    private function monthlyChart(callable $calculator): array
    {
        $months = [];
        $currentYearValues = [];
        $lastYearValues = [];
        $now = CarbonImmutable::now();

        for ($i = 6; $i >= 0; $i--) {
            $month = $now->subMonths($i)->startOfMonth();
            $lastYearMonth = $month->subYear();

            $months[] = $month->format('F');
            $currentYearValues[] = (float) BaseHelper::numberFormat($calculator($month, $month->endOfMonth()));
            $lastYearValues[] = (float) BaseHelper::numberFormat($calculator($lastYearMonth, $lastYearMonth->endOfMonth()));
        }

        return [
            'months' => $months,
            'current_year' => $currentYearValues,
            'last_year' => $lastYearValues,
        ];
    }
}
