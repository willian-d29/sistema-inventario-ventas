<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $typeLabels = $locale === 'en'
        ? [
            'manual_income' => 'MANUAL INCOME',
            'withdrawal' => 'WITHDRAWAL',
            'expense' => 'CASH EXPENSE',
            'adjustment' => 'ADJUSTMENT',
            'sale' => 'SALE',
            'refund' => 'REFUND',
        ]
        : [
            'manual_income' => 'INGRESO MANUAL',
            'withdrawal' => 'RETIRO',
            'expense' => 'GASTO DESDE CAJA',
            'adjustment' => 'AJUSTE',
            'sale' => 'VENTA',
            'refund' => 'DEVOLUCIÓN',
        ];
    $directionLabels = $locale === 'en'
        ? ['income' => 'Income', 'expense' => 'Expense']
        : ['income' => 'Ingreso', 'expense' => 'Egreso'];
    $paymentLabels = $locale === 'en'
        ? ['cash' => 'Cash', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Card', 'transfer' => 'Transfer']
        : ['cash' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'];
    $notice = $locale === 'en'
        ? 'Internal document. Not valid as a tax receipt'
        : 'Documento interno. No válido como comprobante tributario';
    $labels = $locale === 'en'
        ? [
            'browser_title' => 'Cash movement',
            'movement' => 'Movement',
            'cashier' => 'Cashier',
            'responsible' => 'Responsible',
            'date_time' => 'Date and time',
            'type' => 'Type',
            'direction' => 'Direction',
            'method' => 'Method',
            'amount' => 'Amount',
            'reference' => 'Reference',
            'reason' => 'Reason',
            'related_sale' => 'Related sale',
            'related_expense' => 'Related expense',
            'related_movement' => 'Related movement',
            'operational_message' => 'Operational message',
            'signature' => 'Signature / approval',
        ]
        : [
            'browser_title' => 'Movimiento caja',
            'movement' => 'Movimiento',
            'cashier' => 'Cajero',
            'responsible' => 'Responsable',
            'date_time' => 'Fecha y hora',
            'type' => 'Tipo',
            'direction' => 'Dirección',
            'method' => 'Método',
            'amount' => 'Importe',
            'reference' => 'Referencia',
            'reason' => 'Motivo',
            'related_sale' => 'Venta relacionada',
            'related_expense' => 'Gasto relacionado',
            'related_movement' => 'Movimiento relacionado',
            'operational_message' => 'Mensaje operativo',
            'signature' => 'Firma / conformidad',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $labels['browser_title'] }} #{{ $movement->id }}</title>
    <style>
        body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 28px; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .muted { color: #555; }
        .logo { max-width: 110px; max-height: 70px; object-fit: contain; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #bbb; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .notice { margin-top: 18px; border: 1px solid #111; padding: 10px; font-weight: 700; text-align: center; }
        .signature { margin-top: 34px; width: 240px; border-top: 1px solid #111; text-align: center; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        @include('partials.business-document-header', ['title' => $typeLabels[$movement->type] ?? strtoupper(str_replace('_', ' ', $movement->type))])
    </div>
    <table>
        <tr><th>{{ $labels['movement'] }}</th><td>#{{ $movement->id }}</td><th>{{ $business['cash_register_name'] }}</th><td>#{{ $cashRegister->id }}</td></tr>
        <tr><th>{{ $labels['cashier'] }}</th><td>{{ $cashRegister->user->name ?? '-' }}</td><th>{{ $labels['responsible'] }}</th><td>{{ $movement->user->name ?? '-' }}</td></tr>
        <tr><th>{{ $labels['date_time'] }}</th><td>{{ $formatDateTime($movement->occurred_at) }}</td><th>{{ $labels['type'] }}</th><td>{{ $typeLabels[$movement->type] ?? strtoupper(str_replace('_', ' ', $movement->type)) }}</td></tr>
        <tr><th>{{ $labels['direction'] }}</th><td>{{ $directionLabels[$movement->direction] ?? ($movement->direction ?: '-') }}</td><th>{{ $labels['method'] }}</th><td>{{ $paymentLabels[$movement->payment_method] ?? ($movement->payment_method ?: '-') }}</td></tr>
        <tr><th>{{ $labels['amount'] }}</th><td class="right">{{ $money($movement->amount) }}</td><th>{{ $labels['reference'] }}</th><td>{{ $movement->reference ?: '-' }}</td></tr>
    </table>
    <table>
        <tr><th>{{ $labels['reason'] }}</th><td>{{ $movement->description ?: '-' }}</td></tr>
        <tr><th>{{ $labels['related_sale'] }}</th><td>{{ $movement->sale->full_document_number ?? '-' }}</td></tr>
        <tr><th>{{ $labels['related_expense'] }}</th><td>{{ $movement->expense->name ?? '-' }}</td></tr>
        <tr><th>{{ $labels['related_movement'] }}</th><td>{{ $movement->reversedMovement ? '#'.$movement->reversedMovement->id : '-' }}</td></tr>
        <tr><th>{{ $labels['operational_message'] }}</th><td>{{ $message }}</td></tr>
    </table>
    <p class="signature">{{ $labels['signature'] }}</p>
    <p class="notice">{{ $notice }}.</p>
</body>
</html>
