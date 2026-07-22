<?php

namespace App\Services;

use App\Models\DocumentPrintLog;
use Illuminate\Support\Facades\DB;

class DocumentPrintLogService
{
    public function record(array $payload): DocumentPrintLog
    {
        return DB::transaction(function () use ($payload) {
            $actionType = $payload['action_type'];
            $isReprint = false;

            if ($actionType === 'print_requested') {
                $isReprint = DocumentPrintLog::query()
                    ->where('document_type', $payload['document_type'])
                    ->where('action_type', 'print_requested')
                    ->when($payload['sale_id'] ?? null, fn ($query, $id) => $query->where('sale_id', $id), fn ($query) => $query->whereNull('sale_id'))
                    ->when($payload['cash_register_id'] ?? null, fn ($query, $id) => $query->where('cash_register_id', $id), fn ($query) => $query->whereNull('cash_register_id'))
                    ->when($payload['cash_movement_id'] ?? null, fn ($query, $id) => $query->where('cash_movement_id', $id), fn ($query) => $query->whereNull('cash_movement_id'))
                    ->lockForUpdate()
                    ->exists();
            }

            return DocumentPrintLog::create([
                'user_id' => $payload['user_id'],
                'sale_id' => $payload['sale_id'] ?? null,
                'cash_register_id' => $payload['cash_register_id'] ?? null,
                'cash_movement_id' => $payload['cash_movement_id'] ?? null,
                'document_type' => $payload['document_type'],
                'output_type' => $payload['output_type'],
                'action_type' => $actionType,
                'is_reprint' => $isReprint,
                'requested_at' => now(),
                'metadata' => $payload['metadata'] ?? null,
            ]);
        });
    }
}
