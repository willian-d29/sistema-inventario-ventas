<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Str;

class DocumentSequenceService
{
    public function next(string $documentType): array
    {
        $sequence = DocumentSequence::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();

        $nextNumber = $sequence->current_number + 1;
        $sequence->update(['current_number' => $nextNumber]);

        $length = $documentType === 'sale_note' ? 6 : 8;

        return [
            'series' => $sequence->series,
            'number' => $nextNumber,
            'full_number' => $sequence->series.'-'.Str::padLeft((string) $nextNumber, $length, '0'),
        ];
    }
}
