<?php

namespace App\Http\Controllers;

use App\Enums\CashRegister\CashRegisterStatusEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Http\Requests\CashRegister\CashRegisterCloseRequest;
use App\Http\Requests\CashRegister\CashRegisterIndexRequest;
use App\Http\Requests\CashRegister\CashRegisterMovementRequest;
use App\Http\Requests\CashRegister\CashRegisterOpenRequest;
use App\Http\Requests\CashRegister\CashRegisterReviewRequest;
use App\Models\CashRegister;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterController extends Controller
{
    public function __construct(private readonly CashRegisterService $service)
    {
    }

    public function index(CashRegisterIndexRequest $request): Response|JsonResponse
    {
        $currentRegister = CashRegister::query()
            ->with(['user', 'movements' => fn ($query) => $query->latest('occurred_at')->limit(10)])
            ->where('user_id', $request->user()->id)
            ->where('status', CashRegisterStatusEnum::OPEN->value)
            ->latest('opened_at')
            ->first();

        $report = $this->service->report($request->validated(), $request->user());

        if ($request->string('inertia')->toString() === 'disabled') {
            return response()->json([
                'currentRegister' => $currentRegister,
                'currentSummary' => $currentRegister ? $this->service->summary($currentRegister) : null,
                'timeline' => $currentRegister ? $this->service->timeline($currentRegister) : null,
                'registers' => $request->user()->role === 'admin' ? $report['registers'] : null,
                'ownRegisters' => $request->user()->role === 'cajero' ? $report['registers'] : null,
                'movementSummary' => $report['movement_summary'],
                'saleMovementDiscrepancies' => $report['sale_movement_discrepancies'],
                'employeeCashStates' => $request->user()->role === 'admin' ? $this->employeeCashStates() : [],
            ]);
        }

        return Inertia::render('CashRegister/Index', [
            'currentRegister' => $currentRegister,
            'currentSummary' => $currentRegister ? $this->service->summary($currentRegister) : null,
            'timeline' => $currentRegister ? $this->service->timeline($currentRegister) : null,
            'registers' => $request->user()->role === 'admin' ? $report['registers'] : null,
            'ownRegisters' => $request->user()->role === 'cajero' ? $report['registers'] : null,
            'movementSummary' => $report['movement_summary'],
            'saleMovementDiscrepancies' => $report['sale_movement_discrepancies'],
            'employeeCashStates' => $request->user()->role === 'admin' ? $this->employeeCashStates() : [],
            'cashiers' => $request->user()->role === 'admin'
                ? User::query()->whereIn('role', ['admin', 'cajero'])->orderBy('name')->get(['id', 'name'])
                : [],
            'paymentMethods' => PaymentMethodEnum::options(),
            'filters' => $request->validated(),
            'isAdmin' => $request->user()->role === 'admin',
        ]);
    }

    private function employeeCashStates(): array
    {
        return User::query()
            ->whereIn('role', ['admin', 'cajero'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])
            ->map(function (User $user) {
                $register = CashRegister::query()
                    ->where('user_id', $user->id)
                    ->where('status', CashRegisterStatusEnum::OPEN->value)
                    ->latest('opened_at')
                    ->first();

                $summary = $register ? $this->service->summary($register) : null;

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'status' => $register ? 'open' : 'closed',
                    'register_id' => $register?->id,
                    'opened_at' => $register?->opened_at,
                    'opening_amount' => (float) ($register?->opening_amount ?? 0),
                    'sales_count' => (int) ($summary['sales_count'] ?? 0),
                    'sales_total' => (float) ($summary['sales'] ?? 0),
                    'expected_cash' => (float) ($summary['expected_cash'] ?? 0),
                    'system_amounts' => $summary['system_amounts'] ?? [],
                ];
            })
            ->values()
            ->all();
    }

    public function open(CashRegisterOpenRequest $request): RedirectResponse
    {
        $this->service->open($request->user(), $request->validated());

        return back()->with('flash', ['message' => 'Caja abierta correctamente.']);
    }

    public function movement(CashRegisterMovementRequest $request, CashRegister $cashRegister): RedirectResponse
    {
        $this->service->createManualMovement($cashRegister, $request->user(), $request->validated());

        return back()->with('flash', ['message' => 'Movimiento registrado correctamente.']);
    }

    public function close(CashRegisterCloseRequest $request, CashRegister $cashRegister): RedirectResponse
    {
        $this->service->close($cashRegister, $request->user(), $request->validated());

        return back()->with('flash', ['message' => 'Caja cerrada correctamente.']);
    }

    public function review(CashRegisterReviewRequest $request, CashRegister $cashRegister): RedirectResponse
    {
        $this->service->review($cashRegister, $request->user(), $request->validated('review_notes'));

        return back()->with('flash', ['message' => 'Caja revisada correctamente.']);
    }
}
