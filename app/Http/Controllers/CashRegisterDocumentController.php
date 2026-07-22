<?php

namespace App\Http\Controllers;

use App\Enums\Transaction\PaymentMethodEnum;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\User;
use App\Services\BusinessSettingsService;
use App\Services\CashRegisterService;
use App\Services\DocumentPrintLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CashRegisterDocumentController extends Controller
{
    public function __construct(
        private readonly CashRegisterService $service,
        private readonly DocumentPrintLogService $printLogService,
        private readonly BusinessSettingsService $businessSettings,
    )
    {
    }

    public function thermal(CashRegister $cashRegister): Response
    {
        $this->authorizeRegisterDocument($cashRegister);

        return response()->view('cash-registers.closing-thermal', $this->service->documentData($cashRegister));
    }

    public function pdf(CashRegister $cashRegister)
    {
        $this->authorizeRegisterDocument($cashRegister);
        $data = $this->service->documentData($cashRegister);
        $this->recordCashRegisterDownload($cashRegister, 'cash_closing');

        return Pdf::loadView('cash-registers.closing-pdf', $data)
            ->setPaper('a4')
            ->download('cierre-caja-'.$cashRegister->id.'.pdf');
    }

    public function adminReportPdf(CashRegister $cashRegister)
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $data = $this->service->documentData($cashRegister);
        $this->recordCashRegisterDownload($cashRegister, 'cash_admin_report');

        return Pdf::loadView('cash-registers.admin-report-pdf', $data)
            ->setPaper('a4')
            ->download('reporte-admin-caja-'.$cashRegister->id.'.pdf');
    }

    public function movementThermal(CashRegister $cashRegister, CashMovement $cashMovement): Response
    {
        $this->authorizeRegisterDocument($cashRegister);
        $view = $cashMovement->type === 'opening' ? 'cash-registers.opening-thermal' : 'cash-registers.movement-thermal';

        return response()->view($view, $this->service->movementDocumentData($cashRegister, $cashMovement));
    }

    public function movementPdf(CashRegister $cashRegister, CashMovement $cashMovement)
    {
        $this->authorizeRegisterDocument($cashRegister);
        $data = $this->service->movementDocumentData($cashRegister, $cashMovement);
        $view = $cashMovement->type === 'opening' ? 'cash-registers.opening-pdf' : 'cash-registers.movement-pdf';
        $this->recordMovementDownload($cashRegister, $cashMovement);

        return Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->download('movimiento-caja-'.$cashMovement->id.'.pdf');
    }

    public function reconciliation(Request $request): InertiaResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'method' => ['nullable', 'string', Rule::in(PaymentMethodEnum::values())],
            'status' => ['nullable', 'string', Rule::in(['matched', 'mismatch', 'missing_movement', 'missing_payment'])],
            'sale' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:255'],
            'inertia' => ['nullable', 'string'],
        ]);

        $rows = $this->service->reconciliation($filters);

        if ($request->string('inertia')->toString() === 'disabled') {
            return response()->json([
                'rows' => $rows,
                'filters' => $filters,
            ]);
        }

        return Inertia::render('CashRegister/Reconciliation', [
            'rows' => $rows,
            'filters' => $filters,
            'cashiers' => User::query()->whereIn('role', ['admin', 'cajero'])->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethodEnum::options(),
            'statuses' => [
                ['value' => 'matched', 'label' => 'Conciliado'],
                ['value' => 'mismatch', 'label' => 'Diferencia'],
                ['value' => 'missing_movement', 'label' => 'Falta movimiento'],
                ['value' => 'missing_payment', 'label' => 'Falta pago'],
            ],
        ]);
    }

    public function requestPrint(CashRegister $cashRegister): JsonResponse
    {
        $this->authorizeRegisterDocument($cashRegister);

        $log = $this->printLogService->record([
            'user_id' => auth()->id(),
            'cash_register_id' => $cashRegister->id,
            'document_type' => 'cash_closing',
            'output_type' => 'thermal',
            'action_type' => 'print_requested',
            'metadata' => ['cash_register_id' => $cashRegister->id],
        ]);

        return $this->printResponse($log);
    }

    public function requestMovementPrint(CashRegister $cashRegister, CashMovement $cashMovement): JsonResponse
    {
        $this->authorizeRegisterDocument($cashRegister);
        abort_unless((int) $cashMovement->cash_register_id === (int) $cashRegister->id, 404);

        $log = $this->printLogService->record([
            'user_id' => auth()->id(),
            'cash_register_id' => $cashRegister->id,
            'cash_movement_id' => $cashMovement->id,
            'document_type' => $cashMovement->type === 'opening' ? 'cash_opening' : 'cash_movement',
            'output_type' => 'thermal',
            'action_type' => 'print_requested',
            'metadata' => ['cash_register_id' => $cashRegister->id, 'cash_movement_id' => $cashMovement->id],
        ]);

        return $this->printResponse($log);
    }

    private function authorizeRegisterDocument(CashRegister $cashRegister): void
    {
        abort_unless(auth()->user()->role === 'admin' || (int) $cashRegister->user_id === (int) auth()->id(), 403);
    }

    private function recordCashRegisterDownload(CashRegister $cashRegister, string $documentType): void
    {
        $this->printLogService->record([
            'user_id' => auth()->id(),
            'cash_register_id' => $cashRegister->id,
            'document_type' => $documentType,
            'output_type' => 'pdf',
            'action_type' => 'download_requested',
            'metadata' => ['cash_register_id' => $cashRegister->id],
        ]);
    }

    private function recordMovementDownload(CashRegister $cashRegister, CashMovement $cashMovement): void
    {
        $this->printLogService->record([
            'user_id' => auth()->id(),
            'cash_register_id' => $cashRegister->id,
            'cash_movement_id' => $cashMovement->id,
            'document_type' => $cashMovement->type === 'opening' ? 'cash_opening' : 'cash_movement',
            'output_type' => 'pdf',
            'action_type' => 'download_requested',
            'metadata' => ['cash_register_id' => $cashRegister->id, 'cash_movement_id' => $cashMovement->id],
        ]);
    }

    private function printResponse($log): JsonResponse
    {
        return response()->json([
            'message' => 'Solicitud de impresión registrada.',
            'is_reprint' => $log->is_reprint,
            'requested_at' => $this->businessSettings->formatDateTime($log->requested_at),
        ]);
    }
}
