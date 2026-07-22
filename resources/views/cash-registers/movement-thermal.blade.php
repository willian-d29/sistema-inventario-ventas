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
    $printLabels = $locale === 'en'
        ? [
            'print' => 'Print',
            'browser_title' => 'Cash movement',
            'movement' => 'Movement',
            'cashier' => 'Cashier',
            'date' => 'Date',
            'amount' => 'Amount',
            'direction' => 'Direction',
            'method' => 'Method',
            'reason' => 'Reason',
            'reference' => 'Reference',
            'sale' => 'Sale',
            'expense' => 'Expense',
            'related' => 'Related',
            'responsible' => 'Responsible',
            'signature' => 'Approval signature',
            'notice' => 'Internal document. Not valid as a tax receipt',
            'requesting' => 'Registering request...',
            'opened' => 'Print dialog opened.',
        ]
        : [
            'print' => 'Imprimir',
            'browser_title' => 'Movimiento caja',
            'movement' => 'Movimiento',
            'cashier' => 'Cajero',
            'date' => 'Fecha',
            'amount' => 'Importe',
            'direction' => 'Dirección',
            'method' => 'Método',
            'reason' => 'Motivo',
            'reference' => 'Referencia',
            'sale' => 'Venta',
            'expense' => 'Gasto',
            'related' => 'Relacionado',
            'responsible' => 'Responsable',
            'signature' => 'Firma conformidad',
            'notice' => 'Documento interno. No válido como comprobante tributario',
            'requesting' => 'Registrando solicitud...',
            'opened' => 'Diálogo de impresión abierto.',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $printLabels['browser_title'] }} #{{ $movement->id }}</title>
    <style>
        @page { size: {{ (int) $business['thermal_paper_width'] }}mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body { width: {{ max((int) $business['thermal_paper_width'] - 8, 50) }}mm; margin: 0 auto; color: #000; background: #fff; font-family: DejaVu Sans Mono, monospace; font-size: 11px; line-height: 1.35; }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: table; width: 100%; }
        .left { display: table-cell; width: 42%; }
        .right { display: table-cell; text-align: right; }
        .bold { font-weight: 700; }
        .notice { margin-top: 8px; text-align: center; font-weight: 700; }
        .logo { max-width: 32mm; max-height: 18mm; filter: grayscale(1); margin-bottom: 4px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions center" style="margin: 8px 0;"><button id="printButton" type="button">{{ $printLabels['print'] }}</button><p id="printStatus"></p></div>
    @include('partials.business-document-header', ['title' => $typeLabels[$movement->type] ?? strtoupper(str_replace('_', ' ', $movement->type)), 'showLogo' => $business['thermal_show_logo']])
    <div class="line"></div>
    <div class="row"><span class="left">{{ $printLabels['movement'] }}:</span><span class="right">#{{ $movement->id }}</span></div>
    <div class="row"><span class="left">{{ $business['cash_register_name'] }}:</span><span class="right">#{{ $cashRegister->id }}</span></div>
    <div class="row"><span class="left">{{ $printLabels['cashier'] }}:</span><span class="right">{{ $cashRegister->user->name ?? '-' }}</span></div>
    <div class="row"><span class="left">{{ $printLabels['date'] }}:</span><span class="right">{{ $formatDateTime($movement->occurred_at) }}</span></div>
    <div class="row bold"><span class="left">{{ $printLabels['amount'] }}:</span><span class="right">{{ $money($movement->amount) }}</span></div>
    <div class="row"><span class="left">{{ $printLabels['direction'] }}:</span><span class="right">{{ $directionLabels[$movement->direction] ?? ($movement->direction ?: '-') }}</span></div>
    <div class="row"><span class="left">{{ $printLabels['method'] }}:</span><span class="right">{{ $paymentLabels[$movement->payment_method] ?? ($movement->payment_method ?: '-') }}</span></div>
    <div class="line"></div>
    <p><strong>{{ $printLabels['reason'] }}:</strong> {{ $movement->description ?: '-' }}</p>
    <p><strong>{{ $printLabels['reference'] }}:</strong> {{ $movement->reference ?: '-' }}</p>
    @if($movement->sale)
        <p><strong>{{ $printLabels['sale'] }}:</strong> {{ $movement->sale->full_document_number }}</p>
    @endif
    @if($movement->expense)
        <p><strong>{{ $printLabels['expense'] }}:</strong> {{ $movement->expense->name }}</p>
    @endif
    @if($movement->reversedMovement)
        <p><strong>{{ $printLabels['related'] }}:</strong> #{{ $movement->reversedMovement->id }}</p>
    @endif
    <p><strong>{{ $printLabels['responsible'] }}:</strong> {{ $movement->user->name ?? '-' }}</p>
    <div class="line"></div>
    <p class="bold center">{{ $message }}</p>
    <div class="line"></div>
    <p>{{ $printLabels['signature'] }}:</p>
    <p style="margin-top: 18px;">________________________</p>
    <p class="notice">{{ $printLabels['notice'] }}.</p>
    <script>
        document.getElementById('printButton')?.addEventListener('click', async () => {
            const button = document.getElementById('printButton');
            const status = document.getElementById('printStatus');
            button.disabled = true;
            status.textContent = @json($printLabels['requesting']);
            try {
                await fetch(@json(route('cash-registers.movements.print-request', [$cashRegister->id, $movement->id])), {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'},
                });
                status.textContent = @json($printLabels['opened']);
                window.print();
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
