<?php

namespace App\Services;

use App\Enums\CashRegister\CashDifferenceStatusEnum;
use App\Enums\CashRegister\CashMovementDirectionEnum;
use App\Enums\CashRegister\CashMovementTypeEnum;
use App\Enums\CashRegister\CashRegisterStatusEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Helpers\BaseHelper;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CashRegisterService
{
    private const TOLERANCE = 0.01;

    public function open(User $user, array $payload): CashRegister
    {
        return DB::transaction(function () use ($user, $payload) {
            $exists = CashRegister::query()
                ->where('user_id', $user->id)
                ->where('status', CashRegisterStatusEnum::OPEN->value)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['opening_amount' => 'Ya tienes una caja abierta.']);
            }

            $cashRegister = CashRegister::create([
                'user_id' => $user->id,
                'opening_amount' => $payload['opening_amount'],
                'opened_at' => now(),
                'status' => CashRegisterStatusEnum::OPEN->value,
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->createMovement([
                'cash_register_id' => $cashRegister->id,
                'user_id' => $user->id,
                'type' => CashMovementTypeEnum::OPENING->value,
                'direction' => CashMovementDirectionEnum::INCOME->value,
                'payment_method' => PaymentMethodEnum::CASH->value,
                'amount' => $payload['opening_amount'],
                'description' => 'Monto inicial de caja',
                'occurred_at' => now(),
            ]);

            return $cashRegister->load('movements');
        });
    }

    public function createSaleMovement(CashRegister $cashRegister, Sale $sale, Payment $payment, int $userId): CashMovement
    {
        return CashMovement::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'cash_register_id' => $cashRegister->id,
                'user_id' => $userId,
                'sale_id' => $sale->id,
                'type' => CashMovementTypeEnum::SALE->value,
                'direction' => CashMovementDirectionEnum::INCOME->value,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'reference' => $payment->operation_number,
                'description' => 'Venta '.$sale->full_document_number,
                'metadata' => [
                    'sale_document' => $sale->full_document_number,
                    'bank_name' => $payment->bank_name,
                ],
                'occurred_at' => now(),
            ]
        );
    }

    public function createManualMovement(CashRegister $cashRegister, User $user, array $payload): CashMovement
    {
        return DB::transaction(function () use ($cashRegister, $user, $payload) {
            $cashRegister = CashRegister::query()->lockForUpdate()->findOrFail($cashRegister->id);
            $this->assertCanUseRegister($cashRegister, $user);

            $type = $payload['type'];
            if ($type === CashMovementTypeEnum::ADJUSTMENT->value && $user->role !== 'admin') {
                abort(403);
            }

            if ($type === CashMovementTypeEnum::WITHDRAWAL->value && ! (bool) ($payload['confirmed'] ?? false)) {
                throw ValidationException::withMessages(['confirmed' => 'Confirma el retiro antes de registrarlo.']);
            }

            $direction = match ($type) {
                CashMovementTypeEnum::MANUAL_INCOME->value => CashMovementDirectionEnum::INCOME,
                CashMovementTypeEnum::WITHDRAWAL->value => CashMovementDirectionEnum::EXPENSE,
                CashMovementTypeEnum::ADJUSTMENT->value => CashMovementDirectionEnum::from($payload['direction']),
                default => throw ValidationException::withMessages(['type' => 'Tipo de movimiento inválido.']),
            };

            $paymentMethod = $payload['payment_method'] ?? PaymentMethodEnum::CASH->value;
            if ($type === CashMovementTypeEnum::WITHDRAWAL->value
                && $paymentMethod === PaymentMethodEnum::CASH->value
                && $user->role !== 'admin'
                && (float) $payload['amount'] > $this->expectedByMethod($cashRegister, PaymentMethodEnum::CASH->value)) {
                throw ValidationException::withMessages(['amount' => 'El retiro no puede exceder el efectivo disponible.']);
            }

            return $this->createMovement([
                'cash_register_id' => $cashRegister->id,
                'user_id' => $user->id,
                'reversed_movement_id' => $payload['reversed_movement_id'] ?? null,
                'type' => $type,
                'direction' => $direction->value,
                'payment_method' => $paymentMethod,
                'amount' => $payload['amount'],
                'reference' => $payload['reference'] ?? null,
                'description' => $payload['description'],
                'metadata' => [
                    'reason' => $payload['description'],
                    'confirmed' => (bool) ($payload['confirmed'] ?? false),
                ],
                'occurred_at' => $payload['occurred_at'] ?? now(),
            ]);
        });
    }

    public function createExpenseMovement(CashRegister $cashRegister, User $user, Expense $expense): CashMovement
    {
        return $this->createMovement([
            'cash_register_id' => $cashRegister->id,
            'user_id' => $user->id,
            'expense_id' => $expense->id,
            'type' => CashMovementTypeEnum::EXPENSE->value,
            'direction' => CashMovementDirectionEnum::EXPENSE->value,
            'payment_method' => PaymentMethodEnum::CASH->value,
            'amount' => $expense->amount,
            'description' => 'Gasto: '.$expense->name,
            'occurred_at' => now(),
        ]);
    }

    public function close(CashRegister $cashRegister, User $user, array $payload): CashRegister
    {
        return DB::transaction(function () use ($cashRegister, $user, $payload) {
            $cashRegister = CashRegister::query()->lockForUpdate()->findOrFail($cashRegister->id);
            $this->assertCanUseRegister($cashRegister, $user);

            if (! (bool) ($payload['confirmed'] ?? false)) {
                throw ValidationException::withMessages(['confirmed' => 'Confirma el cierre de caja antes de continuar.']);
            }

            $system = $this->systemAmounts($cashRegister);
            $declared = $this->normalizeAmounts($payload['declared_amounts'] ?? []);
            foreach ($this->paymentMethods() as $method) {
                if ($method !== PaymentMethodEnum::CASH->value) {
                    $declared[$method] = $system[$method];
                }
            }

            $denominations = $payload['denominations'] ?? null;
            if (is_array($denominations) && count($denominations) > 0) {
                $denominationTotal = $this->denominationTotal($denominations);
                if (abs($denominationTotal - $declared[PaymentMethodEnum::CASH->value]) > self::TOLERANCE) {
                    throw ValidationException::withMessages([
                        'denominations' => 'El conteo por denominaciones no coincide con el efectivo declarado.',
                    ]);
                }
            }

            $differences = [];
            foreach ($this->paymentMethods() as $method) {
                $differences[$method] = BaseHelper::numberFormat($declared[$method] - $system[$method]);
            }

            $totalDifference = BaseHelper::numberFormat($differences[PaymentMethodEnum::CASH->value]);
            $differenceStatus = $this->differenceStatus($totalDifference);

            $cashRegister->update([
                'closing_amount' => $declared[PaymentMethodEnum::CASH->value],
                'expected_amount' => $system[PaymentMethodEnum::CASH->value],
                'declared_amounts' => $declared,
                'system_amounts' => $system,
                'differences' => $differences,
                'total_difference' => $totalDifference,
                'difference_status' => $differenceStatus,
                'closed_at' => now(),
                'status' => CashRegisterStatusEnum::CLOSED->value,
                'closing_notes' => $payload['closing_notes'] ?? null,
                'denominations' => $denominations,
            ]);

            return $cashRegister->refresh();
        });
    }

    public function review(CashRegister $cashRegister, User $user, ?string $notes = null): CashRegister
    {
        abort_unless($user->role === 'admin', 403);

        if ($cashRegister->status !== CashRegisterStatusEnum::CLOSED->value) {
            throw ValidationException::withMessages(['review_notes' => 'Solo puedes revisar cajas cerradas.']);
        }

        $cashRegister->update([
            'status' => CashRegisterStatusEnum::REVIEWED->value,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return $cashRegister->refresh();
    }

    public function systemAmounts(CashRegister $cashRegister): array
    {
        $totals = $this->normalizeAmounts([]);
        $rows = CashMovement::query()
            ->selectRaw("payment_method, SUM(CASE WHEN direction = 'income' THEN amount ELSE -amount END) as total")
            ->where('cash_register_id', $cashRegister->id)
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        foreach ($this->paymentMethods() as $method) {
            $totals[$method] = BaseHelper::numberFormat((float) ($rows[$method] ?? 0));
        }

        return $totals;
    }

    public function summary(CashRegister $cashRegister): array
    {
        $system = $this->systemAmounts($cashRegister);
        $movements = $cashRegister->movements()->latest('occurred_at')->limit(10)->get();

        return [
            'system_amounts' => $system,
            'expected_cash' => $system[PaymentMethodEnum::CASH->value],
            'sales_count' => Sale::query()->where('cash_register_id', $cashRegister->id)->count(),
            'digital_total' => BaseHelper::numberFormat(collect($system)
                ->except(PaymentMethodEnum::CASH->value)
                ->sum(fn ($amount) => (float) $amount)),
            'manual_income' => $this->sumByType($cashRegister, CashMovementTypeEnum::MANUAL_INCOME->value),
            'withdrawals' => $this->sumByType($cashRegister, CashMovementTypeEnum::WITHDRAWAL->value),
            'expenses' => $this->sumByType($cashRegister, CashMovementTypeEnum::EXPENSE->value),
            'sales' => $this->sumByType($cashRegister, CashMovementTypeEnum::SALE->value),
            'movements' => $movements,
            'payment_methods' => PaymentMethodEnum::options(),
        ];
    }

    public function documentData(CashRegister $cashRegister): array
    {
        $cashRegister->loadMissing(['user', 'reviewer']);
        $openingMovement = $this->openingMovementOrFail($cashRegister);
        $movements = $this->documentMovements($cashRegister)->get();
        $sales = Sale::query()
            ->with(['payments', 'cashier:id,name'])
            ->where('cash_register_id', $cashRegister->id)
            ->orderBy('sold_at')
            ->get();

        return [
            'cashRegister' => $cashRegister,
            'openingMovement' => $openingMovement,
            'summary' => $this->summary($cashRegister),
            'operationSummary' => $this->operationSummary($cashRegister),
            'methodRows' => $this->methodRows($cashRegister),
            'denominationRows' => $this->denominationRows($cashRegister),
            'movements' => $movements,
            'timeline' => $this->timelineItems($cashRegister, false),
            'sales' => $sales,
            'reconciliation' => $this->reconciliation(['cash_register_id' => $cashRegister->id])->values(),
            'businessName' => app(BusinessSettingsService::class)->getBusinessName(),
            ...app(BusinessSettingsService::class)->documentViewData(),
            'generatedAt' => now(),
        ];
    }

    public function movementDocumentData(CashRegister $cashRegister, CashMovement $cashMovement): array
    {
        if ((int) $cashMovement->cash_register_id !== (int) $cashRegister->id) {
            abort(404);
        }

        $cashRegister->loadMissing('user');
        $cashMovement->loadMissing(['user', 'sale.cashier', 'payment', 'expense', 'reversedMovement']);

        if ($cashMovement->type === CashMovementTypeEnum::OPENING->value) {
            $this->openingMovementOrFail($cashRegister);
        }

        return [
            'cashRegister' => $cashRegister,
            'movement' => $cashMovement,
            'businessName' => app(BusinessSettingsService::class)->getBusinessName(),
            ...app(BusinessSettingsService::class)->documentViewData(),
            'generatedAt' => now(),
            'message' => $this->movementImpactMessage($cashMovement),
        ];
    }

    public function openingMovementOrFail(CashRegister $cashRegister): CashMovement
    {
        $movements = CashMovement::query()
            ->where('cash_register_id', $cashRegister->id)
            ->where('type', CashMovementTypeEnum::OPENING->value)
            ->get();

        if ($movements->count() !== 1) {
            Log::warning('Inconsistencia de apertura de caja.', [
                'cash_register_id' => $cashRegister->id,
                'opening_movements' => $movements->count(),
            ]);

            abort(422, 'La caja no tiene un movimiento de apertura válido.');
        }

        return $movements->first()->loadMissing('user');
    }

    public function timeline(CashRegister $cashRegister, int $perPage = 10): LengthAwarePaginator
    {
        return $this->documentMovements($cashRegister)
            ->orderByDesc('occurred_at')
            ->paginate($perPage, ['*'], 'movement_page')
            ->withQueryString()
            ->through(fn (CashMovement $movement) => $this->timelineMovementItem($movement));
    }

    public function timelineItems(CashRegister $cashRegister, bool $latestFirst = true): Collection
    {
        $movements = $this->documentMovements($cashRegister)
            ->when($latestFirst, fn (Builder $query) => $query->orderByDesc('occurred_at'))
            ->when(! $latestFirst, fn (Builder $query) => $query->orderBy('occurred_at'))
            ->get()
            ->map(fn (CashMovement $movement) => $this->timelineMovementItem($movement));

        if ($cashRegister->closed_at) {
            $movements->push([
                'kind' => 'close',
                'occurred_at' => $cashRegister->closed_at,
                'type' => 'closing',
                'type_label' => 'Cierre',
                'direction' => null,
                'amount' => $cashRegister->closing_amount,
                'signed_amount' => $cashRegister->closing_amount,
                'payment_method' => 'cash',
                'description' => $cashRegister->closing_notes ?: 'Caja cerrada',
                'responsible' => $cashRegister->user?->name,
                'reference' => null,
                'relation' => 'Arqueo',
                'thermal_url' => route('cash-registers.thermal', $cashRegister->id),
                'pdf_url' => route('cash-registers.pdf', $cashRegister->id),
            ]);
        }

        if ($cashRegister->reviewed_at) {
            $movements->push([
                'kind' => 'review',
                'occurred_at' => $cashRegister->reviewed_at,
                'type' => 'review',
                'type_label' => 'Revisión',
                'direction' => null,
                'amount' => null,
                'signed_amount' => null,
                'payment_method' => null,
                'description' => $cashRegister->review_notes ?: 'Caja revisada',
                'responsible' => $cashRegister->reviewer?->name,
                'reference' => null,
                'relation' => 'Revisión administrativa',
                'thermal_url' => null,
                'pdf_url' => route('cash-registers.admin-report.pdf', $cashRegister->id),
            ]);
        }

        return $latestFirst
            ? $movements->sortByDesc('occurred_at')->values()
            : $movements->sortBy('occurred_at')->values();
    }

    public function report(array $filters = [], ?User $user = null): array
    {
        $registers = CashRegister::query()
            ->with('user:id,name')
            ->when($user?->role === 'cajero', fn (Builder $query) => $query->where('user_id', $user->id))
            ->when($filters['cashier_id'] ?? null, fn (Builder $query, int $cashierId) => $query->where('user_id', $cashierId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(($filters['difference'] ?? null) === 'with', function (Builder $query) {
                $query->whereNotNull('total_difference')->where('total_difference', '!=', 0);
            })
            ->when(($filters['difference'] ?? null) === 'without', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->whereNull('total_difference')->orWhere('total_difference', 0);
                });
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('opened_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('opened_at', '<=', $date))
            ->latest('opened_at')
            ->paginate(20)
            ->withQueryString()
            ->through(function (CashRegister $register) {
                $summary = $this->summary($register);

                return [
                    'id' => $register->id,
                    'user' => $register->user,
                    'opened_at' => $register->opened_at,
                    'closed_at' => $register->closed_at,
                    'opening_amount' => (float) $register->opening_amount,
                    'closing_amount' => (float) $register->closing_amount,
                    'expected_amount' => (float) ($register->expected_amount ?? $summary['expected_cash']),
                    'declared_amounts' => $register->declared_amounts,
                    'system_amounts' => $register->system_amounts ?: $summary['system_amounts'],
                    'total_difference' => (float) $register->total_difference,
                    'difference_status' => $register->difference_status,
                    'status' => $register->status,
                    'sales_count' => $summary['sales_count'],
                    'sales_total' => $summary['sales'],
                ];
            });

        return [
            'registers' => $registers,
            'movement_summary' => CashMovement::query()
                ->selectRaw('type, direction, payment_method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('type', 'direction', 'payment_method')
                ->get(),
            'sale_movement_discrepancies' => $this->saleMovementDiscrepancies(),
        ];
    }

    public function reconciliation(array $filters = []): Collection
    {
        $rows = collect();
        $paymentQuery = Payment::query()
            ->with(['sale.cashier', 'cashMovement'])
            ->whereHas('sale', function (Builder $query) use ($filters) {
                $this->applyReconciliationSaleFilters($query, $filters);
            })
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method));

        foreach ($paymentQuery->get() as $payment) {
            $movement = $payment->cashMovement;
            $status = 'matched';
            $movementAmount = $movement ? (float) $movement->amount : null;

            if (! $movement) {
                $status = 'missing_movement';
            } elseif ($movement->payment_method !== $payment->payment_method || abs((float) $movement->amount - (float) $payment->amount) > self::TOLERANCE) {
                $status = 'mismatch';
            }

            $difference = $movementAmount === null
                ? (float) $payment->amount
                : BaseHelper::numberFormat((float) $payment->amount - $movementAmount);

            $rows->push([
                'sale_id' => $payment->sale_id,
                'document' => $payment->sale?->full_document_number,
                'cashier' => $payment->sale?->cashier?->name,
                'cashier_id' => $payment->sale?->cashier_id,
                'cash_register_id' => $payment->sale?->cash_register_id,
                'method' => $payment->payment_method,
                'payment_amount' => (float) $payment->amount,
                'movement_amount' => $movementAmount,
                'difference' => $difference,
                'status' => $status,
                'sold_at' => $payment->sale?->sold_at,
            ]);
        }

        $movementQuery = CashMovement::query()
            ->with(['sale.cashier', 'cashRegister.user', 'payment'])
            ->where('type', CashMovementTypeEnum::SALE->value)
            ->where(function (Builder $query) {
                $query->whereNull('payment_id')->orWhereDoesntHave('payment');
            })
            ->when($filters['cash_register_id'] ?? null, fn (Builder $query, int $cashRegisterId) => $query->where('cash_register_id', $cashRegisterId))
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method));

        if ($filters['date_from'] ?? null) {
            $movementQuery->where('occurred_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if ($filters['date_to'] ?? null) {
            $movementQuery->where('occurred_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if ($filters['cashier_id'] ?? null) {
            $movementQuery->whereHas('cashRegister', fn (Builder $query) => $query->where('user_id', $filters['cashier_id']));
        }
        if ($filters['sale'] ?? null) {
            $needle = $filters['sale'];
            $movementQuery->where(function (Builder $query) use ($needle) {
                $query->where('sale_id', $needle)
                    ->orWhereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('full_document_number', 'like', '%'.$needle.'%'));
            });
        }
        if ($filters['document'] ?? null) {
            $document = $filters['document'];
            $movementQuery->whereHas('sale', fn (Builder $query) => $query->where('full_document_number', 'like', '%'.$document.'%'));
        }

        foreach ($movementQuery->get() as $movement) {
            $rows->push([
                'sale_id' => $movement->sale_id,
                'document' => $movement->sale?->full_document_number ?? $movement->metadata['sale_document'] ?? null,
                'cashier' => $movement->sale?->cashier?->name ?? $movement->cashRegister?->user?->name,
                'cashier_id' => $movement->sale?->cashier_id ?? $movement->cashRegister?->user_id,
                'cash_register_id' => $movement->cash_register_id,
                'method' => $movement->payment_method,
                'payment_amount' => null,
                'movement_amount' => (float) $movement->amount,
                'difference' => BaseHelper::numberFormat(0 - (float) $movement->amount),
                'status' => 'missing_payment',
                'sold_at' => $movement->occurred_at,
            ]);
        }

        return $rows
            ->when($filters['status'] ?? null, fn (Collection $items, string $status) => $items->where('status', $status))
            ->sortByDesc('sold_at')
            ->values();
    }

    public function assertCanUseRegister(CashRegister $cashRegister, User $user): void
    {
        abort_unless($user->role === 'admin' || $cashRegister->user_id === $user->id, 403);

        if ($cashRegister->status !== CashRegisterStatusEnum::OPEN->value) {
            throw ValidationException::withMessages(['cash_register_id' => 'La caja no está abierta.']);
        }
    }

    public function paymentMethods(): array
    {
        return PaymentMethodEnum::values();
    }

    public function movementImpactMessage(CashMovement $movement): string
    {
        if ($movement->type === CashMovementTypeEnum::ADJUSTMENT->value) {
            return 'Ajuste administrativo auditable de caja.';
        }

        return $movement->direction === CashMovementDirectionEnum::INCOME->value
            ? 'Incrementa el saldo esperado de caja.'
            : 'Reduce el saldo esperado de caja.';
    }

    private function createMovement(array $payload): CashMovement
    {
        return CashMovement::create($payload);
    }

    private function expectedByMethod(CashRegister $cashRegister, string $method): float
    {
        return (float) $this->systemAmounts($cashRegister)[$method];
    }

    private function sumByType(CashRegister $cashRegister, string $type): float
    {
        return (float) CashMovement::query()
            ->where('cash_register_id', $cashRegister->id)
            ->where('type', $type)
            ->sum('amount');
    }

    private function signedSumByType(CashRegister $cashRegister, string $type): float
    {
        return (float) CashMovement::query()
            ->where('cash_register_id', $cashRegister->id)
            ->where('type', $type)
            ->selectRaw("SUM(CASE WHEN direction = 'income' THEN amount ELSE -amount END) as total")
            ->value('total');
    }

    private function operationSummary(CashRegister $cashRegister): array
    {
        return [
            'sales_count' => Sale::query()->where('cash_register_id', $cashRegister->id)->count(),
            'sales_total' => (float) CashMovement::query()
                ->where('cash_register_id', $cashRegister->id)
                ->where('type', CashMovementTypeEnum::SALE->value)
                ->sum('amount'),
            'manual_income' => $this->sumByType($cashRegister, CashMovementTypeEnum::MANUAL_INCOME->value),
            'withdrawals' => $this->sumByType($cashRegister, CashMovementTypeEnum::WITHDRAWAL->value),
            'expenses' => $this->sumByType($cashRegister, CashMovementTypeEnum::EXPENSE->value),
            'adjustments' => $this->signedSumByType($cashRegister, CashMovementTypeEnum::ADJUSTMENT->value),
            'refunds' => $this->signedSumByType($cashRegister, CashMovementTypeEnum::REFUND->value),
        ];
    }

    private function methodRows(CashRegister $cashRegister): array
    {
        $system = $cashRegister->system_amounts ?: $this->systemAmounts($cashRegister);
        $declared = $cashRegister->declared_amounts ?: $this->normalizeAmounts([]);
        $differences = $cashRegister->differences ?: $this->normalizeAmounts([]);
        $labels = collect(PaymentMethodEnum::options())->pluck('label', 'value');

        return collect($this->paymentMethods())
            ->map(fn (string $method) => [
                'method' => $method,
                'label' => $labels[$method] ?? ucfirst($method),
                'system' => (float) ($system[$method] ?? 0),
                'declared' => (float) ($declared[$method] ?? 0),
                'difference' => (float) ($differences[$method] ?? 0),
            ])
            ->all();
    }

    private function denominationRows(CashRegister $cashRegister): array
    {
        return collect($cashRegister->denominations ?: [])
            ->map(fn ($quantity, $value) => [
                'value' => (float) $value,
                'quantity' => (int) $quantity,
                'subtotal' => BaseHelper::numberFormat((float) $value * (int) $quantity),
            ])
            ->filter(fn (array $row) => $row['quantity'] > 0)
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    private function documentMovements(CashRegister $cashRegister): Builder
    {
        return CashMovement::query()
            ->with(['user:id,name', 'sale:id,full_document_number', 'expense:id,name', 'reversedMovement:id,type,amount'])
            ->where('cash_register_id', $cashRegister->id);
    }

    private function timelineMovementItem(CashMovement $movement): array
    {
        $signedAmount = $movement->direction === CashMovementDirectionEnum::EXPENSE->value
            ? 0 - (float) $movement->amount
            : (float) $movement->amount;

        return [
            'kind' => 'movement',
            'id' => $movement->id,
            'occurred_at' => $movement->occurred_at,
            'type' => $movement->type,
            'type_label' => $this->movementTypeLabel($movement->type),
            'direction' => $movement->direction,
            'amount' => (float) $movement->amount,
            'signed_amount' => BaseHelper::numberFormat($signedAmount),
            'payment_method' => $movement->payment_method,
            'description' => $movement->description,
            'responsible' => $movement->user?->name,
            'reference' => $movement->reference,
            'relation' => $movement->sale?->full_document_number
                ?: ($movement->expense ? 'Gasto: '.$movement->expense->name : null),
            'thermal_url' => route('cash-registers.movements.thermal', [$movement->cash_register_id, $movement->id]),
            'pdf_url' => route('cash-registers.movements.pdf', [$movement->cash_register_id, $movement->id]),
        ];
    }

    private function applyReconciliationSaleFilters(Builder $query, array $filters): void
    {
        $query->when($filters['cash_register_id'] ?? null, fn (Builder $saleQuery, int $cashRegisterId) => $saleQuery->where('cash_register_id', $cashRegisterId))
            ->when($filters['cashier_id'] ?? null, fn (Builder $saleQuery, int $cashierId) => $saleQuery->where('cashier_id', $cashierId));

        if ($filters['date_from'] ?? null) {
            $query->where('sold_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if ($filters['date_to'] ?? null) {
            $query->where('sold_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if ($filters['sale'] ?? null) {
            $needle = $filters['sale'];
            $query->where(function (Builder $saleQuery) use ($needle) {
                $saleQuery->where('id', $needle)
                    ->orWhere('full_document_number', 'like', '%'.$needle.'%');
            });
        }
        if ($filters['document'] ?? null) {
            $query->where('full_document_number', 'like', '%'.$filters['document'].'%');
        }
    }

    private function movementTypeLabel(string $type): string
    {
        return [
            CashMovementTypeEnum::OPENING->value => 'Apertura',
            CashMovementTypeEnum::SALE->value => 'Venta',
            CashMovementTypeEnum::MANUAL_INCOME->value => 'Ingreso manual',
            CashMovementTypeEnum::WITHDRAWAL->value => 'Retiro',
            CashMovementTypeEnum::EXPENSE->value => 'Gasto desde caja',
            CashMovementTypeEnum::REFUND->value => 'Devolución',
            CashMovementTypeEnum::ADJUSTMENT->value => 'Ajuste',
        ][$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    private function normalizeAmounts(array $amounts): array
    {
        $normalized = [];
        foreach ($this->paymentMethods() as $method) {
            $normalized[$method] = BaseHelper::numberFormat((float) ($amounts[$method] ?? 0));
        }

        return $normalized;
    }

    private function differenceStatus(float $difference): string
    {
        if (abs($difference) <= self::TOLERANCE) {
            return CashDifferenceStatusEnum::BALANCED->value;
        }

        return $difference > 0
            ? CashDifferenceStatusEnum::SURPLUS->value
            : CashDifferenceStatusEnum::SHORTAGE->value;
    }

    private function denominationTotal(array $denominations): float
    {
        $total = 0.0;
        foreach ($denominations as $value => $quantity) {
            $total += (float) $value * (int) $quantity;
        }

        return BaseHelper::numberFormat($total);
    }

    private function saleMovementDiscrepancies(): int
    {
        return $this->reconciliation()
            ->where('status', '!=', 'matched')
            ->count();
    }
}
