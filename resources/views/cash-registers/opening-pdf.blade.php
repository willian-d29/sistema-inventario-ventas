<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $paymentLabels = $locale === 'en'
        ? ['cash' => 'Cash', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Card', 'transfer' => 'Transfer']
        : ['cash' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'];
    $directionLabels = $locale === 'en'
        ? ['income' => 'Income', 'expense' => 'Expense']
        : ['income' => 'Ingreso', 'expense' => 'Egreso'];
    $notice = $locale === 'en'
        ? 'Internal document. Not valid as a tax receipt'
        : 'Documento interno. No válido como comprobante tributario';
    $labels = $locale === 'en'
        ? [
            'title' => 'CASH REGISTER OPENING',
            'browser_title' => 'Cash register opening',
            'cash_register_shift' => 'Cash register / shift',
            'movement' => 'Movement',
            'cashier' => 'Cashier',
            'responsible' => 'Responsible',
            'date_time' => 'Date and time',
            'method' => 'Method',
            'opening_amount' => 'Opening amount',
            'direction' => 'Direction',
            'note' => 'Note',
        ]
        : [
            'title' => 'APERTURA DE CAJA',
            'browser_title' => 'Apertura caja',
            'cash_register_shift' => 'Caja / turno',
            'movement' => 'Movimiento',
            'cashier' => 'Cajero',
            'responsible' => 'Responsable',
            'date_time' => 'Fecha y hora',
            'method' => 'Método',
            'opening_amount' => 'Monto inicial',
            'direction' => 'Dirección',
            'note' => 'Observación',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $labels['browser_title'] }} #{{ $cashRegister->id }}</title>
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
    </style>
</head>
<body>
    <div class="header">
        @include('partials.business-document-header', ['title' => $labels['title']])
    </div>
    <table>
        <tr><th>{{ $labels['cash_register_shift'] }}</th><td>#{{ $cashRegister->id }}</td><th>{{ $labels['movement'] }}</th><td>#{{ $movement->id }}</td></tr>
        <tr><th>{{ $labels['cashier'] }}</th><td>{{ $cashRegister->user->name ?? '-' }}</td><th>{{ $labels['responsible'] }}</th><td>{{ $movement->user->name ?? '-' }}</td></tr>
        <tr><th>{{ $labels['date_time'] }}</th><td>{{ $formatDateTime($movement->occurred_at) }}</td><th>{{ $labels['method'] }}</th><td>{{ $paymentLabels[$movement->payment_method] ?? ($movement->payment_method ?: '-') }}</td></tr>
        <tr><th>{{ $labels['opening_amount'] }}</th><td class="right">{{ $money($movement->amount) }}</td><th>{{ $labels['direction'] }}</th><td>{{ $directionLabels[$movement->direction] ?? ($movement->direction ?: '-') }}</td></tr>
    </table>
    <table>
        <tr><th>{{ $labels['note'] }}</th></tr>
        <tr><td>{{ $cashRegister->notes ?: $movement->description ?: '-' }}</td></tr>
    </table>
    <p class="notice">{{ $notice }}.</p>
</body>
</html>
