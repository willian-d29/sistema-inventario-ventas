<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $labels = $locale === 'en'
        ? [
            'title' => 'Printer test ticket',
            'button' => 'Print test ticket',
            'ticket' => 'Test ticket',
            'heading' => 'PRINTER TEST',
            'characters' => 'Characters',
            'discount' => 'Discount',
            'divider' => 'Upper and lower divider line',
            'message' => 'Internal test message.',
            'notice' => 'Internal document. Not valid as a tax receipt',
            'requesting' => 'Registering request...',
            'opened' => 'Print dialog opened.',
            'failed' => 'Could not register the request. Try again.',
        ]
        : [
            'title' => 'Ticket de prueba',
            'button' => 'Imprimir ticket de prueba',
            'ticket' => 'Ticket de prueba',
            'heading' => 'PRUEBA DE IMPRESORA',
            'characters' => 'Caracteres',
            'discount' => 'Descuento',
            'divider' => 'Línea divisoria superior e inferior',
            'message' => 'Mensaje de prueba interno.',
            'notice' => 'Documento interno. No válido como comprobante tributario',
            'requesting' => 'Registrando solicitud...',
            'opened' => 'Diálogo de impresión abierto.',
            'failed' => 'No se pudo registrar la solicitud. Intenta de nuevo.',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $labels['title'] }}</title>
    <style>
        @page { size: {{ (int) $business['thermal_paper_width'] }}mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            width: {{ max((int) $business['thermal_paper_width'] - 8, 50) }}mm;
            margin: 0 auto;
            color: #111;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: {{ (int) $business['thermal_paper_width'] === 80 ? 11 : 10 }}px;
            line-height: 1.3;
        }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .muted { color: #444; }
        .bold { font-weight: 700; }
        .line { border-top: 1px dashed #111; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .right { text-align: right; }
        .logo { max-width: 32mm; max-height: 18mm; filter: grayscale(1); margin-bottom: 4px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions center" style="margin: 8px 0;">
        <button id="printButton" type="button">{{ $labels['button'] }}</button>
        <p id="printStatus" class="muted" style="margin-top: 4px;"></p>
    </div>

    <header class="center">
        @if($business['thermal_show_logo'] && $business['logo_url'])
            <img src="{{ $business['logo_url'] }}" alt="Logo {{ $business['business_name'] }}" class="logo">
        @endif
        <h1>{{ $business['business_name'] }}</h1>
        @if($business['legal_name'])
            <p>{{ $business['legal_name'] }}</p>
        @endif
        <p class="muted">{{ $labels['ticket'] }} · {{ $business['thermal_paper_width'] }} mm</p>
        <p>{{ $formatDateTime($generatedAt) }}</p>
    </header>

    <div class="line"></div>
    <p class="bold center">{{ $labels['heading'] }}</p>
    <div class="line"></div>
    <p>{{ $labels['characters'] }}:</p>
    <p>ABCDEFGHIJKLMNÑOPQRSTUVWXYZ</p>
    <p>abcdefghijklmnñopqrstuvwxyz</p>
    <p>0123456789 / . , : ; - _ + *</p>
    <div class="line"></div>
    <div class="row"><span>Subtotal</span><span>{{ $money(123.45) }}</span></div>
    <div class="row"><span>{{ $labels['discount'] }}</span><span>{{ $money(3.45) }}</span></div>
    <div class="row bold"><span>Total</span><span>{{ $money(120) }}</span></div>
    <div class="line"></div>
    <p class="center">{{ $labels['divider'] }}</p>
    <div class="line"></div>
    <p class="center bold">{{ $labels['message'] }}</p>
    <p class="center muted">{{ $labels['notice'] }}.</p>

    <script>
        const button = document.getElementById('printButton');
        const status = document.getElementById('printStatus');
        button?.addEventListener('click', async () => {
            button.disabled = true;
            status.textContent = @json($labels['requesting']);
            try {
                await fetch(@json(route('settings.printer-test.print-request')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                status.textContent = @json($labels['opened']);
                window.print();
            } catch (error) {
                status.textContent = @json($labels['failed']);
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
