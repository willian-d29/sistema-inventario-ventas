<?php

namespace App\Http\Controllers;

use App\Services\SaleService;
use App\Services\BusinessSettingsService;
use App\Services\DocumentPrintLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SaleDocumentController extends Controller
{
    public function __construct(
        private readonly SaleService $service,
        private readonly BusinessSettingsService $businessSettings,
        private readonly DocumentPrintLogService $printLogService,
    )
    {
    }

    public function thermal(int $sale): Response
    {
        $cashierId = auth()->user()->role === 'cajero' ? auth()->id() : null;

        return response()->view('sales.thermal-note', [
            'sale' => $this->service->findVisibleOrFail($sale, $cashierId),
            ...$this->businessSettings->documentViewData(),
            'autoPrint' => request()->boolean('autoprint'),
        ]);
    }

    public function pdf(int $sale)
    {
        $cashierId = auth()->user()->role === 'cajero' ? auth()->id() : null;
        $sale = $this->service->findVisibleOrFail($sale, $cashierId);

        $this->printLogService->record([
            'user_id' => auth()->id(),
            'sale_id' => $sale->id,
            'cash_register_id' => $sale->cash_register_id,
            'document_type' => $sale->document_type,
            'output_type' => 'pdf',
            'action_type' => 'download_requested',
            'metadata' => ['document' => $sale->full_document_number],
        ]);

        return Pdf::loadView('sales.pdf-note', [
            'sale' => $sale,
            ...$this->businessSettings->documentViewData(),
        ])
            ->setPaper('a4')
            ->download($sale->full_document_number.'.pdf');
    }

    public function requestPrint(int $sale): JsonResponse
    {
        $cashierId = auth()->user()->role === 'cajero' ? auth()->id() : null;
        $sale = $this->service->findVisibleOrFail($sale, $cashierId);

        $log = $this->printLogService->record([
            'user_id' => auth()->id(),
            'sale_id' => $sale->id,
            'cash_register_id' => $sale->cash_register_id,
            'document_type' => $sale->document_type,
            'output_type' => 'thermal',
            'action_type' => 'print_requested',
            'metadata' => ['document' => $sale->full_document_number],
        ]);

        return response()->json([
            'message' => 'Solicitud de impresión registrada.',
            'is_reprint' => $log->is_reprint,
            'requested_at' => $this->businessSettings->formatDateTime($log->requested_at),
        ]);
    }
}
