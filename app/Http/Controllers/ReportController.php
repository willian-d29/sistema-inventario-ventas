<?php

namespace App\Http\Controllers;

use App\Enums\Transaction\PaymentMethodEnum;
use App\Exports\VentasExport;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $sales = $this->salesQuery($filters);
        $saleItems = $this->saleItemsQuery($filters);
        $netProductIncome = (float) (clone $saleItems)->sum(DB::raw('sale_items.subtotal - sale_items.discount'));
        $grossProfit = (float) (clone $saleItems)->sum('sale_items.gross_profit');

        $summary = [
            'totalVentas' => (float) (clone $sales)->sum('total'),
            'ingresoNetoProductos' => $netProductIncome,
            'totalCosto' => (float) (clone $saleItems)->sum('sale_items.cost_subtotal'),
            'utilidadBruta' => $grossProfit,
            'margenBruto' => $this->grossMargin($grossProfit, $netProductIncome),
            'totalDescuentos' => (float) (clone $sales)->sum('discount_total'),
            'totalComprobantes' => (clone $sales)->count(),
            'totalItems' => (float) (clone $saleItems)->sum('sale_items.quantity'),
            'ventasPorMetodo' => $this->paymentsByMethod($filters),
            'ventasPorProducto' => $this->salesByProduct($filters),
            'ventasPorCategoria' => $this->salesByCategory($filters),
            'ventasPorCajero' => $this->salesByCashier($filters),
            'ventasPorDocumento' => $this->salesByDocumentType($filters),
        ];

        return Inertia::render('Reports/Index', [
            'summary' => $summary,
            'filters' => $filters,
            'cashiers' => User::query()
                ->whereIn('role', ['admin', 'cajero'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'paymentMethods' => PaymentMethodEnum::options(),
            'documentTypes' => $this->documentTypeOptions(),
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name', 'product_code', 'barcode']),
        ]);
    }

    public function ventasPDF(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $ventas = $this->salesQuery($filters)
            ->with(['cashier', 'payments', 'items'])
            ->latest('sold_at')
            ->get();

        $paymentSummary = $this->paymentsByMethod($filters);
        $productSummary = $this->salesByProduct($filters);
        $categorySummary = $this->salesByCategory($filters);
        $cashierSummary = $this->salesByCashier($filters);
        $documentSummary = $this->salesByDocumentType($filters);
        $itemSummary = [
            'income' => (float) $this->saleItemsQuery($filters)->sum(DB::raw('sale_items.subtotal - sale_items.discount')),
            'cost' => (float) $this->saleItemsQuery($filters)->sum('sale_items.cost_subtotal'),
            'gross_profit' => (float) $this->saleItemsQuery($filters)->sum('sale_items.gross_profit'),
            'discount' => (float) $this->saleItemsQuery($filters)->sum('sale_items.discount'),
            'quantity' => (float) $this->saleItemsQuery($filters)->sum('sale_items.quantity'),
        ];
        $itemSummary['margin'] = $this->grossMargin($itemSummary['gross_profit'], $itemSummary['income']);

        $pdf = Pdf::loadView(
            'reports.ventas-pdf',
            compact('ventas', 'filters', 'paymentSummary', 'productSummary', 'categorySummary', 'cashierSummary', 'documentSummary', 'itemSummary')
        )
            ->setPaper('a4', 'portrait');

        return $pdf->download('reporte_ventas.pdf');
    }

    public function ventasExcel(Request $request)
    {
        return Excel::download(new VentasExport($this->validatedFilters($request)), 'reporte_ventas.xlsx');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method' => ['nullable', 'string', Rule::in(PaymentMethodEnum::values())],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'document_type' => ['nullable', 'string', Rule::in(['receipt', 'invoice'])],
        ]);
    }

    private function salesQuery(array $filters): Builder
    {
        return Sale::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->when($filters['date_from'] ?? null, function (Builder $query, string $date) {
                $query->where('sold_at', '>=', Carbon::parse($date)->startOfDay());
            })
            ->when($filters['date_to'] ?? null, function (Builder $query, string $date) {
                $query->where('sold_at', '<=', Carbon::parse($date)->endOfDay());
            })
            ->when($filters['cashier_id'] ?? null, fn (Builder $query, int $cashierId) => $query->where('cashier_id', $cashierId))
            ->when($filters['payment_method'] ?? null, function (Builder $query, string $method) {
                $query->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('payment_method', $method));
            })
            ->when($filters['product_id'] ?? null, function (Builder $query, int $productId) {
                $query->whereHas('items', fn (Builder $itemQuery) => $itemQuery->where('product_id', $productId));
            })
            ->when($filters['document_type'] ?? null, function (Builder $query, string $documentType) {
                $query->where('document_type', $documentType);
            });
    }

    private function paymentSaleFilters(Builder $query, array $filters): void
    {
        $query->whereNotIn('status', ['cancelled', 'refunded'])
            ->when($filters['date_from'] ?? null, function (Builder $saleQuery, string $date) {
                $saleQuery->where('sold_at', '>=', Carbon::parse($date)->startOfDay());
            })
            ->when($filters['date_to'] ?? null, function (Builder $saleQuery, string $date) {
                $saleQuery->where('sold_at', '<=', Carbon::parse($date)->endOfDay());
            })
            ->when($filters['cashier_id'] ?? null, fn (Builder $saleQuery, int $cashierId) => $saleQuery->where('cashier_id', $cashierId))
            ->when($filters['product_id'] ?? null, function (Builder $saleQuery, int $productId) {
                $saleQuery->whereHas('items', fn (Builder $itemQuery) => $itemQuery->where('product_id', $productId));
            })
            ->when($filters['document_type'] ?? null, function (Builder $saleQuery, string $documentType) {
                $saleQuery->where('document_type', $documentType);
            });
    }

    private function paymentsByMethod(array $filters): array
    {
        $labels = collect(PaymentMethodEnum::options())->pluck('label', 'value');

        return Payment::query()
            ->whereHas('sale', fn (Builder $query) => $this->paymentSaleFilters($query, $filters))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method))
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($payment) => [
                'method' => $payment->payment_method,
                'label' => $labels[$payment->payment_method] ?? ucfirst($payment->payment_method),
                'count' => (int) $payment->count,
                'total' => (float) $payment->total,
            ])
            ->values()
            ->all();
    }

    private function saleItemsQuery(array $filters): Builder
    {
        return SaleItem::query()
            ->whereHas('sale', function (Builder $query) use ($filters) {
                $this->paymentSaleFilters($query, $filters);

                if ($filters['payment_method'] ?? null) {
                    $query->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('payment_method', $filters['payment_method']));
                }
            })
            ->when($filters['product_id'] ?? null, fn (Builder $query, int $productId) => $query->where('product_id', $productId));
    }

    private function salesByProduct(array $filters): array
    {
        return $this->saleItemsQuery($filters)
            ->selectRaw('product_name_snapshot as name, SUM(quantity) as quantity, SUM(subtotal - discount) as total, SUM(cost_subtotal) as cost, SUM(gross_profit) as gross_profit, SUM(discount) as discount')
            ->groupBy('product_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'total' => (float) $item->total,
                'cost' => (float) $item->cost,
                'gross_profit' => (float) $item->gross_profit,
                'margin' => $this->grossMargin((float) $item->gross_profit, (float) $item->total),
                'discount' => (float) $item->discount,
            ])
            ->all();
    }

    private function salesByCategory(array $filters): array
    {
        return $this->saleItemsQuery($filters)
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Sin categoría') as name, SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal - sale_items.discount) as total, SUM(sale_items.cost_subtotal) as cost, SUM(sale_items.gross_profit) as gross_profit")
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($category) => [
                'name' => $category->name,
                'quantity' => (float) $category->quantity,
                'total' => (float) $category->total,
                'cost' => (float) $category->cost,
                'gross_profit' => (float) $category->gross_profit,
                'margin' => $this->grossMargin((float) $category->gross_profit, (float) $category->total),
            ])
            ->all();
    }

    private function salesByCashier(array $filters): array
    {
        return $this->salesQuery($filters)
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->selectRaw('users.name as name, COUNT(*) as count, SUM(sales.total) as total')
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($cashier) => [
                'name' => $cashier->name,
                'count' => (int) $cashier->count,
                'total' => (float) $cashier->total,
            ])
            ->all();
    }

    private function salesByDocumentType(array $filters): array
    {
        $labels = collect($this->documentTypeOptions())->pluck('label', 'value');

        return $this->salesQuery($filters)
            ->selectRaw('document_type, COUNT(*) as count, SUM(total) as total')
            ->groupBy('document_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($document) => [
                'document_type' => $document->document_type,
                'label' => $labels[$document->document_type] ?? $document->document_type,
                'count' => (int) $document->count,
                'total' => (float) $document->total,
            ])
            ->all();
    }

    private function grossMargin(float $grossProfit, float $netIncome): float
    {
        if ($netIncome <= 0) {
            return 0.0;
        }

        return round(($grossProfit / $netIncome) * 100, 2);
    }

    private function documentTypeOptions(): array
    {
        return [
            ['value' => 'receipt', 'label' => 'Boleta'],
            ['value' => 'invoice', 'label' => 'Factura'],
        ];
    }
}
