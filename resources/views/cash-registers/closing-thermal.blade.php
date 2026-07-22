<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $statusLabels = $locale === 'en'
        ? ['open' => 'Open', 'closed' => 'Closed', 'reviewed' => 'Reviewed', 'cancelled' => 'Cancelled']
        : ['open' => 'Abierta', 'closed' => 'Cerrada', 'reviewed' => 'Revisada', 'cancelled' => 'Anulada'];
    $labels = $locale === 'en'
        ? [
            'print' => 'Print',
            'closed_title' => 'CLOSING AND COUNT',
            'provisional_title' => 'PROVISIONAL SUMMARY',
            'subtitle' => 'Internal document',
            'browser_title' => 'Cash register closing',
            'cashier' => 'Cashier',
            'opened_at' => 'Opened',
            'closed_at' => 'Closed',
            'status' => 'Status',
            'opening_amount' => 'Opening amount',
            'sales_total' => 'Sales total',
            'expected_cash' => 'Expected cash',
            'counted_cash' => 'Counted cash',
            'difference' => 'Difference',
            'method_summary' => 'Summary by method',
            'note' => 'Note',
            'signature' => 'Signature',
            'notice' => 'Internal document. Not valid as a tax receipt',
            'requesting' => 'Registering request...',
            'opened' => 'Print dialog opened.',
        ]
        : [
            'print' => 'Imprimir',
            'closed_title' => 'CIERRE Y ARQUEO',
            'provisional_title' => 'RESUMEN PROVISIONAL',
            'subtitle' => 'Documento interno',
            'browser_title' => 'Cierre caja',
            'cashier' => 'Cajero',
            'opened_at' => 'Apertura',
            'closed_at' => 'Cierre',
            'status' => 'Estado',
            'opening_amount' => 'Monto inicial',
            'sales_total' => 'Total ventas',
            'expected_cash' => 'Efectivo esperado',
            'counted_cash' => 'Efectivo contado',
            'difference' => 'Diferencia',
            'method_summary' => 'Resumen por método',
            'note' => 'Observación',
            'signature' => 'Firma',
            'notice' => 'Documento interno. No válido como comprobante tributario',
            'requesting' => 'Registrando solicitud...',
            'opened' => 'Diálogo de impresión abierto.',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $labels['browser_title'] }} #{{ $cashRegister->id }}</title>
    <style>
        @page { size: {{ (int) $business['thermal_paper_width'] }}mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body { width: {{ max((int) $business['thermal_paper_width'] - 8, 50) }}mm; margin: 0 auto; color: #000; background: #fff; font-family: DejaVu Sans Mono, monospace; font-size: 10.5px; line-height: 1.35; }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: table; width: 100%; }
        .left { display: table-cell; width: 54%; }
        .right { display: table-cell; text-align: right; }
        .bold { font-weight: 700; }
        .logo { max-width: 32mm; max-height: 18mm; filter: grayscale(1); margin-bottom: 4px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions center" style="margin: 8px 0;"><button id="printButton" type="button">{{ $labels['print'] }}</button><p id="printStatus"></p></div>
    @include('partials.business-document-header', ['title' => $cashRegister->closed_at ? $labels['closed_title'] : $labels['provisional_title'], 'subtitle' => $labels['subtitle'], 'showLogo' => $business['thermal_show_logo']])
    <div class="line"></div>
    <div class="row"><span class="left">{{ $business['cash_register_name'] }}:</span><span class="right">#{{ $cashRegister->id }}</span></div>
    <div class="row"><span class="left">{{ $labels['cashier'] }}:</span><span class="right">{{ $cashRegister->user->name ?? '-' }}</span></div>
    <div class="row"><span class="left">{{ $labels['opened_at'] }}:</span><span class="right">{{ $formatDateTime($cashRegister->opened_at) }}</span></div>
    <div class="row"><span class="left">{{ $labels['closed_at'] }}:</span><span class="right">{{ $cashRegister->closed_at ? $formatDateTime($cashRegister->closed_at) : '-' }}</span></div>
    <div class="row"><span class="left">{{ $labels['status'] }}:</span><span class="right">{{ $statusLabels[$cashRegister->status] ?? $cashRegister->status }}</span></div>
    <div class="line"></div>
    <div class="row"><span class="left">{{ $labels['opening_amount'] }}:</span><span class="right">{{ $money($openingMovement->amount) }}</span></div>
    <div class="row"><span class="left">{{ $labels['sales_total'] }}:</span><span class="right">{{ $money($operationSummary['sales_total']) }}</span></div>
    <div class="row bold"><span class="left">{{ $labels['expected_cash'] }}:</span><span class="right">{{ $money($cashRegister->expected_amount ?: $summary['expected_cash']) }}</span></div>
    <div class="row bold"><span class="left">{{ $labels['counted_cash'] }}:</span><span class="right">{{ $money($cashRegister->closing_amount ?: 0) }}</span></div>
    <div class="row bold"><span class="left">{{ $labels['difference'] }}:</span><span class="right">{{ $money($cashRegister->total_difference ?: 0) }}</span></div>
    <div class="line"></div>
    <p class="bold">{{ $labels['method_summary'] }}</p>
    @foreach($methodRows as $row)
        <div class="row"><span class="left">{{ $row['label'] }}</span><span class="right">{{ $money($row['system']) }}</span></div>
    @endforeach
    <div class="line"></div>
    <p><strong>{{ $labels['note'] }}:</strong> {{ $cashRegister->closing_notes ?: '-' }}</p>
    <div class="line"></div>
    <p>{{ $labels['signature'] }}:</p>
    <p style="margin-top: 18px;">________________________</p>
    <p class="center bold" style="margin-top: 8px;">{{ $labels['notice'] }}.</p>
    <script>
        document.getElementById('printButton')?.addEventListener('click', async () => {
            const button = document.getElementById('printButton');
            const status = document.getElementById('printStatus');
            button.disabled = true;
            status.textContent = @json($labels['requesting']);
            try {
                await fetch(@json(route('cash-registers.print-request', $cashRegister->id)), {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'},
                });
                status.textContent = @json($labels['opened']);
                window.print();
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
