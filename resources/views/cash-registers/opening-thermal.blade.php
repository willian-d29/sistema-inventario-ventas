<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $labels = $locale === 'en'
        ? [
            'print' => 'Print',
            'title' => 'CASH REGISTER OPENING',
            'browser_title' => 'Cash register opening',
            'movement' => 'Movement',
            'cashier' => 'Cashier',
            'date' => 'Date',
            'opening_amount' => 'Opening amount',
            'note' => 'Note',
            'responsible' => 'Responsible',
            'notice' => 'Internal document. Not valid as a tax receipt',
            'requesting' => 'Registering request...',
            'opened' => 'Print dialog opened.',
        ]
        : [
            'print' => 'Imprimir',
            'title' => 'APERTURA DE CAJA',
            'browser_title' => 'Apertura caja',
            'movement' => 'Movimiento',
            'cashier' => 'Cajero',
            'date' => 'Fecha',
            'opening_amount' => 'Monto inicial',
            'note' => 'Observación',
            'responsible' => 'Responsable',
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
    <div class="actions center" style="margin: 8px 0;"><button id="printButton" type="button">{{ $labels['print'] }}</button><p id="printStatus"></p></div>
    @include('partials.business-document-header', ['title' => $labels['title'], 'showLogo' => $business['thermal_show_logo']])
    <div class="line"></div>
    <div class="row"><span class="left">{{ $business['cash_register_name'] }}:</span><span class="right">#{{ $cashRegister->id }}</span></div>
    <div class="row"><span class="left">{{ $labels['movement'] }}:</span><span class="right">#{{ $movement->id }}</span></div>
    <div class="row"><span class="left">{{ $labels['cashier'] }}:</span><span class="right">{{ $cashRegister->user->name ?? '-' }}</span></div>
    <div class="row"><span class="left">{{ $labels['date'] }}:</span><span class="right">{{ $formatDateTime($movement->occurred_at) }}</span></div>
    <div class="row bold"><span class="left">{{ $labels['opening_amount'] }}:</span><span class="right">{{ $money($movement->amount) }}</span></div>
    <div class="line"></div>
    <p><strong>{{ $labels['note'] }}:</strong> {{ $cashRegister->notes ?: $movement->description ?: '-' }}</p>
    <p><strong>{{ $labels['responsible'] }}:</strong> {{ $movement->user->name ?? '-' }}</p>
    <div class="line"></div>
    <p class="notice">{{ $labels['notice'] }}.</p>
    <script>
        document.getElementById('printButton')?.addEventListener('click', async () => {
            const button = document.getElementById('printButton');
            const status = document.getElementById('printStatus');
            button.disabled = true;
            status.textContent = @json($labels['requesting']);
            try {
                await fetch(@json(route('cash-registers.movements.print-request', [$cashRegister->id, $movement->id])), {
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
