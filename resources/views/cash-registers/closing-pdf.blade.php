<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $statusLabels = $locale === 'en'
        ? ['open' => 'Open', 'closed' => 'Closed', 'reviewed' => 'Reviewed', 'cancelled' => 'Cancelled']
        : ['open' => 'Abierta', 'closed' => 'Cerrada', 'reviewed' => 'Revisada', 'cancelled' => 'Anulada'];
    $differenceLabels = $locale === 'en'
        ? ['balanced' => 'Balanced', 'surplus' => 'Surplus', 'shortage' => 'Shortage']
        : ['balanced' => 'Cuadrada', 'surplus' => 'Sobrante', 'shortage' => 'Faltante'];
    $notice = $locale === 'en'
        ? 'Internal document. Not valid as a tax receipt'
        : 'Documento interno. No válido como comprobante tributario';
    $labels = $locale === 'en'
        ? [
            'title' => 'CASH REGISTER CLOSING AND COUNT',
            'browser_title' => 'Cash register closing',
            'cashier' => 'Cashier',
            'opened_at' => 'Opened at',
            'closed_at' => 'Closed at',
            'status' => 'Status',
            'review' => 'Review',
            'pending' => 'Pending',
            'operation_summary' => 'Operation summary',
            'opening_amount' => 'Opening amount',
            'sales_count' => 'Sales count',
            'sales_total' => 'Sales total',
            'manual_income' => 'Manual income',
            'withdrawals' => 'Withdrawals',
            'cash_expenses' => 'Cash expenses',
            'adjustments' => 'Adjustments',
            'refunds' => 'Refunds',
            'method_summary' => 'Summary by method',
            'method' => 'Method',
            'system' => 'System',
            'declared' => 'Declared',
            'difference' => 'Difference',
            'cash' => 'Cash',
            'expected' => 'Expected',
            'counted' => 'Counted',
            'provisional' => 'Provisional',
            'denominations' => 'Denominations',
            'denomination' => 'Denomination',
            'quantity' => 'Quantity',
            'subtotal' => 'Subtotal',
            'closing_review' => 'Closing and review',
            'cashier_note' => 'Cashier note',
            'review_note' => 'Review note',
            'reviewer' => 'Reviewer',
            'review_date' => 'Review date',
            'signature' => 'Cashier signature / approval',
        ]
        : [
            'title' => 'CIERRE Y ARQUEO DE CAJA',
            'browser_title' => 'Cierre caja',
            'cashier' => 'Cajero',
            'opened_at' => 'Apertura',
            'closed_at' => 'Cierre',
            'status' => 'Estado',
            'review' => 'Revisión',
            'pending' => 'Pendiente',
            'operation_summary' => 'Resumen de operación',
            'opening_amount' => 'Monto inicial',
            'sales_count' => 'Cantidad de ventas',
            'sales_total' => 'Total vendido',
            'manual_income' => 'Ingresos manuales',
            'withdrawals' => 'Retiros',
            'cash_expenses' => 'Gastos desde caja',
            'adjustments' => 'Ajustes',
            'refunds' => 'Devoluciones',
            'method_summary' => 'Resumen por método',
            'method' => 'Método',
            'system' => 'Sistema',
            'declared' => 'Declarado',
            'difference' => 'Diferencia',
            'cash' => 'Efectivo',
            'expected' => 'Esperado',
            'counted' => 'Contado',
            'provisional' => 'Provisional',
            'denominations' => 'Denominaciones',
            'denomination' => 'Denominación',
            'quantity' => 'Cantidad',
            'subtotal' => 'Subtotal',
            'closing_review' => 'Cierre y revisión',
            'cashier_note' => 'Observación cajero',
            'review_note' => 'Observación revisión',
            'reviewer' => 'Usuario revisor',
            'review_date' => 'Fecha revisión',
            'signature' => 'Firma cajero / conformidad',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $labels['browser_title'] }} #{{ $cashRegister->id }}</title>
    <style>
        body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 24px; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 14px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #bbb; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .section { margin-top: 16px; }
        .notice { margin-top: 18px; border: 1px solid #111; padding: 10px; font-weight: 700; text-align: center; }
        .signature { margin-top: 34px; width: 240px; border-top: 1px solid #111; text-align: center; padding-top: 4px; }
        .logo { max-width: 110px; max-height: 70px; object-fit: contain; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        @include('partials.business-document-header', ['title' => $labels['title']])
    </div>
    <table>
        <tr><th>{{ $business['cash_register_name'] }}</th><td>#{{ $cashRegister->id }}</td><th>{{ $labels['cashier'] }}</th><td>{{ $cashRegister->user->name ?? '-' }}</td></tr>
        <tr><th>{{ $labels['opened_at'] }}</th><td>{{ $formatDateTime($cashRegister->opened_at) }}</td><th>{{ $labels['closed_at'] }}</th><td>{{ $cashRegister->closed_at ? $formatDateTime($cashRegister->closed_at) : '-' }}</td></tr>
        <tr><th>{{ $labels['status'] }}</th><td>{{ $statusLabels[$cashRegister->status] ?? $cashRegister->status }}</td><th>{{ $labels['review'] }}</th><td>{{ $cashRegister->reviewed_at ? $statusLabels['reviewed'] : $labels['pending'] }}</td></tr>
    </table>

    <div class="section">
        <h3>{{ $labels['operation_summary'] }}</h3>
        <table>
            <tr><th>{{ $labels['opening_amount'] }}</th><td class="right">{{ $money($openingMovement->amount) }}</td><th>{{ $labels['sales_count'] }}</th><td class="right">{{ $operationSummary['sales_count'] }}</td></tr>
            <tr><th>{{ $labels['sales_total'] }}</th><td class="right">{{ $money($operationSummary['sales_total']) }}</td><th>{{ $labels['manual_income'] }}</th><td class="right">{{ $money($operationSummary['manual_income']) }}</td></tr>
            <tr><th>{{ $labels['withdrawals'] }}</th><td class="right">{{ $money($operationSummary['withdrawals']) }}</td><th>{{ $labels['cash_expenses'] }}</th><td class="right">{{ $money($operationSummary['expenses']) }}</td></tr>
            <tr><th>{{ $labels['adjustments'] }}</th><td class="right">{{ $money($operationSummary['adjustments']) }}</td><th>{{ $labels['refunds'] }}</th><td class="right">{{ $money($operationSummary['refunds']) }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ $labels['method_summary'] }}</h3>
        <table>
            <thead><tr><th>{{ $labels['method'] }}</th><th class="right">{{ $labels['system'] }}</th><th class="right">{{ $labels['declared'] }}</th><th class="right">{{ $labels['difference'] }}</th></tr></thead>
            <tbody>
                @foreach($methodRows as $row)
                    <tr><td>{{ $row['label'] }}</td><td class="right">{{ $money($row['system']) }}</td><td class="right">{{ $money($row['declared']) }}</td><td class="right">{{ $money($row['difference']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>{{ $labels['cash'] }}</h3>
        <table>
            <tr><th>{{ $labels['expected'] }}</th><td class="right">{{ $money($cashRegister->expected_amount ?: $summary['expected_cash']) }}</td><th>{{ $labels['counted'] }}</th><td class="right">{{ $money($cashRegister->closing_amount ?: 0) }}</td></tr>
            <tr><th>{{ $labels['difference'] }}</th><td class="right">{{ $money($cashRegister->total_difference ?: 0) }}</td><th>{{ $labels['status'] }}</th><td>{{ $differenceLabels[$cashRegister->difference_status] ?? $labels['provisional'] }}</td></tr>
        </table>
    </div>

    @if(count($denominationRows))
        <div class="section">
            <h3>{{ $labels['denominations'] }}</h3>
            <table>
                <thead><tr><th>{{ $labels['denomination'] }}</th><th class="right">{{ $labels['quantity'] }}</th><th class="right">{{ $labels['subtotal'] }}</th></tr></thead>
                <tbody>
                    @foreach($denominationRows as $row)
                        <tr><td>{{ $money($row['value']) }}</td><td class="right">{{ $row['quantity'] }}</td><td class="right">{{ $money($row['subtotal']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section">
        <h3>{{ $labels['closing_review'] }}</h3>
        <table>
            <tr><th>{{ $labels['cashier_note'] }}</th><td>{{ $cashRegister->closing_notes ?: '-' }}</td></tr>
            <tr><th>{{ $labels['review_note'] }}</th><td>{{ $cashRegister->review_notes ?: '-' }}</td></tr>
            <tr><th>{{ $labels['reviewer'] }}</th><td>{{ $cashRegister->reviewer->name ?? '-' }}</td></tr>
            <tr><th>{{ $labels['review_date'] }}</th><td>{{ $cashRegister->reviewed_at ? $formatDateTime($cashRegister->reviewed_at) : '-' }}</td></tr>
        </table>
    </div>

    <p class="signature">{{ $labels['signature'] }}</p>
    <p class="notice">{{ $notice }}.</p>
</body>
</html>
