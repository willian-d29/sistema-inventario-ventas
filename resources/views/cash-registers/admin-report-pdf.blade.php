<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $statusLabels = $locale === 'en'
        ? ['open' => 'Open', 'closed' => 'Closed', 'reviewed' => 'Reviewed', 'cancelled' => 'Cancelled']
        : ['open' => 'Abierta', 'closed' => 'Cerrada', 'reviewed' => 'Revisada', 'cancelled' => 'Anulada'];
    $differenceLabels = $locale === 'en'
        ? ['balanced' => 'Balanced', 'surplus' => 'Surplus', 'shortage' => 'Shortage']
        : ['balanced' => 'Cuadrada', 'surplus' => 'Sobrante', 'shortage' => 'Faltante'];
    $paymentLabels = $locale === 'en'
        ? ['cash' => 'Cash', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Card', 'transfer' => 'Transfer']
        : ['cash' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'];
    $directionLabels = $locale === 'en'
        ? ['income' => 'Income', 'expense' => 'Expense']
        : ['income' => 'Ingreso', 'expense' => 'Egreso'];
    $reconciliationLabels = $locale === 'en'
        ? ['matched' => 'Matched', 'mismatch' => 'Mismatch', 'missing_movement' => 'Missing movement', 'missing_payment' => 'Missing payment']
        : ['matched' => 'Conciliado', 'mismatch' => 'Diferencia', 'missing_movement' => 'Falta movimiento', 'missing_payment' => 'Falta pago'];
    $notice = $locale === 'en'
        ? 'Internal document. Not valid as a tax receipt'
        : 'Documento interno. No válido como comprobante tributario';
    $labels = $locale === 'en'
        ? [
            'report_title' => 'DETAILED CASH REGISTER ADMIN REPORT',
            'browser_title' => 'Cash register admin report',
            'generated' => 'Generated',
            'page' => 'Page',
            'cash_summary' => 'Cash register summary',
            'cashier' => 'Cashier',
            'opened_at' => 'Opened at',
            'closed_at' => 'Closed at',
            'status' => 'Status',
            'review' => 'Review',
            'pending' => 'Pending',
            'opening_amount' => 'Opening amount',
            'sales_total' => 'Sales total',
            'expected_cash' => 'Expected cash',
            'counted_cash' => 'Counted cash',
            'total_difference' => 'Total difference',
            'difference_status' => 'Difference status',
            'provisional' => 'Provisional',
            'method_summary' => 'Summary by method',
            'method' => 'Method',
            'system' => 'System',
            'declared' => 'Declared',
            'difference' => 'Difference',
            'timeline' => 'Chronological timeline',
            'date' => 'Date',
            'type' => 'Type',
            'direction' => 'Direction',
            'responsible' => 'Responsible',
            'reference' => 'Reference',
            'amount' => 'Amount',
            'related_sales' => 'Related sales',
            'document' => 'Document',
            'methods' => 'Methods',
            'total' => 'Total',
            'no_sales' => 'No related sales.',
            'reconciliation' => 'Reconciliation',
            'sale' => 'Sale',
            'payment' => 'Payment',
            'movement' => 'Movement',
            'no_reconciliation' => 'No reconciliation records.',
        ]
        : [
            'report_title' => 'REPORTE ADMINISTRATIVO DETALLADO DE CAJA',
            'browser_title' => 'Reporte administrativo caja',
            'generated' => 'Generado',
            'page' => 'Página',
            'cash_summary' => 'Resumen de caja',
            'cashier' => 'Cajero',
            'opened_at' => 'Apertura',
            'closed_at' => 'Cierre',
            'status' => 'Estado',
            'review' => 'Revisión',
            'pending' => 'Pendiente',
            'opening_amount' => 'Monto inicial',
            'sales_total' => 'Total ventas',
            'expected_cash' => 'Efectivo esperado',
            'counted_cash' => 'Efectivo contado',
            'total_difference' => 'Diferencia total',
            'difference_status' => 'Estado diferencia',
            'provisional' => 'Provisional',
            'method_summary' => 'Resumen por método',
            'method' => 'Método',
            'system' => 'Sistema',
            'declared' => 'Declarado',
            'difference' => 'Diferencia',
            'timeline' => 'Línea de tiempo cronológica',
            'date' => 'Fecha',
            'type' => 'Tipo',
            'direction' => 'Dirección',
            'responsible' => 'Responsable',
            'reference' => 'Referencia',
            'amount' => 'Importe',
            'related_sales' => 'Ventas relacionadas',
            'document' => 'Documento',
            'methods' => 'Métodos',
            'total' => 'Total',
            'no_sales' => 'Sin ventas relacionadas.',
            'reconciliation' => 'Conciliación',
            'sale' => 'Venta',
            'payment' => 'Pago',
            'movement' => 'Movimiento',
            'no_reconciliation' => 'Sin registros de conciliación.',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $labels['browser_title'] }} #{{ $cashRegister->id }}</title>
    <style>
        body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 10.5px; margin: 22px; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 14px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #bbb; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .section { margin-top: 16px; }
        .notice { margin-top: 18px; border: 1px solid #111; padding: 10px; font-weight: 700; text-align: center; }
        .page-number:after { content: counter(page); }
        .logo { max-width: 110px; max-height: 70px; object-fit: contain; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        @include('partials.business-document-header')
        <h2>{{ $labels['report_title'] }}</h2>
        <p>{{ $labels['generated'] }}: {{ $formatDateTime($generatedAt) }} · {{ $labels['page'] }} <span class="page-number"></span></p>
    </div>

    <div class="section">
        <h3>{{ $labels['cash_summary'] }}</h3>
        <table>
            <tr><th>{{ $business['cash_register_name'] }}</th><td>#{{ $cashRegister->id }}</td><th>{{ $labels['cashier'] }}</th><td>{{ $cashRegister->user->name ?? '-' }}</td></tr>
            <tr><th>{{ $labels['opened_at'] }}</th><td>{{ $formatDateTime($cashRegister->opened_at) }}</td><th>{{ $labels['closed_at'] }}</th><td>{{ $cashRegister->closed_at ? $formatDateTime($cashRegister->closed_at) : '-' }}</td></tr>
            <tr><th>{{ $labels['status'] }}</th><td>{{ $statusLabels[$cashRegister->status] ?? $cashRegister->status }}</td><th>{{ $labels['review'] }}</th><td>{{ $cashRegister->reviewed_at ? $statusLabels['reviewed'] : $labels['pending'] }}</td></tr>
            <tr><th>{{ $labels['opening_amount'] }}</th><td class="right">{{ $money($openingMovement->amount) }}</td><th>{{ $labels['sales_total'] }}</th><td class="right">{{ $money($operationSummary['sales_total']) }}</td></tr>
            <tr><th>{{ $labels['expected_cash'] }}</th><td class="right">{{ $money($cashRegister->expected_amount ?: $summary['expected_cash']) }}</td><th>{{ $labels['counted_cash'] }}</th><td class="right">{{ $money($cashRegister->closing_amount ?: 0) }}</td></tr>
            <tr><th>{{ $labels['total_difference'] }}</th><td class="right">{{ $money($cashRegister->total_difference ?: 0) }}</td><th>{{ $labels['difference_status'] }}</th><td>{{ $differenceLabels[$cashRegister->difference_status] ?? $labels['provisional'] }}</td></tr>
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
        <h3>{{ $labels['timeline'] }}</h3>
        <table>
            <thead><tr><th>{{ $labels['date'] }}</th><th>{{ $labels['type'] }}</th><th>{{ $labels['direction'] }}</th><th>{{ $labels['method'] }}</th><th>{{ $labels['responsible'] }}</th><th>{{ $labels['reference'] }}</th><th class="right">{{ $labels['amount'] }}</th></tr></thead>
            <tbody>
                @foreach($timeline as $item)
                    <tr>
                        <td>{{ $formatDateTime($item['occurred_at']) }}</td>
                        <td>{{ $item['type_label'] }}</td>
                        <td>{{ $directionLabels[$item['direction']] ?? '-' }}</td>
                        <td>{{ $paymentLabels[$item['payment_method']] ?? ($item['payment_method'] ?: '-') }}</td>
                        <td>{{ $item['responsible'] ?: '-' }}</td>
                        <td>{{ $item['reference'] ?: $item['relation'] ?: '-' }}</td>
                        <td class="right">{{ $item['signed_amount'] === null ? '-' : $money($item['signed_amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>{{ $labels['related_sales'] }}</h3>
        <table>
            <thead><tr><th>{{ $labels['document'] }}</th><th>{{ $labels['cashier'] }}</th><th>{{ $labels['date'] }}</th><th>{{ $labels['methods'] }}</th><th class="right">{{ $labels['total'] }}</th></tr></thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>{{ $sale->full_document_number }}</td>
                        <td>{{ $sale->cashier->name ?? '-' }}</td>
                        <td>{{ $formatDateTime($sale->sold_at) }}</td>
                        <td>{{ $sale->payments->pluck('payment_method')->map(fn ($method) => $paymentLabels[$method] ?? $method)->join(' + ') }}</td>
                        <td class="right">{{ $money($sale->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ $labels['no_sales'] }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>{{ $labels['reconciliation'] }}</h3>
        <table>
            <thead><tr><th>{{ $labels['sale'] }}</th><th>{{ $labels['document'] }}</th><th>{{ $labels['method'] }}</th><th class="right">{{ $labels['payment'] }}</th><th class="right">{{ $labels['movement'] }}</th><th class="right">{{ $labels['difference'] }}</th><th>{{ $labels['status'] }}</th></tr></thead>
            <tbody>
                @forelse($reconciliation as $row)
                    <tr>
                        <td>{{ $row['sale_id'] ?: '-' }}</td>
                        <td>{{ $row['document'] ?: '-' }}</td>
                        <td>{{ $paymentLabels[$row['method']] ?? ($row['method'] ?: '-') }}</td>
                        <td class="right">{{ $row['payment_amount'] === null ? '-' : $money($row['payment_amount']) }}</td>
                        <td class="right">{{ $row['movement_amount'] === null ? '-' : $money($row['movement_amount']) }}</td>
                        <td class="right">{{ $money($row['difference']) }}</td>
                        <td>{{ $reconciliationLabels[$row['status']] ?? $row['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">{{ $labels['no_reconciliation'] }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="notice">{{ $notice }}.</p>
</body>
</html>
