<?php

namespace App\Http\Controllers;

use App\Models\DocumentPrintLog;
use App\Services\BusinessSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentPrintLogController extends Controller
{
    public function __invoke(Request $request, BusinessSettingsService $businessSettings): JsonResponse
    {
        $logs = DocumentPrintLog::query()
            ->with(['user:id,name', 'sale:id,full_document_number', 'cashRegister:id,user_id', 'cashMovement:id,type'])
            ->when($request->user()->role === 'cajero', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest('requested_at')
            ->paginate(20)
            ->through(fn (DocumentPrintLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name,
                'requested_at' => $businessSettings->formatDateTime($log->requested_at),
                'document' => $log->sale?->full_document_number
                    ?? ($log->cash_movement_id ? 'Movimiento #'.$log->cash_movement_id : null)
                    ?? ($log->cash_register_id ? 'Caja #'.$log->cash_register_id : 'Ticket de prueba'),
                'document_type' => $log->document_type,
                'output_type' => $log->output_type,
                'action_type' => $log->action_type,
                'is_reprint' => $log->is_reprint,
            ]);

        return response()->json($logs);
    }
}
