<?php

namespace App\Services;

use App\Enums\Core\SortOrderEnum;
use App\Enums\CashRegister\CashRegisterStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\StockMovementTypeEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Exceptions\SaleCreateException;
use App\Helpers\BaseHelper;
use App\Models\Cart;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly DocumentSequenceService $documentSequenceService,
        private readonly CashRegisterService $cashRegisterService
    ) {
    }

    public function getAll(array $queryParameters): LengthAwarePaginator
    {
        $perPage = BaseHelper::perPage($queryParameters['per_page'] ?? null);
        $sortOrder = $queryParameters['sort_order'] ?? SortOrderEnum::DESC->value;

        return Sale::query()
            ->with(['cashier', 'cashRegister', 'payments', 'documentPrintLogs'])
            ->when(filled($queryParameters['cashier_id'] ?? null), function ($query) use ($queryParameters) {
                $query->where('cashier_id', $queryParameters['cashier_id']);
            })
            ->when(filled($queryParameters['document_type'] ?? null), function ($query) use ($queryParameters) {
                $query->where('document_type', $queryParameters['document_type']);
            })
            ->when(filled($queryParameters['full_document_number'] ?? null), function ($query) use ($queryParameters) {
                $query->where('full_document_number', 'like', '%'.$queryParameters['full_document_number'].'%');
            })
            ->when(filled($queryParameters['payment_method'] ?? null), function ($query) use ($queryParameters) {
                $query->whereHas('payments', fn ($paymentQuery) => $paymentQuery->where('payment_method', $queryParameters['payment_method']));
            })
            ->when(filled($queryParameters['status'] ?? null), function ($query) use ($queryParameters) {
                $query->where('status', $queryParameters['status']);
            })
            ->when(filled($queryParameters['date_from'] ?? null), function ($query) use ($queryParameters) {
                $query->where('sold_at', '>=', Carbon::parse($queryParameters['date_from'])->startOfDay());
            })
            ->when(filled($queryParameters['date_to'] ?? null), function ($query) use ($queryParameters) {
                $query->where('sold_at', '<=', Carbon::parse($queryParameters['date_to'])->endOfDay());
            })
            ->when(filled($queryParameters['amount_min'] ?? null), function ($query) use ($queryParameters) {
                $query->where('total', '>=', $queryParameters['amount_min']);
            })
            ->when(filled($queryParameters['amount_max'] ?? null), function ($query) use ($queryParameters) {
                $query->where('total', '<=', $queryParameters['amount_max']);
            })
            ->orderBy('sold_at', $sortOrder)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findVisibleOrFail(int $id, ?int $cashierId = null): Sale
    {
        return Sale::query()
            ->with(['cashier', 'cashRegister.user', 'items', 'payments', 'documentPrintLogs.user:id,name'])
            ->when($cashierId, fn ($query) => $query->where('cashier_id', $cashierId))
            ->findOrFail($id);
    }

    public function createForUser(array $payload, int $userId): Sale
    {
        return DB::transaction(function () use ($payload, $userId) {
            $cashRegister = CashRegister::query()
                ->where('user_id', $userId)
                ->where('status', CashRegisterStatusEnum::OPEN->value)
                ->lockForUpdate()
                ->first();

            if (! $cashRegister) {
                throw new SaleCreateException('Debes abrir caja antes de registrar una venta.');
            }

            $documentType = $payload['document_type'] ?? 'receipt';

            $carts = Cart::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->get();

            if ($carts->isEmpty()) {
                throw new SaleCreateException('La venta está vacía.');
            }

            $subtotal = 0.0;
            $items = [];
            $lockedProducts = [];

            foreach ($carts as $cart) {
                $product = Product::query()->lockForUpdate()->find($cart->product_id);
                if (! $product || $product->status !== ProductStatusEnum::ACTIVE->value) {
                    throw new SaleCreateException("El producto {$cart->product_id} no está disponible.");
                }

                if ((float) $cart->quantity > (float) $product->quantity) {
                    throw new SaleCreateException("Stock insuficiente para {$product->name}.");
                }

                $lineSubtotal = BaseHelper::numberFormat((float) $product->selling_price * (float) $cart->quantity);
                $subtotal += $lineSubtotal;
                $unitCost = BaseHelper::numberFormat(max((float) ($product->buying_price ?? 0), 0));
                $costSubtotal = BaseHelper::numberFormat($unitCost * (float) $cart->quantity);

                $items[] = [
                    'product' => $product,
                    'quantity' => (float) $cart->quantity,
                    'unit_price' => (float) $product->selling_price,
                    'subtotal' => $lineSubtotal,
                    'unit_cost' => $unitCost,
                    'cost_subtotal' => $costSubtotal,
                    'cost_is_estimated' => $unitCost <= 0,
                ];
                $lockedProducts[] = [$product, (float) $cart->quantity];
            }

            $discountTotal = $this->calculateDiscount($payload, $subtotal);
            if ($discountTotal > $subtotal) {
                throw new SaleCreateException('El descuento no puede superar el subtotal.');
            }

            $taxableAmount = BaseHelper::numberFormat($subtotal - $discountTotal);
            $igv = BaseHelper::calculateTax($taxableAmount)['totalTax'];
            $total = BaseHelper::numberFormat($taxableAmount + $igv);

            $payments = $payload['payments'] ?? [];
            if (count($payments) > 2) {
                throw new SaleCreateException('El pago mixto permite máximo 2 métodos por ahora.');
            }

            $methods = collect($payments)->pluck('method');
            if ($methods->count() !== $methods->unique()->count()) {
                throw new SaleCreateException('No repitas el mismo método de pago en una venta mixta.');
            }

            $paid = BaseHelper::numberFormat(collect($payments)->sum(fn (array $payment) => (float) $payment['amount']));
            if (abs($paid - $total) > 0.01) {
                throw new SaleCreateException('La suma de los pagos debe coincidir con el total de la venta.');
            }

            foreach ($payments as $payment) {
                if ($payment['method'] === PaymentMethodEnum::CASH->value
                    && (float) ($payment['received_amount'] ?? 0) < (float) $payment['amount']) {
                    throw new SaleCreateException('El efectivo recibido no cubre el monto indicado.');
                }
            }

            $sequence = $this->documentSequenceService->next($documentType);
            $sale = Sale::create([
                'cash_register_id' => $cashRegister->id,
                'cashier_id' => $userId,
                'customer_id' => null,
                'document_type' => $documentType,
                'document_series' => $sequence['series'],
                'document_number' => $sequence['number'],
                'full_document_number' => $sequence['full_number'],
                'issue_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'taxable_amount' => $taxableAmount,
                'igv' => $igv,
                'total' => $total,
                'status' => 'paid',
                'sold_at' => now(),
            ]);

            $allocatedItems = $this->allocateDiscountToItems($items, $subtotal, $discountTotal);
            foreach ($allocatedItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'product_name_snapshot' => $item['product']->name,
                    'product_code_snapshot' => $item['product']->barcode
                        ?: ($item['product']->product_code ?: $item['product']->product_number),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                    'unit_cost' => $item['unit_cost'],
                    'cost_subtotal' => $item['cost_subtotal'],
                    'gross_profit' => $item['gross_profit'],
                    'cost_is_estimated' => $item['cost_is_estimated'],
                ]);
            }

            foreach ($payments as $payment) {
                $received = $payment['method'] === PaymentMethodEnum::CASH->value
                    ? (float) ($payment['received_amount'] ?? $payment['amount'])
                    : null;

                $createdPayment = Payment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'received_amount' => $received,
                    'change_amount' => $received ? max($received - (float) $payment['amount'], 0) : null,
                    'operation_number' => $payment['operation_number'] ?? $payment['reference'] ?? null,
                    'bank_name' => $payment['bank_name'] ?? null,
                    'notes' => $payment['notes'] ?? null,
                ]);

                $this->cashRegisterService->createSaleMovement($cashRegister, $sale, $createdPayment, $userId);
            }

            foreach ($lockedProducts as [$product, $quantity]) {
                $previousQuantity = (float) $product->quantity;
                $product->update(['quantity' => $previousQuantity - $quantity]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => StockMovementTypeEnum::SALE->value,
                    'quantity' => -$quantity,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $previousQuantity - $quantity,
                    'reference_type' => $sale->getMorphClass(),
                    'reference_id' => $sale->id,
                    'reason' => 'Venta '.$sale->full_document_number,
                ]);
            }

            Cart::query()->where('user_id', $userId)->delete();

            return $sale->load(['cashier', 'cashRegister', 'items', 'payments']);
        });
    }

    private function calculateDiscount(array $payload, float $subtotal): float
    {
        $discountTotal = BaseHelper::calculateDefaultDiscount($subtotal)['totalDiscount'];
        if (! empty($payload['custom_discount'])) {
            $discountTotal += BaseHelper::calculateCustomDiscount(
                $subtotal,
                $payload['custom_discount']['discount'],
                $payload['custom_discount']['discount_type']
            )['totalDiscount'];
        }

        return BaseHelper::numberFormat($discountTotal);
    }

    private function allocateDiscountToItems(array $items, float $subtotal, float $discountTotal): array
    {
        if ($subtotal <= 0 || $discountTotal <= 0) {
            return array_map(function (array $item) {
                $item['discount'] = 0;
                $item['gross_profit'] = BaseHelper::numberFormat($item['subtotal'] - $item['cost_subtotal']);

                return $item;
            }, $items);
        }

        $remainingDiscount = $discountTotal;
        $lastIndex = array_key_last($items);

        foreach ($items as $index => $item) {
            $discount = $index === $lastIndex
                ? $remainingDiscount
                : BaseHelper::numberFormat($discountTotal * ($item['subtotal'] / $subtotal));

            $discount = min($discount, $item['subtotal']);
            $remainingDiscount = BaseHelper::numberFormat($remainingDiscount - $discount);
            $netSubtotal = BaseHelper::numberFormat($item['subtotal'] - $discount);

            $items[$index]['discount'] = $discount;
            $items[$index]['gross_profit'] = BaseHelper::numberFormat($netSubtotal - $item['cost_subtotal']);
        }

        return $items;
    }
}
