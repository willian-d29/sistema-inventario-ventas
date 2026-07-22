<?php

namespace App\Http\Controllers;

use App\Exceptions\SaleCreateException;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Http\Requests\Sale\SaleCreateRequest;
use App\Http\Requests\Sale\SaleIndexRequest;
use App\Models\User;
use App\Services\BusinessSettingsService;
use App\Services\SaleService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $service,
        private readonly BusinessSettingsService $businessSettings,
    )
    {
    }

    public function index(SaleIndexRequest $request): Response|LengthAwarePaginator
    {
        $params = $request->validated();
        if ($request->user()->role === 'cajero') {
            $params['cashier_id'] = $request->user()->id;
        }

        if ($request->string('inertia')->toString() === 'disabled') {
            return $this->service->getAll($params);
        }

        return Inertia::render('Sale/Index', [
            'sales' => $this->service->getAll($params),
            'filters' => $params,
            'cashiers' => $request->user()->role === 'admin'
                ? User::query()->whereIn('role', ['admin', 'cajero'])->orderBy('name')->get(['id', 'name'])
                : [],
            'documentTypes' => [
                ['value' => 'receipt', 'label' => 'Boleta'],
                ['value' => 'invoice', 'label' => 'Factura'],
            ],
            'paymentMethods' => PaymentMethodEnum::options(),
            'saleStatuses' => [
                ['value' => 'paid', 'label' => 'Pagada'],
                ['value' => 'cancelled', 'label' => 'Anulada'],
                ['value' => 'refunded', 'label' => 'Devuelta'],
            ],
        ]);
    }

    public function store(SaleCreateRequest $request): RedirectResponse
    {
        try {
            $sale = $this->service->createForUser(
                payload: $request->validated(),
                userId: $request->user()->id
            );

            $target = $this->businessSettings->public()['keep_sale_confirmation']
                ? 'sales.show'
                : 'carts.index';

            return redirect()
                ->route($target, $target === 'sales.show' ? $sale : [])
                ->with('flash', [
                    'message' => 'Venta registrada correctamente: '.$sale->full_document_number,
                ]);
        } catch (SaleCreateException $exception) {
            return back()->with('flash', [
                'isSuccess' => false,
                'message' => $exception->getMessage(),
            ]);
        } catch (Exception $exception) {
            Log::error('Sale creation failed', ['exception' => $exception]);

            return back()->with('flash', [
                'isSuccess' => false,
                'message' => 'No se pudo registrar la venta.',
            ]);
        }
    }

    public function show(int $sale): Response
    {
        $cashierId = auth()->user()->role === 'cajero' ? auth()->id() : null;

        return Inertia::render('Sale/Show', [
            'sale' => $this->service->findVisibleOrFail($sale, $cashierId),
        ]);
    }
}
